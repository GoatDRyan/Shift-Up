<?php 
session_start();
require_once '../../config/db_connect.php';

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
$dbError = '';
$companyId = null;
$companies = [];
if ($pdo) {
    $companies = $pdo->query("SELECT id, nom FROM companies ORDER BY id ASC")->fetchAll();
    if (!empty($companies)) $companyId = (int)$companies[0]['id'];
}

$userDistribution = [];
if ($pdo) {
    $sql = "
        SELECT COALESCE(d.nom, 'Sans département') AS label, COUNT(u.id) AS cnt
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE ".($companyId ? "u.company_id = :company_id" : "1=1")."
        GROUP BY COALESCE(d.nom,'Sans département')
        ORDER BY cnt DESC
    ";
    $stmt = $pdo->prepare($sql);
    if ($companyId) $stmt->execute([':company_id'=>$companyId]); else $stmt->execute();
    $userDistribution = $stmt->fetchAll();
}
if (!$userDistribution) {
    $userDistribution = [
        ['label'=>'Administration','cnt'=>12],
        ['label'=>'Production','cnt'=>35],
        ['label'=>'Support','cnt'=>18],
    ];
}

$carbonTrend = [];
if ($pdo) {
    try {
        $sqlTrend = "
            SELECT DATE_FORMAT(ua.date_action, '%Y-%m') AS period, 
                   ROUND(COALESCE(SUM(c.co2_kg),0), 2) AS val
            FROM user_actions ua
            JOIN users u ON ua.user_id = u.id
            LEFT JOIN challenges c ON ua.challenge_id = c.id
            WHERE ".($companyId ? "u.company_id = :company_id" : "1=1")."
            GROUP BY DATE_FORMAT(ua.date_action, '%Y-%m')
            ORDER BY period ASC
        ";
        $stmt = $pdo->prepare($sqlTrend);
        if ($companyId) $stmt->execute([':company_id'=>$companyId]); else $stmt->execute();
        $carbonTrend = $stmt->fetchAll();
    } catch (Exception $e) {
    }
}
if (!$carbonTrend) {
    $now = new DateTime();
    for ($i=11;$i>=0;$i--) {
        $m = (clone $now)->modify("-{$i} months")->format('Y-m');
        $carbonTrend[] = ['period'=>$m, 'val'=>0];
    }
}


$kpis = [
    'shifter_moyen' => ['name'=>'Shifter moyen', 'value'=>''],
    'top_shifter' => ['name'=>'Top shifter', 'value'=>''],
    'top_department' => ['name'=>'Top département', 'value'=>''],
];

