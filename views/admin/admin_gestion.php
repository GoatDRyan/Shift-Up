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
    :root { --brand-orange: #FF4800; }
    .rounded-full-xl { border-radius: 999px; }
    .card-radius { border-radius: 16px; }
    .pill-search { background: #ffffff; border: 2px solid var(--brand-orange); } 
    .pill-export { background: var(--brand-orange); color: white ! deprivation; } 
    .btn-pill { border-radius: 999px; padding: 0.6rem 1.2rem; transition: all 0.3s; }
    .btn-pill:hover { opacity: 0.9; transform: translateY(-1px); }
    .action-pill { padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; }
    input::placeholder { opacity: 0.6; color: var(--brand-orange); }
    
    /* Custom Scrollbar for better look */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #FF4800; border-radius: 10px; }
  </style>
</head>

<body class="bg-white font-sans antialiased">

<header class="bg-[#FF4800] h-16 relative shadow-md">
  <div class="absolute left-0 top-0 bottom-0 w-20 md:w-64 bg-[#e64100] flex items-center justify-center">
    <div class="w-10 h-10 flex items-center justify-center">
      <a href="admin_dashboard.php" aria-label="Aller au dashboard">
        <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2L4 5v6c0 5 3.5 9.7 8 11 4.5-1.3 8-6 8-11V5l-8-3z"
                stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" fill="none"/>
          <text x="12" y="15.5" text-anchor="middle" font-size="9" font-family="Arial"
                fill="currentColor" style="font-weight:900">S</text>
        </svg>
      </a>
    </div>
  </div>

  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-20 md:pl-64 pr-6">
    <nav class="hidden md:flex items-center gap-10">
      <a href="admin_shift_manager.php" class="text-white font-semibold hover:opacity-80 transition-opacity">Shift Manager</a>
      <a href="admin_gestion.php" class="text-white font-semibold hover:opacity-80 transition-opacity border-b-2 border-white/30">Gestion</a>
      <a href="admin_profile.php" class="w-10 h-10 rounded-full border-2 border-white flex items-center justify-center hover:bg-white/10 transition-all">
        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.5" fill="none"/>
          <path d="M6 20c0-3 4-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        </svg>
      </a>
    </nav>
    <button class="md:hidden ml-2 p-2 text-white" aria-label="Ouvrir le menu">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
    </button>
  </div>
</header>

  <main class="max-w-screen-2xl mx-auto pl-20 md:pl-64 pr-6 py-10">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-4">
      <h1 class="text-4xl font-bold text-gray-900">Gestion <span class="text-[#FF4800]">Utilisateurs</span></h1>
      <div class="flex items-center">
        <a id="exportBtn" href="?export=1" class="inline-flex items-center btn-pill bg-[#FF4800] text-white shadow-lg hover:shadow-[#FF4800]/30 font-medium">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M16 9l-4-4m0 0L8 9m4-4v12"></path></svg>
          Exporter en CSV
        </a>
      </div>
    </div>

   <div class="bg-gray-50 border border-gray-100 p-8 card-radius shadow-sm">
      <form id="searchForm" method="get" class="mb-10" action="admin_gestion.php">
        <div class="relative max-w-2xl mx-auto">
          <label for="q" class="absolute left-5 top-1/2 -translate-y-1/2 text-[#FF4800]">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="7"></circle>
              <path d="M21 21l-4.35-4.35"></path>
            </svg>
          </label>
          <input id="q" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Rechercher par pseudo ou email..." autocomplete="off"
                 class="w-full pill-search rounded-full-xl h-14 pl-14 pr-6 text-gray-800 focus:ring-4 focus:ring-[#FF4800]/10 focus:outline-none transition-all" />
        </div>
      </form>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <div class="min-w-[800px]">
            <div class="hidden md:grid grid-cols-7 gap-4 text-xs font-bold uppercase tracking-wider text-gray-400 px-6 py-4 border-b bg-gray-50/50">
              <div class="col-span-1">ID</div>
              <div class="col-span-2">Utilisateur</div>
              <div class="col-span-1">Département</div>
              <div class="col-span-1 text-center">Connexion</div>
              <div class="col-span-1">Progression XP</div>
              <div class="col-span-1 text-center">Actions</div>
            </div>

            <div id="usersList">
              <?php if (count($users) === 0): ?>
                <div id="noResults" class="px-4 py-16 text-center text-gray-400 font-medium">Aucun utilisateur trouvé dans la base.</div>
              <?php else: ?>
                <?php foreach ($users as $u):
                  $last = $u['last_activity'] ? date('d/m/Y', strtotime($u['last_activity'])) : 'Jamais';
                  $department = $u['department'] ?: 'Non assigné';
                  $points = (int)$u['points_wallet'];
                  $maxxp = 3000;
                  $pct = min(100, $maxxp ? intval($points / $maxxp * 100) : 0);
                  $ds_pseudo = htmlspecialchars($u['pseudo'] ?: $u['email'], ENT_QUOTES);
                  $ds_email = htmlspecialchars($u['email'] ?? '', ENT_QUOTES);
                  $ds_dept = htmlspecialchars($department, ENT_QUOTES);
                ?>
                <div class="grid grid-cols-1 md:grid-cols-7 items-center py-5 px-6 border-b border-gray-100 hover:bg-gray-50 transition-colors user-row"
                     data-pseudo="<?php echo $ds_pseudo; ?>"
                     data-email="<?php echo $ds_email; ?>"
                     data-department="<?php echo $ds_dept; ?>">

                  <div class="col-span-1 text-sm font-mono text-gray-400">#<?php echo htmlspecialchars($u['id']); ?></div>

                  <div class="col-span-2">
                    <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($u['pseudo'] ?: 'Anonyme'); ?></div>
                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($u['email']); ?></div>
                  </div>

                  <div class="col-span-1">
                    <span class="px-2 py-1 text-[10px] font-bold bg-gray-100 text-gray-600 rounded uppercase"><?php echo htmlspecialchars($department); ?></span>
                  </div>

                  <div class="col-span-1 text-center text-xs text-gray-600 font-medium"><?php echo $last; ?></div>

                  <div class="col-span-1 pr-4">
                    <div class="flex justify-between items-center mb-1.5">
                        <span class="text-[10px] font-bold text-gray-500"><?php echo $points; ?> / <?php echo $maxxp; ?></span>
                        <span class="text-[10px] font-bold text-[#FF4800]"><?php echo $pct; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-100 h-1.5 rounded-full">
                      <div class="h-1.5 rounded-full transition-all duration-500" style="width: <?php echo $pct; ?>%; background: #FF4800;"></div>
                    </div>
                  </div>

                  <div class="col-span-1 flex items-center justify-center gap-2">
                    <a href="admin_ban.php?id=<?php echo $u['id']; ?>"
                       class="action-pill rounded-lg border-2 border-orange-100 bg-orange-50 text-[#FF4800] hover:bg-[#FF4800] hover:text-white transition-all flex-1 text-center py-2">
                       Bannir
                    </a>

                    <form method="post" action="admin_delete.php" onsubmit="return confirm('Supprimer définitivement l\'utilisateur #<?php echo $u['id']; ?> ?');" class="flex-1">
                      <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                      <button type="submit" class="action-pill rounded-lg bg-gray-800 text-white border-2 border-gray-800 hover:bg-black transition-all w-full py-2">
                        Suppr.
                      </button>
                    </form>
                  </div>
                </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
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
          noResults.style.display = (visible === 0) ? 'block' : 'none';
        }
      }

      if (searchInput) {
        searchInput.addEventListener('input', (e) => filterUsersClientside(e.target.value));
      }

      if (exportBtn) {
        exportBtn.addEventListener('click', function(e){
          e.preventDefault();
          const url = this.getAttribute('href');
          Swal.fire({
            title: 'Export en cours',
            html: 'Veuillez patienter...',
            didOpen: () => Swal.showLoading(),
            allowOutsideClick: false
          });

          fetch(url, { method: 'GET', credentials: 'same-origin' })
            .then(response => {
              if (!response.ok) throw new Error('Erreur');
              return response.blob().then(blob => ({ blob, filename: 'users_export.csv' }));
            })
            .then(({ blob, filename }) => {
              const url = window.URL.createObjectURL(blob);
              const a = document.createElement('a');
              a.href = url;
              a.download = filename;
              document.body.appendChild(a);
              a.click();
              a.remove();
              Swal.fire({ title: 'Export réussi', icon: 'success', confirmButtonColor: '#FF4800' });
            })
            .catch(() => {
              Swal.fire({ title: 'Erreur', text: 'Impossible de générer l\'export', icon: 'error' });
            });
        });
      }
    })();
  </script>
</body>
</html>