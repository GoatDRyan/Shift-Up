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

// Récupération des infos utilisateur
$stmtUser = $pdo->prepare("SELECT points_rank, points_wallet FROM users WHERE id = :uid");
$stmtUser->execute(['uid' => $_SESSION['user_id']]);
$currentUser = $stmtUser->fetch();

$userXp = $currentUser['points_rank'] ?? 0;
$userMoney = $currentUser['points_wallet'] ?? 0;

// Calcul dynamique du niveau en fonction de l'XP (ex: 2500 XP par niveau)
$userLevel = floor($userXp / 2500) + 1;
$nextLevelXp = $userLevel * 2500;

$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'fr';
if (!in_array($lang, ['fr', 'en'])) $lang = 'fr';
$textes = require_once "lang/$lang.php";

$sql = "SELECT * FROM challenges ORDER BY domaine, categorie, titre_$lang";
$stmt = $pdo->query($sql);
$allChallenges = $stmt->fetchAll();

// Groupement des défis dynamiquement depuis la base de données (colonne 'categorie')
$groupedChallenges = [];
foreach ($allChallenges as $c) {
    // On utilise la catégorie de la BDD. Si vide, on la classe dans "Autre"
    $catName = !empty($c['categorie']) ? $c['categorie'] : 'Autre';
    if (!isset($groupedChallenges[$catName])) {
        $groupedChallenges[$catName] = [];
    }
    $groupedChallenges[$catName][] = $c;
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
                 url('Bahnschrift.woff') format('woff');
            font-weight: normal;
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

        body { font-family: 'Bahnschrift', sans-serif; background-color: #f3f4f6; }

        .clip-tab-search { clip-path: polygon(0 0, 85% 0, 100% 100%, 0% 100%); }
        .clip-tab-mesdefis { clip-path: polygon(15% 0, 100% 0, 100% 100%, 0% 100%); }
        .clip-parallelogram { clip-path: polygon(10% 0, 100% 0, 90% 100%, 0% 100%); }
        .clip-filter { clip-path: polygon(0 0, 100% 0, 85% 100%, 0% 100%); }
        
        /* Correction du responsive pour tous les formats */
        .app-container { 
            width: 100%; 
            max-width: 500px; 
            margin: 0 auto; 
            min-height: 100vh; 
            position: relative; 
            background: #fff; 
            box-shadow: 0 0 15px rgba(0,0,0,0.1); 
            overflow-x: hidden; 
        }
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
                <div class="p-2 bg-gray-300 rounded cursor-pointer hover:bg-gray-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div class="p-2 bg-gray-300 rounded cursor-pointer hover:bg-gray-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </div>
            </div>
        </div>

        <div class="relative w-full h-16 mt-2 flex">
            <div class="absolute right-0 top-0 w-[55%] h-full bg-tab-inactive clip-tab-mesdefis flex items-center justify-center cursor-pointer hover:opacity-90 transition">
                <span class="text-sm font-stacked text-black opacity-40 ml-4">MES DÉFIS</span>
            </div>
            <div class="absolute left-0 top-0 w-[55%] h-full bg-header-grey clip-tab-search flex flex-col justify-center items-center z-10 shadow-md cursor-pointer hover:bg-gray-300 transition">
                <h2 class="text-sm font-stacked text-black leading-none uppercase text-center pr-4">Rechercher<br>des défis</h2>
            </div>
        </div>

        <button onclick="toggleFilter()" class="bg-tab-inactive py-1.5 w-32 clip-filter mt-4 mb-6 flex items-center justify-center cursor-pointer hover:bg-gray-400 transition border-none text-black">
            <span class="text-[10px] font-bold uppercase text-center pr-2">Filtre</span>
        </button>

        <div class="px-3 space-y-8">
            <?php foreach ($groupedChallenges as $categoryName => $challengesInCat): ?>
                <div class="bg-group-bg rounded-[30px] p-4 pb-6 shadow-sm">
                    <h2 class="text-center text-lg font-stacked mb-5 text-black uppercase tracking-wider"><?= htmlspecialchars($categoryName) ?> :</h2>
                    
                    <?php foreach($challengesInCat as $defi): ?>
                        <?php 
                            $sql_today = "SELECT COUNT(*) FROM user_actions WHERE user_id = :uid AND challenge_id = :cid AND DATE(date_action) = CURDATE()";
                            $stmt_td = $pdo->prepare($sql_today);
                            $stmt_td->execute(['uid' => $_SESSION['user_id'], 'cid' => $defi['id']]);
                            $today_count = $stmt_td->fetchColumn();
                            $disabled = ($today_count >= $defi['max_actions_day']);
                            
                            // Correction Bug Difficulté
                            $diff = strtolower($defi['difficulty'] ?? 'facile');
                            $leafCount = ($diff == 'difficile') ? 3 : (($diff == 'moyen') ? 2 : 1);
                        ?>
                        <div class="bg-card-bg rounded-[25px] p-2 flex relative h-24 mb-4 shadow-md items-center cursor-pointer transition transform hover:scale-[1.02]" onclick="openModal('modal-<?= $defi['id'] ?>')">
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
                                    <span> PT</span>
                                    <span><?= htmlspecialchars($defi['xp_gain']) ?> XP</span>
                                </div>
                            </div>

                            <div class="absolute bottom-0 right-0 z-10" onclick="event.stopPropagation();">
                                <form action="validate_mission.php" method="POST">
                                    <input type="hidden" name="challenge_id" value="<?= $defi['id'] ?>">
                                    <button type="submit" <?= $disabled ? 'disabled' : '' ?> class="w-11 h-10 bg-gray-500 rounded-tl-[15px] rounded-br-[25px] flex items-center justify-center <?= $disabled ? 'opacity-20 cursor-not-allowed' : 'hover:bg-gray-600' ?> shadow-inner transition">
                                        <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3.5"><path d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                       <div id="modal-<?= $defi['id'] ?>" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
                            <div class="bg-card-bg w-full max-w-[320px] rounded-[35px] p-2 relative shadow-2xl">
                                <button onclick="closeModal('modal-<?= $defi['id'] ?>')" class="absolute top-4 right-5 text-black font-bold text-2xl z-20 hover:text-gray-600 transition">&times;</button>
                                
                                <div class="bg-inner-card rounded-[30px] p-5 pt-8 flex flex-col items-center">
                                    
                                    <div class="w-16 h-16 bg-white rounded-full mb-3 flex items-center justify-center shadow-inner overflow-hidden border-2 border-gray-300">
                                        <img src="https://cdn-icons-png.flaticon.com/512/751/751463.png" alt="icon" class="w-10 h-10 object-contain">
                                    </div>

                                    <h2 class="font-stacked text-lg text-center uppercase leading-tight mb-4 text-black"><?= get_trad_bdd($defi, 'titre', $lang) ?></h2>

                                    <div class="flex flex-col items-start w-full px-2 mb-4 space-y-1">
                                        <p class="text-[11px] font-bahnschrift text-gray-800">
                                            <span class="font-bold">Difficulté :</span> <?= ucfirst($defi['difficulty'] ?? 'Moyen') ?>
                                        </p>
                                        <p class="text-[11px] font-bahnschrift text-gray-800">
                                            <span class="font-bold">Date :</span> Aujourd'hui
                                        </p>
                                        <p class="text-[11px] font-bahnschrift text-gray-800">
                                            <span class="font-bold">Type :</span> <?= htmlspecialchars($categoryName) ?>
                                        </p>
                                    </div>
                                    <div class="text-[11px] font-bahnschrift text-gray-800 mb-5 px-2 text-left w-full bg-white/40 p-3 rounded-xl shadow-sm">
                                        <p class="font-bold mb-1 underline decoration-gray-400">Description de la tâche :</p>
                                        <p class="mb-2"><?= $defi['descr_'.$lang] ?? 'Pas de description disponible.' ?></p>
                                    </div>

                                    <div class="flex items-center justify-between w-full px-4 mb-5 text-[12px] font-bold text-black bg-gray-300 py-2 rounded-lg">
                                        <div class="flex items-center gap-1">
                                            <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <span><?= $defi['duration_days'] ?? '1' ?> jours</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span><?= isset($defi['co2_kg']) ? ($defi['co2_kg'] * 1000) : '0' ?> g de CO2</span>
                                        </div>
                                    </div>

                                    <form action="validate_mission.php" method="POST" class="w-full">
                                        <input type="hidden" name="challenge_id" value="<?= $defi['id'] ?>">
                                        <button type="submit" class="w-full bg-group-bg text-black font-stacked py-3 rounded-[20px] text-[13px] uppercase shadow-md transition hover:bg-gray-400">Valider la tâche</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

      <?php if (isset($_SESSION['flash_message'])): ?>
    <div id="success-modal" class="fixed inset-0 z-[150] flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
        <div class="bg-card-bg w-full max-w-[320px] rounded-[35px] p-2 relative shadow-2xl">
            
            <button onclick="closeModal('success-modal')" class="absolute top-4 right-5 text-black font-bold text-2xl z-20 hover:text-gray-600 transition">&times;</button>

            <div class="bg-inner-card rounded-[30px] p-6 pt-10 flex flex-col items-center text-center">
                
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center border-[4px] border-green-500 mb-5 shadow-sm overflow-hidden">
                    <img src="https://cdn-icons-png.flaticon.com/512/190/190411.png" alt="success icon" class="w-12 h-12 object-contain">
                </div>

                <h2 class="font-stacked text-2xl uppercase mb-3 text-green-700">Merci !</h2>
                
                <p class="font-bahnschrift text-[13px] text-gray-800 mb-4 font-bold">Votre tâche a bien été validée.</p>
                
                <p class="font-bahnschrift text-[11px] text-gray-500 mb-6 uppercase border-t border-gray-300 pt-3 w-full">
                    <?= htmlspecialchars($_SESSION['flash_message']) ?>
                </p>
                
                <button onclick="closeModal('success-modal')" class="bg-group-bg text-black font-stacked w-full py-3 rounded-[20px] text-xs uppercase shadow-md transition hover:bg-gray-400">Continuer</button>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['flash_message']); ?>

<?php endif; ?>
        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[500px] bg-dark-nav h-16 flex items-center justify-around z-40">
            <a href="#" class="text-white opacity-50 hover:opacity-100 transition"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg></a>
            <a href="#" class="text-white opacity-50 hover:opacity-100 transition"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21h6M12 17v4M7 4v7c0 2.8 2.2 5 5 5s5-2.2 5-5V4H7z"/><path d="M7 6H5c-1.1 0-2 .9-2 2s.9 2 2 2h2M17 6h2c1.1 0 2 .9 2 2s-.9 2-2 2h-2"/></svg></a>
            <a href="defis.php" class="text-white transform scale-110"><svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg></a>
            <a href="#" class="text-white opacity-50 hover:opacity-100 transition"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3.6 9h16.8M3.6 15h16.8M12 3a16.5 16.5 0 000 18M12 3a16.5 16.5 0 010 18"/></svg></a>
            <a href="#" class="text-white opacity-50 hover:opacity-100 transition"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></a>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }
        function toggleFilter() {
           console.log('Filtre cliqué');
        }
    </script>
</body>
</html>