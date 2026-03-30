<?php
// 1. SÉCURITÉ & CONNEXION
require_once '../../includes/init.php';

$companyId = (int)$user['company_id'];

// ==========================================
// EXPORT CSV SÉCURISÉ
// ==========================================
if (isset($_GET['export']) && $_GET['export'] == '1' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    try {
        $stmt = $pdo->prepare("SELECT u.pseudo, u.email, u.last_activity, u.points_wallet, u.points_rank, d.nom AS department
                               FROM users u
                               LEFT JOIN departments d ON u.department_id = d.id
                               WHERE u.company_id = ?
                               ORDER BY u.pseudo ASC");
        $stmt->execute([$companyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=employes_export_'.date('Ymd').'.csv');
        $out = fopen('php://output', 'w');
        
        fputs($out, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
        fputcsv($out, ['Pseudo', 'Email', 'Département', 'Dernière Connexion', 'Points', 'XP Global']);
        
        foreach ($rows as $r) {
            fputcsv($out, [$r['pseudo'], $r['email'], $r['department'], $r['last_activity'], $r['points_wallet'], $r['points_rank']]);
        }
        fclose($out);
        exit;
    } catch (Exception $e) {}
}

if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['ajax_action'];
    $targetId = (int)($_POST['user_id'] ?? 0);

    if ($targetId > 0) {
        $check = $pdo->prepare("SELECT id, est_actif FROM users WHERE id = ? AND company_id = ?");
        $check->execute([$targetId, $companyId]);
        $targetUser = $check->fetch();

        if (!$targetUser) {
            echo json_encode(['success' => false, 'error' => 'Utilisateur non trouvé ou non autorisé.']);
            exit;
        }

        if ($targetId === (int)$user['id']) {
            echo json_encode(['success' => false, 'error' => 'Vous ne pouvez pas agir sur votre propre compte.']);
            exit;
        }
    }

    if ($action === 'toggle_status') {
        $nextStatus = $targetUser['est_actif'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE users SET est_actif = ? WHERE id = ?");
        $stmt->execute([$nextStatus, $targetId]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$targetId]);
        echo json_encode(['success' => true]);
        exit;
    }
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
                 WHERE u.company_id = :cid AND (u.pseudo LIKE :q OR u.email LIKE :q)
                 ORDER BY u.pseudo ASC
                 LIMIT 200"
            );
            $like = (mb_strlen($q) <= 2) ? $q . '%' : '%' . $q . '%';
            $stmt->execute([':cid' => $companyId, ':q' => $like]);
        } else {
            $stmt = $pdo->prepare(
                "SELECT u.id, u.pseudo, u.email, u.last_activity, u.points_wallet, u.points_rank, u.est_actif, d.nom AS department
                 FROM users u
                 LEFT JOIN departments d ON u.department_id = d.id
                 WHERE u.company_id = :cid
                 ORDER BY u.pseudo ASC
                 LIMIT 200"
            );
            $stmt->execute([':cid' => $companyId]);
        }
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'users' => $users, 'my_id' => $user['id']]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'users' => []]);
    }
    exit;
}

