<?php
session_start();
require_once 'db_connect.php';

// --- Logique PHP (Connexion & Données) ---
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
} elseif (file_exists('functions.php')) {
    require_once 'functions.php';
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 4; // Fallback test
}

// Stats User
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

// Récupération Défis
$sql = "SELECT * FROM challenges ORDER BY domaine, titre_$lang";
$stmt = $pdo->query($sql);
$allChallenges = $stmt->fetchAll();

// Groupement par catégories (Simulation basée sur mots-clés pour matcher le design)
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
                        'header-grey': '#e5e5e5', /* Le gris clair des onglets */
                        'group-bg': '#f3f4f6',    /* Le gris clair du conteneur de catégorie */
                        'card-grey': '#c4c4c4',   /* Le gris foncé des cartes */
                        'tab-inactive': '#d1d5db',
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

        /* Forme spéciale pour l'onglet "Rechercher" */
        .clip-tab-search {
            clip-path: polygon(0 0, 85% 0, 100% 100%, 0% 100%);
        }
        /* Forme spéciale pour "Mes défis" */
        .clip-tab-bg {
            clip-path: polygon(15% 0, 100% 0, 100% 100%, 0% 100%);
        }
    </style>
</head>
<body class="bg-app-bg pb-24 text-black min-w-[320px]">

    <div class="sticky top-0 z-50 bg-white pt-3 pb-2 px-3 flex items-center justify-between shadow-sm">
        <div class="flex items-center space-x-2 bg-gray-200 rounded-full pr-3 pl-0 py-0">
            <div class="w-8 h-8 rounded-full bg-gray-500 text-white flex items-center justify-center font-bold text-sm border-2 border-white shrink-0">
                <?= $userLevel ?>
            </div>
            <span class="text-xs font-bold text-gray-700 whitespace-nowrap"><?= $userXp ?>/<?= $nextLevelXp ?></span>
        </div>

        <div class="flex items-center space-x-1 bg-gray-200 rounded-full px-3 py-1 ml-1">
            <div class="w-4 h-4 rounded-full border border-black flex items-center justify-center text-[10px] font-bold shrink-0">
                $
            </div>
            <span class="text-xs font-bold text-gray-700"><?= $userMoney ?></span>
        </div>

        <div class="flex space-x-2 ml-auto">
            <button class="p-1.5 bg-gray-200 rounded text-black hover:bg-gray-300 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <button class="p-1.5 bg-gray-200 rounded text-black hover:bg-gray-300 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
            </button>
        </div>
    </div>

    <div class="relative w-full h-24 bg-white mt-2">
        <div class="absolute right-0 top-0 w-[60%] h-full bg-tab-inactive clip-tab-bg flex items-center justify-end pr-6 pb-4">
            <h2 class="text-lg font-bold text-black opacity-60 whitespace-nowrap">Mes défis</h2>
        </div>
        
        <div class="absolute left-0 top-0 w-[65%] h-full bg-header-grey clip-tab-search flex flex-col justify-center pl-6 pb-4 z-10 shadow-md">
            <h2 class="text-xl font-bold text-black leading-tight">Rechercher<br>des défis</h2>
        </div>
    </div>

    <div class="bg-gray-300 py-1.5 px-6 mb-6 w-[45%] rounded-r-full mt-4">
        <span class="text-xs font-bold text-black uppercase tracking-wide">Filtre</span>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="mx-4 mb-4 p-2 bg-green-100 border border-green-400 text-green-700 rounded text-xs text-center">
            <?= $_SESSION['flash_message']; ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="px-3 space-y-8">
        
        <?php foreach ($groupedChallenges as $categoryName => $challengesInCat): ?>
            
            <div class="bg-group-bg rounded-[30px] p-4 pb-6 shadow-sm">
                
                <h2 class="text-center text-lg font-bold mb-5 text-black"><?= $categoryName ?></h2>

                <?php foreach($challengesInCat as $defi): ?>
                    <?php 
                        // Logique de statut
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

                    <div class="bg-card-grey rounded-[25px] p-3 flex relative h-28 mb-4 shadow-sm w-full mx-auto">
                        
                        <div class="w-20 h-full bg-gray-300 rounded-[20px] flex items-center justify-center shrink-0 relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <div class="absolute top-[25%] left-[25%] text-gray-800 text-[10px] font-bold">+</div> 
                        </div>

                        <div class="ml-3 flex flex-col justify-center flex-1 pr-6 relative">
                            <div class="absolute -top-1 -right-2 flex space-x-0.5">
                                <?php for($i=0; $i<$leafCount; $i++): ?>
                                    <svg class="w-3 h-3 text-black fill-transparent stroke-black stroke-2" viewBox="0 0 24 24">
                                        <path d="M20.2 17.6c-2.4-7.2-9.6-9.6-9.6-9.6s-2.4 7.2 9.6 9.6z" />
                                        <path d="M2.8 17.6c2.4-7.2 9.6-9.6 9.6-9.6s2.4 7.2-9.6 9.6z" />
                                        <line x1="12" y1="21" x2="12" y2="8" />
                                    </svg>
                                <?php endfor; ?>
                            </div>

                            <h3 class="font-semibold text-black text-sm leading-tight mb-0.5 w-[90%] truncate">
                                <?= get_trad_bdd($defi, 'titre', $lang) ?>
                            </h3>
                            
                            <p class="text-[10px] text-black mb-3 opacity-80">Date</p>

                            <div class="flex items-center text-[11px] font-bold text-black space-x-3">
                                <span>50 Point</span>
                                <span><?= $defi['xp_gain'] ?> XP</span>
                            </div>
                        </div>

                        <div class="absolute bottom-0 right-0">
                            <?php if (!$disabled): ?>
                                <form action="validate_mission.php" method="POST">
                                    <input type="hidden" name="challenge_id" value="<?= $defi['id'] ?>">
                                    <button type="submit" class="w-10 h-9 bg-gray-500 rounded-tl-[15px] rounded-br-[25px] flex items-center justify-center hover:bg-gray-600 transition shadow-md border-t border-l border-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="w-10 h-9 bg-gray-400 rounded-tl-[15px] rounded-br-[25px] flex items-center justify-center opacity-50 cursor-not-allowed">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
        <a href="#" class="text-white opacity-70 hover:opacity-100 flex flex-col items-center justify-center w-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
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