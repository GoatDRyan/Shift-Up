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
    } catch (Exception $e) {}
}

if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    try {
        if ($q !== '') {
            $stmt = $pdo->prepare(
                "SELECT u.id, u.pseudo, u.email, u.last_activity, u.points_wallet, u.points_rank, u.est_actif, d.nom AS department
                 FROM users u
                 LEFT JOIN departments d ON u.department_id = d.id
                 WHERE u.pseudo LIKE :q OR u.email LIKE :q
                 ORDER BY u.pseudo ASC
                 LIMIT 200"
            );
            $like = (mb_strlen($q) <= 2) ? $q . '%' : '%' . $q . '%';
            $stmt->execute([':q' => $like]);
        } else {
            $stmt = $pdo->prepare(
                "SELECT u.id, u.pseudo, u.email, u.last_activity, u.points_wallet, u.points_rank, u.est_actif, d.nom AS department
                 FROM users u
                 LEFT JOIN departments d ON u.department_id = d.id
                 ORDER BY u.id ASC
                 LIMIT 200"
            );
            $stmt->execute();
        }
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'users' => $users]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'users' => []]);
    }
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.pseudo, u.email, u.last_activity, u.points_wallet, u.points_rank, u.est_actif, d.nom AS department
         FROM users u
         LEFT JOIN departments d ON u.department_id = d.id
         ORDER BY u.id ASC
         LIMIT 200"
    );
    $stmt->execute();
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
    .card-radius { border-radius: 16px; }
    .btn-pill { border-radius: 999px; padding: 0.6rem 1.2rem; transition: all 0.3s; }
    .btn-pill:hover { opacity: 0.9; transform: translateY(-1px); }
    .action-pill { padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; }
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #FF4800; border-radius: 10px; }
    #searchInput::placeholder { color: #FF4800; opacity: 0.6; }
    .spinner { display: none; width: 20px; height: 20px; border: 3px solid #ffd0bb; border-top-color: #FF4800; border-radius: 50%; animation: spin .6s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>

<body class="bg-white font-sans antialiased">

<header class="bg-[#FF4800] h-16 relative shadow-md">
  <div class="absolute left-0 top-0 bottom-0 w-24 md:w-72 bg-black/10 flex items-center justify-center">
    <a href="admin_dashboard.php" aria-label="Accueil" class="w-16 h-16 flex items-center justify-center">
        <img src="../../img/icone/shiftup-logo.png" alt="ShiftUp Logo" class="w-14 h-14 object-contain">
    </a>
</div>
  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-20 md:pl-64 pr-6">
    <nav class="hidden md:flex items-center gap-10">
      <a href="admin_shift_manager.php" class="text-white font-semibold hover:opacity-80 transition-opacity">Shift Manager</a>
      <a href="admin_gestion.php" class="text-white font-semibold hover:opacity-80 transition-opacity border-b-2 border-white/30">Gestion</a>
      <a href="admin_profile.php" class="w-10 h-10 rounded-full border-2 border-white flex items-center justify-center hover:bg-white/10 transition-all">
        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M6 20c0-3 4-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
    </nav>
  </div>
</header>

<main class="max-w-screen-2xl mx-auto pl-20 md:pl-64 pr-6 py-10">
  <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-4">
    <h1 class="text-4xl font-bold text-gray-900">Gestion <span class="text-[#FF4800]">Utilisateurs</span></h1>
    <a id="exportBtn" href="?export=1" class="inline-flex items-center btn-pill bg-[#FF4800] text-white shadow-lg hover:shadow-[#FF4800]/30 font-medium">
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M16 9l-4-4m0 0L8 9m4-4v12"/></svg>
      Exporter en CSV
    </a>
  </div>

  <div class="bg-gray-50 border border-gray-100 p-8 card-radius shadow-sm">

    <!-- Search bar -->
    <div class="mb-10">
      <div class="relative max-w-2xl mx-auto">
        <label for="searchInput" class="absolute left-5 top-1/2 -translate-y-1/2 text-[#FF4800]">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
        </label>
        <input id="searchInput" placeholder="Rechercher par pseudo ou email (dès la 1ère lettre)..." autocomplete="off"
               class="w-full bg-white border-2 border-[#FF4800] rounded-full h-14 pl-14 pr-14 text-gray-800 focus:ring-4 focus:ring-[#FF4800]/10 focus:outline-none transition-all" />
        <div id="searchSpinner" class="spinner absolute right-5 top-1/2 -translate-y-1/2"></div>
      </div>
    </div>

    <!-- Table -->
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
            <?php $this_maxxp = 3000; ?>
            <?php if (count($users) === 0): ?>
              <div class="px-4 py-16 text-center text-gray-400 font-medium">Aucun utilisateur trouvé dans la base.</div>
            <?php else: ?>
              <?php foreach ($users as $u):
                $last   = $u['last_activity'] ? date('d/m/Y', strtotime($u['last_activity'])) : 'Jamais';
                $dept   = $u['department'] ?: 'Non assigné';
                $points = (int)$u['points_wallet'];
                $pct    = min(100, $this_maxxp ? intval($points / $this_maxxp * 100) : 0);
              ?>
              <?php echo buildUserRow($u, $last, $dept, $points, $pct, $this_maxxp); ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php
function buildUserRow(array $u, string $last, string $dept, int $points, int $pct, int $maxxp): string {
    $id   = (int)$u['id'];
    $ds_pseudo = htmlspecialchars($u['pseudo'] ?: 'Anonyme', ENT_QUOTES);
    $ds_email  = htmlspecialchars($u['email']  ?? '', ENT_QUOTES);
    $ds_dept   = htmlspecialchars($dept, ENT_QUOTES);
    return <<<HTML
    <div class="grid grid-cols-1 md:grid-cols-7 items-center py-5 px-6 border-b border-gray-100 hover:bg-gray-50 transition-colors">
      <div class="col-span-1 text-sm font-mono text-gray-400">#{$id}</div>
      <div class="col-span-2">
        <div class="text-sm font-bold text-gray-900">{$ds_pseudo}</div>
        <div class="text-xs text-gray-500">{$ds_email}</div>
      </div>
      <div class="col-span-1">
        <span class="px-2 py-1 text-[10px] font-bold bg-gray-100 text-gray-600 rounded uppercase">{$ds_dept}</span>
      </div>
      <div class="col-span-1 text-center text-xs text-gray-600 font-medium">{$last}</div>
      <div class="col-span-1 pr-4">
        <div class="flex justify-between items-center mb-1.5">
          <span class="text-[10px] font-bold text-gray-500">{$points} / {$maxxp}</span>
          <span class="text-[10px] font-bold text-[#FF4800]">{$pct}%</span>
        </div>
        <div class="w-full bg-gray-100 h-1.5 rounded-full">
          <div class="h-1.5 rounded-full transition-all duration-500" style="width:{$pct}%;background:#FF4800;"></div>
        </div>
      </div>
      <div class="col-span-1 flex items-center justify-center gap-2">
        <a href="admin_ban.php?id={$id}" class="action-pill rounded-lg border-2 border-orange-100 bg-orange-50 text-[#FF4800] hover:bg-[#FF4800] hover:text-white transition-all flex-1 text-center py-2">Bannir</a>
        <form method="post" action="admin_delete.php" onsubmit="return confirm('Supprimer définitivement l\\'utilisateur #{$id} ?');" class="flex-1">
          <input type="hidden" name="id" value="{$id}">
          <button type="submit" class="action-pill rounded-lg bg-gray-800 text-white border-2 border-gray-800 hover:bg-black transition-all w-full py-2">Suppr.</button>
        </form>
      </div>
    </div>
HTML;
}
?>

<script>
(function(){
  const searchInput = document.getElementById('searchInput');
  const usersList   = document.getElementById('usersList');
  const spinner     = document.getElementById('searchSpinner');
  const MAXXP       = 3000;
  let debounce;

  function renderUsers(users) {
    if (!users || users.length === 0) {
      usersList.innerHTML = '<div class="px-4 py-16 text-center text-gray-400 font-medium">Aucun utilisateur trouvé.</div>';
      return;
    }
    usersList.innerHTML = users.map(u => {
      const id      = u.id;
      const pseudo  = escHtml(u.pseudo || 'Anonyme');
      const email   = escHtml(u.email  || '');
      const dept    = escHtml(u.department || 'Non assigné');
      const last    = u.last_activity ? formatDate(u.last_activity) : 'Jamais';
      const points  = parseInt(u.points_wallet) || 0;
      const pct     = Math.min(100, MAXXP ? Math.floor(points / MAXXP * 100) : 0);

      return `
      <div class="grid grid-cols-1 md:grid-cols-7 items-center py-5 px-6 border-b border-gray-100 hover:bg-gray-50 transition-colors">
        <div class="col-span-1 text-sm font-mono text-gray-400">#${id}</div>
        <div class="col-span-2">
          <div class="text-sm font-bold text-gray-900">${pseudo}</div>
          <div class="text-xs text-gray-500">${email}</div>
        </div>
        <div class="col-span-1">
          <span class="px-2 py-1 text-[10px] font-bold bg-gray-100 text-gray-600 rounded uppercase">${dept}</span>
        </div>
        <div class="col-span-1 text-center text-xs text-gray-600 font-medium">${last}</div>
        <div class="col-span-1 pr-4">
          <div class="flex justify-between items-center mb-1.5">
            <span class="text-[10px] font-bold text-gray-500">${points} / ${MAXXP}</span>
            <span class="text-[10px] font-bold text-[#FF4800]">${pct}%</span>
          </div>
          <div class="w-full bg-gray-100 h-1.5 rounded-full">
            <div class="h-1.5 rounded-full" style="width:${pct}%;background:#FF4800;"></div>
          </div>
        </div>
        <div class="col-span-1 flex items-center justify-center gap-2">
          <a href="admin_ban.php?id=${id}" class="action-pill rounded-lg border-2 border-orange-100 bg-orange-50 text-[#FF4800] hover:bg-[#FF4800] hover:text-white transition-all flex-1 text-center py-2">Bannir</a>
          <form method="post" action="admin_delete.php" onsubmit="return confirm('Supprimer définitivement l\\'utilisateur #${id} ?');" class="flex-1">
            <input type="hidden" name="id" value="${id}">
            <button type="submit" class="action-pill rounded-lg bg-gray-800 text-white border-2 border-gray-800 hover:bg-black transition-all w-full py-2">Suppr.</button>
          </form>
        </div>
      </div>`;
    }).join('');
  }

  function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function formatDate(str) {
    const d = new Date(str);
    if (isNaN(d)) return str;
    return d.toLocaleDateString('fr-FR');
  }

  searchInput.addEventListener('input', function() {
    clearTimeout(debounce);
    const q = this.value.trim();
    debounce = setTimeout(() => fetchUsers(q), 200);  
  });

  function fetchUsers(q) {
    spinner.style.display = 'block';
    const url = '?ajax=1&q=' + encodeURIComponent(q);
    fetch(url, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(data => {
        spinner.style.display = 'none';
        renderUsers(data.users || []);
      })
      .catch(() => { spinner.style.display = 'none'; });
  }

  const exportBtn = document.getElementById('exportBtn');
  if (exportBtn) {
    exportBtn.addEventListener('click', function(e) {
      e.preventDefault();
      const url = this.getAttribute('href');
      Swal.fire({ title: 'Export en cours', html: 'Veuillez patienter...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });
      fetch(url, { method: 'GET', credentials: 'same-origin' })
        .then(r => { if (!r.ok) throw new Error(); return r.blob().then(blob => ({ blob })); })
        .then(({ blob }) => {
          const a = document.createElement('a');
          a.href = window.URL.createObjectURL(blob);
          a.download = 'users_export.csv';
          document.body.appendChild(a); a.click(); a.remove();
          Swal.fire({ title: 'Export réussi', icon: 'success', confirmButtonColor: '#FF4800' });
        })
        .catch(() => Swal.fire({ title: 'Erreur', text: 'Impossible de générer l\'export', icon: 'error' }));
    });
  }
})();
</script>
</body>
</html>