// ==========================================
// CHARGEMENT INITIAL DE LA PAGE
// ==========================================
try {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.pseudo, u.email, u.last_activity, u.points_wallet, u.points_rank, u.est_actif, d.nom AS department
         FROM users u
         LEFT JOIN departments d ON u.department_id = d.id
         WHERE u.company_id = ?
         ORDER BY u.pseudo ASC
         LIMIT 200"
    );
    $stmt->execute([$companyId]);
    $usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $usersList = [];
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { background:#f9fafb; font-family: 'Inter', sans-serif; }
    .card-radius { border-radius: 16px; }
    .soft-shadow { box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #FF4800; border-radius: 10px; }
    #searchInput::placeholder { color: #FF4800; opacity: 0.6; }
    .spinner { display: none; width: 20px; height: 20px; border: 3px solid #ffd0bb; border-top-color: #FF4800; border-radius: 50%; animation: spin .6s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body class="min-h-screen text-gray-900">

<header class="bg-[#FF4800] h-16 sticky top-0 z-50 shadow-md">
  <div class="absolute left-0 top-0 bottom-0 w-24 md:w-72 bg-white flex items-center justify-center">
      <a href="admin_dashboard.php" aria-label="Accueil" class="w-16 h-16 flex items-center justify-center">
          <img src="../../img/logo/logo.png" alt="ShiftUp Logo" class="w-14 h-14 object-contain">
      </a>
  </div>
  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-20 md:pl-64 pr-6 text-white font-bold text-sm">
    <nav class="hidden md:flex items-center gap-10">
      <a href="admin_dashboard.php" class="opacity-80 hover:opacity-100 transition">Dashboard</a>
      <a href="admin_shift_manager.php" class="opacity-80 hover:opacity-100 transition">Shift manager</a>
      <a href="admin_gestion.php" class="border-b-2 border-white pb-1">Gestion</a>
      <a href="admin_profile.php" class="w-10 h-10 rounded-full border-2 border-white/50 flex items-center justify-center hover:bg-white/10 transition"><i class="fa-solid fa-user"></i></a>
    </nav>
  </div>
</header>

<main class="max-w-screen-2xl mx-auto px-4 md:px-8 py-8">
  <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
    <h1 class="text-4xl font-bold text-gray-800 tracking-tight">Gestion <span class="text-[#FF4800]">Utilisateurs</span></h1>
    <a id="exportBtn" href="?export=1" class="inline-flex items-center gap-2 bg-[#FF4800] hover:bg-orange-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-orange-200 transition active:scale-95">
      <i class="fa-solid fa-file-csv"></i> Exporter en CSV
    </a>
  </div>

  <div class="bg-white card-radius p-8 soft-shadow border border-gray-100">
    <div class="mb-10 max-w-2xl mx-auto relative">
      <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-[#FF4800]"></i>
      <input id="searchInput" placeholder="Rechercher un employé par pseudo ou email..." autocomplete="off" class="w-full bg-gray-50 border-2 border-[#FF4800]/50 focus:border-[#FF4800] rounded-full px-14 py-4 font-bold text-gray-800 outline-none transition shadow-sm" />
      <div id="searchSpinner" class="spinner absolute right-5 top-1/2 -translate-y-1/2"></div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-gray-100">
      <div class="min-w-[800px]">
        <div class="grid grid-cols-7 gap-4 text-xs font-black uppercase tracking-widest text-gray-400 bg-gray-50 px-6 py-4 border-b border-gray-100">
          <div class="col-span-2">Employé</div>
          <div class="col-span-1">Département</div>
          <div class="col-span-1 text-center">Dernière Action</div>
          <div class="col-span-2">Progression XP</div>
          <div class="col-span-1 text-center">Actions</div>
        </div>
        
        <div id="usersListContainer" class="divide-y divide-gray-50">
          <?php $this_maxxp = 3000; ?>
          <?php if (count($usersList) === 0): ?>
            <div class="px-4 py-16 text-center text-gray-400 font-medium">Aucun utilisateur trouvé.</div>
          <?php else: ?>
            <?php foreach ($usersList as $u): 
              $last   = $u['last_activity'] ? date('d/m/Y', strtotime($u['last_activity'])) : 'Jamais';
              $dept   = $u['department'] ?: 'Non assigné';
              $points = (int)$u['points_wallet'];
              $pct    = min(100, $this_maxxp ? intval($points / $this_maxxp * 100) : 0);
              $isActive = (int)$u['est_actif'] === 1;
              $isMe = ((int)$u['id'] === (int)$user['id']); // Permet de bloquer l'action sur soi-même
            ?>
            <?= buildUserRow($u, $last, $dept, $points, $pct, $this_maxxp, $isActive, $isMe); ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>

<?php
function buildUserRow(array $u, string $last, string $dept, int $points, int $pct, int $maxxp, bool $isActive, bool $isMe): string {
    $id   = (int)$u['id'];
    $ds_pseudo = htmlspecialchars($u['pseudo'] ?: 'Anonyme', ENT_QUOTES);
    $ds_email  = htmlspecialchars($u['email']  ?? '', ENT_QUOTES);
    $ds_dept   = htmlspecialchars($dept, ENT_QUOTES);
    
    $banText = $isActive ? 'Désactiver' : 'Réactiver';
    $banColor = $isActive ? 'text-[#FF4800] bg-orange-50 border-orange-100 hover:bg-[#FF4800] hover:text-white' : 'text-green-600 bg-green-50 border-green-100 hover:bg-green-600 hover:text-white';

    $actionsHtml = '';
    if (!$isMe) {
        $actionsHtml = <<<HTML
        <div class="flex gap-2 w-full">
            <button onclick="toggleUser({$id})" class="w-full px-2 py-1.5 rounded-lg border text-[9px] font-black uppercase tracking-widest transition-all {$banColor}">
               {$banText}
            </button>
            <button onclick="deleteUser({$id})" class="px-3 py-1.5 rounded-lg border border-red-100 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all text-[10px]" title="Supprimer">
               <i class="fa-solid fa-trash"></i>
            </button>
        </div>
HTML;
    } else {
        $actionsHtml = '<span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">C\'est vous</span>';
    }

    return <<<HTML
    <div class="grid grid-cols-7 gap-4 items-center py-4 px-6 hover:bg-orange-50/30 transition-colors group">
      <div class="col-span-2">
        <div class="text-sm font-bold text-gray-900 truncate">
            {$ds_pseudo} 
        </div>
        <div class="text-xs text-gray-400 truncate">{$ds_email}</div>
      </div>
      <div class="col-span-1">
        <span class="px-3 py-1.5 text-[10px] font-bold bg-gray-100 text-gray-600 rounded-lg uppercase tracking-widest truncate max-w-full inline-block">{$ds_dept}</span>
      </div>
      <div class="col-span-1 text-center text-xs text-gray-500 font-bold">{$last}</div>
      <div class="col-span-2 pr-6">
        <div class="flex justify-between items-center mb-1.5">
          <span class="text-[10px] font-bold text-gray-400">{$points} XP</span>
          <span class="text-[10px] font-black text-[#FF4800]">{$pct}%</span>
        </div>
        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
          <div class="h-full rounded-full transition-all duration-1000 ease-out" style="width:{$pct}%;background:#FF4800;"></div>
        </div>
      </div>
      <div class="col-span-1 flex items-center justify-center opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
        {$actionsHtml}
      </div>
    </div>
HTML;
}
?>

<script>
const MAXXP = 3000;

// Actions AJAX
async function toggleUser(id) {
    const fd = new FormData();
    fd.append('ajax_action', 'toggle_status');
    fd.append('user_id', id);
    const res = await fetch(location.href, { method: 'POST', body: fd });
    const data = await res.json();
    if(data.success) {
        location.reload();
    } else {
        Swal.fire('Erreur', data.error, 'error');
    }
}

async function deleteUser(id) {
    if(!confirm("Êtes-vous sûr de vouloir supprimer définitivement cet utilisateur ? L'historique de ses actions carbone sera perdu.")) return;
    
    const fd = new FormData();
    fd.append('ajax_action', 'delete');
    fd.append('user_id', id);
    const res = await fetch(location.href, { method: 'POST', body: fd });
    const data = await res.json();
    if(data.success) {
        location.reload();
    } else {
        Swal.fire('Erreur', data.error, 'error');
    }
}

// Recherche AJAX Live
(function(){
  const searchInput = document.getElementById('searchInput');
  const usersList   = document.getElementById('usersListContainer');
  const spinner     = document.getElementById('searchSpinner');
  let debounce;

  function renderUsers(users, myId) {
    if (!users || users.length === 0) {
      usersList.innerHTML = '<div class="px-4 py-16 text-center text-gray-400 font-bold uppercase tracking-widest text-sm">Aucun employé trouvé.</div>';
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
      const isActive = parseInt(u.est_actif) === 1;
      const isMe    = (id === parseInt(myId));
      
      const banText = isActive ? 'Désactiver' : 'Réactiver';
      const banColor = isActive ? 'text-[#FF4800] bg-orange-50 border-orange-100 hover:bg-[#FF4800] hover:text-white' : 'text-green-600 bg-green-50 border-green-100 hover:bg-green-600 hover:text-white';
      const statusLabel = !isActive ? '<span class="text-[10px] text-red-500 ml-2 font-black uppercase tracking-widest">(Désactivé)</span>' : '';

      let actionsHtml = '';
      if(!isMe) {
          actionsHtml = `
          <div class="flex gap-2 w-full">
            <button onclick="toggleUser(${id})" class="w-full px-2 py-1.5 rounded-lg border text-[9px] font-black uppercase tracking-widest transition-all ${banColor}">
               ${banText}
            </button>
            <button onclick="deleteUser(${id})" class="px-3 py-1.5 rounded-lg border border-red-100 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all text-[10px]" title="Supprimer">
               <i class="fa-solid fa-trash"></i>
            </button>
          </div>`;
      } else {
          actionsHtml = `<span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">C'est vous</span>`;
      }

      return `
      <div class="grid grid-cols-7 gap-4 items-center py-4 px-6 hover:bg-orange-50/30 transition-colors group">
        <div class="col-span-2">
          <div class="text-sm font-bold text-gray-900 truncate">${pseudo} ${statusLabel}</div>
          <div class="text-xs text-gray-400 truncate">${email}</div>
        </div>
        <div class="col-span-1">
          <span class="px-3 py-1.5 text-[10px] font-bold bg-gray-100 text-gray-600 rounded-lg uppercase tracking-widest truncate max-w-full inline-block">${dept}</span>
        </div>
        <div class="col-span-1 text-center text-xs text-gray-500 font-bold">${last}</div>
        <div class="col-span-2 pr-6">
          <div class="flex justify-between items-center mb-1.5">
            <span class="text-[10px] font-bold text-gray-400">${points} XP</span>
            <span class="text-[10px] font-black text-[#FF4800]">${pct}%</span>
          </div>
          <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500" style="width:${pct}%;background:#FF4800;"></div>
          </div>
        </div>
        <div class="col-span-1 flex items-center justify-center opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
          ${actionsHtml}
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
    debounce = setTimeout(() => fetchUsers(q), 300);  
  });

  function fetchUsers(q) {
    spinner.style.display = 'block';
    const url = '?ajax=1&q=' + encodeURIComponent(q);
    fetch(url, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(data => {
        spinner.style.display = 'none';
        renderUsers(data.users || [], data.my_id);
      })
      .catch(() => { spinner.style.display = 'none'; });
  }

  const exportBtn = document.getElementById('exportBtn');
  if (exportBtn) {
    exportBtn.addEventListener('click', function(e) {
      e.preventDefault();
      const url = this.getAttribute('href');
      Swal.fire({ title: 'Export en cours', html: 'Création du fichier CSV...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });
      fetch(url, { method: 'GET', credentials: 'same-origin' })
        .then(r => { if (!r.ok) throw new Error(); return r.blob().then(blob => ({ blob })); })
        .then(({ blob }) => {
          const a = document.createElement('a');
          a.href = window.URL.createObjectURL(blob);
          a.download = 'employes_export.csv';
          document.body.appendChild(a); a.click(); a.remove();
          Swal.fire({ title: 'Export réussi !', icon: 'success', confirmButtonColor: '#FF4800' });
        })
        .catch(() => Swal.fire({ title: 'Erreur', text: 'Impossible de générer le fichier CSV.', icon: 'error', confirmButtonColor: '#FF4800' }));
    });
  }
})();
</script>
</body>
</html>