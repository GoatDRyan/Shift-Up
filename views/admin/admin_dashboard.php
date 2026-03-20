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
    .rounded-full-xl { border-radius: 999px; }
    .card-radius { border-radius: 12px; }
  </style>
</head>
<body class="bg-gray-50 text-gray-900">
<header class="bg-gray-200 h-16 relative">
  <div class="absolute left-0 top-0 bottom-0 w-20 md:w-64 bg-gray-400 flex items-center justify-center">
     <a href="admin/admin_dashboard.php">
  <div class="w-10 h-10 flex items-center justify-center" aria-hidden="true">
      <svg class="w-6 h-6 text-gray-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Logo Shift-Up">
        <path d="M12 2L4 5v6c0 5 3.5 9.7 8 11 4.5-1.3 8-6 8-11V5l-8-3z"
              stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" stroke-linecap="round" fill="none"/>
        <text x="12" y="15.3" text-anchor="middle" font-size="9" font-family="Segoe UI, Roboto, Arial, sans-serif"
              fill="currentColor" style="font-weight:700">S</text>
      </svg>
</a>
    </div>
  </div>

  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-20 md:pl-64 pr-6">
    <nav class="hidden md:flex items-center gap-8">
      <a href="admin/admin_shift_manager.php" class="text-gray-700 hover:text-gray-900">Shift manager</a>
      <a href="admin/admin_gestion.php" class="text-gray-700 hover:text-gray-900">Gestion</a>
      <div class="w-10 h-10 rounded-full border border-gray-800 flex items-center justify-center">
        <svg class="w-6 h-6 text-gray-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.2" fill="none"/>
          <path d="M6 20c0-3 4-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        </svg>
      </div>
    </nav>

    <button class="md:hidden ml-2 p-2 rounded bg-transparent" aria-label="Ouvrir le menu">
      <svg class="w-6 h-6 text-gray-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </button>
  </div>