if ($pdo) {
    try {
        $sqlAvg = "
            SELECT ROUND(AVG(total_xp),2) AS avg_xp FROM (
                SELECT u.id AS user_id, COALESCE(SUM(c.xp_gain),0) AS total_xp
                FROM users u
                LEFT JOIN user_actions ua ON ua.user_id = u.id
                LEFT JOIN challenges c ON ua.challenge_id = c.id
                WHERE ".($companyId ? "u.company_id = :company_id" : "1=1")."
                GROUP BY u.id
            ) t
        ";
        $stmt = $pdo->prepare($sqlAvg);
        if ($companyId) $stmt->execute([':company_id'=>$companyId]); else $stmt->execute();
        $avg = $stmt->fetchColumn();
        $kpis['shifter_moyen']['value'] = ($avg !== false && $avg !== null) ? number_format((float)$avg, 2, '.', '') : '0.00';
    } catch (Exception $e) {
        $kpis['shifter_moyen']['value'] = '0.00';
    }

    try {
        $sqlTopShifter = "
            SELECT 
              COALESCE(u.pseudo, u.email, 'Utilisateur') AS name, 
              COALESCE(SUM(c.xp_gain),0) AS total_xp
            FROM users u
            LEFT JOIN user_actions ua ON ua.user_id = u.id
            LEFT JOIN challenges c ON ua.challenge_id = c.id
            WHERE ".($companyId ? "u.company_id = :company_id" : "1=1")."
            GROUP BY u.id
            ORDER BY total_xp DESC
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sqlTopShifter);
        if ($companyId) $stmt->execute([':company_id'=>$companyId]); else $stmt->execute();
        $top = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($top) {
            $kpis['top_shifter']['value'] = ($top['name'] ?: 'Utilisateur').' ('.(float)$top['total_xp'].' XP)';
        } else {
            $kpis['top_shifter']['value'] = 'N/A';
        }
    } catch (Exception $e) {
        $kpis['top_shifter']['value'] = 'N/A';
    }

    try {
        $sqlDept = "
          SELECT COALESCE(d.nom,'Sans département') AS label, COUNT(u.id) AS cnt
          FROM users u
          LEFT JOIN departments d ON u.department_id = d.id
          WHERE ".($companyId ? "u.company_id = :company_id" : "1=1")."
          GROUP BY COALESCE(d.nom,'Sans département')
          ORDER BY cnt DESC
          LIMIT 1
        ";
        $stmt = $pdo->prepare($sqlDept);
        if ($companyId) $stmt->execute([':company_id'=>$companyId]); else $stmt->execute();
        $td = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($td) $kpis['top_department']['value'] = ($td['label']?:'Inconnu').' ('.$td['cnt'].')';
        else $kpis['top_department']['value'] = 'N/A';
    } catch (Exception $e) {
        $kpis['top_department']['value'] = 'N/A';
    }
}

$objectives = [];
if ($pdo) {
    $sql = "SELECT titre_fr FROM challenges WHERE ".($companyId ? "company_id = :company_id OR company_id IS NULL" : "1=1")." ORDER BY id ASC LIMIT 3";
    $stmt = $pdo->prepare($sql);
    if ($companyId) $stmt->execute([':company_id'=>$companyId]); else $stmt->execute();
    $objectives = array_column($stmt->fetchAll(), 'titre_fr');
}
if (count($objectives) < 3) {
    $defaults = ['Venir à vélo', 'Déjeuner végétarien', 'Zéro déchet'];
    foreach ($defaults as $d) if (!in_array($d, $objectives)) $objectives[] = $d;
    $objectives = array_slice($objectives, 0, 3);
}

if (isset($_GET['export']) && $_GET['export']=='1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=shiftup_export_'.date('Ymd_His').'.csv');
    $out = fopen('php://output','w');

    $dumpTable = function($t) use($pdo,$out) {
        try {
            $colsStmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t ORDER BY ordinal_position");
            $colsStmt->execute([':t'=>$t]);
            $cols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
            if (!$cols) return;
            fputcsv($out, ["table: {$t}"]);
            fputcsv($out, $cols);
            $q = $pdo->query("SELECT ".implode(',',array_map(function($c){ return "`$c`"; }, $cols))." FROM `{$t}` LIMIT 10000");
            while ($row = $q->fetch(PDO::FETCH_NUM)) fputcsv($out, $row);
            fputcsv($out, []);
        } catch (Exception $e) {}
    };

    if ($pdo) {
        foreach (['companies','departments','users','carbon_logs','user_actions'] as $t) {
            $tblExists = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t");
            $tblExists->execute([':t'=>$t]);
            if ((int)$tblExists->fetchColumn() > 0) $dumpTable($t);
        }
    } else {
        fputcsv($out, ['db error: '.($dbError ?? 'no connection')]);
    }
    fclose($out);
    exit;
}

$pieLabels = array_column($userDistribution, 'label');
$pieValues = array_map('intval', array_column($userDistribution, 'cnt'));

