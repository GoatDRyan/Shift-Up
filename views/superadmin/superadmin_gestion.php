<?php 
session_start();
require_once '../../config/db_connect.php';

// Exportation en CSV des entreprises
if (isset($_GET['export']) && $_GET['export'] == '1') {
    try {
        $stmt = $pdo->prepare("SELECT id, nom, secteur, code_invite, total_xp, total_carbon_saved, created_at 
                               FROM companies 
                               ORDER BY id ASC");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=companies_export.csv');
        $out = fopen('php://output', 'w');
        
        // En-têtes du CSV
        fputcsv($out, ['id', 'nom', 'secteur', 'code_invite', 'total_xp', 'total_carbon_saved', 'created_at']);
        
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'], $r['nom'], $r['secteur'], $r['code_invite'], $r['total_xp'], $r['total_carbon_saved'], $r['created_at']]);
        }
        fclose($out);
        exit;
    } catch (Exception $e) {
        // Gérer l'erreur silencieusement ou l'afficher si besoin en dev
    }
}

// Recherche et récupération des entreprises
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT id, nom, secteur, total_xp 
        FROM companies ";
$params = [];

if ($q !== '') {
    $sql .= "WHERE nom LIKE :q OR secteur LIKE :q ";
    $params[':q'] = "%$q%";
}
$sql .= "ORDER BY id ASC LIMIT 200";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $companies = [];
}

?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Super Admin - Gestion entreprise</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .rounded-full-xl { border-radius: 999px; }
    .card-radius { border-radius: 12px; }
    .pill-search { background: #9b9b9b; } 
    .pill-export { background: #e9e9e9; } 
    .btn-pill { border-radius: 999px; padding-left: 0.9rem; padding-right: 0.9rem; }
    .action-pill { padding: 0.35rem 0.9rem; font-size: 0.85rem; }
  </style>
</head>

<body class="bg-white">

<header class="bg-gray-200 h-16 relative">
  <a href="superadmin_dashboard.php" class="absolute left-0 top-0 bottom-0 w-20 md:w-64 bg-gray-400 flex items-center justify-center">
  <div class="w-10 h-10 flex items-center justify-center" aria-hidden="true">
    <svg class="w-6 h-6 text-gray-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Logo Shift-Up">
      <path d="M12 2L4 5v6c0 5 3.5 9.7 8 11 4.5-1.3 8-6 8-11V5l-8-3z"
            stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" stroke-linecap="round" fill="none"/>
      <text x="12" y="15.3" text-anchor="middle" font-size="9" font-family="Segoe UI, Roboto, Arial, sans-serif"
            fill="currentColor" style="font-weight:700">S</text>
    </svg>
  </div>
</a>

  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-20 md:pl-64 pr-6">
    <nav class="hidden md:flex items-center gap-8">
      <a href="super_admin_shift_manager.php" class="text-gray-700 hover:text-gray-900">Shift manager</a>
      <a href="superadmin_gestion.php" class="text-gray-700 hover:text-gray-900">Gestion</a>
     <a href="super_admin_profile.php" class="w-10 h-10 rounded-full border border-gray-800 flex items-center justify-center">
  <svg class="w-6 h-6 text-gray-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.2" fill="none"/>
    <path d="M6 20c0-3 4-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
  </svg>
</a>
    </nav>
    <button class="md:hidden ml-2 p-2 rounded bg-transparent" aria-label="Ouvrir le menu">
      <svg class="w-6 h-6 text-gray-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </button>
  </div>
</header>

  <main class="max-w-screen-2xl mx-auto pl-20 md:pl-64 pr-6 py-8">

    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-medium text-gray-800">Gestion entreprise</h1>
      <div class="flex items-center gap-4">
        <a href="superadmin_entreprise.php" class="inline-flex items-center pill-export btn-pill text-gray-800 shadow-sm border border-gray-200">
          Ajouter une entreprise
        </a>
        <a href="?export=1" class="inline-flex items-center pill-export btn-pill text-gray-800 shadow-sm border border-gray-200">
          Export des données
        </a>
      </div>
    </div>

    <div class="bg-gray-200 p-6 card-radius">

      <form method="get" class="mb-6">
        <div class="relative max-w-4xl mx-auto">
          <label for="q" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-900">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11" cy="11" r="7"></circle>
              <path d="M21 21l-4.35-4.35"></path>
            </svg>
          </label>
          <input id="q" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Rechercher une entreprise" class="w-full pill-search rounded-full-xl h-14 pl-14 pr-6 text-black placeholder-black/70 focus:outline-none" />
        </div>
      </form>

      <div class="bg-white card-radius p-6 shadow-inner overflow-hidden">
        <div class="space-y-6">

          <div class="hidden md:grid grid-cols-6 gap-4 text-sm text-gray-600 px-4 pb-4 border-b">
            <div class="col-span-1">Id</div>
            <div class="col-span-2">Nom entreprise</div>
            <div class="col-span-1">Secteur</div>
            <div class="col-span-1 text-right">Score/xp</div>
            <div class="col-span-1 text-right">Action</div>
          </div>

          <?php if (count($companies) === 0): ?>
            <div class="px-4 py-12 text-center text-gray-500">Aucune entreprise trouvée.</div>
          <?php else: ?>
            <?php foreach ($companies as $c): 
                $points = (int)$c['total_xp'];
                // Augmentation du maxXP à 50000 pour que la jauge des entreprises soit pertinente
                $maxxp = 50000; 
                $pct = $maxxp ? min(100, intval($points / $maxxp * 100)) : 0;
            ?>

            <div class="grid grid-cols-1 md:grid-cols-6 items-center py-6 border-b last:border-b-0">

              <div class="col-span-1 text-sm text-gray-800 px-4"><?php echo htmlspecialchars($c['id']); ?></div>

              <div class="col-span-2 px-4">
                <div class="text-gray-800 font-medium"><?php echo htmlspecialchars($c['nom']); ?></div>
              </div>

              <div class="col-span-1 px-4 text-gray-700"><?php echo htmlspecialchars($c['secteur'] ?: '—'); ?></div>

              <div class="col-span-1 px-4 text-right md:pr-6">
                <div class="text-sm text-gray-700"><?php echo number_format($points, 0, ',', ' ') . ' / ' . number_format($maxxp, 0, ',', ' '); ?></div>
                <div class="w-full bg-gray-100 h-2 rounded-full mt-2">
                  <div class="h-2 rounded-full" style="width: <?php echo $pct; ?>%; background: linear-gradient(90deg,#a3a3a3,#6b6b6b)"></div>
                </div>
              </div>

              <div class="col-span-1 px-4 flex items-center justify-center">
                <form method="post" action="admin_delete.php" onsubmit="return confirm('Supprimer l\\'entreprise #<?php echo htmlspecialchars(addslashes($c['id'])); ?> ?');" class="w-full flex justify-center">
                  <input type="hidden" name="id" value="<?php echo htmlspecialchars($c['id']); ?>">
                  <input type="hidden" name="type" value="company">
                  <button type="submit"
                          title="Supprimer <?php echo htmlspecialchars($c['nom']); ?>"
                          class="action-pill rounded-full-xl bg-gray-500 text-white text-sm w-28 inline-flex items-center justify-center border border-gray-500 hover:bg-red-500 hover:border-red-500 transition-colors">
                    Supprimer
                  </button>
                </form>
              </div>

            </div>

            <?php endforeach; ?>
          <?php endif; ?>

        </div>
      </div>

    </div>

  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function(){});
  </script>
</body>
</html>