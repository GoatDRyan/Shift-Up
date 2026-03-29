<?php
session_start();
require_once '../../config/db_connect.php';

$companyId = (int)($_SESSION['company_id'] ?? 1);

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function difficultyLeaves(?string $difficulty): int {
    $d = mb_strtolower((string)$difficulty);
    if (str_contains($d, 'diffic')) return 3;
    if (str_contains($d, 'moy')) return 2;
    return 1;
}

function leafSVG(): string {
    return '<svg class="w-5 h-5 text-[#FF4800]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M11 20A7 7 0 019.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10ZM2 21c0-3 1.8-5.4 5.1-6C9.5 14.5 12 13 13 12" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

function renderLeaves(int $n): string {
    $n = max(1, min(3, $n));
    return str_repeat('<span class="inline-flex items-center">' . leafSVG() . '</span>', $n);
}

function ensureChallengeActiveColumn(PDO $pdo): void {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'challenges' AND COLUMN_NAME = 'is_active'");
        $stmt->execute();
        if (!(int)$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE challenges ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER company_id");
        }
    } catch (Throwable $e) {}
}

if (isset($pdo) && $pdo instanceof PDO) {
    ensureChallengeActiveColumn($pdo);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $action = (string)$_POST['ajax_action'];
        $id = (int)($_POST['challenge_id'] ?? 0);

        if ($action !== 'create' && $id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID invalide']);
            exit;
        }

        if ($action === 'toggle') {
            $stmt = $pdo->prepare('SELECT COALESCE(is_active,1) FROM challenges WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $current = (int)($stmt->fetchColumn() ?: 1);
            $next = $current ? 0 : 1;
            $stmt = $pdo->prepare('UPDATE challenges SET is_active = ? WHERE id = ?');
            $stmt->execute([$next, $id]);
            echo json_encode(['success' => true, 'action' => $next ? 'enabled' : 'disabled']);
            exit;
        }

        if ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM challenges WHERE id = ?');
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'create') {
            $titreFr   = trim((string)($_POST['titre_fr'] ?? ''));
            $titreEn   = trim((string)($_POST['titre_en'] ?? ''));
            $descrFr   = trim((string)($_POST['descr_fr'] ?? ''));
            $descrEn   = trim((string)($_POST['descr_en'] ?? ''));
            $difficulty = trim((string)($_POST['difficulty'] ?? 'facile'));
            $xp        = (int)($_POST['xp_gain'] ?? 10);
            $co2       = (float)($_POST['co2_kg'] ?? 0);
            $domaine   = trim((string)($_POST['domaine'] ?? 'ecologique'));
            $categorie = trim((string)($_POST['categorie'] ?? 'Général'));
            $duration  = (int)($_POST['duration_days'] ?? 1);
            $maxActions = (int)($_POST['max_actions_day'] ?? 1);

            if ($titreFr === '') {
                echo json_encode(['success' => false, 'error' => 'Le titre FR est obligatoire']);
                exit;
            }
            if ($titreEn === '') $titreEn = $titreFr;
            if ($descrFr === '') $descrFr = 'Description';
            if ($descrEn === '') $descrEn = $descrFr;

            $stmt = $pdo->prepare(
                'INSERT INTO challenges
                (titre_fr, titre_en, descr_fr, descr_en, image_url, xp_gain, co2_kg, difficulty, domaine, domaine_2, categorie, duration_days, max_actions_day, company_id, is_active)
                VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, NULL, ?, ?, ?, ?, 1)'
            );
            $stmt->execute([$titreFr, $titreEn, $descrFr, $descrEn, $xp, $co2, $difficulty, $domaine, $categorie, $duration, $maxActions, $companyId]);
            echo json_encode(['success' => true]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$types = []; $categories = []; $difficulties = [];
$rankingDept = []; $rankingSolo = []; $tasks = [];
$SHOW_LIMIT = 6; 

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $types       = $pdo->query("SELECT DISTINCT domaine   FROM challenges WHERE domaine   IS NOT NULL AND domaine   <> '' ORDER BY domaine")->fetchAll(PDO::FETCH_COLUMN);
        $categories  = $pdo->query("SELECT DISTINCT categorie FROM challenges WHERE categorie IS NOT NULL AND categorie <> '' ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);
        $difficulties = $pdo->query("SELECT DISTINCT difficulty FROM challenges WHERE difficulty IS NOT NULL AND difficulty <> '' ORDER BY difficulty")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {}

    try {
        $stmt = $pdo->prepare(
            'SELECT d.nom, SUM(COALESCE(u.points_rank, u.points_wallet, 0)) AS total_points
             FROM departments d
             LEFT JOIN users u ON u.department_id = d.id
             WHERE d.company_id = ?
             GROUP BY d.id, d.nom
             ORDER BY total_points DESC, d.nom ASC
             LIMIT 5'
        );
        $stmt->execute([$companyId]);
        $rankingDept = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $rankingDept = []; }

    try {
        $stmt = $pdo->prepare(
            'SELECT u.id, u.pseudo, COUNT(ua.id) AS actions
             FROM users u
             LEFT JOIN user_actions ua ON ua.user_id = u.id
             GROUP BY u.id
             ORDER BY actions DESC
             LIMIT 5'
        );
        $stmt->execute();
        $rankingSolo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $rankingSolo = []; }

    try {
        $stmt = $pdo->prepare(
            'SELECT c.id, c.titre_fr, c.xp_gain, c.co2_kg, c.difficulty, c.domaine, c.categorie, c.duration_days,
                    COALESCE(c.is_active,1) AS is_active,
                    (SELECT COUNT(DISTINCT ua.user_id) FROM user_actions ua WHERE ua.challenge_id = c.id) AS users_count
             FROM challenges c
             WHERE c.company_id IS NULL OR c.company_id = ?
             ORDER BY c.id DESC'
        );
        $stmt->execute([$companyId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $tasks = []; }
}

$firstTask = $tasks[0] ?? null;
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Shift Manager</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { background:#f9fafb; color:#111; font-family: 'Inter', system-ui, sans-serif; }
    .card-radius  { border-radius:16px; }
    .soft-shadow  { box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
    .bg-orange-brand   { background-color: #FF4800; }
    .text-orange-brand { color: #FF4800; }
    .border-orange-brand { border-color: #FF4800; }
    #tasksList::-webkit-scrollbar { width: 4px; }
    #tasksList::-webkit-scrollbar-thumb { background: #FF4800; border-radius: 10px; }
    .tab-btn { transition: all .2s; }
    .tab-btn.active { background:#FF4800; color:#fff; }
    .task-hidden { display:none !important; }
  </style>
</head>
<body class="min-h-screen">

<header class="bg-[#FF4800] h-16 sticky top-0 z-50 shadow-md">
  <div class="absolute left-0 top-0 bottom-0 w-20 md:w-64 bg-black/10 flex items-center justify-center">
    <a href="admin_dashboard.php" aria-label="Accueil" class="flex items-center justify-center">
      <img src="../../img/icone/shiftup-logo.png" alt="ShiftUp Logo" class="w-9 h-9 object-contain">
    </a>
  </div>
  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-20 md:pl-64 pr-6">
    <nav class="hidden md:flex items-center gap-10 text-[15px]">
      <a href="admin_shift_manager.php" class="font-bold text-white border-b-2 border-white pb-0.5">Shift manager</a>
      <a href="admin_gestion.php" class="text-white/80 hover:text-white font-semibold transition">Gestion</a>
      <a href="admin_profile.php" class="w-10 h-10 rounded-full border-2 border-white/50 hover:border-white flex items-center justify-center hover:bg-white/10 transition">
        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3"/><path d="M6 20c0-3 4-5 6-5s6 2 6 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
    </nav>
  </div>
</header>

<main class="max-w-screen-2xl mx-auto px-4 md:px-8 py-8">
  <h1 class="text-4xl font-bold mb-8 text-gray-800">Shift <span class="text-[#FF4800]">Manager</span></h1>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <section class="space-y-6">

      <div class="bg-white card-radius p-6 soft-shadow border-t-4 border-orange-brand">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 truncate" id="previewTitle"><?php echo $firstTask ? h($firstTask['titre_fr']) : 'Titre'; ?></h2>
        <div class="space-y-3 text-[15px]">
          <div class="flex items-center justify-between gap-4 border-b border-gray-50 pb-2">
            <span class="text-gray-500">Expérience :</span>
            <span id="previewXp" class="font-bold text-orange-brand"><?php echo $firstTask ? h($firstTask['xp_gain']) : '—'; ?> XP</span>
          </div>
          <div class="flex items-center justify-between gap-4 border-b border-gray-50 pb-2">
            <span class="text-gray-500">Récompense CO₂ :</span>
            <span id="previewScore" class="font-bold text-green-600"><?php echo $firstTask ? h($firstTask['co2_kg']) : '—'; ?> kg</span>
          </div>
          <div class="flex items-center justify-between gap-4 border-b border-gray-50 pb-2">
            <span class="text-gray-500">Difficulté :</span>
            <span id="previewLeaves" class="inline-flex items-center gap-1"><?php echo $firstTask ? renderLeaves(difficultyLeaves($firstTask['difficulty'])) : renderLeaves(1); ?></span>
          </div>
          <div class="flex items-center justify-between gap-4 border-b border-gray-50 pb-2">
            <span class="text-gray-500">Catégorie :</span>
            <span id="previewCategory" class="font-medium text-gray-700"><?php echo $firstTask ? h($firstTask['categorie']) : '—'; ?></span>
          </div>
          <div class="flex items-center justify-between gap-4">
            <span class="text-gray-500">Statut :</span>
            <span id="previewStatus" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?php echo $firstTask && (int)$firstTask['is_active'] === 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
              <?php echo $firstTask ? (((int)$firstTask['is_active'] === 1) ? 'Actif' : 'Désactivé') : '—'; ?>
            </span>
          </div>
        </div>
      </div>

      <div class="bg-white card-radius p-6 soft-shadow">
        <div class="flex items-center gap-2 mb-5">
          <h2 class="text-xl font-bold">Classement</h2>
          <div class="ml-auto flex gap-2">
            <button class="tab-btn active px-4 py-1.5 rounded-full text-sm font-bold bg-gray-100 text-gray-600" data-tab="dept">Départements</button>
            <button class="tab-btn px-4 py-1.5 rounded-full text-sm font-bold bg-gray-100 text-gray-600" data-tab="solo">Solo</button>
          </div>
        </div>

        <div id="tab-dept" class="space-y-3">
          <?php if (!empty($rankingDept)): $rank = 1; foreach ($rankingDept as $row): ?>
            <div class="flex items-center gap-4 bg-gray-50 hover:bg-orange-50 transition rounded-xl p-3 border border-gray-100">
              <div class="w-8 h-8 rounded-lg bg-orange-brand text-white flex items-center justify-center font-bold text-sm"><?php echo $rank; ?></div>
              <div class="flex-1 font-semibold text-gray-700 truncate"><?php echo h($row['nom']); ?></div>
              <div class="text-orange-brand font-bold text-sm"><?php echo number_format((float)$row['total_points'], 0, ',', ' '); ?> <span class="text-[10px]">PTS</span></div>
            </div>
          <?php $rank++; endforeach; else: ?>
            <div class="text-gray-400 text-center py-4">Aucune donnée.</div>
          <?php endif; ?>
        </div>

        <div id="tab-solo" class="space-y-3 hidden">
          <?php if (!empty($rankingSolo)):
            $soloColors = ['bg-orange-100 text-[#FF4800]','bg-gray-100 text-gray-600','bg-orange-50 text-orange-400','bg-gray-50 text-gray-500','bg-gray-50 text-gray-500'];
            $pos = 1; foreach ($rankingSolo as $row):
              $pseudo = h($row['pseudo'] ?? 'user'.$row['id']);
              $cc = $soloColors[$pos-1] ?? 'bg-gray-50';
          ?>
            <div class="flex items-center gap-4 bg-gray-50 hover:bg-orange-50 transition rounded-xl p-3 border border-gray-100">
              <div class="w-8 h-8 rounded-full <?php echo $cc; ?> flex items-center justify-center font-black text-sm"><?php echo $pos; ?></div>
              <div class="flex-1 font-semibold text-gray-700 truncate"><?php echo $pseudo; ?></div>
              <div class="text-xs font-bold text-gray-400"><?php echo (int)$row['actions']; ?> act.</div>
            </div>
          <?php $pos++; endforeach; else: ?>
            <div class="text-gray-400 text-center py-4">Aucun utilisateur.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="bg-white card-radius p-6 soft-shadow">
        <h2 class="text-lg font-bold mb-4">Nouvel objectif ?</h2>
        <button id="openCreateBtn" class="w-full bg-orange-brand hover:bg-orange-600 text-white font-bold px-6 py-4 rounded-xl transition shadow-md shadow-orange-200 flex items-center justify-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Créer la tâche
        </button>
      </div>
    </section>

    <section class="lg:col-span-2 bg-white card-radius p-6 soft-shadow border border-gray-100">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Catalogue des défis</h2>
        <button id="openFilterBtn" class="flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-xl transition font-semibold">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.5a1 1 0 01-.293.707L12 14.5V19a1 1 0 01-.447.894l-3 2A1 1 0 017 21v-6.5L3.293 7.207A1 1 0 013 6.5V4z" stroke-width="1.8"/></svg>
          Filtrer
        </button>
      </div>

      <div class="mb-6 relative group">
        <svg class="w-5 h-5 absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-orange-brand transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5" stroke-linecap="round"/></svg>
        <input id="taskSearch" placeholder="Rechercher une tâche par nom ou catégorie..." class="w-full bg-gray-50 border border-gray-100 focus:border-orange-brand focus:bg-white placeholder-gray-400 px-14 py-4 rounded-xl outline-none transition text-gray-700 shadow-sm" />
      </div>

      <div class="flex flex-wrap gap-2 mb-5" id="categoryPills">
        <button class="cat-pill active px-4 py-1.5 rounded-full text-xs font-bold border border-gray-200 bg-gray-100 text-gray-600 hover:bg-[#FF4800] hover:text-white hover:border-[#FF4800] transition" data-cat="">Toutes</button>
        <?php foreach ($categories as $cat): ?>
          <button class="cat-pill px-4 py-1.5 rounded-full text-xs font-bold border border-gray-200 bg-gray-100 text-gray-600 hover:bg-[#FF4800] hover:text-white hover:border-[#FF4800] transition" data-cat="<?php echo h($cat); ?>"><?php echo h($cat); ?></button>
        <?php endforeach; ?>
      </div>

      <div id="tasksList" class="space-y-4 pr-1">
        <?php if (!empty($tasks)): ?>
          <?php foreach ($tasks as $i => $task):
            $isActive = (int)$task['is_active'] === 1;
            $leaves   = difficultyLeaves($task['difficulty']);
          ?>
          <article class="task-card flex items-center gap-4 p-4 bg-white rounded-2xl border border-gray-100 hover:border-orange-200 hover:shadow-md transition cursor-pointer<?php echo $i >= $SHOW_LIMIT ? ' task-hidden initially-hidden' : ''; ?>"
            data-title="<?php echo h($task['titre_fr']); ?>"
            data-xp="<?php echo (int)$task['xp_gain']; ?>"
            data-score="<?php echo h($task['co2_kg']); ?>"
            data-leaves="<?php echo $leaves; ?>"
            data-category="<?php echo h($task['categorie']); ?>"
            data-duration="<?php echo h($task['duration_days']); ?>"
            data-status="<?php echo $isActive ? 'Actif' : 'Désactivé'; ?>"
            data-domaine="<?php echo h($task['domaine']); ?>"
            data-difficulty="<?php echo h($task['difficulty']); ?>"
            data-users="<?php echo (int)$task['users_count']; ?>"
          >
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-3 flex-wrap mb-2">
                <div class="flex items-center shrink-0"><?php echo renderLeaves($leaves); ?></div>
                <h3 class="text-base font-bold text-gray-800 truncate"><?php echo h($task['titre_fr']); ?></h3>
                <span class="text-[10px] px-2 py-0.5 rounded-md font-bold uppercase tracking-widest <?php echo $isActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'; ?>"><?php echo $isActive ? 'Actif' : 'Off'; ?></span>
              </div>
              <div class="flex flex-wrap gap-2">
                <span class="bg-gray-50 text-gray-500 text-[11px] px-3 py-1 rounded-full border border-gray-100"><?php echo h($task['categorie']); ?></span>
                <span class="bg-gray-50 text-gray-500 text-[11px] px-3 py-1 rounded-full border border-gray-100"><?php echo h($task['duration_days']); ?> jours</span>
                <span class="bg-orange-50 text-orange-brand text-[11px] px-3 py-1 rounded-full border border-orange-100 font-bold"><?php echo (int)$task['users_count']; ?> participants</span>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <button class="btn-toggle bg-gray-50 hover:bg-orange-brand hover:text-white text-gray-600 transition-all font-bold px-4 py-2 rounded-xl text-xs border border-gray-100" data-id="<?php echo (int)$task['id']; ?>">
                <?php echo $isActive ? 'Désactiver' : 'Activer'; ?>
              </button>
              <button class="btn-params w-10 h-10 rounded-xl bg-gray-50 hover:bg-gray-100 text-gray-400 hover:text-gray-900 transition flex items-center justify-center border border-gray-100" data-id="<?php echo (int)$task['id']; ?>">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
              </button>
            </div>
          </article>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center text-gray-400 py-16 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-100">Aucune tâche disponible.</div>
        <?php endif; ?>
      </div>

      <?php if (count($tasks) > $SHOW_LIMIT): ?>
      <div class="mt-6 text-center" id="voirPlusContainer">
        <button id="voirPlusBtn" class="inline-flex items-center gap-2 px-8 py-3 rounded-xl bg-gray-100 hover:bg-[#FF4800] hover:text-white text-gray-600 font-bold transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          Voir plus (<?php echo count($tasks) - $SHOW_LIMIT; ?> tâches)
        </button>
      </div>
      <?php endif; ?>
    </section>
  </div>
</main>

<div id="createModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
  <div class="bg-white p-8 card-radius w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-auto border-t-8 border-orange-brand">
    <div class="flex items-center justify-between mb-8">
      <h3 class="text-2xl font-bold text-gray-800">Nouvelle tâche</h3>
      <button class="close-modal text-gray-400 hover:text-orange-brand text-3xl leading-none">&times;</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input id="modal_title"    class="bg-gray-50 border border-gray-200 p-3.5 rounded-xl outline-none focus:border-orange-brand transition" placeholder="Titre FR *" />
      <input id="modal_title_en" class="bg-gray-50 border border-gray-200 p-3.5 rounded-xl outline-none focus:border-orange-brand transition" placeholder="Titre EN" />
      <textarea id="modal_descr_fr" class="bg-gray-50 border border-gray-200 p-3.5 rounded-xl md:col-span-2 outline-none focus:border-orange-brand transition" rows="3" placeholder="Description FR"></textarea>

      <select id="modal_difficulty" class="bg-gray-50 border border-gray-200 p-3.5 rounded-xl outline-none focus:border-orange-brand transition">
        <?php if (!empty($difficulties)): foreach ($difficulties as $d): ?>
          <option><?php echo h($d); ?></option>
        <?php endforeach; else: ?>
          <option>facile</option><option>moyen</option><option>difficile</option>
        <?php endif; ?>
      </select>
      <div id="modal_leaves_preview" class="flex items-center justify-center gap-1 bg-orange-50/50 border border-orange-100 p-3.5 rounded-xl"></div>

      <input id="modal_xp"    type="number" class="bg-gray-50 border border-gray-200 p-3.5 rounded-xl outline-none focus:border-orange-brand transition" placeholder="XP" />
      <input id="modal_score" type="number" step="0.01" class="bg-gray-50 border border-gray-200 p-3.5 rounded-xl outline-none focus:border-orange-brand transition" placeholder="Score CO₂ (kg)" />

      <select id="modal_type" class="bg-gray-50 border border-gray-200 p-3.5 rounded-xl outline-none focus:border-orange-brand transition">
        <?php if (!empty($types)): foreach ($types as $t): ?><option><?php echo h($t); ?></option><?php endforeach;
        else: echo '<option>ecologique</option><option>social</option>'; endif; ?>
      </select>

      <select id="modal_category" class="bg-gray-50 border border-gray-200 p-3.5 rounded-xl outline-none focus:border-orange-brand transition">
        <?php if (!empty($categories)): foreach ($categories as $cat): ?>
          <option><?php echo h($cat); ?></option>
        <?php endforeach; else: echo '<option>Général</option>'; endif; ?>
      </select>
    </div>
    <div class="flex justify-end gap-3 mt-8">
      <button class="close-modal px-8 py-3 rounded-xl border border-gray-200 font-bold text-gray-500 hover:bg-gray-50 transition">Annuler</button>
      <button id="createBtn" class="px-8 py-3 rounded-xl bg-orange-brand text-white font-bold hover:bg-orange-600 shadow-lg shadow-orange-200 transition">Créer</button>
    </div>
  </div>
</div>

<div id="filterModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-40 p-4 backdrop-blur-sm">
  <div class="bg-white p-8 card-radius w-full max-w-lg shadow-2xl border-t-8 border-orange-brand">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-xl font-bold">Options de tri</h3>
      <button class="close-modal text-gray-400 hover:text-orange-brand text-3xl leading-none">&times;</button>
    </div>
    <div class="space-y-4">
      <select id="filter_type" class="w-full bg-gray-50 border border-gray-200 p-3.5 rounded-xl outline-none focus:border-orange-brand">
        <option value="">Tous les domaines</option>
        <?php foreach ($types as $t): ?><option><?php echo h($t); ?></option><?php endforeach; ?>
      </select>
      <select id="filter_difficulty" class="w-full bg-gray-50 border border-gray-200 p-3.5 rounded-xl outline-none focus:border-orange-brand">
        <option value="">Toutes les difficultés</option>
        <?php foreach ($difficulties as $d): ?><option><?php echo h($d); ?></option><?php endforeach; ?>
      </select>
      <div class="flex justify-end gap-3 pt-4">
        <button id="filterReset" class="px-6 py-3 rounded-xl border border-gray-200 font-bold text-gray-400 hover:bg-gray-50">Reset</button>
        <button id="filterApply" class="px-6 py-3 rounded-xl bg-orange-brand text-white font-bold shadow-lg shadow-orange-100">Appliquer</button>
      </div>
    </div>
  </div>
</div>

<div id="paramsModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
  <div class="bg-white p-6 rounded-3xl w-full max-w-md shadow-2xl relative">
    <button id="paramsClose" class="absolute top-5 right-5 text-gray-400 hover:text-orange-brand text-3xl leading-none">&times;</button>
    <h3 class="text-xl font-bold mb-6 text-gray-800">Action rapide</h3>
    <div class="space-y-3">
      <button id="editBtn" class="w-full flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-200 rounded-2xl group transition border border-transparent hover:border-gray-300">
        <span class="font-bold text-gray-700">Modifier les détails</span>
        <span class="text-[#FF4800]">✎</span>
      </button>
      <button id="toggleBtn" class="w-full flex items-center justify-between p-4 bg-orange-50 hover:bg-[#FF4800] text-[#FF4800] hover:text-white rounded-2xl group transition border border-orange-100 hover:border-transparent">
        <span id="toggleText" class="font-bold">Désactiver</span>
        <span class="transition-transform group-hover:rotate-180 duration-500">⟲</span>
      </button>
      <button id="deleteBtn" class="w-full flex items-center justify-between p-4 bg-red-50 hover:bg-red-600 hover:text-white rounded-2xl group transition border border-transparent">
        <span class="font-bold">Supprimer définitivement</span>
        <span>🗑</span>
      </button>
    </div>
    <div class="mt-8 pt-6 border-t border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-widest flex justify-between gap-3">
      <span>XP: <strong id="paramsXp" class="text-orange-brand"></strong></span>
      <span>CO₂: <strong id="paramsScore" class="text-orange-brand"></strong></span>
      <span>Users: <strong id="paramsUsers" class="text-orange-brand"></strong></span>
    </div>
  </div>
</div>

<script>
function leafSVG() {
  return '<svg class="w-5 h-5 text-[#FF4800]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M11 20A7 7 0 019.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10ZM2 21c0-3 1.8-5.4 5.1-6C9.5 14.5 12 13 13 12" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}
function renderLeaves(n) {
  return leafSVG().repeat(Math.max(1, Math.min(3, n)));
}
function openModal(m)  { m.classList.remove('hidden'); m.classList.add('flex'); }
function closeModal(m) { m.classList.add('hidden'); m.classList.remove('flex'); }

const tasks        = Array.from(document.querySelectorAll('.task-card'));
const createModal  = document.getElementById('createModal');
const filterModal  = document.getElementById('filterModal');
const paramsModal  = document.getElementById('paramsModal');
const modalLeaves  = document.getElementById('modal_leaves_preview');
let currentId = null, currentActive = 1;

function setPreview(card) {
  if (!card) return;
  document.getElementById('previewTitle').textContent    = card.dataset.title    || 'Titre';
  document.getElementById('previewXp').textContent       = (card.dataset.xp     || '—') + ' XP';
  document.getElementById('previewScore').textContent    = (card.dataset.score   || '—') + ' kg';
  document.getElementById('previewLeaves').innerHTML     = renderLeaves(parseInt(card.dataset.leaves || '1', 10));
  document.getElementById('previewCategory').textContent = card.dataset.category || '—';
  const el = document.getElementById('previewStatus');
  el.textContent  = card.dataset.status || '—';
  el.className    = `px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider ${card.dataset.status === 'Actif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
}
if (tasks[0]) setPreview(tasks[0]);
tasks.forEach(c => c.addEventListener('click', e => { if (!e.target.closest('button')) setPreview(c); }));

document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-dept').classList.toggle('hidden', btn.dataset.tab !== 'dept');
    document.getElementById('tab-solo').classList.toggle('hidden', btn.dataset.tab !== 'solo');
  });
});

let activeCat = '';
document.querySelectorAll('.cat-pill').forEach(pill => {
  pill.addEventListener('click', () => {
    document.querySelectorAll('.cat-pill').forEach(p => {
      p.classList.remove('active','bg-[#FF4800]','text-white','border-[#FF4800]');
      p.classList.add('bg-gray-100','text-gray-600','border-gray-200');
    });
    pill.classList.add('active','bg-[#FF4800]','text-white','border-[#FF4800]');
    pill.classList.remove('bg-gray-100','text-gray-600','border-gray-200');
    activeCat = pill.dataset.cat;
    applyFilters();
  });
});

const voirPlusBtn = document.getElementById('voirPlusBtn');
const voirPlusContainer = document.getElementById('voirPlusContainer');
let expanded = false;
if (voirPlusBtn) {
  voirPlusBtn.addEventListener('click', () => {
    expanded = !expanded;
    document.querySelectorAll('.initially-hidden').forEach(el => {
      el.classList.toggle('task-hidden', !expanded);
    });
    voirPlusBtn.innerHTML = expanded
      ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg> Voir moins'
      : `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg> Voir plus (${document.querySelectorAll('.initially-hidden').length} tâches)`;
  });
}

function applyFilters() {
  const type   = document.getElementById('filter_type').value.toLowerCase();
  const diff   = document.getElementById('filter_difficulty').value.toLowerCase();
  const search = document.getElementById('taskSearch').value.toLowerCase();

  tasks.forEach(card => {
    const title    = (card.dataset.title    || '').toLowerCase();
    const domaine  = (card.dataset.domaine  || '').toLowerCase();
    const difficulty = (card.dataset.difficulty || '').toLowerCase();
    const category = (card.dataset.category || '').toLowerCase();

    const visible =
      (!type || domaine === type) &&
      (!diff || difficulty === diff) &&
      (!activeCat || category === activeCat.toLowerCase()) &&
      (!search || title.includes(search) || category.includes(search));

    const isInitiallyHidden = card.classList.contains('initially-hidden');
    card.style.display = visible && (expanded || !isInitiallyHidden) ? 'flex' : 'none';
  });
}
document.getElementById('taskSearch').addEventListener('input', applyFilters);

function updateCreateLeaves() {
  const d = document.getElementById('modal_difficulty').value.toLowerCase();
  const n = d.includes('diffic') ? 3 : d.includes('moy') ? 2 : 1;
  modalLeaves.innerHTML = renderLeaves(n);
}
document.getElementById('modal_difficulty').addEventListener('change', updateCreateLeaves);
updateCreateLeaves();

document.getElementById('openCreateBtn').addEventListener('click', () => openModal(createModal));
document.getElementById('openFilterBtn').addEventListener('click', () => openModal(filterModal));
document.querySelectorAll('.close-modal, #paramsClose').forEach(b => {
  b.addEventListener('click', () => { closeModal(createModal); closeModal(filterModal); closeModal(paramsModal); });
});
[createModal, filterModal, paramsModal].forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) closeModal(m); });
});

document.getElementById('createBtn').addEventListener('click', async () => {
  const titre = document.getElementById('modal_title').value.trim();
  if (!titre) return alert('Le titre FR est obligatoire');
  const form = new FormData();
  form.append('ajax_action', 'create');
  form.append('titre_fr',   titre);
  form.append('titre_en',   document.getElementById('modal_title_en').value.trim());
  form.append('descr_fr',   document.getElementById('modal_descr_fr').value.trim());
  form.append('difficulty', document.getElementById('modal_difficulty').value);
  form.append('xp_gain',    document.getElementById('modal_xp').value);
  form.append('co2_kg',     document.getElementById('modal_score').value);
  form.append('domaine',    document.getElementById('modal_type').value);
  form.append('categorie',  document.getElementById('modal_category').value);
  const res = await fetch(location.href, { method:'POST', body:form });
  const data = await res.json();
  if (data.success) location.reload(); else alert(data.error || 'Erreur création');
});

document.getElementById('filterApply').addEventListener('click', () => { applyFilters(); closeModal(filterModal); });
document.getElementById('filterReset').addEventListener('click', () => {
  document.getElementById('filter_type').value = '';
  document.getElementById('filter_difficulty').value = '';
  document.getElementById('taskSearch').value = '';
  activeCat = '';
  document.querySelectorAll('.cat-pill').forEach(p => {
    p.classList.remove('active','bg-[#FF4800]','text-white','border-[#FF4800]');
    p.classList.add('bg-gray-100','text-gray-600','border-gray-200');
  });
  document.querySelector('.cat-pill[data-cat=""]')?.classList.add('active','bg-[#FF4800]','text-white','border-[#FF4800]');
  applyFilters();
});

async function postAction(action, id) {
  const form = new FormData();
  form.append('ajax_action', action);
  form.append('challenge_id', id);
  const res = await fetch(location.href, { method:'POST', body:form });
  return await res.json();
}

document.querySelectorAll('.btn-toggle').forEach(btn => {
  btn.addEventListener('click', async e => {
    e.stopPropagation();
    const data = await postAction('toggle', btn.dataset.id);
    if (data.success) location.reload(); else alert(data.error || 'Erreur');
  });
});

document.querySelectorAll('.btn-params').forEach(btn => {
  btn.addEventListener('click', e => {
    e.stopPropagation();
    const card = btn.closest('.task-card');
    currentId = btn.dataset.id;
    currentActive = card.dataset.status === 'Actif' ? 1 : 0;
    document.getElementById('paramsXp').textContent    = card.dataset.xp    || '';
    document.getElementById('paramsScore').textContent = card.dataset.score  || '';
    document.getElementById('paramsUsers').textContent = card.dataset.users  || '0';
    document.getElementById('toggleText').textContent  = currentActive ? 'Désactiver' : 'Réactiver';
    openModal(paramsModal);
  });
});

document.getElementById('editBtn').addEventListener('click', () => {
  if (currentId) window.location.href = 'admin_shift_manager_edit.php?id=' + encodeURIComponent(currentId);
});
document.getElementById('toggleBtn').addEventListener('click', async () => {
  if (!currentId) return;
  const data = await postAction('toggle', currentId);
  if (data.success) location.reload();
});
document.getElementById('deleteBtn').addEventListener('click', async () => {
  if (!currentId || !confirm('Supprimer définitivement cette tâche ?')) return;
  const data = await postAction('delete', currentId);
  if (data.success) location.reload();
});
</script>
</body>
</html>