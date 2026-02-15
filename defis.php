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

$stmtUser = $pdo->prepare("SELECT points_rank, points_wallet FROM users WHERE id = :uid");
$stmtUser->execute(['uid' => $_SESSION['user_id']]);
$currentUser = $stmtUser->fetch();
$userLevel = 1; 
$userXp = $currentUser['points_rank'] ?? 0;
$userMoney = $currentUser['points_wallet'] ?? 0;
$nextLevelXp = 2500; 
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'fr';
if (!in_array($lang, ['fr', 'en'])) $lang = 'fr';
$textes = require_once "lang/$lang.php";

$sql = "SELECT * FROM challenges ORDER BY domaine, titre_$lang";
$stmt = $pdo->query($sql);
$allChallenges = $stmt->fetchAll();

$groupedChallenges = [
    'Mobilité' => [],
    'Recyclage' => [],
    'Social' => [],
    'Autre' => []
];

foreach ($allChallenges as $c) {
    $titre = strtolower($c['titre_' . $lang]);
    if (strpos($titre, 'vélo') !== false || strpos($titre, 'marche') !== false || strpos($titre, 'transport') !== false || strpos($titre, 'covoiturage') !== false) {
        $groupedChallenges['Mobilité'][] = $c;
    } elseif (strpos($titre, 'déchet') !== false || strpos($titre, 'plastique') !== false || strpos($titre, 'bouteille') !== false || strpos($titre, 'tri') !== false || strpos($titre, 'zéro') !== false) {
        $groupedChallenges['Recyclage'][] = $c;
    } elseif (strpos($titre, 'réunion') !== false || strpos($titre, 'collègue') !== false || strpos($titre, 'social') !== false || strpos($titre, 'partage') !== false) {
        $groupedChallenges['Social'][] = $c;
    } else {
        $groupedChallenges['Autre'][] = $c; 
    }
}
$groupedChallenges = array_filter($groupedChallenges, function($a) { return !empty($a); });
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Les Défis Shift'Up</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'app-bg': '#ffffff',
                        'header-grey': '#cfcfcf',
                        'card-grey': '#c4c4c4',
                        'tab-inactive': '#dcdcdc',
                        'dark-nav': '#1e1e1e',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    screens: {
                        'xs': '320px', 
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        
        body { font-family: 'Inter', sans-serif; }

        .clip-tab-search {
            clip-path: polygon(0 0, 85% 0, 100% 100%, 0% 100%);
        }
        .clip-tab-bg {
            clip-path: polygon(0 0, 100% 0, 100% 100%, 15% 100%);
        }
        
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="bg-app-bg pb-24 text-black">

    <div class="sticky top-0 z-50 bg-white pt-2 pb-2 px-4 flex items-center justify-between shadow-sm">
        <div class="flex items-center space-x-2 bg-gray-300 rounded-full pr-3">
            <div class="w-8 h-8 rounded-full bg-gray-500 text-white flex items-center justify-center font-bold text-sm border-2 border-white">
                <?= $userLevel ?>
            </div>
            <span class="text-xs font-semibold text-gray-700"><?= $userXp ?>/<?= $nextLevelXp ?></span>
        </div>

        <div class="flex items-center space-x-2 bg-gray-300 rounded-full px-3 py-1">
            <div class="w-5 h-5 rounded-full border border-black flex items-center justify-center text-[10px] font-bold">
                $
            </div>
            <span class="text-xs font-semibold text-gray-700"><?= $userMoney ?></span>
        </div>

        <div class="flex space-x-3">
            <button class="p-2 bg-gray-300 rounded text-black hover:bg-gray-400 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </button>
            <button class="p-2 bg-gray-300 rounded text-black hover:bg-gray-400 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    <div class="relative w-full h-32 bg-white">
        <div class="absolute right-0 top-0 w-2/3 h-full bg-tab-inactive clip-tab-bg flex items-center justify-end pr-10 pt-4">
            <h2 class="text-xl font-normal text-black">Mes défis</h2>
        </div>
        
        <div class="absolute left-0 top-0 w-2/3 h-full bg-header-grey clip-tab-search flex flex-col justify-center pl-8 pt-4 z-10 shadow-lg drop-shadow-xl">
            <h2 class="text-xl font-normal text-black leading-tight">Rechercher<br>des défis</h2>
        </div>
    </div>

    <div class="bg-gray-300 py-1 px-4 mb-6 w-1/2 rounded-r-full mt-2">
        <span class="text-sm font-medium text-black">Filtre</span>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="mx-4 mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded text-sm text-center">
            <?= $_SESSION['flash_message']; ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="px-4 space-y-6">
        
        <?php foreach ($groupedChallenges as $categoryName => $challengesInCat): ?>
            
            <div class="text-lg font-normal mb-2 pl-2"><?= $categoryName ?> :</div>

            <?php foreach($challengesInCat as $defi): ?>
                <?php 
                    $sql_today = "SELECT COUNT(*) FROM user_actions WHERE user_id = :uid AND challenge_id = :cid AND DATE(date_action) = CURDATE()";
                    $stmt_td = $pdo->prepare($sql_today);
                    $stmt_td->execute(['uid' => $_SESSION['user_id'], 'cid' => $defi['id']]);
                    $today_count = $stmt_td->fetchColumn();

                    $progression_totale = 0;
                    if ($defi['duration_days'] > 1) {
                        $sql_total = "SELECT COUNT(*) FROM user_actions WHERE user_id = :uid AND challenge_id = :cid";
                        $stmt_tot = $pdo->prepare($sql_total);
                        $stmt_tot->execute(['uid' => $_SESSION['user_id'], 'cid' => $defi['id']]);
                        $progression_totale = $stmt_tot->fetchColumn();
                    }


                    $leafCount = 1;
                    if($defi['difficulty'] == 'moyen') $leafCount = 2;
                    if($defi['difficulty'] == 'difficile') $leafCount = 3;
                    
                    $is_finished = ($defi['duration_days'] > 1 && $progression_totale >= $defi['duration_days']);
                    $is_daily_limit = ($today_count >= $defi['max_actions_day']);
                    $disabled = $is_finished || $is_daily_limit;
                ?>

                <div class="bg-card-grey rounded-[20px] p-3 flex relative h-28 mb-4 shadow-sm">
                    
                    <div class="w-24 h-full bg-gray-300 rounded-[15px] flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <div class="absolute top-[28%] left-[23%] text-gray-800 text-xs font-bold">+</div> 
                    </div>

                    <div class="ml-3 flex flex-col justify-center w-full pr-8">
                        <div class="absolute top-3 right-3 flex space-x-0.5">
                            <?php for($i=0; $i<$leafCount; $i++): ?>
                                <svg class="w-4 h-4 text-black fill-transparent stroke-black stroke-2" viewBox="0 0 24 24">
                                    <path d="M20.2 17.6c-2.4-7.2-9.6-9.6-9.6-9.6s-2.4 7.2 9.6 9.6z" />
                                    <path d="M2.8 17.6c2.4-7.2 9.6-9.6 9.6-9.6s2.4 7.2-9.6 9.6z" />
                                    <line x1="12" y1="21" x2="12" y2="8" />
                                </svg>
                            <?php endfor; ?>
                        </div>

                        <h3 class="font-normal text-black text-sm leading-tight mb-0.5 pr-6 truncate">
                            <?= get_trad_bdd($defi, 'titre', $lang) ?>
                        </h3>
                        
                        <p class="text-[10px] text-black mb-3">Date</p>

                        <div class="flex items-center text-xs font-bold text-black space-x-3">
                            <span>50 Point</span> <span><?= $defi['xp_gain'] ?> XP</span>
                        </div>
                    </div>

                    <div class="absolute bottom-0 right-0">
                        <?php if (!$disabled): ?>
                            <form action="validate_mission.php" method="POST">
                                <input type="hidden" name="challenge_id" value="<?= $defi['id'] ?>">
                                <button type="submit" class="w-12 h-10 bg-gray-500 rounded-tl-[15px] rounded-br-[20px] flex items-center justify-center hover:bg-gray-600 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="w-12 h-10 bg-gray-400 rounded-tl-[15px] rounded-br-[20px] flex items-center justify-center opacity-50 cursor-not-allowed">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
            
            <div class="border-b border-gray-400 w-full mb-4 opacity-50"></div>

        <?php endforeach; ?>

        <div class="h-10"></div>
    </div>
<div class="fixed bottom-0 left-0 w-full bg-dark-nav h-20 flex items-center justify-around px-2 z-50">
        <a href="#" class="text-white opacity-80 hover:opacity-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        </a>
        
        <a href="#" class="text-white opacity-80 hover:opacity-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 21h8m-4-4v4M5 5h14m-2 0v1a7 7 0 01-14 0V5h2zm0 0V3h10v2" />
            </svg>
        </a>

        <a href="defis.php" class="text-white opacity-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
            </svg>
        </a>

        <a href="#" class="text-white opacity-80 hover:opacity-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
            </svg>
        </a>

        <a href="#" class="text-white opacity-80 hover:opacity-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </a>
    </div>

</body>
</html>