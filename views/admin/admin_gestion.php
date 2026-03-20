<?php
session_start();
require_once '../../config/db_connect.php';

if (isset($_GET['export']) && $_GET['export'] == '1' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    try {
        $stmt = $pdo->prepare("SELECT u.id, u.pseudo, u.email, u.last_activity, u.points_wallet, u.points_rank, d.nom AS department
                               FROM users u
                               LEFT JOIN departments d ON u.department_id = d.id
                               ORDER BY u.id ASC");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=users_export.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['id','pseudo','email','department','last_activity','points_wallet','points_rank']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'],$r['pseudo'],$r['email'],$r['department'],$r['last_activity'],$r['points_wallet'],$r['points_rank']]);
        }
        fclose($out);
        exit;
    } catch (Exception $e) {
    }
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT u.id, u.pseudo, u.email, u.last_activity, u.points_wallet, u.points_rank, u.est_actif, d.nom AS department
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id ";
$params = [];
if ($q !== '') {
    $sql .= "WHERE u.pseudo LIKE :q OR u.email LIKE :q ";
    $params[':q'] = "%$q%";
}
$sql .= "ORDER BY u.id ASC LIMIT 200";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $users = [];
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin - Gestion utilisateurs</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .rounded-full-xl { border-radius: 999px; }
    .card-radius { border-radius: 12px; }
    .pill-search { background: #9b9b9b; } 
    .pill-export { background: #e9e9e9; } 
    .btn-pill { border-radius: 999px; padding-left: 0.9rem; padding-right: 0.9rem; }
    .action-pill { padding: 0.35rem 0.9rem; font-size: 0.85rem; }
    /* Ensure search placeholder is readable */
    input::placeholder { opacity: 0.8; color:#000; }
  </style>
</head>

<header class="bg-gray-200 h-16 relative">
  <div class="absolute left-0 top-0 bottom-0 w-20 md:w-64 bg-gray-400 flex items-center justify-center">
    <div class="w-10 h-10 flex items-center justify-center" aria-hidden="true">
      <a href="admin/admin_dashboard.php" aria-label="Aller au dashboard">
        <svg class="w-6 h-6 text-gray-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" role="img">
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

<body class="bg-white">
  <main class="max-w-screen-2xl mx-auto pl-20 md:pl-64 pr-6 py-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-medium text-gray-800">Gestion utilisateur</h1>
      <div class="flex items-center gap-4">
        <a id="exportBtn" href="?export=1" class="inline-flex items-center pill-export btn-pill text-gray-800 shadow-sm border border-gray-200">
          Export des données
        </a>
      </div>
    </div>

   <div class="bg-gray-200 p-6 card-radius">
      <form id="searchForm" method="get" class="mb-6" action="admin_gestion.php">
        <div class="relative max-w-4xl mx-auto">
          <label for="q" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-900">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11" cy="11" r="7"></circle>
              <path d="M21 21l-4.35-4.35"></path>
            </svg>
          </label>
          <input id="q" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Rechercher une personne" autocomplete="off"
                 class="w-full pill-search rounded-full-xl h-14 pl-14 pr-6 text-black placeholder-black/70 focus:outline-none" />
        </div>
      </form>

      <div class="bg-white card-radius p-6 shadow-inner overflow-hidden">
        <div class="space-y-6">
          <div class="hidden md:grid grid-cols-7 gap-4 text-sm text-gray-600 px-4 pb-4 border-b">
            <div class="col-span-1">Id</div>
            <div class="col-span-2">Nom-Prénom</div>
            <div class="col-span-1">Département</div>
            <div class="col-span-1 text-center">Dernière connexion</div>
            <div class="col-span-1 text-right">Score/xp</div>
            <div class="col-span-1 text-right">Action</div>
          </div>

          <div id="usersList" class="space-y-0">
            <?php if (count($users) === 0): ?>
              <div id="noResults" class="px-4 py-12 text-center text-gray-500">Aucun utilisateur trouvé.</div>
            <?php else: ?>
              <?php foreach ($users as $u):
                $last = $u['last_activity'] ? date('d/m/Y', strtotime($u['last_activity'])) : '-';
                $department = $u['department'] ? $u['department'] : '—';
                $points = (int)$u['points_wallet'];
                $maxxp = 3000;
                $pct = min(100, $maxxp ? intval($points / $maxxp * 100) : 0);
                $ds_pseudo = htmlspecialchars($u['pseudo'] ?: $u['email'], ENT_QUOTES);
                $ds_email = htmlspecialchars($u['email'] ?? '', ENT_QUOTES);
                $ds_dept = htmlspecialchars($department, ENT_QUOTES);
              ?>
<div class="grid grid-cols-1 md:grid-cols-7 items-center py-6 border-b last:border-b-0 user-row"
     data-pseudo="<?php echo $ds_pseudo; ?>"
     data-email="<?php echo $ds_email; ?>"
     data-department="<?php echo $ds_dept; ?>">

  <div class="col-span-1 text-sm text-gray-800 px-4"><?php echo htmlspecialchars($u['id']); ?></div>

  <div class="col-span-2 px-4">
    <div class="text-gray-800"><?php echo htmlspecialchars($u['pseudo'] ?: $u['email']); ?></div>
    <div class="text-xs text-gray-400"><?php echo htmlspecialchars($u['email']); ?></div>
  </div>

  <div class="col-span-1 px-4 text-gray-700"><?php echo htmlspecialchars($department); ?></div>

  <div class="col-span-1 px-4 text-center text-gray-700"><?php echo $last; ?></div>

  <div class="col-span-1 px-4 text-right md:pr-6">
    <div class="text-sm text-gray-700"><?php echo $points . '/' . $maxxp; ?></div>
    <div class="w-full bg-gray-100 h-2 rounded-full mt-2">
      <div class="h-2 rounded-full" style="width: <?php echo $pct; ?>%; background: linear-gradient(90deg,#a3a3a3,#6b6b6b)"></div>
    </div>
  </div>

  <div class="col-span-1 px-4 flex items-center justify-center">
    <div class="flex flex-col items-center gap-3">
      <a href="admin_ban.php?id=<?php echo $u['id']; ?>"
         title="Bannir <?php echo htmlspecialchars($u['pseudo'] ?: $u['email']); ?>"
         class="action-pill rounded-full-xl border border-gray-300 bg-gray-300 text-gray-800 text-sm inline-flex items-center justify-center w-28">
         Bannir
      </a>

      <form method="post" action="admin_delete.php" onsubmit="return confirm('Supprimer l utilisateur #<?php echo $u['id']; ?> ?');" class="w-full flex justify-center">
        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
        <button type="submit"
                title="Supprimer <?php echo htmlspecialchars($u['pseudo'] ?: $u['email']); ?>"
                class="action-pill rounded-full-xl bg-gray-500 text-white text-sm w-28 inline-flex items-center justify-center border border-gray-500">
          Supprimer
        </button>
      </form>
    </div>
  </div>
</div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </div>
  </main>

  <script>
    (function(){
      const searchInput = document.getElementById('q');
      const usersList = document.getElementById('usersList');
      const userRows = usersList ? Array.from(usersList.querySelectorAll('.user-row')) : [];
      const noResults = document.getElementById('noResults');
      const searchForm = document.getElementById('searchForm');
      const exportBtn = document.getElementById('exportBtn');

      function filterUsersClientside(q) {
        const ql = (q || '').trim().toLowerCase();
        let visible = 0;
        userRows.forEach(row => {
          const pseudo = (row.dataset.pseudo || '').toLowerCase();
          const email = (row.dataset.email || '').toLowerCase();
          const dept = (row.dataset.department || '').toLowerCase();
          const hay = `${pseudo} ${email} ${dept}`;
          if (ql === '' || hay.includes(ql)) {
            row.style.display = 'grid';
            visible++;
          } else {
            row.style.display = 'none';
          }
        });
        if (noResults) {
          if (visible === 0) {
            noResults.style.display = 'block';
          } else {
            noResults.style.display = 'none';
          }
        }
      }

      if (searchInput) {
        searchInput.addEventListener('input', (e) => {
          filterUsersClientside(e.target.value);
        });
        searchInput.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') {
            return;
          }
        });
      }

      filterUsersClientside(searchInput ? searchInput.value : '');

      if (exportBtn) {
        exportBtn.addEventListener('click', function(e){
          e.preventDefault();
          const url = this.getAttribute('href') || '?export=1';
          Swal.fire({
            title: 'Préparation de l’export...',
            didOpen: () => {
              Swal.showLoading();
            },
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            showConfirmButton: false
          });

          fetch(url, { method: 'GET', credentials: 'same-origin' })
            .then(response => {
              if (!response.ok) throw new Error('Erreur réseau ' + response.status);
              const disposition = response.headers.get('Content-Disposition') || '';
              let filename = 'export.csv';
              const m = disposition.match(/filename\*=UTF-8''([^;]+)|filename="([^"]+)"|filename=([^;]+)/);
              if (m) {
                filename = decodeURIComponent(m[1] || m[2] || m[3]);
              }
              return response.blob().then(blob => ({ blob, filename }));
            })
            .then(({ blob, filename }) => {
              const url = window.URL.createObjectURL(blob);
              const a = document.createElement('a');
              a.href = url;
              a.download = filename || 'users_export.csv';
              document.body.appendChild(a);
              a.click();
              a.remove();
              window.URL.revokeObjectURL(url);

              Swal.fire({
                title: 'Export réussi',
                text: 'Les données ont bien été téléchargées au format CSV.',
                icon: 'success',
                confirmButtonColor: '#3b82f6',
                confirmButtonText: 'OK'
              });
            })
            .catch(err => {
              console.error(err);
              Swal.fire({
                title: 'Erreur',
                text: 'Impossible de télécharger le CSV. Le téléchargement direct est toujours disponible via le lien.',
                icon: 'error',
                confirmButtonText: 'OK'
              });
            });
        });
      }
    })();
  </script>
</body>
</html>