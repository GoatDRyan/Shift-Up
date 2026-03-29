<?php
session_start();
require_once '../../config/db_connect.php';
if (!function_exists('e')) {
    function e($string) { return htmlspecialchars($string, ENT_QUOTES, 'UTF-8'); }
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
    $sql = "SELECT COALESCE(d.nom, 'Sans département') AS label, COUNT(u.id) AS cnt FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE " . ($companyId ? "u.company_id = :company_id" : "1=1") . " GROUP BY COALESCE(d.nom,'Sans département') ORDER BY cnt DESC";
    $stmt = $pdo->prepare($sql);
    if ($companyId) $stmt->execute([':company_id' => $companyId]); else $stmt->execute();
    $userDistribution = $stmt->fetchAll();
}
if (!$userDistribution) {
    $userDistribution = [['label'=>'Administration','cnt'=>12],['label'=>'Production','cnt'=>35],['label'=>'Support','cnt'=>18]];
}

$carbonTrend = [];
if ($pdo) {
    $sql = "SELECT DATE_FORMAT(cl.date_log, '%Y-%m') AS period, ROUND(SUM(cl.amount_co2),2) AS val FROM carbon_logs cl JOIN users u ON cl.user_id = u.id WHERE " . ($companyId ? "u.company_id = :company_id AND " : "") . " cl.date_log >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(cl.date_log, '%Y-%m') ORDER BY period ASC";
    $stmt = $pdo->prepare($sql);
    if ($companyId) $stmt->execute([':company_id' => $companyId]); else $stmt->execute();
    $carbonTrend = $stmt->fetchAll();
    if (!$carbonTrend) {
        $sql2 = "SELECT DATE(cl.date_log) AS period, ROUND(SUM(cl.amount_co2),2) AS val FROM carbon_logs cl JOIN users u ON cl.user_id = u.id WHERE " . ($companyId ? "u.company_id = :company_id AND " : "") . " cl.date_log >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(cl.date_log) ORDER BY period ASC";
        $stmt2 = $pdo->prepare($sql2);
        if ($companyId) $stmt2->execute([':company_id' => $companyId]); else $stmt2->execute();
        $carbonTrend = $stmt2->fetchAll();
    }
}
if (!$carbonTrend) {
    $now = new DateTime();
    for ($i=11;$i>=0;$i--) {
        $m = (clone $now)->modify("-{$i} months")->format('Y-m');
        $carbonTrend[] = ['period'=>$m, 'val'=>round(120 - $i*5 + rand(-8,8),2)];
    }
}

$kpis = ['shifter_moyen'=>['name'=>'Shifter moyen','value'=>'N/A'],'top_shifter'=>['name'=>'Top shifter','value'=>'N/A'],'top_department'=>['name'=>'Top département','value'=>'N/A']];
if ($pdo) {
    $sqlTop = "SELECT COALESCE(u.pseudo, u.email) AS label, COUNT(ua.id) AS cnt FROM user_actions ua JOIN users u ON ua.user_id = u.id WHERE " . ($companyId ? "u.company_id = :company_id" : "1=1") . " GROUP BY ua.user_id ORDER BY cnt DESC LIMIT 1";
    $stmt = $pdo->prepare($sqlTop);
    if ($companyId) $stmt->execute([':company_id' => $companyId]); else $stmt->execute();
    $top = $stmt->fetch();
    if ($top) $kpis['top_shifter']['value'] = ($top['label']?:'User').' ('.$top['cnt'].')';

    $sqlAvg = "SELECT ROUND(AVG(t.cnt),2) AS avgcnt FROM (SELECT COUNT(*) AS cnt FROM user_actions ua JOIN users u ON ua.user_id = u.id WHERE " . ($companyId ? "u.company_id = :company_id" : "1=1") . " GROUP BY ua.user_id) t";
    $stmt = $pdo->prepare($sqlAvg);
    if ($companyId) $stmt->execute([':company_id' => $companyId]); else $stmt->execute();
    $avg = $stmt->fetchColumn();
    if ($avg !== false && $avg !== null) $kpis['shifter_moyen']['value'] = $avg;

    $sqlDept = "SELECT COALESCE(d.nom,'Sans département') AS label, COUNT(u.id) AS cnt FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE " . ($companyId ? "u.company_id = :company_id" : "1=1") . " GROUP BY COALESCE(d.nom,'Sans département') ORDER BY cnt DESC LIMIT 1";
    $stmt = $pdo->prepare($sqlDept);
    if ($companyId) $stmt->execute([':company_id' => $companyId]); else $stmt->execute();
    $td = $stmt->fetch();
    if ($td) $kpis['top_department']['value'] = ($td['label']?:'Inconnu').' ('.$td['cnt'].')';
}