</header>

  <div class="max-w-screen-2xl mx-auto p-8">
    <h1 class="text-3xl font-light mb-4">Bienvenue Admin - <?= e($companies[0]['nom'] ?? 'Nom de l’entreprise') ?></h1>
  </div>

  <main class="max-w-screen-2xl mx-auto p-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <section class="bg-gray-200 card-radius p-6">
        <div class="bg-gray-400 card-radius h-64 md:h-96 p-4">
          <canvas id="trendChart" class="w-full h-full"></canvas>
        </div>
        <h2 class="mt-6 text-2xl">Empreinte carbone - Tendance</h2>
        <div class="mt-6">
          <a href="?export=1" id="exportBtn" class="block bg-gray-300 hover:bg-gray-400 text-center py-4 rounded-full-xl text-xl shadow cursor-pointer transition-colors">Export des données</a>
        </div>
      </section>

      <aside class="bg-gray-200 card-radius p-6 flex flex-col md:justify-between">
        <div class="flex items-center gap-6">
          <div class="w-44 h-44 rounded-full bg-white flex items-center justify-center shadow">
            <canvas id="pieChartLarge" width="220" height="220"></canvas>
          </div>

          <div class="flex-1 space-y-4">
            <div class="bg-gray-400 rounded-full px-6 py-3 text-right" id="kpi_shifter_moyen"><?= e($kpis['shifter_moyen']['name']).' : '.e($kpis['shifter_moyen']['value']) ?></div>
            <div class="bg-gray-400 rounded-full px-6 py-3 text-right" id="kpi_top_shifter"><?= e($kpis['top_shifter']['name']).' : '.e($kpis['top_shifter']['value']) ?></div>
            <div class="bg-gray-400 rounded-full px-6 py-3 text-right" id="kpi_top_department"><?= e($kpis['top_department']['name']).' : '.e($kpis['top_department']['value']) ?></div>
          </div>
        </div>

        <div class="mt-8 space-y-4">
          <?php foreach($objectives as $obj): ?>
            <div class="bg-gray-400 rounded-full px-6 py-3 text-center"><?= e($obj) ?></div>
          <?php endforeach; ?>
        </div>
      </aside>
    </div>

    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="bg-gray-200 card-radius p-6">
        <h3 class="text-xl mb-4">Répartition des utilisateurs (par département)</h3>
        <div class="bg-gray-400 p-6 rounded-md">
          <ul class="space-y-2 text-white">
            <?php foreach($userDistribution as $row): ?>
              <li class="flex justify-between">
                <span><?= e($row['label']) ?></span>
                <span><?= (int)$row['cnt'] ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <div class="bg-gray-200 card-radius p-6">
        <h3 class="text-xl mb-4">KPIs</h3>
        <div class="space-y-4">
          <div class="bg-white rounded p-4 shadow flex justify-between items-center">
            <div class="text-lg">Nombre d'utilisateurs</div>
            <div class="text-gray-700 font-semibold">
              <?php
                if ($pdo) {
                  $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE ".($companyId ? "company_id = :company_id" : "1=1"));
                  if ($companyId) $stmt->execute([':company_id'=>$companyId]); else $stmt->execute();
                  echo e($stmt->fetchColumn());
                } else echo 'N/A';
              ?>
            </div>
          </div>

          <div class="bg-white rounded p-4 shadow flex justify-between items-center">
            <div class="text-lg">Total CO₂ économisé (kg)</div>
            <div class="text-gray-700 font-semibold">
              <?php
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("SELECT ROUND(COALESCE(SUM(c.co2_kg),0),2) FROM user_actions ua JOIN users u ON ua.user_id = u.id JOIN challenges c ON ua.challenge_id = c.id WHERE ".($companyId ? "u.company_id = :company_id" : "1=1"));
                        if ($companyId) $stmt->execute([':company_id'=>$companyId]); else $stmt->execute();
                        $val = $stmt->fetchColumn();
                        echo e($val ?: '0.00');
                    } catch (Exception $e) {
                        echo '0.00';
                    }
                } else echo 'N/A';
              ?>
            </div>
          </div>

          <div class="bg-white rounded p-4 shadow flex justify-between items-center">
            <div class="text-lg">Total actions</div>
            <div class="text-gray-700 font-semibold">
              <?php
                if ($pdo) {
                  $stmt = $pdo->prepare("SELECT COUNT(ua.id) FROM user_actions ua JOIN users u ON ua.user_id = u.id WHERE ".($companyId ? "u.company_id = :company_id" : "1=1"));
                  if ($companyId) $stmt->execute([':company_id'=>$companyId]); else $stmt->execute();
                  echo e($stmt->fetchColumn() ?: '0');
                } else echo 'N/A';
              ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php if ($dbError): ?>
      <div class="mt-8 p-4 bg-red-100 border border-red-200 text-red-700 rounded">
        <strong>Erreur connexion DB :</strong> <?= e($dbError) ?><br>
        Vérifie les identifiants et que la base est importée.
      </div>
    <?php endif; ?>
  </main>

  <script>
  const pieLabels = <?= json_encode($pieLabels, JSON_UNESCAPED_UNICODE) ?>;
  const pieValues = <?= json_encode($pieValues) ?>;
  const trendLabels = <?= json_encode($trendLabels) ?>;
  const trendValues = <?= json_encode($trendValues) ?>;

  function grayPalette(n) {
    const out = [];
    if (n <= 0) return out;
    const minL = 20;
    const maxL = 78;
    for (let i = 0; i < n; i++) {
      const t = (n === 1) ? 0.5 : (i / (n - 1)); 
      const L = Math.round(minL + t * (maxL - minL));
      out.push('hsl(0, 0%,' + L + '%)');
    }
    return out;
  }

  const pieCtxLarge = document.getElementById('pieChartLarge').getContext('2d');
  new Chart(pieCtxLarge, {
    type: 'pie',
    data: {
      labels: pieLabels,
      datasets: [{
        data: pieValues,
        backgroundColor: grayPalette(pieValues.length),
        borderColor: '#ffffff',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
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
        tension: 0.3,
        pointRadius: 3,
        backgroundColor: 'rgba(59,130,246,0.15)',
        borderColor: 'rgba(59,130,246,1)',
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      scales: {
        x: { display: true },
        y: { display: true, beginAtZero: true }
      }
    }
  });

  document.getElementById('exportBtn').addEventListener('click', function() {
      setTimeout(() => {
          Swal.fire({
              title: 'Export réussi',
              text: 'Les données ont bien été téléchargées au format CSV.',
              icon: 'success',
              confirmButtonColor: '#3b82f6',
              confirmButtonText: 'OK'
          });
      }, 500);
  });
  </script>
</body>
</html>