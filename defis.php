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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Les Défis Shift'Up</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'app-bg': '#ffffff',
                        'header-grey': '#D9D9D9', 
                        'group-bg': '#8A8989',   
                        'card-grey': '#A7A7A7',
                        'inner-card': '#D9D9D9',
                        'tab-inactive': '#B0B0B0',
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
            clip-path: polygon(0 0, 75% 0, 100% 100%, 0% 100%);
        }
        .clip-tab-bg {
            clip-path: polygon(25% 0, 100% 0, 100% 100%, 0% 100%);
        }
        .clip-filter {
             clip-path: polygon(0 0, 100% 0, 85% 100%, 0% 100%);
        }
        /* Nouvelle classe pour les parallélogrammes du header */
        .clip-parallelogram {
             clip-path: polygon(0 0, 100% 0, 85% 100%, 0% 100%);
        }
        /* Ajustements pour les onglets */
        .clip-tab-left {
            clip-path: polygon(0 0, 90% 0, 100% 100%, 0% 100%);
        }
        .clip-tab-right {
            clip-path: polygon(10% 0, 100% 0, 100% 100%, 0% 100%);
        }
    </style>
</head>
<body class="bg-app-bg pb-24 text-black min-w-[320px]">

    <div class="sticky top-0 z-50 bg-white pt-3 pb-2 px-3 flex items-center justify-between shadow-sm">
        
        <div class="relative flex items-center pr-3 pl-0 py-0 min-w-[110px]">
            <div class="absolute inset-0 bg-gray-400 clip-parallelogram z-0"></div>
            
            <div class="relative z-20 w-8 h-8 rounded-full bg-gray-600 text-white flex items-center justify-center font-bold text-sm border-2 border-white shrink-0 -ml-1">
                <?= $userLevel ?>
            </div>
            
            <span class="relative z-10 text-xs font-bold text-white whitespace-nowrap ml-2"><?= $userXp ?>/<?= $nextLevelXp ?></span>
        </div>

        <div class="relative flex items-center justify-center px-4 py-1 ml-1 min-w-[80px]">
             <div class="absolute inset-0 bg-gray-400 clip-parallelogram z-0"></div>
            
            <div class="relative z-10 flex items-center space-x-1">
                <div class="w-4 h-4 rounded-full border border-black flex items-center justify-center text-[10px] font-bold shrink-0 bg-transparent">
                    $
                </div>
                <span class="text-xs font-bold text-white"><?= $userMoney ?></span>
            </div>
        </div>

        <div class="flex space-x-2 ml-auto z-20">
            <button class="p-2 bg-gray-300 rounded text-black hover:bg-gray-400 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </button>
            <button class="p-2 bg-gray-300 rounded text-black hover:bg-gray-400 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    <div class="relative w-full h-24 bg-white mt-2 flex">
        <div class="w-[55%] h-full bg-header-grey clip-tab-left flex flex-col justify-center pl-8 pb-2 z-10 shadow-md relative -mr-4">
            <h2 class="text-xl font-bold text-black leading-tight">Rechercher<br>des défis</h2>
        </div>
        
        <div class="flex-1 h-full bg-tab-inactive clip-tab-right flex items-center justify-center pr-4 pb-2 z-0 relative ml-[-10%] pl-[10%]">
            <h2 class="text-xl font-bold text-black whitespace-nowrap">Mes défis</h2>
        </div>
    </div>

    <div class="bg-tab-inactive py-2 px-8 mb-6 w-[45%] clip-filter mt-4 ml-0">
        <span class="text-sm font-bold text-black uppercase tracking-wide">Filtre</span>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="mx-4 mb-4 p-2 bg-green-100 border border-green-400 text-green-700 rounded text-xs text-center">
            <?= $_SESSION['flash_message']; ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="px-3 space-y-8">
        
        <?php foreach ($groupedChallenges as $categoryName => $challengesInCat): ?>
            
            <div class="bg-card-grey rounded-[30px] p-4 pb-6 shadow-sm">
                
                <h2 class="text-left text-xl font-bold mb-5 text-black pl-4"><?= $categoryName ?> :</h2>

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

                        $leafCount = ($defi['difficulty'] == 'difficile') ? 3 : (($defi['difficulty'] == 'moyen') ? 2 : 1);
                        $disabled = ($today_count >= $defi['max_actions_day']);
                    ?>

                    <div class="bg-inner-card rounded-[25px] p-3 flex relative h-28 mb-4 shadow-sm w-full mx-auto items-center">
                        
                        <div class="w-24 h-24 bg-gray-300 rounded-[20px] flex items-center justify-center shrink-0 relative ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <div class="absolute top-[25%] left-[25%] text-gray-800 text-xs font-bold">+</div> 
                        </div>

                        <div class="ml-4 flex-1 h-full relative flex flex-col justify-center pr-14">
                            
                            <div class="absolute top-2 right-0 flex space-x-1">
                                <?php for($i=0; $i<$leafCount; $i++): ?>
                                    <svg class="w-4 h-4 text-black fill-transparent stroke-black stroke-2" viewBox="0 0 24 24">
                                        <path d="M20.2 17.6c-2.4-7.2-9.6-9.6-9.6-9.6s-2.4 7.2 9.6 9.6z" />
                                        <path d="M2.8 17.6c2.4-7.2 9.6-9.6 9.6-9.6s2.4 7.2-9.6 9.6z" />
                                        <line x1="12" y1="21" x2="12" y2="8" />
                                    </svg>
                                <?php endfor; ?>
                            </div>

                            <h3 class="font-bold text-black text-base leading-tight mb-1 w-full truncate pr-4">
                                <?= get_trad_bdd($defi, 'titre', $lang) ?>
                            </h3>
                            
                            <p class="text-xs text-black mb-2">Date</p>

                            <div class="flex items-center text-sm font-bold text-black space-x-4 mb-1">
                                <span>50 Point</span>
                                <span><?= $defi['xp_gain'] ?> XP</span>
                            </div>
                        </div>

                        <div class="absolute bottom-0 right-0 z-10">
                            <?php if (!$disabled): ?>
                                <form action="validate_mission.php" method="POST">
                                    <input type="hidden" name="challenge_id" value="<?= $defi['id'] ?>">
                                    <button type="submit" class="w-12 h-12 bg-gray-500 rounded-tl-[20px] rounded-br-[25px] flex items-center justify-center hover:bg-gray-600 transition shadow-md border-t border-l border-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="w-12 h-12 bg-gray-400 rounded-tl-[20px] rounded-br-[25px] flex items-center justify-center opacity-50 cursor-not-allowed">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div> <?php endforeach; ?>

        <div class="h-10"></div>
    </div>

    <div class="fixed bottom-0 left-0 w-full bg-[#1e1e1e] h-20 flex items-center justify-around px-2 z-50 shadow-[0_-5px_15px_rgba(0,0,0,0.3)]">
       <a href="#" class="text-white opacity-80 hover:opacity-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        </a>
        
        <a href="#" class="text-white opacity-70 hover:opacity-100 flex flex-col items-center justify-center w-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 21h6M12 17v4" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 4v7c0 2.8 2.2 5 5 5s5-2.2 5-5V4H7z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 6H5c-1.1 0-2 .9-2 2s.9 2 2 2h2" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 6h2c1.1 0 2 .9 2 2s-.9 2-2 2h-2" />
            </svg>
        </a>

        <a href="defis.php" class="text-white flex flex-col items-center justify-center w-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
            </svg>
        </a>

        <a href="#" class="text-white opacity-70 hover:opacity-100 flex flex-col items-center justify-center w-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9" />
                <path stroke-linecap="round" d="M3.6 9h16.8M3.6 15h16.8" />
                <path stroke-linecap="round" d="M12 3a16.5 16.5 0 000 18M12 3a16.5 16.5 0 010 18" />
            </svg>
        </a>

        <a href="#" class="text-white opacity-70 hover:opacity-100 flex flex-col items-center justify-center w-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </a>
    </div>

</body>
</html>