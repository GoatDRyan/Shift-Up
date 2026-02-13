<?php
session_start();
require_once 'db_connect.php';

// --- 1. SÉCURITÉ : REDIRECTION SI PAS CONNECTÉ ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
} elseif (file_exists('functions.php')) {
    require_once 'functions.php';
}

// ---------------------- GESTION DE LA LANGUE -------------------
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $new = $_GET['lang'];
    $pdo->prepare("UPDATE users SET language_pref = ? WHERE id = ?")->execute([$new, $user_id]);
    $_SESSION['lang'] = $new;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: logout.php");
    exit();
}

$lang = $_SESSION['lang'] ?? ($user['language_pref'] ?? 'fr');
$t = require_once "lang/$lang.php";


// ---------------------------------- GESTION INTELLIGENTE DE LA STREAK -------------------
$last_activity = $user['last_activity'];
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

if ($last_activity < $yesterday) {
    $pdo->prepare("UPDATE users SET current_streak = 0 WHERE id = ?")->execute([$user_id]);
    $user['current_streak'] = 0;
} 


// ---------------------- CALCUL DU NIVEAU ---------------
if (function_exists('get_level_data')) {
    $levelData = get_level_data($user['points_rank']);
} else {
    $levelData = [
        'niveau_actuel' => floor($user['points_rank'] / 1000) + 1,
        'titre_actuel' => 'Shifter',
        'xp_actuel' => $user['points_rank'],
        'xp_prochain' => 10000,
        'pourcentage' => 50
    ];
}


// ----------------------- CLASSEMENT ------------------------
$filter_col = $user['department_id'] ? 'department_id' : 'company_id';
$filter_val = $user['department_id'] ? $user['department_id'] : $user['company_id'];

$sql_rank = "SELECT pseudo, points_rank, id 
             FROM users 
             WHERE $filter_col = :val 
             AND role = 'shifter' 
             ORDER BY points_rank DESC 
             LIMIT 5";
$stmt_rank = $pdo->prepare($sql_rank);
$stmt_rank->execute(['val' => $filter_val]);
$classement = $stmt_rank->fetchAll();


// -------------------- GRAPHIQUE --------------------------
$sql_graph = "
    SELECT DATE(ua.date_action) as jour, SUM(c.co2_kg) as total_co2
    FROM user_actions ua
    JOIN challenges c ON ua.challenge_id = c.id
    WHERE ua.user_id = :uid 
    AND ua.date_action >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(ua.date_action)
    ORDER BY jour ASC
";
$stmt_graph = $pdo->prepare($sql_graph);
$stmt_graph->execute(['uid' => $user_id]);
$graph_data = $stmt_graph->fetchAll(PDO::FETCH_KEY_PAIR);

$labels = [];
$data_points = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('D', strtotime($date));
    $data_points[] = $graph_data[$date] ?? 0;
}

