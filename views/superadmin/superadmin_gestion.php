<?php
session_start();
require_once '../../config/db_connect.php';

if (isset($_GET['export']) && $_GET['export'] == '1') {
    try {
        $stmt = $pdo->prepare("SELECT id, nom, secteur, code_invite, total_xp, total_carbon_saved, created_at FROM companies ORDER BY id ASC");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=companies_export.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['id','nom','secteur','code_invite','total_xp','total_carbon_saved','created_at']);
        foreach ($rows as $r) fputcsv($out, [$r['id'],$r['nom'],$r['secteur'],$r['code_invite'],$r['total_xp'],$r['total_carbon_saved'],$r['created_at']]);
        fclose($out); exit;
    } catch (Exception $e) {}
}

if (isset($_GET['ajax_users'])) {
    header('Content-Type: application/json; charset=utf-8');
    $q = trim($_GET['q'] ?? '');
    $users = [];
    if ($pdo && $q !== '') {
        try {
            $stmt = $pdo->prepare("SELECT u.id, u.pseudo, u.email, u.role, u.est_actif, c.nom AS company_nom FROM users u LEFT JOIN companies c ON u.company_id = c.id WHERE (u.pseudo LIKE :q OR u.email LIKE :q) ORDER BY u.pseudo ASC, u.email ASC LIMIT 50");
            $stmt->execute([':q' => $q . '%']);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }
    echo json_encode(['users' => $users]);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT id, nom, secteur, total_xp FROM companies ";
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
} catch (Exception $e) { $companies = []; }
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Super Admin - Gestion</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root { --orange: #FF4800; }
    body { background: #fff; }
    .rounded-full-xl { border-radius: 999px; }
    .card-radius { border-radius: 12px; }
    .header-bg { background: #FF4800; }
    .btn-orange { background: #FF4800; color: #fff; border-radius: 999px; }
    .btn-orange:hover { background: #cc3a00; }
    .pill-search { background: #fff3ee; border: 1px solid #ffd6c2; color: #FF4800; }
    .tab-btn { border-radius: 999px; padding: .5rem 1.5rem; font-weight: 600; transition: background .2s, color .2s; cursor: pointer; }
    .tab-active { background: #FF4800; color: #fff; }
    .tab-inactive { background: #f5f5f5; color: #555; }
    .tab-inactive:hover { background: #fff3ee; color: #FF4800; }
    .user-row { border-radius: 10px; transition: background .15s; }
    .user-row:hover { background: #fff3ee; }
    .badge-admin { background:#fff3ee; color:#FF4800; }
    .badge-super { background:#FF4800; color:#fff; }
    .badge-shifter { background:#f0fdf4; color:#16a34a; }
    .xbar { height: 8px; border-radius: 999px; background: linear-gradient(90deg, #FF4800, #ff9a6c); }
  </style>
</head>
<body class="bg-white">

<header class="header-bg h-16 relative">
  <a href="superadmin_dashboard.php" class="absolute left-0 top-0 bottom-0 w-20 md:w-64 bg-black/20 flex items-center justify-center">
    <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M12 2L4 5v6c0 5 3.5 9.7 8 11 4.5-1.3 8-6 8-11V5l-8-3z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" stroke-linecap="round" fill="none"/>
      <text x="12" y="15.3" text-anchor="middle" font-size="9" font-family="Segoe UI, Roboto, Arial, sans-serif" fill="white" style="font-weight:700">S</text>
    </svg>
  </a>
  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-20 md:pl-64 pr-6">
    <nav class="hidden md:flex items-center gap-8">
      <a href="super_admin_shift_manager.php" class="text-white font-bold hover:text-orange-200 transition">Shift Manager</a>
      <a href="superadmin_gestion.php" class="text-white hover:text-orange-200 transition">Gestion</a>
      <a href="super_admin_profile.php" class="w-10 h-10 rounded-full border-2 border-white flex items-center justify-center hover:bg-white/20 transition">
        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.5" fill="none"/>
          <path d="M6 20c0-3 4-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        </svg>
      </a>
    </nav>
    <button class="md:hidden ml-2 p-2" aria-label="Menu">
      <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    </button>
  </div>
</header>

<main class="max-w-screen-2xl mx-auto pl-6 md:pl-20 pr-6 py-8">

  <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <h1 class="text-3xl font-medium" style="color:#FF4800">Gestion</h1>
    <div class="flex items-center gap-3 flex-wrap">
      <a href="superadmin_entreprise.php" class="btn-orange px-5 py-2 text-sm font-semibold shadow hover:scale-[1.02] transition">+ Ajouter une entreprise</a>
      <a href="?export=1" class="bg-white border border-orange-200 px-5 py-2 rounded-full-xl text-sm font-semibold hover:bg-orange-50 transition" style="color:#FF4800">Export CSV</a>
    </div>
  </div>

  <div class="flex gap-3 mb-6">
    <button class="tab-btn tab-active" onclick="switchTab('entreprises', this)">Entreprises</button>
    <button class="tab-btn tab-inactive" onclick="switchTab('utilisateurs', this)">Utilisateurs</button>
  </div>

  <div id="tab-entreprises" class="bg-gray-50 p-6 card-radius border border-orange-100">

    <form method="get" class="mb-6">
      <div class="relative max-w-2xl">
        <label for="q" class="absolute left-4 top-1/2 -translate-y-1/2" style="color:#FF4800">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
        </label>
        <input id="q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Rechercher une entreprise" class="w-full pill-search rounded-full-xl h-12 pl-12 pr-6 focus:outline-none focus:ring-2 focus:ring-orange-300" />
      </div>
    </form>

    <div class="bg-white card-radius p-6 shadow-sm">
      <div class="hidden md:grid grid-cols-6 gap-4 text-sm text-gray-500 px-4 pb-4 border-b border-orange-100">
        <div class="col-span-1">Id</div>
        <div class="col-span-2">Nom entreprise</div>
        <div class="col-span-1">Secteur</div>
        <div class="col-span-1 text-right">Score XP</div>
        <div class="col-span-1 text-right">Action</div>
      </div>

      <?php if (count($companies) === 0): ?>
        <div class="px-4 py-12 text-center text-gray-400">Aucune entreprise trouvée.</div>
      <?php else: ?>
        <?php foreach ($companies as $c):
          $points = (int)$c['total_xp'];
          $maxxp = 50000;
          $pct = $maxxp ? min(100, intval($points / $maxxp * 100)) : 0;
        ?>
        <div class="grid grid-cols-1 md:grid-cols-6 items-center py-5 border-b last:border-b-0 hover:bg-orange-50/50 transition rounded-lg px-2">
          <div class="col-span-1 text-sm text-gray-500 px-2">#<?= htmlspecialchars($c['id']) ?></div>
          <div class="col-span-2 px-2 font-semibold" style="color:#FF4800"><?= htmlspecialchars($c['nom']) ?></div>
          <div class="col-span-1 px-2 text-gray-600 text-sm"><?= htmlspecialchars($c['secteur'] ?: '—') ?></div>
          <div class="col-span-1 px-2 text-right md:pr-4">
            <div class="text-sm font-semibold" style="color:#FF4800"><?= number_format($points, 0, ',', ' ') ?> XP</div>
            <div class="w-full bg-gray-100 h-2 rounded-full mt-1.5"><div class="xbar" style="width:<?= $pct ?>%"></div></div>
          </div>
          <div class="col-span-1 px-2 flex justify-center">
            <form method="post" action="admin_delete.php" onsubmit="return confirm('Supprimer l\'entreprise #<?= htmlspecialchars(addslashes($c['id'])) ?> ?');">
              <input type="hidden" name="id" value="<?= htmlspecialchars($c['id']) ?>">
              <input type="hidden" name="type" value="company">
              <button type="submit" class="px-4 py-2 rounded-full-xl text-sm bg-gray-100 text-gray-600 hover:bg-red-100 hover:text-red-600 transition border border-gray-200">Supprimer</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div id="tab-utilisateurs" class="hidden bg-gray-50 p-6 card-radius border border-orange-100">
    <div class="mb-6">
      <div class="relative max-w-2xl">
        <label for="user_search" class="absolute left-4 top-1/2 -translate-y-1/2" style="color:#FF4800">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
        </label>
        <input id="user_search" placeholder="Rechercher par pseudo ou email (saisir la 1ère lettre…)" class="w-full pill-search rounded-full-xl h-12 pl-12 pr-6 focus:outline-none focus:ring-2 focus:ring-orange-300" autocomplete="off" />
      </div>
      <p class="text-xs text-gray-400 mt-2 ml-2">La recherche s'active dès la 1ère lettre saisie.</p>
    </div>

    <div id="userResults" class="bg-white card-radius p-6 shadow-sm min-h-[100px]">
      <div class="text-center text-gray-400 py-8" id="userPlaceholder">Commencez à taper pour rechercher des utilisateurs.</div>
      <div id="userTable" class="hidden">
        <div class="hidden md:grid grid-cols-5 gap-4 text-sm text-gray-500 px-4 pb-3 border-b border-orange-100">
          <div class="col-span-1">Id</div>
          <div class="col-span-1">Pseudo</div>
          <div class="col-span-1">Email</div>
          <div class="col-span-1">Rôle</div>
          <div class="col-span-1">Entreprise</div>
        </div>
        <div id="userList" class="space-y-1 mt-2"></div>
      </div>
      <div id="userEmpty" class="hidden text-center text-gray-400 py-8">Aucun utilisateur trouvé.</div>
    </div>
  </div>

</main>

<script>
function switchTab(tab, btn) {
    document.getElementById('tab-entreprises').classList.toggle('hidden', tab !== 'entreprises');
    document.getElementById('tab-utilisateurs').classList.toggle('hidden', tab !== 'utilisateurs');
    document.querySelectorAll('.tab-btn').forEach(b => {
      b.className = 'tab-btn ' + (b === btn ? 'tab-active' : 'tab-inactive');
    });
  }

  const userSearch = document.getElementById('user_search');
  const userPlaceholder = document.getElementById('userPlaceholder');
  const userTable = document.getElementById('userTable');
  const userList = document.getElementById('userList');
  const userEmpty = document.getElementById('userEmpty');
  let searchTimeout;

  const roleLabels = { super_admin: 'Super Admin', admin: 'Admin', shifter: 'Shifter' };
  const roleBadge = { super_admin: 'badge-super', admin: 'badge-admin', shifter: 'badge-shifter' };

  userSearch.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (!q) {
      userPlaceholder.classList.remove('hidden');
      userTable.classList.add('hidden');
      userEmpty.classList.add('hidden');
      return;
    }
    searchTimeout = setTimeout(() => fetchUsers(q), 150);
  });

  async function fetchUsers(q) {
    try {
      const res = await fetch('?ajax_users=1&q=' + encodeURIComponent(q));
      const data = await res.json();
      const users = data.users || [];
      userPlaceholder.classList.add('hidden');

      if (!users.length) {
        userTable.classList.add('hidden');
        userEmpty.classList.remove('hidden');
        return;
      }
      userEmpty.classList.add('hidden');
      userTable.classList.remove('hidden');
      userList.innerHTML = users.map(u => {
        const role = u.role || 'shifter';
        const badge = roleBadge[role] || 'badge-shifter';
        const label = roleLabels[role] || role;
        const actif = parseInt(u.est_actif) === 1;
        return `<div class="user-row grid grid-cols-5 gap-4 items-center py-3 px-4 rounded-lg">
          <div class="col-span-1 text-sm text-gray-400">#${escHtml(u.id)}</div>
          <div class="col-span-1 font-medium" style="color:#FF4800">${escHtml(u.pseudo || '—')}</div>
          <div class="col-span-1 text-sm text-gray-600 truncate">${escHtml(u.email)}</div>
          <div class="col-span-1"><span class="text-xs px-2 py-0.5 rounded-full font-semibold ${badge}">${label}</span></div>
          <div class="col-span-1 text-sm text-gray-500">${escHtml(u.company_nom || '—')}</div>
        </div>`;
      }).join('');
    } catch (e) { console.error(e); }
  }

  function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
</script>

</body>
</html>