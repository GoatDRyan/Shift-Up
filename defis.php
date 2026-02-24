<?php
session_start();
require_once 'db_connect.php';

if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
} elseif (file_exists('functions.php')) {
    require_once 'functions.php';
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 4; 
}

// Récupération des données du joueur
$stmtUser = $pdo->prepare("SELECT points_rank, points_wallet FROM users WHERE id = :uid");
$stmtUser->execute(['uid' => $_SESSION['user_id']]);
$currentUser = $stmtUser->fetch();

$userXp = $currentUser['points_rank'] ?? 0;
$userMoney = $currentUser['points_wallet'] ?? 0;

$userLevel = floor($userXp / 2500) + 1;
$nextLevelXp = $userLevel * 2500;

$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'fr';
if (!in_array($lang, ['fr', 'en'])) $lang = 'fr';
$textes = require_once "lang/$lang.php";

$sql = "SELECT * FROM challenges ORDER BY categorie, titre_$lang";
$stmt = $pdo->query($sql);
$allChallenges = $stmt->fetchAll();

$stmt_actions = $pdo->prepare("SELECT DISTINCT challenge_id FROM user_actions WHERE user_id = :uid");
$stmt_actions->execute(['uid' => $_SESSION['user_id']]);
$userValidatedIds = $stmt_actions->fetchAll(PDO::FETCH_COLUMN);

