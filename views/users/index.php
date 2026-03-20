<?php
require_once '../../includes/init.php';

// Streak des 7 jours
$week_streak = [];
for ($i = 6; $i >= 0; $i--) {
    $date_check = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date_check));
    $jours = $lang === 'fr' ?
        ['Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mer', 'Thu' => 'Jeu', 'Fri' => 'Ven', 'Sat' => 'Sam', 'Sun' => 'Dim'] :
        ['Mon' => 'Mon', 'Tue' => 'Tue', 'Wed' => 'Wed', 'Thu' => 'Thu', 'Fri' => 'Fri', 'Sat' => 'Sat', 'Sun' => 'Sun'];
    $day_letter = $jours[$day_name] ?? substr($day_name, 0, 1);
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM user_actions WHERE user_id = ? AND DATE(date_action) = ?");
    $stmt_check->execute([$user_id, $date_check]);
    $has_action = $stmt_check->fetchColumn() > 0;

    $week_streak[] = [
        'day' => $day_letter,
        'active' => $has_action,
        'is_today' => ($i === 0)
    ];
}

// --- SYSTÈME DE CLASSEMENTS

// 1. Classement Département
$rank_dept = [];
if (!empty($user['department_id'])) {
    $stmt_dept = $pdo->prepare("SELECT pseudo, points_rank, id FROM users WHERE department_id = :val AND role = 'shifter' ORDER BY points_rank DESC LIMIT 10");
    $stmt_dept->execute(['val' => $user['department_id']]);
    $rank_dept = $stmt_dept->fetchAll();
}

// 2. Classement Entreprise (Tous les collègues)
$stmt_comp = $pdo->prepare("SELECT pseudo, points_rank, id FROM users WHERE company_id = :val AND role = 'shifter' ORDER BY points_rank DESC LIMIT 10");
$stmt_comp->execute(['val' => $user['company_id']]);
$rank_comp = $stmt_comp->fetchAll();