$objectives = [];
if ($pdo) {
    $sql = "SELECT titre_fr FROM challenges WHERE " . ($companyId ? "company_id = :company_id OR company_id IS NULL" : "1=1") . " ORDER BY id ASC LIMIT 3";
    $stmt = $pdo->prepare($sql);
    if ($companyId) $stmt->execute([':company_id' => $companyId]); else $stmt->execute();
    $objectives = array_column($stmt->fetchAll(), 'titre_fr');
}
if (count($objectives) < 3) {
    $defaults = ['Venir à vélo','Déjeuner végétarien','Zéro déchet'];
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
    } else { fputcsv($out, ['db error: '.($dbError ?? 'no connection')]); }
    fclose($out); exit;
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
  <title>Super Admin - Tableau de bord</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root { --orange: #FF4800; }
    body { background: #fff; }
    .rounded-full-xl { border-radius: 999px; }
    .card-radius { border-radius: 16px; }
    .header-bg { background: #FF4800; }
    .btn-orange { background: #FF4800; color: #fff; border-radius: 999px; }
    .btn-orange:hover { background: #cc3a00; }
    .kpi-pill { background: #fff3ee; color: #FF4800; border: 1px solid #ffd6c2; }
    .objective-pill { background: #fff; border: 1.5px solid #FF4800; color: #FF4800; }
    .objective-pill:hover { background: #fff3ee; }
    .chart-bg { background: linear-gradient(135deg, #fff3ee 0%, #fff 100%); border: 1px solid #ffd6c2; }
  </style>
</head>
<body class="bg-white min-h-screen">
  <header class="header-bg h-16 relative">
    <div class="absolute left-0 top-0 bottom-0 w-24 md:w-72 bg-black/20 flex items-center justify-center">
      <a href="superadmin_dashboard.php" aria-label="Accueil" class="w-16 h-16 flex items-center justify-center">
        <img src="../../img/icone/shiftup-logo.png" alt="ShiftUp Logo" class="w-14 h-14 object-contain" onerror="this.style.display='none'">
      </a>
    </div>
    <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-20 md:pl-64 pr-6">
      <nav class="hidden md:flex items-center gap-8">
        <a href="super_admin_shift_manager.php" class="text-white font-bold hover:text-orange-200 transition">Shift Manager</a>
        <a href="superadmin_gestion.php" class="text-white hover:text-orange-200 transition">Gestion</a>
        <a href="super_admin_profile.php" class="w-10 h-10 rounded-full border-2 border-white flex items-center justify-center hover:bg-white/20 transition">
          <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.5" fill="none"/>
            <path d="M6 20c0-3 4-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
          </svg>
        </a>
      </nav>
      <button class="md:hidden ml-2 p-2" aria-label="Ouvrir le menu">
        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      </button>
    </div>
  </header>

  <div class="max-w-screen-2xl mx-auto p-8">
    <h1 class="text-3xl font-light" style="color:#FF4800">Bienvenue Super-admin</h1>
  </div>

  <main class="max-w-screen-2xl mx-auto px-8 pb-12">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">

      <div class="flex flex-col gap-6">
        <section class="bg-gray-50 card-radius p-6 flex flex-col items-center border border-orange-100">
          <div class="chart-bg card-radius w-full h-64 md:h-[22rem] p-4">
            <canvas id="trendChart" class="w-full h-full"></canvas>
          </div>
          <h2 class="mt-6 text-2xl" style="color:#FF4800">Empreinte carbone — Tendance</h2>
        </section>
        <a href="?export=1" id="exportBtn" onclick="animateExport(event, this)" class="block btn-orange text-white text-center py-4 text-xl shadow-lg hover:scale-[1.01] transition">
          Export des données
        </a>
        <a href="superadmin_entreprise.php" class="block bg-gray-50 border border-orange-200 text-center py-4 rounded-full-xl text-xl hover:bg-orange-50 transition" style="color:#FF4800">
          Créer une entreprise
        </a>
      </div>

      <div class="flex flex-col gap-6">
        <aside class="bg-gray-50 card-radius p-6 flex flex-col md:flex-row items-center md:items-start gap-8 border border-orange-100">
          <div class="w-52 h-52 rounded-full bg-white flex items-center justify-center shadow flex-shrink-0 border-4" style="border-color:#FF4800">
            <canvas id="pieChartLarge" width="200" height="200"></canvas>
          </div>
          <div class="flex-1 space-y-4 w-full flex flex-col justify-center mt-4 md:mt-0">
            <div class="kpi-pill rounded-full-xl px-6 py-3 text-right shadow truncate"><?= e($kpis['shifter_moyen']['name']) . ' : ' . e($kpis['shifter_moyen']['value']) ?></div>
            <div class="kpi-pill rounded-full-xl px-6 py-3 text-right shadow truncate"><?= e($kpis['top_shifter']['name']) . ' : ' . e($kpis['top_shifter']['value']) ?></div>
            <div class="kpi-pill rounded-full-xl px-6 py-3 text-right shadow truncate"><?= e($kpis['top_department']['name']) . ' : ' . e($kpis['top_department']['value']) ?></div>
          </div>
        </aside>

        <div class="space-y-4">
          <?php foreach ($objectives as $obj): ?>
            <div class="objective-pill rounded-full-xl px-6 py-4 text-center text-lg hover:bg-orange-50 transition cursor-default">
              <?= e($obj) ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

    <?php if ($dbError): ?>
      <div class="mt-8 p-4 bg-red-50 border border-red-200 text-red-700 rounded"><strong>Erreur connexion DB :</strong> <?= e($dbError) ?></div>
    <?php endif; ?>
  </main>

  <script>
  function animateExport(e, btn) {
    const orig = btn.innerHTML;
    btn.innerHTML = '⏳ Export en cours…';
    btn.style.background = '#cc3a00';
    setTimeout(() => {
      btn.innerHTML = '✅ Téléchargement lancé';
      btn.style.background = '#22c55e';
      setTimeout(() => { btn.innerHTML = orig; btn.style.background = '#FF4800'; }, 3000);
    }, 1000);
  }

  const pieLabels = <?= json_encode($pieLabels, JSON_UNESCAPED_UNICODE) ?>;
  const pieValues = <?= json_encode($pieValues) ?>;
  const trendLabels = <?= json_encode($trendLabels) ?>;
  const trendValues = <?= json_encode($trendValues) ?>;

  function orangePalette(n) {
    const out = [];
    for (let i = 0; i < n; i++) {
      const t = n === 1 ? 0.5 : i / (n - 1);
      const r = Math.round(255 - t * 60);
      const g = Math.round(72 + t * 80);
      const b = Math.round(t * 60);
      out.push(`rgb(${r},${g},${b})`);
    }
    return out;
  }

  new Chart(document.getElementById('pieChartLarge').getContext('2d'), {
    type: 'pie',
    data: { labels: pieLabels, datasets: [{ data: pieValues, backgroundColor: orangePalette(pieValues.length), borderColor: '#fff', borderWidth: 2 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
  });

  new Chart(document.getElementById('trendChart').getContext('2d'), {
    type: 'line',
    data: {
      labels: trendLabels,
      datasets: [{ label: 'Empreinte carbone (kg CO₂)', data: trendValues, fill: true, tension: 0.3, pointRadius: 4, backgroundColor: 'rgba(255,72,0,0.12)', borderColor: '#FF4800', pointBackgroundColor: '#FF4800' }]
    },
    options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { display: false } }, scales: { x: { display: true }, y: { display: true, beginAtZero: true } } }
  });
  </script>
</body>
</html>