// Récupération de l'historique détaillé pour l'onglet "Mes défis"
$stmt_history = $pdo->prepare("
    SELECT 
        ua.date_action, 
        c.titre_fr, 
        c.titre_en, 
        c.descr_fr, 
        c.descr_en, 
        c.xp_gain,
        c.difficulty
    FROM user_actions ua 
    JOIN challenges c ON ua.challenge_id = c.id 
    WHERE ua.user_id = :uid 
    ORDER BY ua.date_action DESC
");
$stmt_history->execute(['uid' => $_SESSION['user_id']]);
$userHistory = $stmt_history->fetchAll();


$groupedChallenges = [];
$categories = [];
$taskTypes = [];

foreach ($allChallenges as $c) {
    $catName = !empty($c['categorie']) ? $c['categorie'] : 'Général';
    $typeName = !empty($c['type_defi']) ? $c['type_defi'] : 'Standard';
    
    if (!isset($groupedChallenges[$catName])) {
        $groupedChallenges[$catName] = [];
        if (!in_array($catName, $categories)) $categories[] = $catName;
    }
    if (!in_array($typeName, $taskTypes)) $taskTypes[] = $typeName;
    
    $groupedChallenges[$catName][] = $c;
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Les Défis Shift'Up</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        @font-face {
            font-family: 'Bahnschrift';
            src: local('Bahnschrift');
        }
        body { font-family: 'Bahnschrift', sans-serif; background-color: #ffffff; }
        
        .clip-pill { clip-path: polygon(0 0, 85% 0, 100% 100%, 0% 100%); }
      .clip-tab-left { clip-path: polygon(0 0, 100% 0, 75% 100%, 0% 100%); }
    .clip-tab-right { clip-path: polygon(25% 0, 100% 0, 100% 100%, 0% 100%); }
    .clip-filter { clip-path: polygon(0 0, 100% 0, 85% 100%, 0% 100%); }
        
.app-container { width: 100%; max-width: 480px; margin: 0 auto; min-height: 100vh; position: relative; background: #ffffff; overflow-x: hidden; }        .tab-transition { transition: all 0.3s ease; }
    </style>
</head>
<body class="flex justify-center bg-gray-100"> 
    <div class="app-container pb-24 bg-white shadow-xl relative">
        
        <div class="relative w-full h-[180px]">
            
            <div id="tab-search" onclick="switchTab('search')" class="absolute left-0 bottom-9 w-[60%] h-[80px] bg-[#d9d9d9] clip-tab-left flex flex-col justify-end items-center cursor-pointer tab-transition z-10 pb-6">
                <h2 class="text-[16px] text-black leading-tight text-center pr-6 font-medium">
                    Rechercher<br>des défis
                </h2>
            </div>

            <div id="tab-my" onclick="switchTab('my')" class="absolute right-0 top-[90px] w-[50%] h-[80px] bg-[#e5e5e5] clip-tab-right flex items-center justify-center cursor-pointer tab-transition z-0">
                <span class="text-[18px] text-black pl-8 font-medium">Mes défis</span>
            </div>

            <div id="filter-btn" class="absolute left-0 top-[150px] w-[45%] h-[30px] bg-[#d9d9d9] clip-filter flex items-center justify-center cursor-pointer hover:bg-gray-300 transition z-20" onclick="openFilter()">
                <span class="text-[12px] text-black pr-2">Filtre</span>
            </div>

            <div class="absolute top-0 left-0 w-full pt-6 px-4 flex justify-between items-start z-30 pointer-events-none">
                
                <div class="flex flex-row space-x-3 pointer-events-auto">
                    <div class="relative flex items-center h-8 w-28">
                        <div class="bg-[#d9d9d9] h-5 pl-8 pr-3 rounded-full flex items-center ml-1 w-full shadow-sm border border-black/5">
                            <span class="text-[9px] font-bold text-black whitespace-nowrap">
                                <?= $userXp ?>/<?= $nextLevelXp ?>
                            </span>
                        </div>
                        <div class="absolute left-0 w-8 h-8 rounded-full bg-[#8c8c8c] text-white flex items-center justify-center font-bold text-[10px] shadow-sm z-10">
                            <?= $userLevel ?>
                        </div>
                    </div>
                    
                    <div class="relative flex items-center h-8 w-28">
                        <div class="bg-[#d9d9d9] h-5 pl-8 pr-4 flex items-center ml-1 w-full shadow-sm transform -skew-x-12 border border-black/5">
                            <span class="text-[9px] font-bold text-black whitespace-nowrap transform skew-x-12">
                                <?= $userMoney ?>
                            </span>
                        </div>
                        <div class="absolute left-0 w-8 h-8 rounded-full border-2 border-black bg-[#d9d9d9] text-black flex items-center justify-center font-bold text-sm shadow-sm z-10">
                            $
                        </div>
                    </div>
                </div>
                
          <div class="flex space-x-2 pointer-events-auto mt-8">
    <div class="w-8 h-8 bg-[#d9d9d9] rounded-lg cursor-pointer hover:bg-gray-300 transition flex items-center justify-center shadow-sm">
        <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
    </div>
    <div class="w-8 h-8 bg-[#d9d9d9] rounded-lg cursor-pointer hover:bg-gray-300 transition flex items-center justify-center shadow-sm">
        <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </div>
</div>
            </div>
        </div>
        <div class="px-4 space-y-6 mt-10" id="challenges-list">
            <?php foreach ($groupedChallenges as $categoryName => $challengesInCat): ?>
                <div class="category-block bg-[#e5e5e5] rounded-[20px] p-4 pb-6 shadow-sm" data-cat-name="<?= htmlspecialchars($categoryName) ?>">
                    <h2 class="text-center text-[17px] mb-4 text-black"><?= htmlspecialchars($categoryName) ?> :</h2>
                    
                    <?php foreach($challengesInCat as $defi): ?>
                        <?php 
                            $sql_today = "SELECT COUNT(*) FROM user_actions WHERE user_id = :uid AND challenge_id = :cid AND DATE(date_action) = CURDATE()";
                            $stmt_td = $pdo->prepare($sql_today);
                            $stmt_td->execute(['uid' => $_SESSION['user_id'], 'cid' => $defi['id']]);
                            $today_count = $stmt_td->fetchColumn();
                            
                            $disabled = ($today_count >= $defi['max_actions_day']);
                            $diff = strtolower($defi['difficulty'] ?? 'facile');
                            $leafCount = ($diff == 'difficile') ? 3 : (($diff == 'moyen') ? 2 : 1);
                            $typeDefi = !empty($defi['type_defi']) ? $defi['type_defi'] : 'Standard';
                            $isValidated = in_array($defi['id'], $userValidatedIds);
                        ?>
                        <div class="challenge-card bg-[#a3a3a3] rounded-2xl p-2.5 flex relative mb-4 shadow-sm items-stretch cursor-pointer" 
                             data-difficulty="<?= $diff ?>" 
                             data-category="<?= htmlspecialchars($categoryName) ?>"
                             data-type="<?= htmlspecialchars($typeDefi) ?>"
                             data-validated="<?= $isValidated ? '1' : '0' ?>"
                             onclick="openModal('modal-<?= $defi['id'] ?>')">
                            
                            <div class="w-[75px] min-h-[75px] bg-[#d9d9d9] rounded-xl flex items-center justify-center shrink-0 relative">
                                <svg class="w-8 h-8 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <div class="absolute text-xl font-bold top-[15px] left-[45px]">+</div>
                            </div>
                            
                            <div class="ml-2 flex-1 min-h-[75px] bg-[#d9d9d9] rounded-xl relative p-2 flex flex-col justify-between">
                                <div class="flex justify-between items-start w-full gap-2">
                                    <div class="flex flex-col flex-1">
                                        <h3 class="font-bold text-[12px] text-black leading-tight line-clamp-2 break-words">
                                            <?= htmlspecialchars($defi['titre_' . $lang] ?? $defi['titre_fr']) ?>
                                        </h3>
                                        <p class="text-[10px] text-black mt-0.5">
    
<p class="text-[10px] text-black mt-0.5">
    <?= $defi['duration_days'] ?> <?= $defi['duration_days'] > 1 ? "Jours" : "Jour" ?>
</p>
                                    </div>
                                    <div class="flex space-x-0.5 shrink-0">
                                        <?php for($i=0; $i<$leafCount; $i++): ?>
                                            <svg class="w-[14px] h-[14px] text-black" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10ZM2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path>
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <div class="text-[11px] font-bold text-black mt-2 pr-8">
                                    50 Point &nbsp; <?= htmlspecialchars($defi['xp_gain']) ?>Xp
                                </div>
                            </div>

                            <div class="absolute -bottom-1 -right-1 z-10" onclick="event.stopPropagation();">
                                <form action="validate_mission.php" method="POST">
                                    <input type="hidden" name="challenge_id" value="<?= $defi['id'] ?>">
                                    <button type="submit" <?= $disabled ? 'disabled' : '' ?> class="w-8 h-8 bg-[#858585] rounded-full border-[3px] border-[#a3a3a3] flex items-center justify-center <?= $disabled ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-600' ?> transition">
                                        <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div id="modal-<?= $defi['id'] ?>" class="fixed inset-0 z-[150] hidden flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
                          <div class="relative w-full max-w-[340px] bg-[#d9d9d9] rounded-[30px] p-6 shadow-2xl">
                            <button type="button" onclick="closeModal('modal-<?= $defi['id'] ?>')" class="absolute -top-3 -right-3 w-8 h-8 bg-[#a3a3a3] border-[2px] border-[#d9d9d9] rounded-full flex items-center justify-center shadow-md z-40 hover:bg-gray-400 transition">
                              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6"/></svg>
                            </button>
                            <div class="bg-white rounded-[20px] w-full h-36 flex items-center justify-center overflow-hidden mb-5">
                              <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            </div>
                            <div class="flex items-center justify-between text-[13px] text-black font-medium px-1 mb-2">
                              <div class="flex items-center gap-1">
                                <span>Difficulté :</span>
                                <span class="flex items-center gap-0.5">
                                  <?php for ($i=0; $i < ($leafCount ?? 1); $i++): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10ZM2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path></svg>
                                  <?php endfor; ?>
                                </span>
                              </div>
                  
                            </div>
                            <h3 class="text-[22px] font-normal mt-1 px-1 leading-tight text-black"><?= htmlspecialchars($defi['titre_' . $lang] ?? $defi['titre_fr'] ?? 'Titre de la tache') ?></h3>
                            <div class="text-[14px] text-black mt-3 px-1 leading-snug max-h-32 overflow-y-auto"><?= nl2br(htmlspecialchars($defi['descr_' . $lang] ?? 'Pas de description disponible.')) ?></div>
                            <div class="flex gap-3 mt-6 px-1">
                              <div class="flex-1 text-center py-2.5 rounded-full bg-[#e5e5e5] text-black text-[14px] font-medium shadow-sm"><?= isset($defi['duration_days']) ? htmlspecialchars($defi['duration_days']) . ' jours' : '1 jour' ?></div>
                              <div class="flex-1 text-center py-2.5 rounded-full bg-[#e5e5e5] text-black text-[14px] font-medium shadow-sm">
                                <?php 
                                  $co2 = floatval($defi['co2_kg'] ?? 0);
                                  echo ($co2 > 0 && $co2 < 1) ? ($co2 * 1000) . 'g de CO2' : $co2 . 'kg de CO2';
                                ?>
                              </div>
                            </div>
                            <form action="validate_mission.php" method="POST" class="mt-5 px-1">
                              <input type="hidden" name="challenge_id" value="<?= $defi['id'] ?>">
                              <button type="submit" <?= $disabled ? 'disabled' : '' ?> class="w-full py-3 rounded-full bg-[#a3a3a3] text-black text-[15px] font-medium shadow-sm <?= $disabled ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-400' ?> transition">Valider la tache</button>
                            </form>
                          </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="px-4 space-y-4 mt-10 hidden pb-20" id="my-challenges-page">
    <h2 class="text-center text-[18px] mb-6 text-black font-bold border-b border-gray-300 pb-2 mx-4">Actions validées</h2>
    
    <?php if(empty($userHistory)): ?>
        <div class="text-center py-10 bg-[#e5e5e5] rounded-[20px]">
            <p class="text-[14px] text-gray-600">Aucune action validée pour le moment.</p>
        </div>
    <?php else: ?>
        <?php foreach($userHistory as $history): ?>
            <div class="bg-[#a3a3a3] rounded-2xl p-4 flex flex-col relative shadow-sm mb-4">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="font-bold text-[15px] text-black pr-4">
                        <?= htmlspecialchars($history['titre_' . $lang] ?? $history['titre_fr']) ?>
                    </h3>
                    <span class="bg-[#d9d9d9] px-3 py-1 rounded-full text-[12px] font-bold shrink-0">
                        +<?= htmlspecialchars($history['xp_gain']) ?> XP
                    </span>
                </div>

                <p class="text-[12px] text-black opacity-90 leading-snug mb-3">
                    <?= htmlspecialchars($history['descr_' . $lang] ?? $history['descr_fr']) ?>
                </p>

                <div class="flex justify-between items-center mt-2 border-t border-black/10 pt-2">
                    <span class="text-[11px] font-bold">Points : 50</span> <span class="text-[10px] text-gray-700 italic">Validé le <?= date('d/m/Y', strtotime($history['date_action'])) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

        <div id="filter-modal" class="fixed inset-0 z-[150] hidden flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
            <div class="bg-[#a3a3a3] w-full max-w-[340px] rounded-[35px] p-2 relative shadow-2xl">
                <button onclick="closeFilter()" class="absolute top-4 right-5 text-black font-bold text-2xl z-20 hover:text-gray-600 transition">&times;</button>
                <div class="bg-[#d9d9d9] rounded-[30px] p-6 pt-10">
                    <h2 class="text-center text-xl mb-6 font-bold">Filtrer les défis</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[11px] font-bold mb-1 ml-1 text-gray-700">Difficulté</label>
                            <select id="filter-difficulty" class="w-full bg-white rounded-xl p-3 text-sm outline-none border border-gray-300">
                                <option value="all">Toutes</option>
                                <option value="facile">Facile</option>
                                <option value="moyen">Moyen</option>
                                <option value="difficile">Difficile</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold mb-1 ml-1 text-gray-700">Domaine</label>
                            <select id="filter-domain" class="w-full bg-white rounded-xl p-3 text-sm outline-none border border-gray-300">
                                <option value="all">Tous les domaines</option>
                                <option value="ecologique">Écologique</option>
                                <option value="social">Social</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold mb-1 ml-1 text-gray-700">Catégorie</label>
                            <select id="filter-category" class="w-full bg-white rounded-xl p-3 text-sm outline-none border border-gray-300">
                                <option value="all">Toutes les catégories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button onclick="applyFilters()" class="w-full bg-[#858585] text-white py-4 rounded-[20px] text-sm mt-8 shadow-md transition">Appliquer</button>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div id="success-modal" class="fixed inset-0 z-[200] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="relative w-full max-w-[320px] bg-white rounded-3xl p-8 pb-10 shadow-2xl flex flex-col items-center">
                <button type="button" onclick="document.getElementById('success-modal').style.display='none'" class="absolute top-4 right-4 w-8 h-8 bg-[#858585] rounded-full flex items-center justify-center text-white hover:bg-gray-600 transition shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6"/></svg>
                </button>
                <h2 class="text-3xl font-normal text-black mt-4 mb-2">Merci !</h2>
                <p class="text-[19px] text-center text-black leading-tight mb-8">Votre tâche a<br>bien été validée</p>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-20 h-20 text-[#1a1a1a]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="10" width="16" height="12" rx="2" ry="2" stroke-width="2"></rect><path d="M3 17l4-4 4 4" stroke-width="2"></path><path d="M10 15l2-2 5 5" stroke-width="2"></path><line x1="16" y1="2" x2="16" y2="8" stroke-width="2.5"></line><line x1="13" y1="5" x2="19" y2="5" stroke-width="2.5"></line></svg>
            </div>
        </div>
        <script>
            window.history.replaceState({}, document.title, window.location.pathname);
        </script>
        <?php endif; ?>

        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[480px] bg-[#222222] h-16 flex items-center justify-around z-40">
            <a href="#" class="text-white opacity-80"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg></a>
            <a href="#" class="text-white opacity-80"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21h6M12 17v4M7 4v7c0 2.8 2.2 5 5 5s5-2.2 5-5V4H7z"/><path d="M7 6H5c-1.1 0-2 .9-2 2s.9 2 2 2h2M17 6h2c1.1 0 2 .9 2 2s-.9 2-2 2h-2"/></svg></a>
            <a href="defis.php" class="text-white transform scale-110"><svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg></a>
            <a href="#" class="text-white opacity-80"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3.6 9h16.8M3.6 15h16.8M12 3a16.5 16.5 0 000 18M12 3a16.5 16.5 0 010 18"/></svg></a>
            <a href="#" class="text-white opacity-80"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></a>
        </div>
    </div>

    <script>
        let currentTab = 'search';

        function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
        function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
        function openFilter() { document.getElementById('filter-modal').classList.remove('hidden'); }
        function closeFilter() { document.getElementById('filter-modal').classList.add('hidden'); }

        // Moteur de navigation (Onglets)
        function switchTab(tab) {
            currentTab = tab;
            const tabSearch = document.getElementById('tab-search');
            const tabMy = document.getElementById('tab-my');
            
            const searchList = document.getElementById('challenges-list');
            const myList = document.getElementById('my-challenges-page');
            const filterBtn = document.getElementById('filter-btn');

            if (tab === 'search') {
                tabSearch.style.backgroundColor = '#d9d9d9';
                tabSearch.style.zIndex = '20';
                tabMy.style.backgroundColor = '#e5e5e5';
                tabMy.style.zIndex = '0';
                
                searchList.classList.remove('hidden');
                myList.classList.add('hidden');
                filterBtn.style.display = 'flex'; // Affiche le filtre
                
                applyFilters();
            } else {
                tabMy.style.backgroundColor = '#d9d9d9';
                tabMy.style.zIndex = '20';
                tabSearch.style.backgroundColor = '#e5e5e5';
                tabSearch.style.zIndex = '0';
                
                searchList.classList.add('hidden');
                myList.classList.remove('hidden');
                filterBtn.style.display = 'none'; // Cache le filtre
            }
        }

        // Moteur de tri (Filtres)
        function applyFilters() {
            if (currentTab !== 'search') return;

            const diff = document.getElementById('filter-difficulty').value;
            const domain = document.getElementById('filter-domain').value;
            const cat = document.getElementById('filter-category').value;

            const cards = document.querySelectorAll('.challenge-card');
            const sections = document.querySelectorAll('.category-block');

            cards.forEach(card => {
                const cardDiff = card.getAttribute('data-difficulty');
                const cardDomain = card.getAttribute('data-domain');
                const cardCat = card.getAttribute('data-category');

                const matchDiff = (diff === 'all' || cardDiff === diff);
                const matchDomain = (domain === 'all' || cardDomain === domain);
                const matchCat = (cat === 'all' || cardCat === cat);

                if (matchDiff && matchDomain && matchCat) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });

            sections.forEach(sec => {
                const visibleCardsInSec = sec.querySelectorAll('.challenge-card[style="display: flex;"]');
                sec.style.display = (visibleCardsInSec.length > 0) ? 'block' : 'none';
            });

            closeFilter();
        }
    </script>
</body>
</html>