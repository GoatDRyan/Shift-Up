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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        @font-face {
       font-family: 'Bahnschrift';
       src: url('Bahnschrift.woff2') format('woff2'),
             url('Bahnschrift.woff') format('woff');         font-weight: normal;
      font-style: normal;
       font-display: swap;
    }
    @font-face {
        font-family: 'Stacked Strong';
        src: url('Stacked-Strong.woff2') format('woff2'),
             url('Stacked-Strong.woff') format('woff');
        font-weight: normal;
         font-style: normal;
         font-display: swap;
    }

      body { font-family: 'Inter', sans-serif; }
        body { font-family: 'Bahnschrift', sans-serif; background-color: #ffffff; }

        .clip-tab-search { clip-path: polygon(0 0, 85% 0, 100% 100%, 0% 100%); }
        .clip-tab-mesdefis { clip-path: polygon(15% 0, 100% 0, 100% 100%, 0% 100%); }
        .clip-parallelogram { clip-path: polygon(10% 0, 100% 0, 90% 100%, 0% 100%); }
        .clip-filter { clip-path: polygon(0 0, 100% 0, 85% 100%, 0% 100%); }
        .app-container { width: 320px; margin: 0 auto; min-height: 100vh; position: relative; background: #fff; box-shadow: 0 0 15px rgba(0,0,0,0.1); overflow-x: hidden; }
    </style>
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
                   bahnschrift: ['Bahnschrift', 'sans-serif'],
                stacked: ['"Stacked Strong"', 'sans-serif'],
                  },
                  screens: {
                      'xs': '320px', 
                  }
              }
          }
      }
  </script>
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="app-container pb-24">
        
        <div class="sticky top-0 z-40 bg-white pt-3 pb-2 px-3 flex items-center justify-between">
            <div class="flex items-center space-x-1 bg-gray-300 rounded-full pr-3">
                <div class="w-8 h-8 rounded-full bg-gray-600 text-white flex items-center justify-center font-bold text-xs border-2 border-white shrink-0"><?= $userLevel ?></div>
                <span class="text-[10px] font-bold"><?= $userXp ?>/<?= $nextLevelXp ?></span>
            </div>

            <div class="flex items-center space-x-1 bg-gray-300 px-4 py-1 clip-parallelogram">
                <div class="w-4 h-4 rounded-full border border-black flex items-center justify-center text-[9px] font-bold">$</div>
                <span class="text-[10px] font-bold"><?= $userMoney ?></span>
            </div>

            <div class="flex space-x-2">
                <div class="p-2 bg-gray-300 rounded">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div class="p-2 bg-gray-300 rounded">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </div>
            </div>
        </div>

        <div class="relative w-full h-20 mt-2">
            <div class="absolute right-0 top-0 w-[55%] h-full bg-tab-inactive clip-tab-mesdefis flex items-center justify-center pt-2">
                <span class="text-sm font-stacked text-black opacity-40">MES DÉFIS</span>
            </div>
            <div class="absolute left-0 top-0 w-[58%] h-full bg-header-grey clip-tab-search flex flex-col justify-center pl-6 pt-2 z-10 shadow-md">
                <h2 class="text-sm font-stacked text-black leading-none uppercase">Rechercher<br>des défis</h2>
            </div>
        </div>

        <div class="bg-tab-inactive py-1.5 px-8 mb-6 w-32 clip-filter mt-4">
            <span class="text-[10px] font-bold uppercase">Filtre</span>
        </div>

        <div class="px-3 space-y-8">
            <?php foreach ($groupedChallenges as $categoryName => $challengesInCat): ?>
                <div class="bg-group-bg rounded-[30px] p-4 pb-6 shadow-sm">
                    <h2 class="text-left text-lg font-stacked mb-5 text-black pl-2 uppercase tracking-wider"><?= $categoryName ?> :</h2>
                    
                    <?php foreach($challengesInCat as $defi): ?>
                        <?php 
                            $sql_today = "SELECT COUNT(*) FROM user_actions WHERE user_id = :uid AND challenge_id = :cid AND DATE(date_action) = CURDATE()";
                            $stmt_td = $pdo->prepare($sql_today);
                            $stmt_td->execute(['uid' => $_SESSION['user_id'], 'cid' => $defi['id']]);
                            $today_count = $stmt_td->fetchColumn();
                            $disabled = ($today_count >= $defi['max_actions_day']);
                            $leafCount = ($defi['difficulty'] == 'difficile') ? 3 : (($defi['difficulty'] == 'moyen') ? 2 : 1);
                        ?>
                        <div class="bg-card-bg rounded-[25px] p-2 flex relative h-24 mb-4 shadow-md items-center cursor-pointer" onclick="openModal('modal-<?= $defi['id'] ?>')">
                            <div class="w-20 h-20 bg-gray-300 rounded-[20px] flex items-center justify-center shrink-0 ml-1">
                                <span class="text-gray-600 text-xl font-bold">+</span>
                            </div>
                            
                            <div class="ml-4 flex-1 flex flex-col justify-center bg-inner-card h-full rounded-[20px] pl-3 pr-10 relative">
                                <div class="absolute top-2 right-2 flex space-x-0.5">
                                    <?php for($i=0; $i<$leafCount; $i++): ?>
                                        <svg class="w-3.5 h-3.5 text-black fill-current" viewBox="0 0 24 24"><path d="M17,8C8,10 5,16 5,16C5,16 11,13 20,15C20,15 18,8 17,8Z"/></svg>
                                    <?php endfor; ?>
                                </div>
                                
                                <h3 class="font-bahnschrift font-bold text-[11px] leading-tight mb-0.5 uppercase truncate w-[85%]"><?= get_trad_bdd($defi, 'titre', $lang) ?></h3>
                                <p class="text-[9px] text-black mb-1.5 opacity-60 italic">Date</p>
                                <div class="flex items-center text-[10px] font-bold space-x-3">
                                    <span>50 PT</span>
                                    <span><?= $defi['xp_gain'] ?> XP</span>
                                </div>
                            </div>

                            <div class="absolute bottom-0 right-0 z-10" onclick="event.stopPropagation();">
                                <form action="validate_mission.php" method="POST">
                                    <input type="hidden" name="challenge_id" value="<?= $defi['id'] ?>">
                                    <button type="submit" <?= $disabled ? 'disabled' : '' ?> class="w-11 h-10 bg-gray-500 rounded-tl-[15px] rounded-br-[25px] flex items-center justify-center <?= $disabled ? 'opacity-20' : '' ?> shadow-inner">
                                        <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3.5"><path d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div id="modal-<?= $defi['id'] ?>" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/70 backdrop-blur-sm">
                            <div class="bg-card-bg w-[290px] rounded-[35px] p-2 relative shadow-2xl">
                                <button onclick="closeModal('modal-<?= $defi['id'] ?>')" class="absolute top-4 right-5 text-black font-bold text-2xl z-20">&times;</button>
                                
                                <div class="bg-inner-card rounded-[30px] p-5 pt-8 flex flex-col items-center">
                                    <div class="w-24 h-24 bg-gray-300 rounded-[20px] mb-4 flex items-center justify-center shadow-inner">
                                        <span class="text-4xl text-gray-500 font-bold">+</span>
                                    </div>
                                    
                                    <h2 class="font-stacked text-base text-center uppercase leading-tight mb-3"><?= get_trad_bdd($defi, 'titre', $lang) ?></h2>
                                    
                                    <div class="flex items-center justify-center space-x-4 mb-4">
                                        <div class="bg-gray-400 px-3 py-1 rounded-full text-[11px] font-bold uppercase shadow-sm">50 PT</div>
                                        <div class="bg-gray-400 px-3 py-1 rounded-full text-[11px] font-bold uppercase shadow-sm"><?= $defi['xp_gain'] ?> XP</div>
                                    </div>

                                    <div class="flex space-x-1 mb-4">
                                        <?php for($i=0; $i<$leafCount; $i++): ?>
                                            <svg class="w-5 h-5 text-black fill-current" viewBox="0 0 24 24"><path d="M17,8C8,10 5,16 5,16C5,16 11,13 20,15C20,15 18,8 17,8Z"/></svg>
                                        <?php endfor; ?>
                                    </div>

                                    <p class="text-xs text-center font-bahnschrift text-gray-800 mb-6 leading-relaxed">
                                        <?= isset($defi['description_' . $lang]) && !empty($defi['description_' . $lang]) ? $defi['description_' . $lang] : "Participez à ce défi pour gagner des points, augmenter votre niveau et réduire votre impact carbone !" ?>
                                    </p>

                                    <form action="validate_mission.php" method="POST" class="w-full">
                                        <input type="hidden" name="challenge_id" value="<?= $defi['id'] ?>">
                                        <button type="submit" <?= $disabled ? 'disabled' : '' ?> class="w-full bg-group-bg text-black font-stacked py-3 rounded-[20px] text-sm uppercase tracking-wider shadow-md <?= $disabled ? 'opacity-50' : 'hover:bg-gray-500' ?> transition">
                                            <?= $disabled ? 'Déjà fait aujourd\'hui' : 'Ajouter le défi' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (isset($_SESSION['flash_message'])): ?>
            <div id="success-modal" class="fixed inset-0 z-[150] flex items-center justify-center bg-black/70 backdrop-blur-sm">
                <div class="bg-card-bg w-[290px] rounded-[35px] p-2 relative shadow-2xl">
                    <div class="bg-inner-card rounded-[30px] p-6 flex flex-col items-center text-center">
                        <div class="w-20 h-20 bg-group-bg rounded-full flex items-center justify-center border-[5px] border-white mb-4 shadow-sm">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4"><path d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h2 class="font-stacked text-xl uppercase mb-2">Félicitations !</h2>
                        <p class="font-bahnschrift text-sm text-gray-800 mb-6 font-bold"><?= htmlspecialchars($_SESSION['flash_message']) ?></p>
                        <button onclick="closeModal('success-modal')" class="bg-group-bg text-black font-stacked px-10 py-3 rounded-[20px] text-sm uppercase tracking-wider shadow-md hover:bg-gray-500 transition">
                            Continuer
                        </button>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-[320px] bg-dark-nav h-16 flex items-center justify-around z-40">
            <a href="#" class="text-white opacity-50"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg></a>
            <a href="#" class="text-white opacity-50"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21h6M12 17v4M7 4v7c0 2.8 2.2 5 5 5s5-2.2 5-5V4H7z"/><path d="M7 6H5c-1.1 0-2 .9-2 2s.9 2 2 2h2M17 6h2c1.1 0 2 .9 2 2s-.9 2-2 2h-2"/></svg></a>
            <a href="defis.php" class="text-white"><svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg></a>
            <a href="#" class="text-white opacity-50"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3.6 9h16.8M3.6 15h16.8M12 3a16.5 16.5 0 000 18M12 3a16.5 16.5 0 010 18"/></svg></a>
            <a href="#" class="text-white opacity-50"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></a>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }
    </script>
</body>
</html>