// -------------------- ETAT QUIZ --------------------------
$default_footprint = 32.60;
$user_footprint = (float) $user['initial_footprint_kg'];
$has_done_quiz = ($user_footprint != $default_footprint);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift'Up</title>
    <?php include 'tailwindcss.html'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .skew-container { transform: skewX(-20deg); overflow: hidden; border-radius: 12px; }
        .skew-child { transform: skewX(20deg); }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-black text-white font-sans overflow-x-hidden pb-24">

    <header class="fixed top-0 w-full bg-black/90 backdrop-blur-md z-40 px-4 py-3 flex justify-between items-center border-b border-gray-800">
        <div class="flex items-center gap-3">
            <div class="relative w-12 h-12 flex items-center justify-center bg-white text-black rounded-full border-2 border-white shadow-[0_0_10px_rgba(255,255,255,0.3)]">
                <span class="font-black text-lg"><?= $levelData['niveau_actuel'] ?></span>
                <div class="absolute -bottom-2 bg-black text-white text-[9px] font-bold px-1.5 py-0.5 rounded border border-gray-700">
                    <?= strtoupper($levelData['titre_actuel']) ?>
                </div>
            </div>
            
            <div class="flex flex-col ml-1">
                <span class="text-xs text-gray-400 font-medium uppercase tracking-tighter"><?= $t['pts'] ?></span>
                <span class="font-bold text-xl leading-none tracking-tight">
                    <?= number_format($user['points_wallet'], 0, ',', ' ') ?>
                </span>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <button class="relative text-gray-300 hover:text-white transition">
                <i class="fa-regular fa-newspaper text-xl"></i>
            </button>
            <button onclick="toggleMenu()" class="text-gray-300 hover:text-white transition">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
        </div>
    </header>

    <div id="settings-menu" class="fixed inset-0 bg-black/95 z-50 hidden flex flex-col justify-center items-center opacity-0 transition-opacity duration-300">
        <button onclick="toggleMenu()" class="absolute top-6 right-6 text-white text-2xl">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <h2 class="text-2xl font-bold mb-6"><?= $t['settings'] ?></h2>
        <p class="text-gray-400 text-sm mb-3"><?= $t['choose_lang'] ?></p>
        <div class="flex gap-4 mb-8">
            <a href="?lang=fr" class="lang-btn border border-gray-600 px-6 py-2 rounded-full text-sm font-bold transition <?= $lang == 'fr' ? 'active' : 'text-gray-400' ?>">Français 🇫🇷</a>
            <a href="?lang=en" class="lang-btn border border-gray-600 px-6 py-2 rounded-full text-sm font-bold transition <?= $lang == 'en' ? 'active' : 'text-gray-400' ?>">English 🇬🇧</a>
        </div>
        <nav class="flex flex-col gap-6 text-center text-xl w-full px-10">
            <a href="#" class="hover:text-gray-400 border-b border-gray-800 pb-4"><?= $t['account'] ?></a>
            <a href="#" class="hover:text-gray-400 border-b border-gray-800 pb-4"><?= $t['privacy'] ?></a>
            <a href="logout.php" class="text-red-500 mt-4 font-bold"><?= $t['logout'] ?></a>
        </nav>
    </div>

    <main class="mt-20 px-4">

        <div class="flex justify-center mb-6 mt-4 px-6">
            <div class="skew-container flex w-full max-w-sm bg-gray-900 border border-gray-700 shadow-lg shadow-white/5 h-14 relative overflow-hidden">
                <button id="btn-dept" onclick="switchView('dept')" class="group relative h-full transition-all duration-500 ease-out flex-[1.5] bg-white text-black">
                    <div class="absolute inset-0 bg-gradient-to-tr from-gray-200 to-white opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="skew-child flex items-center justify-center gap-2 h-full w-full">
                        <span class="font-bold text-sm uppercase tracking-wider"><?= $t['view_dept'] ?></span>
                    </div>
                </button>
                <button id="btn-solo" onclick="switchView('solo')" class="group relative h-full transition-all duration-500 ease-out flex-[1] bg-gray-900 text-gray-500 hover:text-gray-300 hover:bg-gray-800">
                    <div class="skew-child flex items-center justify-center gap-2 h-full w-full">
                        <span class="font-bold text-xs uppercase tracking-wider"><?= $t['view_solo'] ?></span>
                    </div>
                </button>
            </div>
        </div>

        <div id="view-dept" class="fade-in">
            <div class="bg-gradient-to-r from-gray-900 to-gray-800 border border-gray-700 rounded-2xl p-4 mb-6 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="bg-gray-700 p-2 rounded-full text-white"><i class="fa-solid fa-fire text-xl"></i></div>
                    <div>
                        <h3 class="font-bold text-sm text-gray-300"><?= $t['streak_title'] ?></h3>
                        <p class="text-xl font-bold text-white"><?= $user['current_streak'] ?> <?= $t['days'] ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-6">
                <a href="defis.php" class="col-span-2 bg-white text-black rounded-2xl p-5 flex items-center justify-between hover:bg-gray-200 transition shadow-lg shadow-white/10">
                    <div class="text-left">
                        <span class="text-xs font-bold uppercase tracking-wider border-b border-black pb-1"><?= $t['todo_today'] ?></span>
                        <h3 class="font-bold text-lg mt-2"><?= $t['btn_todo'] ?? 'Voir les défis' ?></h3>
                    </div>
                    <i class="fa-solid fa-person-biking text-3xl"></i>
                </a>
                <a href="article.php" class="bg-gray-800 border border-gray-700 rounded-2xl p-4 flex flex-col items-center justify-center gap-2 hover:border-white transition">
                    <i class="fa-solid fa-book-open text-2xl text-gray-300"></i>
                    <span class="text-sm font-medium"><?= $t['btn_article'] ?></span>
                </a>
                <a href="quiz_solo.php" class="bg-gray-800 border border-gray-700 rounded-2xl p-4 flex flex-col items-center justify-center gap-2 hover:border-white transition">
                    <i class="fa-solid fa-clipboard-question text-2xl text-gray-300"></i>
                    <span class="text-sm font-medium"><?= $t['btn_question'] ?></span>
                </a>
            </div>

            <div class="mb-24">
                <h3 class="font-bold text-lg border-l-4 border-white pl-3 mb-3"><?= $t['ranking_title'] ?></h3>
                <div class="bg-gray-900 rounded-2xl p-4 border border-gray-800 space-y-3">
                    <?php if(empty($classement)): ?>
                        <p class="text-gray-500 text-center text-sm">Aucun classement disponible.</p>
                    <?php else: ?>
                        <?php foreach($classement as $index => $joueur): ?>
                            <?php 
                                $rank = $index + 1;
                                $is_me = ($joueur['id'] == $user_id);
                                $color = $rank == 1 ? 'text-yellow-500' : ($rank == 2 ? 'text-gray-400' : ($rank == 3 ? 'text-orange-700' : 'text-gray-600'));
                                $bg_class = $is_me ? 'bg-gray-800 border border-white/20' : '';
                            ?>
                            <div class="flex items-center justify-between <?= $bg_class ?> p-2 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="<?= $color ?> font-bold w-4"><?= $rank ?></span>
                                    <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center text-xs">
                                        <?= strtoupper(substr($joueur['pseudo'], 0, 1)) ?>
                                    </div>
                                    <span class="font-medium <?= $is_me ? 'text-white' : 'text-gray-300' ?>">
                                        <?= htmlspecialchars($joueur['pseudo']) ?> <?= $is_me ? '(' . ($t['who_rank'] ?? 'Moi') . ')' : '' ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="view-solo" class="hidden fade-in">
            <?php if (!$has_done_quiz): ?>
                <div class="flex flex-col items-center justify-center text-center py-10 bg-gray-900 rounded-2xl border border-dashed border-gray-600 p-6 mb-6">
                    <div class="w-20 h-20 bg-gray-800 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-leaf text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2"><?= $t['impact_title'] ?></h3>
                    <p class="text-gray-400 text-sm mb-6"><?= $t['impact_desc'] ?></p>
                    <a href="questionnaire_impact.php" class="bg-white text-black px-6 py-3 rounded-full font-bold hover:bg-gray-200 transition shadow-lg shadow-white/20">
                        <?= $t['btn_quiz'] ?> <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
                </div>
            <?php else: ?>
                
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 mb-4">
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Niveau Suivant</span>
                        <span class="text-xs font-mono text-gray-500"><?= number_format($levelData['xp_actuel']) ?> / <?= number_format($levelData['xp_prochain']) ?> XP</span>
                    </div>
                    <div class="w-full bg-gray-800 h-2 rounded-full overflow-hidden">
                        <div class="bg-white h-full transition-all duration-1000 ease-out" style="width: <?= $levelData['pourcentage'] ?>%"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4">
                        <p class="text-xs text-gray-400 mb-1"><?= $t['initial_footprint'] ?></p>
                        <p class="text-2xl font-bold text-white"><?= $user['initial_footprint_kg'] ?> <span class="text-xs font-normal">t/an</span></p>
                    </div>
                    <div class="bg-white text-black rounded-2xl p-4">
                        <p class="text-xs font-bold mb-1 opacity-70"><?= $t['saved'] ?></p>
                        <p class="text-2xl font-bold">- <?= round($user['total_carbon_saved'], 1) ?> <span class="text-xs font-normal">kg</span></p>
                    </div>
                </div>

                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 mb-6">
                    <h3 class="text-sm font-bold mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-chart-line"></i> <?= $t['chart_title'] ?>
                    </h3>
                    <div class="h-48 w-full">
                        <canvas id="carbonChart"></canvas>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <nav class="fixed bottom-0 w-full bg-black border-t border-gray-800 pb-safe pt-2 px-6 flex justify-between items-center z-40 pb-4 h-20">
        <a href="#" class="flex flex-col items-center gap-1 text-gray-500 hover:text-white transition w-12"><i class="fa-solid fa-store text-xl"></i><span class="text-[10px]"><?= $t['nav_shop'] ?></span></a>
        <a href="defis.php" class="flex flex-col items-center gap-1 text-gray-500 hover:text-white transition w-12"><i class="fa-solid fa-trophy text-xl"></i><span class="text-[10px]"><?= $t['nav_defs'] ?></span></a>
        <a href="#" class="relative -top-5 flex flex-col items-center justify-center w-14 h-14 bg-white text-black rounded-full shadow-lg shadow-white/20 border-4 border-black"><i class="fa-solid fa-house text-xl"></i></a>
        <a href="#" class="flex flex-col items-center gap-1 text-gray-500 hover:text-white transition w-12"><i class="fa-solid fa-users text-xl"></i><span class="text-[10px]"><?= $t['nav_social'] ?></span></a>
        <a href="#" class="flex flex-col items-center gap-1 text-gray-500 hover:text-white transition w-12"><i class="fa-solid fa-user text-xl"></i><span class="text-[10px]"><?= $t['nav_prof'] ?></span></a>
    </nav>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('settings-menu');
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                setTimeout(() => menu.classList.remove('opacity-0'), 10);
            } else {
                menu.classList.add('opacity-0');
                setTimeout(() => menu.classList.add('hidden'), 300);
            }
        }
        function switchView(viewName) {
            const btnDept = document.getElementById('btn-dept');
            const btnSolo = document.getElementById('btn-solo');
            const viewDept = document.getElementById('view-dept');
            const viewSolo = document.getElementById('view-solo');
            const activeClasses = ['flex-[1.5]', 'bg-white', 'text-black'];
            const inactiveClasses = ['flex-[1]', 'bg-gray-900', 'text-gray-500', 'hover:text-gray-300', 'hover:bg-gray-800'];

            if (viewName === 'dept') {
                btnDept.classList.remove(...inactiveClasses);
                btnSolo.classList.remove(...activeClasses);
                btnDept.classList.add(...activeClasses);
                btnSolo.classList.add(...inactiveClasses);
                btnDept.querySelector('span').classList.replace('text-xs', 'text-sm');
                btnSolo.querySelector('span').classList.replace('text-sm', 'text-xs');
                viewDept.classList.remove('hidden');
                viewSolo.classList.add('hidden');
            } else {
                btnSolo.classList.remove(...inactiveClasses);
                btnDept.classList.remove(...activeClasses);
                btnSolo.classList.add(...activeClasses);
                btnDept.classList.add(...inactiveClasses);
                btnSolo.querySelector('span').classList.replace('text-xs', 'text-sm');
                btnDept.querySelector('span').classList.replace('text-sm', 'text-xs');
                viewDept.classList.add('hidden');
                viewSolo.classList.remove('hidden');
            }
        }
        
        <?php if ($has_done_quiz): ?>
        const ctx = document.getElementById('carbonChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'CO2',
                    data: <?= json_encode($data_points) ?>,
                    borderColor: '#ffffff',
                    backgroundColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false, color: '#333' }, ticks: { color: '#888' } },
                    y: { grid: { color: '#333' }, ticks: { color: '#888' } }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>