$trendLabels = array_column($carbonTrend, 'period');
$trendValues = array_map(function($v){ return (float)$v['val']; }, $carbonTrend);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin - Tableau de bord</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
    body { font-family: 'Inter', sans-serif; }
    .card-shadow { box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
  </style>
</head>
<body class="bg-gray-50 text-gray-900">

<header class="bg-[#FF4800] h-16 sticky top-0 z-50 shadow-lg">
  <div class="absolute left-0 top-0 bottom-0 w-20 md:w-64 bg-[#FF4800] flex items-center justify-center border-r border-orange-600">
    <a href="admin_dashboard.php" class="transition-transform hover:scale-105">
      <div class="w-10 h-10 flex items-center justify-center">
        <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2L4 5v6c0 5 3.5 9.7 8 11 4.5-1.3 8-6 8-11V5l-8-3z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" fill="none"/>
          <text x="12" y="15.5" text-anchor="middle" font-size="10" fill="currentColor" style="font-weight:900">S</text>
        </svg>
      </div>
    </a>
  </div>

  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-20 md:pl-64 pr-8">
    <nav class="hidden md:flex items-center gap-10">
      <a href="admin_shift_manager.php" class="text-white/90 hover:text-white font-semibold transition-colors">Shift manager</a>
      <a href="admin_gestion.php" class="text-white/90 hover:text-white font-semibold transition-colors">Gestion</a>
      <a href="admin_profile.php" class="w-10 h-10 rounded-full border-2 border-white/50 hover:border-white flex items-center justify-center transition-all">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      </a>
    </nav>
  </div>
</header>

<main class="max-w-screen-2xl mx-auto p-8">
  <div class="mb-10">
    <h1 class="text-4xl font-bold text-gray-900 tracking-tight">
      Bienvenue, <span class="text-[#FF4800]">Admin</span>
    </h1>
    <p class="text-gray-500 mt-2"><?= e($companies[0]['nom'] ?? 'Nom de l’entreprise') ?></p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <section class="lg:col-span-2 bg-white rounded-3xl p-8 card-shadow border border-gray-100">
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-xl font-bold">Empreinte carbone - Tendance</h2>
        <a href="?export=1" id="exportBtn" class="bg-[#FF4800] hover:bg-[#e64100] text-white px-6 py-2 rounded-full font-bold transition-all shadow-md active:scale-95">
          Exporter CSV
        </a>
      </div>
      <div class="h-80 w-full">
        <canvas id="trendChart"></canvas>
      </div>
    </section>

    <aside class="space-y-8">
      <div class="bg-white rounded-3xl p-8 card-shadow border border-gray-100 flex flex-col items-center">
        <h2 class="text-lg font-bold mb-6 w-full text-left">Répartition Shift</h2>
        <div class="relative w-48 h-48 mb-6">
            <canvas id="pieChartLarge"></canvas>
        </div>
        <div class="w-full space-y-3">
            <div class="flex items-center justify-between p-3 bg-orange-50 rounded-2xl border border-orange-100">
                <span class="text-sm font-medium text-gray-600">Moyenne XP</span>
                <span class="font-bold text-[#FF4800]"><?= e($kpis['shifter_moyen']['value']) ?></span>
            </div>
            <div class="flex flex-col p-3 bg-gray-50 rounded-2xl border border-gray-100">
                <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">Top Shifter</span>
                <span class="font-bold text-gray-800 truncate"><?= e($kpis['top_shifter']['value']) ?></span>
            </div>
        </div>
      </div>

      <div class="bg-[#FF4800] rounded-3xl p-8 shadow-xl text-white">
        <h2 class="font-bold mb-4 opacity-80 uppercase text-sm tracking-widest">Objectifs Prioritaires</h2>
        <ul class="space-y-3">
          <?php foreach($objectives as $obj): ?>
            <li class="flex items-center gap-3 bg-white/10 p-3 rounded-xl border border-white/20">
                <div class="w-2 h-2 bg-white rounded-full"></div>
                <span class="font-medium"><?= e($obj) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </aside>
  </div>

  <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="bg-white rounded-3xl p-8 card-shadow border border-gray-100">
      <h3 class="text-xl font-bold mb-6">Membres par département</h3>
      <div class="overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="text-gray-400 text-sm uppercase">
                    <th class="pb-4 font-semibold">Département</th>
                    <th class="pb-4 font-semibold text-right">Membres</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach($userDistribution as $row): ?>
                <tr>
                    <td class="py-4 font-medium"><?= e($row['label']) ?></td>
                    <td class="py-4 text-right">
                        <span class="inline-block px-3 py-1 bg-gray-100 rounded-lg font-bold"><?= (int)$row['cnt'] ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4">
      <div class="bg-white rounded-3xl p-6 card-shadow border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 font-bold uppercase">Utilisateurs Actifs</p>
            <p class="text-3xl font-black mt-1">
                <?php
                if ($pdo) {
                  $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE ".($companyId ? "company_id = :company_id" : "1=1"));
                  if ($companyId) $stmt->execute([':company_id'=>$companyId]); else $stmt->execute();
                  echo e($stmt->fetchColumn());
                } else echo 'N/A';
                ?>
            </p>
        </div>
        <div class="p-4 bg-orange-100 rounded-2xl text-[#FF4800]">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </div>
      </div>

      <div class="bg-white rounded-3xl p-6 card-shadow border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 font-bold uppercase">Économie CO₂</p>
            <p class="text-3xl font-black mt-1 text-green-600">
                <?php
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("SELECT ROUND(COALESCE(SUM(c.co2_kg),0),2) FROM user_actions ua JOIN users u ON ua.user_id = u.id JOIN challenges c ON ua.challenge_id = c.id WHERE ".($companyId ? "u.company_id = :company_id" : "1=1"));
                        if ($companyId) $stmt->execute([':company_id'=>$companyId]); else $stmt->execute();
                        $val = $stmt->fetchColumn();
                        echo e($val ?: '0.00');
                    } catch (Exception $e) { echo '0.00'; }
                } else echo 'N/A';
                ?> <span class="text-lg">kg</span>
            </p>
        </div>
        <div class="p-4 bg-green-100 rounded-2xl text-green-600">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 002 2h2.935M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
      </div>
    </div>
  </div>

  <?php if ($dbError): ?>
    <div class="mt-8 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-3">
      <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
      <div><strong>Erreur Base de données :</strong> <?= e($dbError) ?></div>
    </div>
  <?php endif; ?>
