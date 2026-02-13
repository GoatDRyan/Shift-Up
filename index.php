<?php
session_start();
require_once 'db_connect.php';

// --- 1. SÉCURITÉ ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// --- 2. FONCTIONS & LANGUE ---
if (file_exists('includes/functions.php')) { require_once 'includes/functions.php'; } 
elseif (file_exists('functions.php')) { require_once 'functions.php'; }

if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $new = $_GET['lang'];
    $pdo->prepare("UPDATE users SET language_pref = ? WHERE id = ?")->execute([$new, $user_id]);
    $_SESSION['lang'] = $new;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) { header("Location: logout.php"); exit(); }

$lang = $_SESSION['lang'] ?? ($user['language_pref'] ?? 'fr');
$t = require_once "lang/$lang.php";

// --- 3. LOGIQUE MÉTIER ---
$week_streak = [];
for ($i = 6; $i >= 0; $i--) {
    $date_check = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date_check));
    $jours_fr = ['Mon'=>'Lun', 'Tue'=>'Mar', 'Wed'=>'Mer', 'Thu'=>'Jeu', 'Fri'=>'Ven', 'Sat'=>'Sam', 'Sun'=>'Dim'];
    $day_letter = $jours_fr[$day_name] ?? $day_name[0];
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM user_actions WHERE user_id = ? AND DATE(date_action) = ?");
    $stmt_check->execute([$user_id, $date_check]);
    $has_action = $stmt_check->fetchColumn() > 0;

    $week_streak[] = [
        'day' => $day_letter,
        'active' => $has_action,
        'is_today' => ($i === 0)
    ];
}

// Niveau
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

// Classement
$filter_col = $user['department_id'] ? 'department_id' : 'company_id';
$filter_val = $user['department_id'] ? $user['department_id'] : $user['company_id'];
$sql_rank = "SELECT pseudo, points_rank, id FROM users WHERE $filter_col = :val AND role = 'shifter' ORDER BY points_rank DESC LIMIT 5";
$stmt_rank = $pdo->prepare($sql_rank);
$stmt_rank->execute(['val' => $filter_val]);
$classement = $stmt_rank->fetchAll();

// Graphique
$sql_graph = "SELECT DATE(ua.date_action) as jour, SUM(c.co2_kg) as total_co2 FROM user_actions ua JOIN challenges c ON ua.challenge_id = c.id WHERE ua.user_id = :uid AND ua.date_action >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(ua.date_action) ORDER BY jour ASC";
$stmt_graph = $pdo->prepare($sql_graph);
$stmt_graph->execute(['uid' => $user_id]);
$graph_data = $stmt_graph->fetchAll(PDO::FETCH_KEY_PAIR);

$labels = []; $data_points = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('D', strtotime($date));
    $data_points[] = $graph_data[$date] ?? 0;
}

