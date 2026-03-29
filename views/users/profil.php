<?php
require_once '../../includes/init.php';

// 1. Récupération des infos de base
$stmt = $pdo->prepare("
    SELECT u.*, d.nom as department_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$deptName = $user['department_name'] ?? ($t['no_dept'] ?? "Sans département");

// 2. Récupération de la meilleure série (streak) et du score (rank)
$streak = $user['max_streak'] ?? 0;
$rank = $user['points_rank'] ?? 0;

// 3. Calcul du total de défis réalisés
$stmtActions = $pdo->prepare("SELECT COUNT(*) FROM user_actions WHERE user_id = ?");
$stmtActions->execute([$user_id]);
$total_actions = $stmtActions->fetchColumn();

// 4. Calcul du classement dans l'entreprise
$company_rank = 0;
if (!empty($user['company_id'])) {
    $stmtLeaderboard = $pdo->prepare("SELECT COUNT(*) + 1 FROM users WHERE company_id = ? AND points_rank > ?");
    $stmtLeaderboard->execute([$user['company_id'], $rank]);
    $company_rank = $stmtLeaderboard->fetchColumn();
}

// 5. Récupération des badges
$stmtBadges = $pdo->prepare("
    SELECT b.*, 
           c.titre_fr as c_titre_fr, c.titre_en as c_titre_en,
           CASE 
               WHEN ub.obtained_at IS NOT NULL THEN 1 
               WHEN b.challenge_required_id IS NULL AND b.xp_threshold > 0 AND :xp >= b.xp_threshold THEN 1 
               ELSE 0 
           END as is_unlocked
    FROM badges b
    LEFT JOIN user_badges ub ON b.id = ub.badge_id AND ub.user_id = :uid
    LEFT JOIN challenges c ON b.challenge_required_id = c.id
    ORDER BY b.xp_threshold ASC, b.id ASC
");
$stmtBadges->execute(['xp' => $rank, 'uid' => $user_id]);
$badges = $stmtBadges->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pseudo) ?> - Shift'Up</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../js/tailwind-config.js"></script>
</head>

<body class="bg-brand-card text-brand-dark font-sans overflow-x-hidden pb-24">
    <header class="bg-brand-primary text-brand-card p-4 shadow-md relative z-40">

        <div class="absolute right-5 top-16 flex gap-2 max-[375px]:gap-1 z-50">
            <button onclick="openNotifications()" class="w-11 h-11 bg-brand-secondary rounded-xl flex items-center justify-center shadow-sm active:scale-95 transition">
                <img class="w-8 h-8" src="../../img/icone/icone-notification-blanc.svg" alt="Notifications">
            </button>
            <button onclick="toggleMenu()" class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-secondary rounded-xl flex items-center justify-center text-brand-card shadow-sm active:scale-95 transition">
                <img src="../../img/icone/icone-parametre-blanc.svg" alt="icone parametre" class="w-8 max-[375px]:w-4"></img>
            </button>
        </div>

        <div class="relative h-56 top-[-35px] flex items-center justify-center -mx-4 overflow-hidden mt-2 z-10">
            
            <div class="absolute left-[-10%] w-[68%] h-32 max-[375px]:h-28 max-[320px]:h-24 bg-brand-secondary skew-tile cursor-pointer flex items-center justify-end pr-8 max-[375px]:pr-4 transition-colors">
                <div class="unskew text-right flex flex-col justify-center">
                    <h2 class="font-display text-4xl max-[375px]:text-3xl max-[320px]:text-2xl font-bold text-brand-card leading-none mb-1"><?= htmlspecialchars($pseudo) ?></h2>
                    <p class="text-xl max-[375px]:text-lg max-[320px]:text-base font-semibold text-brand-card leading-none"><?= htmlspecialchars($deptName) ?></p>
                </div>
            </div>
            
            <div class="absolute left-0 top-40 w-[65%]  bg-brand-dark p-2 pl-8 flex items-center justify-center text-brand-card shadow-sm" style="clip-path: polygon(0 0, 100% 0, 92% 50%, 100% 100%, 0 100%);">
                 <div class="grid gap-2 max-[375px]:gap-2 grid-cols-5 items-center justify-center text-brand-card shadow-sm">
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-card shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-card shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-medal text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-card shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-10 h-10 max-[375px]:w-8 max-[375px]:h-8 rounded-full bg-brand-secondary flex items-center justify-center text-brand-primary font-display font-bold border-2 border-brand-primary text-lg max-[375px]:text-sm"><?= $levelData['niveau_actuel'] ?></div>
                </div>
            </div>
        </div>
    </header>

    <main class="p-4 mt-6 flex flex-col gap-10 z-10 relative">
        
        <div class="relative w-full">
            <div class="absolute right-[-16px] top-[-20px] w-[75%] h-10 max-[375px]:h-10 max-[320px]:h-10 z-30 bg-brand-secondary pl-20 flex items-center text-brand-card shadow-sm" style="clip-path: polygon(8% 50%, 0 0, 100% 0, 100% 100%, 0% 100%)">
                <h3 class="font-bold">Solo</h3>
            </div>
            
            <div class="mt-8 w-full text-center grid gap-5 max-[375px]:gap-5 grid-cols-2 items-center justify-center text-brand-card shadow-sm">
                <div class="flex flex-col items-center gap-1 bg-brand-dark p-4 rounded-xl">
                    <h3 class="text-md font-bold underline"><?= $t['total_actions'] ?? 'Défis validés' ?></h3>
                    <span class="text-xl font-bold"><?= number_format($total_actions, 0, ',', ' ') ?></span>
                </div>
                <div class="flex flex-col items-center gap-1 bg-brand-dark p-4 rounded-xl">
                    <h3 class="text-md font-bold underline"><?= $t['top_streak'] ?? 'Top streak' ?></h3>
                    <span class="text-xl font-bold"><?= number_format($streak, 0, ',', ' ') ?> <i class="fa-solid fa-fire text-brand-secondary text-sm"></i></span>
                </div>
            </div>
        </div>

        <div class="relative w-full">
            <div class="absolute right-[-16px] top-[-20px] w-[75%] h-10 max-[375px]:h-10 max-[320px]:h-10 z-30 bg-brand-secondary pl-20 flex items-center text-brand-card shadow-sm" style="clip-path: polygon(8% 50%, 0 0, 100% 0, 100% 100%, 0% 100%)">
                <h3 class="font-bold">Shift League</h3>
            </div>
            
            <div class="mt-8 w-full text-center grid gap-5 max-[375px]:gap-5 grid-cols-2 items-center justify-center text-brand-card shadow-sm">
                <div class="flex flex-col items-center gap-1 bg-brand-dark p-4 rounded-xl">
                    <h3 class="text-md font-bold underline"><?= $t['total_score'] ?? 'Total XP' ?></h3>
                    <span class="text-xl font-bold"><?= number_format($rank, 0, ',', ' ') ?></span>
                </div>
                <div class="flex flex-col items-center gap-1 bg-brand-dark p-4 rounded-xl">
                    <h3 class="text-md font-bold underline"><?= $t['ranking'] ?? 'Classement' ?></h3>
                    <span class="text-xl font-bold">#<?= $company_rank ?></span>
                </div>
            </div>
        </div>

        <div class="relative w-full">
            <div class="absolute right-[-16px] top-[-20px] w-[75%] h-10 max-[375px]:h-10 max-[320px]:h-10 z-30 bg-brand-secondary pl-20 flex items-center text-brand-card shadow-sm" style="clip-path: polygon(8% 50%, 0 0, 100% 0, 100% 100%, 0% 100%)">
                <h3 class="font-bold"><?= $t['badges'] ?? 'Badges' ?></h3>
            </div>
            
            <div class="badge-slider mt-10 overflow-x-scroll flex gap-6 px-2 snap-x snap-mandatory scrollbar-hide overflow-y-hidden" style="scrollbar-width: none;">
                <?php if (empty($badges)): ?>
                    <p class="text-brand-tertiary text-sm italic py-4 w-full text-center">Aucun badge disponible.</p>
                <?php else: ?>
                    <?php foreach ($badges as $badge): ?>
                        <?php 
                            $unlocked = $badge['is_unlocked'] == 1;
                            $b_nom = $lang === 'en' && !empty($badge['nom_en']) ? $badge['nom_en'] : $badge['nom_fr'];
                            $b_desc = $lang === 'en' && !empty($badge['descr_en']) ? $badge['descr_en'] : $badge['descr_fr'];
                            $icon = !empty($badge['icon_url']) ? $badge['icon_url'] : '../../img/carte/carte-trier.svg';
                            
                            $c_titre = "";
                            if (!empty($badge['challenge_required_id'])) {
                                $c_titre = $lang === 'en' && !empty($badge['c_titre_en']) ? $badge['c_titre_en'] : $badge['c_titre_fr'];
                            }
                        ?>
                        
                        <div class="badge-card snap-center shrink-0 w-[80%] bg-brand-secondary rounded-xl p-1 flex flex-col items-center justify-center relative overflow-hidden group">
                            
                            <img src="<?= htmlspecialchars($icon) ?>" alt="<?= htmlspecialchars($b_nom) ?>" class="w-full h-full object-cover rounded-lg <?= $unlocked ? '' : 'grayscale opacity-40' ?>" />
                            
                            <?php if (!$unlocked): ?>
                                <div class="absolute inset-0 flex flex-col items-center justify-center bg-brand-dark/60 rounded-lg transition-opacity duration-300 group-hover:opacity-0">
                                    <i class="fa-solid fa-lock text-white text-4xl shadow-lg"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="absolute -bottom-full left-0 right-0 bg-brand-dark/95 p-3 transition-all duration-300 group-hover:bottom-0 flex flex-col justify-center z-10">
                                <h4 class="text-brand-secondary font-bold text-[13px] text-center leading-tight mb-1"><?= htmlspecialchars($b_nom) ?></h4>
                                <p class="text-white text-[10px] text-center leading-tight mb-1 opacity-90"><?= htmlspecialchars($b_desc) ?></p>

                                <?php if (!$unlocked): ?>
                                    <div class="mt-1 pt-1 border-t border-white/20 text-center">
                                        <span class="text-brand-secondary text-[8px] font-bold uppercase tracking-wider block mb-0.5"><?= $t['to_unlock'] ?? 'Pour débloquer :' ?></span>
                                        
                                        <?php if ($badge['xp_threshold'] > 0 && empty($badge['challenge_required_id'])): ?>
                                            <span class="text-white text-[10px] font-bold"><?= $badge['xp_threshold'] ?> XP</span>
                                        <?php elseif (!empty($c_titre)): ?>
                                            <span class="text-white text-[10px] font-bold leading-tight"><?= htmlspecialchars($c_titre) ?></span>
                                        <?php endif; ?>
                                        
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include '../../includes/level_up_popup.php'; ?>
    <?php include '../../includes/settings_menu.php'; ?>
    <?php include '../../includes/navbar.php'; ?>
    <?php include '../../includes/notifications_popup.php'; ?>

    <script>
        const slider = document.querySelector('.badge-slider');
        const cards = document.querySelectorAll('.badge-card');

        function updateCards() {
            if(!slider || cards.length === 0) return;
            const center = slider.scrollLeft + slider.offsetWidth / 2;

            cards.forEach(card => {
                const cardCenter = card.offsetLeft + card.offsetWidth / 2;
                const distance = Math.abs(center - cardCenter);

                const scale = Math.max(0.92, 1 - distance / 500);
                const offset = Math.min(25, distance / 12);

                card.style.transform = `translateY(${offset}px) scale(${scale})`;

                if (distance < 80) {
                    card.classList.add("active");
                } else {
                    card.classList.remove("active");
                }
            });
        }

        if(slider) {
            slider.addEventListener('scroll', updateCards);
            setTimeout(updateCards, 100); 
        }
    </script>
</body>
</html>