// 3. Classement Inter-Entreprises (Les meilleures entreprises le goat des goats ceux a qui on presque mon niveau)
$stmt_global = $pdo->query("
    SELECT c.nom as pseudo, SUM(u.points_rank) as points_rank, c.id 
    FROM users u 
    JOIN companies c ON u.company_id = c.id 
    WHERE u.role = 'shifter' 
    GROUP BY c.id 
    ORDER BY points_rank DESC 
    LIMIT 10
");
$rank_global = $stmt_global->fetchAll();

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

$has_done_quiz = ((float)$user['initial_footprint_kg'] != 32.60);
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift'Up</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../js/tailwind-config.js"></script>
</head>

<body class="bg-brand-card text-brand-dark font-sans overflow-x-hidden pb-24">

    <header class="top-0 w-full bg-brand-primary p-4 pb-0 relative z-40 shadow-sm">
        <div class="flex items-center gap-2 max-[375px]:gap-1 mb-4 z-50 relative">
            
            <div class="flex items-center bg-brand-secondary rounded-full pr-4 max-[375px]:pr-2 h-10 max-[375px]:h-8 shadow-sm">
                <div class="w-10 h-10 max-[375px]:w-8 max-[375px]:h-8 rounded-full bg-brand-tertiary flex items-center justify-center text-brand-primary font-display font-bold border-2 border-brand-primary text-lg max-[375px]:text-sm">
                    <?= $levelData['niveau_actuel'] ?>
                </div>
                <span class="ml-2 text-xs max-[320px]:text-[10px] font-bold text-brand-dark whitespace-nowrap">
                    <?= number_format($levelData['xp_actuel']) ?>/<?= number_format($levelData['xp_prochain']) ?>
                </span>
            </div>

            <div class="flex items-center bg-brand-secondary w-[105px] max-[375px]:w-[115px] min-[414px]:w-[130px] h-10 max-[375px]:h-8 px-3 max-[375px]:px-2 shadow-sm rounded-l-3xl shrink-0" style="clip-path: polygon(0 0, 100% 0, calc(100% - 15px) 100%, 0 100%);">
                <div class="flex items-center gap-2 max-[375px]:gap-1">
                    <div class="w-6 h-6 max-[375px]:w-5 max-[375px]:h-5 rounded-full bg-brand-primary flex items-center justify-center border border-brand-tertiary shrink-0">
                        <i class="fa-solid fa-leaf text-brand-tertiary text-[10px] max-[375px]:text-[8px]"></i>
                    </div>
                    <span class="text-sm max-[376px]:text-[11px] font-bold text-brand-dark truncate">
                        <?= number_format($money, 0, '.', ' ') ?>
                    </span>
                </div>
            </div>

            <div class="absolute right-0 top-12 flex gap-2 max-[375px]:gap-1 z-50">
                <button class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                    <i class="fa-solid fa-newspaper text-xl max-[375px]:text-base"></i>
                </button>
                <button onclick="toggleMenu()" class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                    <i class="fa-solid fa-bars text-xl max-[375px]:text-base"></i>
                </button>
            </div>
        </div>

        <div class="relative h-[200px] top-[-35px] flex items-center justify-center -mx-4 overflow-hidden mt-2 z-10">
            
            <div id="btn-solo" onclick="switchView('solo')" 
                 class="absolute left-[-10%] w-[68%] h-32 max-[375px]:h-28 max-[320px]:h-24 bg-brand-secondary skew-tile cursor-pointer flex items-center justify-end pr-8 max-[375px]:pr-4 transition-colors">
                <div class="unskew text-right flex flex-col justify-center">
                    <h2 class="font-display text-4xl max-[375px]:text-3xl max-[320px]:text-2xl font-bold text-brand-dark uppercase tracking-tighter leading-none mb-1">Solo</h2>
                    <p class="text-xl max-[375px]:text-lg max-[320px]:text-base font-semibold text-brand-dark leading-none"><?= htmlspecialchars($pseudo) ?></p>
                </div>
            </div>

            <div id="btn-dept" onclick="switchView('dept')" 
                 class="absolute right-[-5%] top-[45%] w-[45%] h-28 max-[375px]:h-24 max-[320px]:h-20 bg-brand-border skew-tile cursor-pointer flex items-center justify-center pl-4 transition-colors">
                <div class="unskew">
                    <h2 class="font-display text-2xl max-[375px]:text-xl max-[320px]:text-lg font-bold text-brand-tertiary uppercase tracking-tighter">Ranked</h2>
                </div>
            </div>

        </div>
    </header>
    
    <?php include '../../includes/settings_menu.php'; ?>

    <main class="px-4 max-[375px]:px-2 pt-10">

        <div id="view-solo" class="fade-in mt-[-30px]">
            
            <div class="bg-brand-primary border border-brand-border rounded-2xl p-4 mb-6 shadow-sm">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-bold text-sm text-brand-dark flex items-center gap-2">
                        <img class="w-4 h-4" src="img/icone/icone-flamme.svg" alt="Flamme">
                        Shift streak
                    </h3>
                    <span class="text-xs font-mono text-brand-tertiary font-bold"><?= $user['current_streak'] ?> Jours</span>
                </div>
                
                <div class="flex justify-between items-center px-1">
                    <?php foreach($week_streak as $day): ?>
                        <div class="flex flex-col items-center gap-1.5">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 
                                <?= $day['active'] ? 'bg-brand-primary text-brand-primary' : 'bg-brand-card border-brand-border text-brand-secondary' ?> 
                                <?= $day['is_today'] ? 'ring-2 ring-brand-tertiary ring-offset-2 ring-offset-brand-primary' : '' ?>">
                                <?php if($day['active']): ?>
                                    <img class="w-6 h-6" src="img/icone/icone-flamme.svg" alt="Flamme">
                                <?php else: ?>
                                    <div class="w-1.5 h-1.5 rounded-full bg-brand-secondary"></div>
                                <?php endif; ?>
                            </div>
                            <span class="text-[10px] font-bold uppercase <?= $day['is_today'] ? 'text-brand-dark' : 'text-brand-tertiary' ?>"><?= $day['day'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!$has_done_quiz): ?>
                <div class="flex flex-col items-center justify-center text-center py-10 bg-brand-primary rounded-2xl border border-dashed border-brand-tertiary p-6 mb-6 shadow-sm">
                    <div class="w-20 h-20 bg-brand-card rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-leaf text-3xl text-brand-tertiary"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold mb-2 text-brand-dark"><?= $t['impact_title'] ?></h3>
                    <p class="text-brand-tertiary text-sm mb-6"><?= $t['impact_desc'] ?></p>
                    <a href="questionnaire_impact.php" class="bg-brand-dark text-brand-primary px-6 py-3 rounded-full font-bold hover:bg-black transition shadow-lg">
                        <?= $t['btn_quiz'] ?> <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
                </div>
            <?php else: ?>
                
            <h3 class="text-brand-tertiary uppercase text-xs font-bold tracking-widest mb-2 mt-4"><?= $t['stat_title'] ?? 'Statistiques' ?></h3>

                <div class="bg-brand-dark p-5 rounded-2xl shadow-md mb-5 flex justify-between items-center relative overflow-hidden">
                    <i class="fa-solid fa-earth-europe text-8xl text-white opacity-5 absolute -right-6 -bottom-6"></i>
                    
                    <div class="flex flex-col relative z-10">
                        <p class="text-[10px] uppercase font-bold text-brand-secondary mb-1 tracking-wider"><?= $t['base_footprint'] ?? 'Bilan de départ' ?></p>
                        <p class="font-display text-3xl font-black text-brand-primary mb-3">
                            <?= number_format($user['initial_footprint_kg'], 0, ',', ' ') ?> <span class="text-sm font-sans text-brand-secondary font-medium"><?= $t['unit_kg'] ?></span>
                        </p>
                        
                        <a href="questionnaire_impact.php" class="text-[10px] font-bold text-brand-primary bg-brand-tertiary/50 hover:bg-brand-tertiary w-fit px-3 py-1.5 rounded-full transition-colors flex items-center gap-2 border border-brand-tertiary">
                            <i class="fa-solid fa-rotate-right"></i> <?= $t['btn_retake'] ?? 'Refaire le test' ?>
                        </a>
                    </div>
                    
                    <div class="w-12 h-12 rounded-full bg-brand-tertiary border-2 border-brand-secondary flex items-center justify-center relative z-10">
                        <i class="fa-solid fa-leaf text-xl text-brand-primary"></i>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 max-[375px]:gap-2 mb-6">
                    <div class="bg-brand-primary p-4 max-[375px]:p-3 rounded-2xl border border-brand-border shadow-sm flex flex-col items-center justify-center">
                        <p class="text-[10px] uppercase font-bold text-brand-tertiary mb-1"><?= $t['niv_title'] ?></p>
                        <p class="font-display text-2xl font-black text-brand-dark"><?= $levelData['titre_actuel'] ?></p>
                    </div>
                    <div class="bg-brand-primary p-4 max-[375px]:p-3 rounded-2xl border border-brand-border shadow-sm flex flex-col items-center justify-center">
                        <p class="text-[10px] uppercase font-bold text-brand-tertiary mb-1"><?= $t['carbon_title'] ?></p>
                        <p class="font-display text-2xl font-black text-brand-success">- <?= round($user['total_carbon_saved'], 1) ?> <span class="text-sm">kg</span></p>
                    </div>
                </div>

                <div class="bg-brand-primary border border-brand-border rounded-2xl p-4 mb-6 shadow-sm">
                    <h3 class="text-xs font-bold mb-4 flex items-center gap-2 text-brand-tertiary uppercase tracking-widest">
                        <i class="fa-solid fa-chart-line"></i> <?= $t['chart_title'] ?>
                    </h3>
                    <div class="h-48 w-full">
                        <canvas id="carbonChart"></canvas>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div id="view-dept" class="hidden fade-in mt-[-30px]">
            <div class="grid grid-cols-2 gap-3 mb-6">
                <a href="defis.php" class="col-span-2 bg-brand-dark text-brand-primary rounded-2xl p-5 flex items-center justify-between hover:bg-black transition shadow-lg shadow-brand-dark/20">
                    <div class="text-left">
                        <span class="text-xs font-bold uppercase tracking-wider border-b border-brand-tertiary pb-1"><?= $t['todo_today'] ?></span>
                        <h3 class="font-display font-bold text-xl mt-2"><?= $t['btn_todo'] ?? 'Voir les défis' ?></h3>
                    </div>
                    <i class="fa-solid fa-person-biking text-4xl text-brand-secondary"></i>
                </a>
                <a href="article.php" class="bg-brand-primary border border-brand-border shadow-sm rounded-2xl p-4 flex flex-col items-center justify-center gap-2 hover:border-brand-tertiary transition">
                    <i class="fa-solid fa-book-open text-2xl text-brand-tertiary"></i>
                    <span class="text-sm font-bold text-brand-dark"><?= $t['btn_article'] ?></span>
                </a>
                <a href="quiz_solo.php" class="bg-brand-primary border border-brand-border shadow-sm rounded-2xl p-4 flex flex-col items-center justify-center gap-2 hover:border-brand-tertiary transition">
                    <i class="fa-solid fa-clipboard-question text-2xl text-brand-tertiary"></i>
                    <span class="text-sm font-bold text-brand-dark"><?= $t['btn_question'] ?></span>
                </a>
            </div>

            <h3 class="text-brand-tertiary uppercase text-xs font-bold tracking-widest mb-3 mt-8"><?= $t['ranking_title'] ?? 'Classement' ?></h3>
            
            <div class="flex bg-brand-primary rounded-2xl p-1 border border-brand-border shadow-sm mb-4">
                <?php if(!empty($user['department_id'])): ?>
                <button id="btn-rank-dept" onclick="switchRank('dept')" class="flex-1 text-[11px] font-bold py-2.5 rounded-xl bg-brand-dark text-brand-primary transition active:scale-95">Équipe</button>
                <?php endif; ?>
                <button id="btn-rank-comp" onclick="switchRank('comp')" class="flex-1 text-[11px] font-bold py-2.5 rounded-xl <?= empty($user['department_id']) ? 'bg-brand-dark text-brand-primary' : 'text-brand-tertiary hover:bg-brand-secondary' ?> transition active:scale-95"><?= $t['company_rank'] ?></button>
                <button id="btn-rank-glob" onclick="switchRank('glob')" class="flex-1 text-[11px] font-bold py-2.5 rounded-xl text-brand-tertiary hover:bg-brand-secondary transition active:scale-95">Global</button>
            </div>

            <div class="bg-brand-primary rounded-3xl p-2 border border-brand-border shadow-sm min-h-[250px]">
                
                <?php if(!empty($user['department_id'])): ?>
                <div id="list-rank-dept" class="rank-list">
                    <?php if(empty($rank_dept)): ?>
                        <p class="text-brand-tertiary text-center text-sm py-8">Aucun classement disponible.</p>
                    <?php else: ?>
                        <?php foreach($rank_dept as $index => $joueur): 
                            $rank = $index + 1;
                            $is_me = ($joueur['id'] == $user_id);
                            $medalColor = $rank == 1 ? 'text-yellow-500' : ($rank == 2 ? 'text-gray-400' : ($rank == 3 ? 'text-orange-500' : 'text-brand-tertiary'));
                            $bg_class = $is_me ? 'border-2 border-brand-tertiary shadow-md' : 'border border-transparent';
                        ?>
                            <div class="flex items-center justify-between <?= $bg_class ?> p-4 max-[375px]:p-3 bg-brand-card rounded-2xl mb-2 transition-all">
                                <span class="<?= $medalColor ?> font-display font-bold text-xl w-6 text-center"><?= $rank ?></span>
                                <div class="flex items-center gap-3 flex-1 px-4">
                                    <div class="w-8 h-8 rounded-full bg-brand-border flex items-center justify-center text-xs font-bold text-brand-dark shrink-0">
                                        <?= strtoupper(substr($joueur['pseudo'], 0, 1)) ?>
                                    </div>
                                    <span class="font-bold text-brand-dark max-[375px]:text-sm truncate">
                                        <?= htmlspecialchars($joueur['pseudo']) ?> 
                                        <?= $is_me ? '<span class="text-[10px] text-brand-tertiary ml-1">'.$t['who_rank'].'</span>' : '' ?>
                                    </span>
                                </div>
                                <span class="font-display font-bold text-brand-dark max-[375px]:text-sm shrink-0"><?= number_format($joueur['points_rank'], 0, ',', ' ') ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div id="list-rank-comp" class="rank-list <?= !empty($user['department_id']) ? 'hidden' : '' ?>">
                    <?php foreach($rank_comp as $index => $joueur): 
                        $rank = $index + 1;
                        $is_me = ($joueur['id'] == $user_id);
                        $medalColor = $rank == 1 ? 'text-yellow-500' : ($rank == 2 ? 'text-gray-400' : ($rank == 3 ? 'text-orange-500' : 'text-brand-tertiary'));
                        $bg_class = $is_me ? 'border-2 border-brand-tertiary shadow-md' : 'border border-transparent';
                    ?>
                        <div class="flex items-center justify-between <?= $bg_class ?> p-4 max-[375px]:p-3 bg-brand-card rounded-2xl mb-2 transition-all">
                            <span class="<?= $medalColor ?> font-display font-bold text-xl w-6 text-center"><?= $rank ?></span>
                            <div class="flex items-center gap-3 flex-1 px-4">
                                <div class="w-8 h-8 rounded-full bg-brand-border flex items-center justify-center text-xs font-bold text-brand-dark shrink-0">
                                    <?= strtoupper(substr($joueur['pseudo'], 0, 1)) ?>
                                </div>
                                <span class="font-bold text-brand-dark max-[375px]:text-sm truncate">
                                    <?= htmlspecialchars($joueur['pseudo']) ?> 
                                    <?= $is_me ? '<span class="text-[10px] text-brand-tertiary ml-1">'.$t['who_rank'].'</span>' : '' ?>
                                </span>
                            </div>
                            <span class="font-display font-bold text-brand-dark max-[375px]:text-sm shrink-0"><?= number_format($joueur['points_rank'], 0, ',', ' ') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="list-rank-glob" class="rank-list hidden">
                    <?php foreach($rank_global as $index => $entreprise): 
                        $rank = $index + 1;
                        $is_my_company = ($entreprise['id'] == $user['company_id']); 
                        $medalColor = $rank == 1 ? 'text-yellow-500' : ($rank == 2 ? 'text-gray-400' : ($rank == 3 ? 'text-orange-500' : 'text-brand-tertiary'));
                        $bg_class = $is_my_company ? 'border-2 border-brand-tertiary shadow-md' : 'border border-transparent';
                    ?>
                        <div class="flex items-center justify-between <?= $bg_class ?> p-4 max-[375px]:p-3 bg-brand-card rounded-2xl mb-2 transition-all">
                            <span class="<?= $medalColor ?> font-display font-bold text-xl w-6 text-center"><?= $rank ?></span>
                            <div class="flex items-center gap-3 flex-1 px-4">
                                <div class="w-8 h-8 rounded-full bg-brand-secondary flex items-center justify-center text-xs font-bold text-brand-dark shrink-0">
                                    <i class="fa-solid fa-building"></i>
                                </div>
                                <span class="font-bold text-brand-dark max-[375px]:text-sm truncate">
                                    <?= htmlspecialchars($entreprise['pseudo']) ?> 
                                    <?= $is_my_company ? '<span class="text-[10px] text-brand-tertiary ml-1">(Nous)</span>' : '' ?>
                                </span>
                            </div>
                            <span class="font-display font-bold text-brand-dark max-[375px]:text-sm shrink-0"><?= number_format($entreprise['points_rank'], 0, ',', ' ') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>

    </main>

    <?php include '../../includes/navbar.php'; ?>
    
    <script>
        function switchView(viewName) {
            const btnSolo = document.getElementById('btn-solo');
            const btnDept = document.getElementById('btn-dept');
            const viewSolo = document.getElementById('view-solo');
            const viewDept = document.getElementById('view-dept');
            
            const textSolo = btnSolo.querySelector('h2');
            const textDept = btnDept.querySelector('h2');
            const pseudoSolo = document.getElementById('solo-pseudo');
            const activeClasses = ['left-[-10%]', 'w-[68%]', 'h-32', 'max-[375px]:h-28', 'max-[320px]:h-24', 'bg-brand-secondary', 'justify-end', 'pr-8', 'z-20'];
            const inactiveClasses = ['right-[-5%]', 'left-auto', 'top-[45%]', 'w-[45%]', 'h-28', 'max-[375px]:h-24', 'max-[320px]:h-20', 'bg-brand-border', 'justify-center', 'pl-4', 'z-10', 'border-brand-primary'];

            if (viewName === 'solo') {
                btnSolo.classList.remove(...inactiveClasses);
                btnSolo.classList.add(...activeClasses);
                btnDept.classList.remove(...activeClasses);
                btnDept.classList.add(...inactiveClasses);
                textSolo.classList.replace('text-brand-tertiary', 'text-brand-dark');
                textDept.classList.replace('text-brand-dark', 'text-brand-tertiary');
                if(pseudoSolo) pseudoSolo.classList.remove('hidden');
                viewDept.classList.add('hidden');
                viewSolo.classList.remove('hidden');
            } else {
                btnDept.classList.remove(...inactiveClasses);
                btnDept.classList.add(...activeClasses);
                
                btnSolo.classList.remove(...activeClasses);
                btnSolo.classList.add(...inactiveClasses);

                textDept.classList.replace('text-brand-tertiary', 'text-brand-dark');
                textSolo.classList.replace('text-brand-dark', 'text-brand-tertiary');
                if(pseudoSolo) pseudoSolo.classList.add('hidden');
                
                viewSolo.classList.add('hidden');
                viewDept.classList.remove('hidden');
            }
        }

        function switchRank(rankType) {
            const types = ['dept', 'comp', 'glob'];
            
            types.forEach(type => {
                const btn = document.getElementById(`btn-rank-${type}`);
                const list = document.getElementById(`list-rank-${type}`);
                
                if(!btn || !list) return;
                
                if (type === rankType) {
                    btn.classList.replace('text-brand-tertiary', 'text-brand-primary');
                    btn.classList.replace('hover:bg-brand-secondary', 'bg-brand-dark');
                    list.classList.remove('hidden');
                } else {
                    btn.classList.replace('bg-brand-dark', 'hover:bg-brand-secondary');
                    btn.classList.replace('text-brand-primary', 'text-brand-tertiary');
                    list.classList.add('hidden');
                }
            });
        }
        
        <?php if ($has_done_quiz): ?>
        const ctx = document.getElementById('carbonChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'CO2 Économisé (kg)',
                    data: <?= json_encode($data_points) ?>,
                    borderColor: '#111827',
                    backgroundColor: 'rgba(17, 24, 39, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#111827'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#9ca3af' } },
                    y: { grid: { color: '#e5e7eb' }, ticks: { color: '#9ca3af' } }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>