// Quiz
$has_done_quiz = ((float)$user['initial_footprint_kg'] != 32.60);
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
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="bg-brand-dark text-brand-primary font-body overflow-x-hidden pb-24">

    <header class="fixed top-0 w-full bg-brand-dark/90 backdrop-blur-md z-40 px-4 py-3 flex justify-between items-center border-b border-brand-border">
        <div class="flex items-center gap-3">
            <div class="relative w-12 h-12 flex items-center justify-center bg-brand-primary text-brand-dark rounded-full border-2 border-brand-primary shadow-[0_0_10px_rgba(255,255,255,0.3)]">
                <span class="font-display font-black text-lg"><?= $levelData['niveau_actuel'] ?></span>
                <div class="absolute -bottom-2 bg-brand-dark text-brand-primary text-[9px] font-bold px-1.5 py-0.5 rounded border border-brand-border">
                    <?= strtoupper($levelData['titre_actuel']) ?>
                </div>
            </div>
            
            <div class="flex flex-col ml-1">
                <span class="text-xs text-brand-secondary font-medium uppercase tracking-tighter"><?= $t['pts'] ?></span>
                <span class="font-display font-bold text-xl leading-none tracking-tight">
                    <?= number_format($user['points_wallet'], 0, ',', ' ') ?>
                </span>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <button class="relative text-brand-secondary hover:text-brand-primary transition">
                <i class="fa-regular fa-newspaper text-xl"></i>
            </button>
            <button onclick="toggleMenu()" class="text-brand-secondary hover:text-brand-primary transition">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
        </div>
    </header>

    <div id="settings-menu" class="fixed inset-0 bg-brand-dark/95 z-50 hidden flex flex-col justify-center items-center opacity-0 transition-opacity duration-300">
        <button onclick="toggleMenu()" class="absolute top-6 right-6 text-brand-primary text-2xl">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <h2 class="font-display text-2xl font-bold mb-6"><?= $t['settings'] ?></h2>
        <p class="text-brand-secondary text-sm mb-3"><?= $t['choose_lang'] ?></p>
        <div class="flex gap-4 mb-8">
            <a href="?lang=fr" class="border border-brand-border px-6 py-2 rounded-full text-sm font-bold transition <?= $lang == 'fr' ? 'bg-brand-primary text-brand-dark' : 'text-brand-tertiary' ?>">Français 🇫🇷</a>
            <a href="?lang=en" class="border border-brand-border px-6 py-2 rounded-full text-sm font-bold transition <?= $lang == 'en' ? 'bg-brand-primary text-brand-dark' : 'text-brand-tertiary' ?>">English 🇬🇧</a>
        </div>
        <nav class="flex flex-col gap-6 text-center text-xl w-full px-10">
            <a href="#" class="hover:text-brand-secondary border-b border-brand-border pb-4"><?= $t['account'] ?></a>
            <a href="#" class="hover:text-brand-secondary border-b border-brand-border pb-4"><?= $t['privacy'] ?></a>
            <a href="logout.php" class="text-red-500 mt-4 font-bold"><?= $t['logout'] ?></a>
        </nav>
    </div>

    <main class="mt-20 px-4">

        <div class="flex justify-center mb-6 mt-4 px-6">
            <div class="skew-container flex w-full max-w-sm bg-brand-card border border-brand-border shadow-lg shadow-brand-primary/5 h-14 relative overflow-hidden">
                <button id="btn-dept" onclick="switchView('dept')" class="group relative h-full transition-all duration-500 ease-out flex-[1.5] bg-brand-primary text-brand-dark">
                    <div class="absolute inset-0 bg-gradient-to-tr from-gray-200 to-white opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="skew-child flex items-center justify-center gap-2 h-full w-full">
                        <span class="font-display font-bold text-sm uppercase tracking-wider"><?= $t['view_dept'] ?></span>
                    </div>
                </button>
                <button id="btn-solo" onclick="switchView('solo')" class="group relative h-full transition-all duration-500 ease-out flex-[1] bg-brand-card text-brand-tertiary hover:text-brand-secondary hover:bg-brand-border">
                    <div class="skew-child flex items-center justify-center gap-2 h-full w-full">
                        <span class="font-display font-bold text-xs uppercase tracking-wider"><?= $t['view_solo'] ?></span>
                    </div>
                </button>
            </div>
        </div>

        <div id="view-dept" class="fade-in">
            <div class="bg-brand-card border border-brand-border rounded-2xl p-4 mb-6 shadow-lg">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-bold text-sm text-brand-secondary flex items-center gap-2">
                        <i class="fa-solid fa-fire text-brand-primary"></i> 
                        <?= $t['streak_title'] ?>
                    </h3>
                    <span class="text-xs font-mono text-brand-tertiary"><?= $user['current_streak'] ?> Jours</span>
                </div>
                
                <div class="flex justify-between items-center">
                    <?php foreach($week_streak as $day): ?>
                        <div class="flex flex-col items-center gap-1">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center border 
                                <?= $day['active'] ? 'bg-brand-primary text-brand-dark border-brand-primary' : 'bg-transparent border-brand-border text-brand-tertiary' ?> 
                                <?= $day['is_today'] ? 'ring-2 ring-brand-secondary ring-offset-2 ring-offset-brand-card' : '' ?>">
                                
                                <?php if($day['active']): ?>
                                    <i class="fa-solid fa-fire text-sm"></i>
                                <?php else: ?>
                                    <div class="w-2 h-2 rounded-full bg-brand-border"></div>
                                <?php endif; ?>
                            </div>
                            
                            <span class="text-[10px] font-bold <?= $day['is_today'] ? 'text-brand-primary' : 'text-brand-tertiary' ?>">
                                <?= $day['day'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-6">
                <a href="defis.php" class="col-span-2 bg-brand-primary text-brand-dark rounded-2xl p-5 flex items-center justify-between hover:bg-gray-200 transition shadow-lg shadow-brand-primary/10">
                    <div class="text-left">
                        <span class="text-xs font-bold uppercase tracking-wider border-b border-brand-dark pb-1"><?= $t['todo_today'] ?></span>
                        <h3 class="font-display font-bold text-lg mt-2"><?= $t['btn_todo'] ?? 'Voir les défis' ?></h3>
                    </div>
                    <i class="fa-solid fa-person-biking text-3xl"></i>
                </a>
                <a href="article.php" class="bg-brand-card border border-brand-border rounded-2xl p-4 flex flex-col items-center justify-center gap-2 hover:border-brand-primary transition">
                    <i class="fa-solid fa-book-open text-2xl text-brand-secondary"></i>
                    <span class="text-sm font-medium"><?= $t['btn_article'] ?></span>
                </a>
                <a href="quiz_solo.php" class="bg-brand-card border border-brand-border rounded-2xl p-4 flex flex-col items-center justify-center gap-2 hover:border-brand-primary transition">
                    <i class="fa-solid fa-clipboard-question text-2xl text-brand-secondary"></i>
                    <span class="text-sm font-medium"><?= $t['btn_question'] ?></span>
                </a>
            </div>

            <div class="mb-24">
                <h3 class="font-display font-bold text-lg border-l-4 border-brand-primary pl-3 mb-3"><?= $t['ranking_title'] ?></h3>
                <div class="bg-brand-card rounded-2xl p-4 border border-brand-border space-y-3">
                    <?php if(empty($classement)): ?>
                        <p class="text-brand-tertiary text-center text-sm">Aucun classement disponible.</p>
                    <?php else: ?>
                        <?php foreach($classement as $index => $joueur): ?>
                            <?php 
                                $rank = $index + 1;
                                $is_me = ($joueur['id'] == $user_id);
                                $color = $rank == 1 ? 'text-yellow-500' : ($rank == 2 ? 'text-gray-400' : ($rank == 3 ? 'text-orange-700' : 'text-brand-tertiary'));
                                $bg_class = $is_me ? 'bg-brand-border border border-brand-primary/20' : '';
                            ?>
                            <div class="flex items-center justify-between <?= $bg_class ?> p-2 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="<?= $color ?> font-display font-bold w-4"><?= $rank ?></span>
                                    <div class="w-8 h-8 rounded-full bg-brand-border flex items-center justify-center text-xs font-bold">
                                        <?= strtoupper(substr($joueur['pseudo'], 0, 1)) ?>
                                    </div>
                                    <span class="font-medium <?= $is_me ? 'text-brand-primary' : 'text-brand-secondary' ?>">
                                        <?= htmlspecialchars($joueur['pseudo']) ?> <?= $is_me ? '(' . ($t['who_rank'] ?? 'Moi') . ')' : '' ?>
                                    </span>
                                </div>
                                <span class="font-mono text-sm text-brand-secondary"><?= $joueur['points_rank'] ?> XP</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="view-solo" class="hidden fade-in">
            <?php if (!$has_done_quiz): ?>
                <div class="flex flex-col items-center justify-center text-center py-10 bg-brand-card rounded-2xl border border-dashed border-brand-border p-6 mb-6">
                    <div class="w-20 h-20 bg-brand-border rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-leaf text-3xl text-brand-secondary"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold mb-2"><?= $t['impact_title'] ?></h3>
                    <p class="text-brand-secondary text-sm mb-6"><?= $t['impact_desc'] ?></p>
                    <a href="questionnaire_impact.php" class="bg-brand-primary text-brand-dark px-6 py-3 rounded-full font-bold hover:bg-gray-200 transition shadow-lg shadow-brand-primary/20">
                        <?= $t['btn_quiz'] ?> <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
                </div>
            <?php else: ?>
                
                <div class="bg-brand-card border border-brand-border rounded-2xl p-4 mb-4">
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-xs font-bold text-brand-tertiary uppercase tracking-widest">Niveau Suivant</span>
                        <span class="text-xs font-mono text-brand-secondary"><?= number_format($levelData['xp_actuel']) ?> / <?= number_format($levelData['xp_prochain']) ?> XP</span>
                    </div>
                    <div class="w-full bg-brand-border h-2 rounded-full overflow-hidden">
                        <div class="bg-brand-primary h-full transition-all duration-1000 ease-out" style="width: <?= $levelData['pourcentage'] ?>%"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-brand-card border border-brand-border rounded-2xl p-4">
                        <p class="text-xs text-brand-tertiary mb-1"><?= $t['initial_footprint'] ?></p>
                        <p class="font-display text-2xl font-bold text-brand-primary"><?= $user['initial_footprint_kg'] ?> <span class="text-xs font-normal">t/an</span></p>
                    </div>
                    <div class="bg-brand-primary text-brand-dark rounded-2xl p-4">
                        <p class="text-xs font-bold mb-1 opacity-70"><?= $t['saved'] ?></p>
                        <p class="font-display text-2xl font-bold">- <?= round($user['total_carbon_saved'], 1) ?> <span class="text-xs font-normal">kg</span></p>
                    </div>
                </div>

                <div class="bg-brand-card border border-brand-border rounded-2xl p-4 mb-6">
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

    <nav class="fixed bottom-0 w-full bg-brand-dark border-t border-brand-border pb-safe pt-2 px-6 flex justify-between items-center z-40 pb-4 h-20">
        <a href="#" class="flex flex-col items-center gap-1 text-brand-tertiary hover:text-brand-primary transition w-12"><i class="fa-solid fa-store text-xl"></i><span class="text-[10px]"><?= $t['nav_shop'] ?></span></a>
        <a href="defis.php" class="flex flex-col items-center gap-1 text-brand-tertiary hover:text-brand-primary transition w-12"><i class="fa-solid fa-trophy text-xl"></i><span class="text-[10px]"><?= $t['nav_defs'] ?></span></a>
        <a href="#" class="relative -top-5 flex flex-col items-center justify-center w-14 h-14 bg-brand-primary text-brand-dark rounded-full shadow-lg shadow-brand-primary/20 border-4 border-brand-dark"><i class="fa-solid fa-house text-xl"></i></a>
        <a href="#" class="flex flex-col items-center gap-1 text-brand-tertiary hover:text-brand-primary transition w-12"><i class="fa-solid fa-users text-xl"></i><span class="text-[10px]"><?= $t['nav_social'] ?></span></a>
        <a href="#" class="flex flex-col items-center gap-1 text-brand-tertiary hover:text-brand-primary transition w-12"><i class="fa-solid fa-user text-xl"></i><span class="text-[10px]"><?= $t['nav_prof'] ?></span></a>
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
            
            const activeClasses = ['flex-[1.5]', 'bg-brand-primary', 'text-brand-dark'];
            const inactiveClasses = ['flex-[1]', 'bg-brand-card', 'text-brand-tertiary', 'hover:text-brand-secondary', 'hover:bg-brand-border'];

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
                    x: { grid: { display: false, color: '#374151' }, ticks: { color: '#9ca3af' } },
                    y: { grid: { color: '#374151' }, ticks: { color: '#9ca3af' } }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>