</main>

<script>
const pieLabels = <?= json_encode($pieLabels, JSON_UNESCAPED_UNICODE) ?>;
const pieValues = <?= json_encode($pieValues) ?>;
const trendLabels = <?= json_encode($trendLabels) ?>;
const trendValues = <?= json_encode($trendValues) ?>;

function orangePalette(n) {
  const colors = ['#FF4800', '#FF7033', '#FF9966', '#FFC199', '#FFE4D1'];
  return n <= 5 ? colors.slice(0, n) : Array(n).fill('#FF4800');
}

const pieCtxLarge = document.getElementById('pieChartLarge').getContext('2d');
new Chart(pieCtxLarge, {
  type: 'doughnut',
  data: {
    labels: pieLabels,
    datasets: [{
      data: pieValues,
      backgroundColor: orangePalette(pieValues.length),
      borderWidth: 0,
      hoverOffset: 10
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: { legend: { display: false } }
  }
});

const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
  type: 'line',
  data: {
    labels: trendLabels,
    datasets: [{
      label: 'CO₂ Économisé (kg)',
      data: trendValues,
      fill: true,
      tension: 0.4,
      pointRadius: 4,
      pointBackgroundColor: '#FF4800',
      backgroundColor: 'rgba(255, 72, 0, 0.05)',
      borderColor: '#FF4800',
      borderWidth: 3
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false } },
      y: { 
        beginAtZero: true,
        grid: { color: '#f3f4f6' }
      }
    }
  }
});

document.getElementById('exportBtn').addEventListener('click', function() {
    setTimeout(() => {
        Swal.fire({
            title: 'Export prêt !',
            text: 'Le fichier CSV a été généré avec succès.',
            icon: 'success',
            confirmButtonColor: '#FF4800',
            confirmButtonText: 'Parfait'
        });
    }, 500);
});
</script>
</body>
</html>