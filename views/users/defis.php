<?php
require_once '../../includes/init.php';

$sql = "SELECT * FROM challenges ORDER BY categorie DESC, titre_$lang";
$stmt = $pdo->query($sql);
$allChallenges = $stmt->fetchAll();

$stmt_actions = $pdo->prepare("SELECT DISTINCT challenge_id FROM user_actions WHERE user_id = :uid");
$stmt_actions->execute(['uid' => $user_id]);
$userValidatedIds = $stmt_actions->fetchAll(PDO::FETCH_COLUMN);

$stmt_prog = $pdo->prepare("SELECT challenge_id, COUNT(DISTINCT DATE(date_action)) as days_done FROM user_actions WHERE user_id = :uid GROUP BY challenge_id");
$stmt_prog->execute(['uid' => $user_id]);
$userProgress = $stmt_prog->fetchAll(PDO::FETCH_KEY_PAIR);

$defis_en_cours = [];

foreach ($allChallenges as $c) {
    $duration = (int)($c['duration_days'] ?? 1);
    $days_done = $userProgress[$c['id']] ?? 0;

    if ($duration > 1 && $days_done > 0 && $days_done < $duration) {
        $c['days_done'] = $days_done;
        $defis_en_cours[] = $c;
    }
}

$stmt_history = $pdo->prepare("
    SELECT ua.date_action, c.titre_fr, c.titre_en, c.descr_fr, c.descr_en, c.xp_gain, c.difficulty
    FROM user_actions ua 
    JOIN challenges c ON ua.challenge_id = c.id 
    WHERE ua.user_id = :uid 
    ORDER BY ua.date_action DESC
    LIMIT 20
");
$stmt_history->execute(['uid' => $user_id]);
$userHistory = $stmt_history->fetchAll();

$groupedChallenges = [];
$categories = [];
foreach ($allChallenges as $c) {
    $catName = !empty($c['categorie']) ? $c['categorie'] : 'Général';
    if (!isset($groupedChallenges[$catName])) {
        $groupedChallenges[$catName] = [];
        if (!in_array($catName, $categories)) $categories[] = $catName;
    }
    $groupedChallenges[$catName][] = $c;
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars(($t['nav_defs'] ?? 'Défis') . " - Shift'Up") ?></title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../../js/tailwind-config.js"></script>
    
    <style>
        @keyframes popIn {
            0% { transform: scale(0.8) translateY(20px); opacity: 0; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        .animate-pop {
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
    </style>
</head>
<body class="bg-brand-card text-brand-dark font-sans overflow-x-hidden pb-24"> 

    <header class="top-0 w-full bg-brand-primary p-4 pb-0 relative z-40 shadow-sm">
        <div class="flex items-center gap-2 max-[375px]:gap-1 mb-4 z-50 relative">
         <div class="flex items-center bg-brand-dark rounded-full pr-4 max-[375px]:pr-2 h-10 max-[375px]:h-8 shadow-sm">
    <div class="w-10 h-10 max-[375px]:w-8 max-[375px]:h-8 rounded-full bg-brand-secondary flex items-center justify-center text-brand-primary font-display font-bold border-2 border-brand-primary text-lg max-[375px]:text-sm"><?= $levelData['niveau_actuel'] ?></div>
    <span class="ml-2 text-xs max-[320px]:text-[10px] font-bold text-white whitespace-nowrap">
        <?= number_format($levelData['xp_actuel'] ?? 0) ?>/<?= number_format($levelData['xp_prochain'] ?? 2500) ?>
    </span>
</div>
<div class="flex items-center bg-brand-dark w-[105px] max-[375px]:w-[115px] min-[414px]:w-[130px] h-10 max-[375px]:h-8 px-3 max-[375px]:px-2 shadow-sm rounded-l-3xl shrink-0" style="clip-path: polygon(0 0, 100% 0, calc(100% - 15px) 100%, 0 100%);">
    <div class="flex items-center gap-2 max-[375px]:gap-1">
        <img class="w-8 h-8" src="../../img/icone/mascotte-monnaie.svg" alt="">
            <span class="text-sm max-[376px]:text-[11px] font-bold text-brand-primary whitespace-nowrap">
                <?= number_format($money, 0, '.', ' ') ?>
            </span>
    </div>
</div>

            <div class="absolute right-0 top-12 flex gap-2 max-[375px]:gap-1 z-50">
                <button onclick="openNotifications()" class="w-11 h-11 bg-brand-secondary rounded-xl flex items-center justify-center shadow-sm active:scale-95 transition">
                    <img class="w-8 h-8" src="../../img/icone/icone-notification-blanc.svg" alt="Notifications">
                </button>
                <button onclick="toggleMenu()" class="w-11 h-11 bg-brand-secondary rounded-xl flex items-center justify-center shadow-sm active:scale-95 transition">
                    <img class="w-8 h-8" src="../../img/icone/icone-parametre-blanc.svg" alt="Paramètres">
                </button>
            </div>
        </div>

        <div class="relative h-[200px] top-[-35px] flex items-center justify-center -mx-4 mt-2 z-10">
            
            <div id="tab-search" onclick="switchTab('search')" 
                 class="absolute left-[-10%] w-[68%] h-32 max-[375px]:h-28 max-[320px]:h-24 bg-brand-secondary skew-tile cursor-pointer flex items-center justify-end pr-8 max-[375px]:pr-4 transition-colors z-20">
                <div class="unskew text-right flex flex-col justify-center">
                    <h2 class="font-display text-3xl max-[375px]:text-2xl font-bold text-brand-primary leading-none mb-1 transition-colors"><?= htmlspecialchars($t['tab_explore'] ?? 'Explorer') ?></h2>
                </div>
            </div>

            <div id="tab-my" onclick="switchTab('my')" 
                 class="absolute right-[-5%] top-[45%] w-[45%] h-28 max-[375px]:h-24 max-[320px]:h-20 bg-brand-border skew-tile cursor-pointer flex items-center justify-center pl-4 transition-colors z-10">
                <div class="unskew text-center">
                    <span class="font-display text-xl max-[375px]:text-lg font-bold text-brand-primary leading-nonetransition-colors"><?= htmlspecialchars($t['tab_my_challenges'] ?? 'Mes défis') ?></span>
                </div>
            </div>

            <div id="filter-btn" class="absolute left-[-10%] top-[180px] max-[375px]:top-[170px] max-[320px]:top-[134px] w-[60%] h-[36px] bg-brand-dark skew-tile flex items-center justify-center cursor-pointer hover:bg-gray-800 transition z-30 shadow-sm" onclick="openFilter()">
                <div class="unskew pl-10 max-[375px]:pl-6 flex items-center justify-center">
                    <span class="text-l text-white font-bold tracking-widest"><?= htmlspecialchars($t['btn_filter'] ?? 'Filtre') ?></span>
                </div>
            </div>

        </div>
    </header>

    <div class="px-4 space-y-6 mt-10" id="challenges-list">
        <?php foreach ($groupedChallenges as $categoryName => $challengesInCat): ?>
            <div class="category-block bg-brand-primary rounded-[20px] p-4 pb-6 shadow-lg" data-cat-name="<?= htmlspecialchars($categoryName) ?>">
                <h2 class="text-left text-xs font-bold uppercase tracking-widest text-brand-secondary mb-4 border-b border-brand-border pb-2">
                    <?= htmlspecialchars($t['cat_' . $categoryName] ?? $categoryName) ?>
                </h2>
                
                <?php foreach($challengesInCat as $defi): ?>
                    <?php 
                        $sql_today = "SELECT COUNT(*) FROM user_actions WHERE user_id = :uid AND challenge_id = :cid AND DATE(date_action) = CURDATE()";
                        $stmt_td = $pdo->prepare($sql_today);
                        $stmt_td->execute(['uid' => $user_id, 'cid' => $defi['id']]);
                        $today_count = $stmt_td->fetchColumn();
                        
                        $disabled = ($today_count >= $defi['max_actions_day']);
                        $diff = strtolower($defi['difficulty'] ?? 'facile');
                        $leafCount = ($diff == 'difficile') ? 3 : (($diff == 'moyen') ? 2 : 1);
                        $isValidated = in_array($defi['id'], $userValidatedIds);

                        $duration = (int)($defi['duration_days'] ?? 1);
                        $days_done = $userProgress[$defi['id']] ?? 0;
                        if ($days_done > $duration) $days_done = $duration;
                        $progress_percent = ($duration > 0) ? round(($days_done / $duration) * 100) : 0;
                        
                        $catStr = strtolower($categoryName);
                        if (strpos($catStr, 'covoiturage') !== false) {
                            $catIcon = '../../img/carte/carte-covoiturage.svg';
                        } elseif (strpos($catStr, 'recyclage') !== false) {
                            $catIcon = '../../img/carte/carte-trier.svg';
                        } elseif (strpos($catStr, 'mobilit') !== false) {
                            $catIcon = '../../img/carte/carte-mediateur.svg';
                        } else {
                            $catIcon = '../../img/carte/carte-covoiturage2.svg';
                        }
                    ?>
                    
                    <div class="challenge-card bg-brand-secondary border-transparent rounded-2xl p-2.5 flex relative mb-4 shadow-md items-stretch cursor-pointer hover:opacity-90 transition text-white" 
                        data-difficulty="<?= $diff ?>" 
                        data-category="<?= htmlspecialchars($categoryName) ?>"
                        data-domain="<?= htmlspecialchars($defi['domaine'] ?? 'ecologique') ?>"
                        data-domain-2="<?= htmlspecialchars($defi['domaine_2'] ?? '') ?>"
                        onclick="openModal('modal-<?= $defi['id'] ?>')">
                        
                        <div class="w-[65px] min-h-[65px] bg-transparent rounded-xl flex items-center justify-center shrink-0 relative overflow-hidden">
                            <?php if (!empty($defi['image_url'])): ?>
                                <img src="<?= htmlspecialchars($defi['image_url']) ?>" alt="Image défi" class="w-full h-full object-cover absolute inset-0">
                            <?php else: ?>
                                <img src="<?= htmlspecialchars($catIcon) ?>" alt="Icone catégorie" class="w-full h-full scale-110 object-contain relative z-10">
                            <?php endif; ?>
                        </div>
                        
                        <div class="ml-3 flex-1 flex flex-col justify-center py-1">
                            <div class="flex justify-between items-start w-full gap-2">
                                <h3 class="font-bold text-[13px] text-white leading-tight line-clamp-2 pr-10">
                                    <?= htmlspecialchars($defi['titre_' . $lang] ?? $defi['titre_fr']) ?>
                                </h3>
                                <div class="flex items-center gap-0.5 shrink-0 text-white mt-0.5">
                                <?php for($i=0; $i<$leafCount; $i++): ?>
                                <i class="fa-solid fa-leaf text-[10px]"></i>
                                <?php endfor; ?>
                                </div>
                            </div>
                           <div class="flex items-center gap-3 mt-1.5 bg-brand-dark px-3 py-1 rounded-full w-fit">
                                <span class="text-[10px] font-bold text-white uppercase">
                                    <i class="fa-solid fa-clock mr-1"></i><?= $duration ?> <?= htmlspecialchars($t['days_abbr'] ?? 'j') ?>
                                </span>
                                <span class="text-[10px] font-bold text-white uppercase">
                                    <i class="fa-solid fa-star mr-1"></i><?= htmlspecialchars($defi['xp_gain']) ?> XP
                                </span>
                            </div>

                            <?php if ($duration > 1): ?>
                            <div class="mt-2 pr-10">
                                <div class="flex justify-between items-center text-[9px] font-bold text-white mb-1">
                                    <span class="uppercase tracking-wider">Progression</span>
                                    <span><?= $days_done ?>/<?= $duration ?> <?= htmlspecialchars($t['days_abbr'] ?? 'j') ?></span>
                                </div>
                                <div class="w-full h-1.5 bg-white/30 rounded-full overflow-hidden">
                                    <div class="h-full bg-white transition-all duration-500" style="width: <?= $progress_percent ?>%;"></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="absolute -bottom-2 -right-2 z-10" onclick="event.stopPropagation();">
                            <form action="validate_mission.php" method="POST">
                                <input type="hidden" name="challenge_id" value="<?= $defi['id'] ?>">
                                <button type="submit" <?= $disabled ? 'disabled' : '' ?> class="w-10 h-10 bg-brand-dark rounded-full flex items-center justify-center text-brand-primary <?= $disabled ? 'opacity-40 cursor-not-allowed' : 'hover:bg-brand-dark active:scale-95' ?> transition shadow-md">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div id="modal-<?= $defi['id'] ?>" class="fixed inset-0 z-[150] hidden flex items-center justify-center bg-brand-dark/80 p-4 backdrop-blur-sm">
                        <div class="relative w-full max-w-sm bg-brand-primary rounded-3xl p-6 shadow-2xl">
                            
                            <button type="button" onclick="closeModal('modal-<?= $defi['id'] ?>')" class="absolute top-4 right-4 w-8 h-8 bg-brand-secondary rounded-full flex items-center justify-center text-brand-primary hover:opacity-90 transition">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                            
                            <h3 class="text-xl font-display font-bold text-brand-secondary leading-tight mb-2 mt-4">
                                <?= htmlspecialchars($defi['titre_' . $lang] ?? $defi['titre_fr']) ?>
                            </h3>
                            
                            <p class="text-sm text-brand-secondary opacity-90 mb-6 max-h-40 overflow-y-auto">
                                <?= nl2br(htmlspecialchars($defi['descr_' . $lang] ?? $defi['descr_fr'])) ?>
                            </p>
                            
                            <div class="flex gap-3 mb-6">
                                <div class="flex-1 text-center py-2 rounded-xl bg-brand-secondary text-brand-primary text-xs font-bold shadow-sm flex flex-col justify-center">
                                    <i class="fa-solid fa-calendar mb-1 text-brand-primary"></i>
                                    <span><?= $duration ?> <?= htmlspecialchars($t['days_abbr'] ?? 'j') ?></span>
                                </div>
                                <div class="flex-1 text-center py-2 rounded-xl bg-brand-secondary text-brand-primary text-xs font-bold shadow-sm flex flex-col justify-center">
                                    <i class="fa-solid fa-cloud mb-1 text-brand-primary"></i>
                                    <span><?= floatval($defi['co2_kg']) ?> kg CO2</span>
                                </div>
                            </div>
                            
                            <?php if ($duration > 1): ?>
                            <div class="mb-6 bg-brand-card border border-brand-border p-3 rounded-xl">
                                <div class="w-full h-2 bg-brand-border rounded-full overflow-hidden">
                                    <div class="h-full bg-brand-secondary transition-all duration-500" style="width: <?= $progress_percent ?>%;"></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <form action="validate_mission.php" method="POST">
                                <input type="hidden" name="challenge_id" value="<?= $defi['id'] ?>">
                                <button type="submit" <?= $disabled ? 'disabled' : '' ?> class="w-full py-4 rounded-xl bg-brand-secondary text-brand-primary font-bold shadow-lg <?= $disabled ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90' ?> transition">
                                    <?= $disabled ? "Déjà fait" : "Valider ce défi" ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="px-4 space-y-8 mt-10 hidden" id="my-challenges-page">
        
        <div>
            <h2 class="text-left text-xs font-bold uppercase tracking-widest text-brand-secondary mb-4 border-b border-brand-border pb-2">
                <?= htmlspecialchars($t['in_progress'] ?? 'En cours') ?>
            </h2>
            
            <?php if(empty($defis_en_cours)): ?>
                <div class="text-center py-8 bg-brand-primary rounded-[20px] border border-brand-border border-dashed">
                    <p class="text-sm text-brand-tertiary font-bold"><?= htmlspecialchars($t['no_challenge_in_progress'] ?? 'Aucun défi en cours.') ?></p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach($defis_en_cours as $defi): ?>
                        <?php 
                            $sql_today = "SELECT COUNT(*) FROM user_actions WHERE user_id = :uid AND challenge_id = :cid AND DATE(date_action) = CURDATE()";
                            $stmt_td = $pdo->prepare($sql_today);
                            $stmt_td->execute(['uid' => $user_id, 'cid' => $defi['id']]);
                            $today_count = $stmt_td->fetchColumn();
                            
                            $disabled = ($today_count >= $defi['max_actions_day']);
                            $diff = strtolower($defi['difficulty'] ?? 'facile');
                            $leafCount = ($diff == 'difficile') ? 3 : (($diff == 'moyen') ? 2 : 1);
                            
                            $duration = (int)($defi['duration_days'] ?? 1);
                            $days_done = $defi['days_done'];
                            $progress_percent = ($duration > 0) ? round(($days_done / $duration) * 100) : 0;

                            $catStr = strtolower($defi['categorie'] ?? '');
                            if (strpos($catStr, 'covoiturage') !== false) {
                                $catIcon = '../../img/carte/carte-covoiturage.svg';
                            } elseif (strpos($catStr, 'recyclage') !== false) {
                                $catIcon = '../../img/carte/carte-trier.svg';
                            } elseif (strpos($catStr, 'mobilit') !== false) {
                                $catIcon = '../../img/carte/carte-mediateur.svg';
                            } else {
                                $catIcon = '../../img/carte/carte-covoiturage2.svg';
                            }
                        ?>
                        
                        <div class="challenge-card bg-brand-secondary border-transparent rounded-2xl p-2.5 flex relative shadow-md items-stretch cursor-pointer hover:opacity-90 transition text-white" 
                             onclick="openModal('modal-my-<?= $defi['id'] ?>')">
                            
                            <div class="w-[65px] min-h-[65px] bg-transparent rounded-xl flex items-center justify-center shrink-0 relative overflow-hidden">
                                <?php if (!empty($defi['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($defi['image_url']) ?>" alt="Image défi" class="w-full h-full object-cover absolute inset-0">
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($catIcon) ?>" alt="Icone catégorie" class="w-full h-full scale-110 object-contain relative z-10">
                                <?php endif; ?>
                            </div>
                            
                            <div class="ml-3 flex-1 flex flex-col justify-center py-1">
                                <h3 class="font-bold text-[13px] text-white leading-tight line-clamp-2 pr-10">
                                    <?= htmlspecialchars($defi['titre_' . $lang] ?? $defi['titre_fr']) ?>
                                </h3>
                                
                                <div class="mt-2 pr-10">
                                    <div class="flex justify-between items-center text-[9px] font-bold text-white mb-1">
                                        <span class="uppercase tracking-wider">Progression</span>
                                        <span><?= $days_done ?>/<?= $duration ?> <?= htmlspecialchars($t['days_abbr'] ?? 'j') ?></span>
                                    </div>
                                    <div class="w-full h-1.5 bg-white/30 rounded-full overflow-hidden">
                                        <div class="h-full bg-white transition-all duration-500" style="width: <?= $progress_percent ?>%;"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="absolute -bottom-2 -right-2 z-10" onclick="event.stopPropagation();">
                                <form action="validate_mission.php" method="POST">
                                    <input type="hidden" name="challenge_id" value="<?= $defi['id'] ?>">
                                    <button type="submit" <?= $disabled ? 'disabled' : '' ?> class="w-10 h-10 bg-brand-dark rounded-full flex items-center justify-center text-brand-primary <?= $disabled ? 'opacity-40 cursor-not-allowed' : 'hover:bg-brand-dark active:scale-95' ?> transition shadow-md">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div id="modal-my-<?= $defi['id'] ?>" class="fixed inset-0 z-[150] hidden flex items-center justify-center bg-brand-dark/80 p-4 backdrop-blur-sm">
                            <div class="relative w-full max-w-sm bg-brand-primary rounded-3xl p-6 shadow-2xl">
                                <button type="button" onclick="closeModal('modal-my-<?= $defi['id'] ?>')" class="absolute top-4 right-4 w-8 h-8 bg-brand-secondary rounded-full flex items-center justify-center text-brand-primary hover:opacity-90 transition">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                                
                                <h3 class="text-xl font-display font-bold text-brand-secondary leading-tight mb-2 mt-4"><?= htmlspecialchars($defi['titre_' . $lang] ?? $defi['titre_fr']) ?></h3>
                                <p class="text-sm text-brand-secondary opacity-90 mb-6 max-h-40 overflow-y-auto"><?= nl2br(htmlspecialchars($defi['descr_' . $lang] ?? $defi['descr_fr'])) ?></p>
                                
                                <div class="mb-6 bg-brand-card p-3 rounded-xl">
                                    <div class="flex justify-between items-center text-[10px] font-bold text-brand-secondary mb-2 uppercase tracking-widest">
                                        <span>Avancement</span>
                                        <span class="text-brand-dark"><?= $days_done ?> / <?= $duration ?> <?= htmlspecialchars($t['days_abbr'] ?? 'j') ?></span>
                                    </div>
                                    <div class="w-full h-2 bg-brand-secondary rounded-full overflow-hidden shadow-inner">
                                        <div class="h-full bg-brand-dark transition-all duration-500" style="width: <?= $progress_percent ?>%;"></div>
                                    </div>
                                </div>
                                
                                <form action="validate_mission.php" method="POST">
                                    <input type="hidden" name="challenge_id" value="<?= $defi['id'] ?>">
                                    <button type="submit" <?= $disabled ? 'disabled' : '' ?> class="w-full py-4 rounded-xl bg-brand-secondary text-brand-primary font-bold shadow-lg <?= $disabled ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90' ?> transition">
                                        <?= $disabled ? htmlspecialchars($t['already_done'] ?? "Déjà fait aujourd'hui") : htmlspecialchars($t['validate_challenge'] ?? 'Valider ce défi') ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <h2 class="text-left text-xs font-bold uppercase tracking-widest text-brand-secondary mb-4 border-b border-brand-border pb-2">
                <?= htmlspecialchars($t['history'] ?? 'Historique') ?>
            </h2>
            
            <?php if(empty($userHistory)): ?>
                <div class="text-center py-8 bg-brand-primary rounded-[20px] border border-brand-border border-dashed">
                    <p class="text-sm text-brand-tertiary font-bold"><?= htmlspecialchars($t['no_action_validated'] ?? 'Aucune action validée.') ?></p>
                </div>
            <?php else: ?>
                <div class="space-y-3 max-h-[300px] overflow-y-auto pr-2 pb-2 custom-scrollbar">
                    <?php foreach($userHistory as $history): ?>
                        <div class="bg-brand-secondary border-transparent text-white rounded-2xl p-4 flex flex-col relative shadow-md shrink-0">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-bold text-sm text-white pr-4">
                                    <?= htmlspecialchars($history['titre_' . $lang] ?? $history['titre_fr']) ?>
                                </h3>
                                <span class="bg-white/20 text-white px-2 py-1 rounded-md text-[10px] font-bold shrink-0">
                                    +<?= htmlspecialchars($history['xp_gain']) ?> XP
                                </span>
                            </div>
                            <div class="flex justify-between items-center mt-2 pt-2">
                                <span class="text-[10px] text-white"><i class="fa-regular fa-calendar mr-1"></i> <?= date('d/m/Y', strtotime($history['date_action'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="filter-modal" class="fixed inset-0 z-[150] hidden flex items-end justify-center bg-brand-dark/80 backdrop-blur-sm sm:items-center">
        <div class="bg-brand-primary w-full max-w-md rounded-t-3xl sm:rounded-3xl p-6 relative shadow-2xl transition-transform transform translate-y-0">
            <button onclick="closeFilter()" class="absolute top-4 right-4 text-brand-secondary hover:text-brand-dark font-bold text-xl z-20 transition"><i class="fa-solid fa-xmark"></i></button>
            <h2 class="text-center text-lg mb-6 font-display font-bold uppercase tracking-widest text-brand-dark"><?= htmlspecialchars($t['filter_title'] ?? 'Filtres') ?></h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold mb-1 uppercase tracking-wider text-brand-tertiary"><?= htmlspecialchars($t['filter_difficulty'] ?? 'Difficulté') ?></label>
                    <select id="filter-difficulty" class="w-full bg-brand-card rounded-xl p-3 text-sm outline-none border border-brand-border text-brand-dark font-bold">
                        <option value="all"><?= htmlspecialchars($t['filter_all'] ?? 'Toutes') ?></option>
                        <option value="facile"><?= htmlspecialchars($t['filter_easy'] ?? 'Facile') ?></option>
                        <option value="moyen"><?= htmlspecialchars($t['filter_medium'] ?? 'Moyen') ?></option>
                        <option value="difficile"><?= htmlspecialchars($t['filter_hard'] ?? 'Difficile') ?></option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 uppercase tracking-wider text-brand-tertiary mt-4"><?= htmlspecialchars($t['filter_domain'] ?? 'Domaine') ?></label>
                    <select id="filter-domain" class="w-full bg-brand-card rounded-xl p-3 text-sm outline-none border border-brand-border text-brand-dark font-bold">
                        <option value="all"><?= htmlspecialchars($t['filter_all_domains'] ?? 'Tous les domaines') ?></option>
                        <option value="ecologique"><?= htmlspecialchars($t['filter_ecological'] ?? 'Écologique') ?></option>
                        <option value="social"><?= htmlspecialchars($t['filter_social'] ?? 'Social') ?></option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 uppercase tracking-wider text-brand-tertiary mt-4"><?= htmlspecialchars($t['filter_category'] ?? 'Catégorie') ?></label>
                    
                    <select id="filter-category" class="w-full bg-brand-card rounded-xl p-3 text-sm outline-none border border-brand-border text-brand-dark font-bold">
                        <option value="all"><?= htmlspecialchars($t['filter_all_categories'] ?? 'Toutes les catégories') ?></option>
                        
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>">
                                <?= htmlspecialchars($t['cat_' . $cat] ?? $cat) ?>
                            </option>
                        <?php endforeach; ?>
                        
                    </select>
                    </div>
            </div>
            <button onclick="applyFilters()" class="w-full bg-brand-dark text-brand-primary font-bold py-4 rounded-xl mt-8 shadow-lg hover:bg-brand-dark transition"><?= htmlspecialchars($t['btn_apply_filters'] ?? 'Appliquer les filtres') ?></button>
        </div>
    </div>

    <?php if (isset($_SESSION['mission_validated']) || isset($_GET['validated'])): ?>
    <div id="validation-popup" class="fixed inset-0 z-[200] flex items-center justify-center bg-brand-dark/80 p-4 backdrop-blur-sm transition-opacity duration-300">
        <div class="bg-brand-primary w-full max-w-sm rounded-3xl p-8 text-center shadow-2xl animate-pop">
            
            <h2 class="font-display text-4xl font-black text-brand-secondary mb-2 uppercase tracking-tight">
                <?= htmlspecialchars($t['merci'] ?? 'Merci !') ?>
            </h2>
            
            <p class="text-brand-secondary text-sm font-bold mb-8 opacity-80">
                <?= htmlspecialchars($t['mission_validated_msg'] ?? 'Votre tâche a bien été validée.') ?>
            </p>

            <img class="w-20 h-20 mx-auto mb-8 drop-shadow-lg" src="../../img/icone/mascotte-monnaie.svg" alt="Validation">
            
            <button onclick="closeValidationPopup()" class="w-full bg-brand-secondary text-brand-primary font-bold py-4 rounded-xl shadow-lg hover:opacity-90 transition active:scale-95 flex justify-center items-center gap-2">
                <?= htmlspecialchars($t['btn_continue'] ?? 'Continuer') ?> <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </div>
    
    <script>
        // Si le popup de validation est affiché, on bloque le scroll
        document.body.classList.add('overflow-hidden');

        function closeValidationPopup() {
            const popup = document.getElementById('validation-popup');
            popup.style.opacity = '0';
            setTimeout(() => {
                popup.remove();
                // On libère le scroll
                document.body.classList.remove('overflow-hidden');
            }, 300);
            
            // Nettoyer l'URL si on utilise le paramètre GET
            if(window.location.search.includes('validated')) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }
    </script>
    <?php 
        unset($_SESSION['mission_validated']); 
    endif; 
    ?>

    <?php include '../../includes/level_up_popup.php'; ?>
    <?php include '../../includes/settings_menu.php'; ?>
    <?php include '../../includes/navbar.php'; ?>
    <?php include '../../includes/notifications_popup.php'; ?>

    <script>
        function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.body.classList.add('overflow-hidden'); }
        function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
        function openFilter() { document.getElementById('filter-modal').classList.remove('hidden'); document.body.classList.add('overflow-hidden'); }
        function closeFilter() { document.getElementById('filter-modal').classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
        
        function switchTab(tab) {
            const tabSearch = document.getElementById('tab-search');
            const tabMy = document.getElementById('tab-my');
            const searchList = document.getElementById('challenges-list');
            const myList = document.getElementById('my-challenges-page');
            const filterBtn = document.getElementById('filter-btn');
            const textSearch = tabSearch.querySelector('h2');
            const textMy = tabMy.querySelector('span');
            const activeClasses = ['left-[-10%]', 'w-[68%]', 'h-32', 'max-[375px]:h-28', 'max-[320px]:h-24', 'bg-brand-secondary', 'justify-end', 'pr-8', 'z-20'];
            const inactiveClasses = ['right-[-5%]', 'left-auto', 'top-[45%]', 'w-[45%]', 'h-28', 'max-[375px]:h-24', 'max-[320px]:h-20', 'bg-brand-border', 'justify-center', 'pl-4', 'z-10', 'border-brand-primary'];
            
            if (tab === 'search') {
                tabSearch.classList.remove(...inactiveClasses);
                tabSearch.classList.add(...activeClasses);
                tabMy.classList.remove(...activeClasses);
                tabMy.classList.add(...inactiveClasses);

                textSearch.classList.replace('text-brand-tertiary', 'text-brand-dark');
                textMy.classList.replace('text-brand-dark', 'text-brand-tertiary');
                
                searchList.classList.remove('hidden');
                myList.classList.add('hidden');
                filterBtn.style.display = 'flex';
            } else {
                tabMy.classList.remove(...inactiveClasses);
                tabMy.classList.add(...activeClasses);
                tabSearch.classList.remove(...activeClasses);
                tabSearch.classList.add(...inactiveClasses);

                textMy.classList.replace('text-brand-tertiary', 'text-brand-dark');
                textSearch.classList.replace('text-brand-dark', 'text-brand-tertiary');
                
                searchList.classList.add('hidden');
                myList.classList.remove('hidden');
                filterBtn.style.display = 'none';
            }
        }

        function applyFilters() {
            const diff = document.getElementById('filter-difficulty').value;
            const domain = document.getElementById('filter-domain').value;
            const cat = document.getElementById('filter-category').value;

            const cards = document.querySelectorAll('.challenge-card');
            const sections = document.querySelectorAll('.category-block');

            cards.forEach(card => {
                const cardDiff = card.getAttribute('data-difficulty');
                const cardDomain = card.getAttribute('data-domain');
                const cardDomain2 = card.getAttribute('data-domain-2');
                const cardCat = card.getAttribute('data-category');

                const matchDiff = (diff === 'all' || cardDiff === diff);
                const matchDomain = (domain === 'all' || cardDomain === domain || cardDomain2 === domain);
                const matchCat = (cat === 'all' || cardCat === cat);

                if (matchDiff && matchDomain && matchCat) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
            
            sections.forEach(sec => {
                const visibleCardsInSec = sec.querySelectorAll('.challenge-card[style="display: flex;"], .challenge-card:not([style*="display: none"])');
                sec.style.display = (visibleCardsInSec.length > 0) ? 'block' : 'none';
            });

            closeFilter();
        }
    </script>
</body>
</html>