<?php
session_start();
require_once '../../config/db_connect.php';

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function difficultyLeaves(?string $difficulty): int {
    $d = mb_strtolower((string)$difficulty);
    if (strpos($d, 'diffic') !== false) return 3;
    if (strpos($d, 'moy') !== false) return 2;
    return 1;
}

function leafSVG(): string {
    return '<svg class="w-5 h-5" style="color:#FF4800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M11 20A7 7 0 019.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10ZM2 21c0-3 1.8-5.4 5.1-6C9.5 14.5 12 13 13 12" stroke-linecap="round" stroke-linejoin="round"/></svg>';
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
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            echo json_encode(['success' => false, 'error' => 'Connexion DB indisponible']); exit;
        }
        $action = (string)$_POST['ajax_action'];
        $id = (int)($_POST['challenge_id'] ?? 0);

        if ($action !== 'create' && $id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID invalide']); exit;
        }

        if ($action === 'toggle') {
            $stmt = $pdo->prepare('SELECT COALESCE(is_active,1) FROM challenges WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $current = (int)($stmt->fetchColumn() ?: 1);
            $next = $current ? 0 : 1;
            $stmt = $pdo->prepare('UPDATE challenges SET is_active = ? WHERE id = ?');
            $stmt->execute([$next, $id]);
            echo json_encode(['success' => true, 'action' => $next ? 'enabled' : 'disabled']); exit;
        }

        if ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM challenges WHERE id = ?');
            $stmt->execute([$id]);
            echo json_encode(['success' => true]); exit;
        }

        if ($action === 'create') {
            $titreFr  = trim((string)($_POST['titre_fr'] ?? ''));
            $titreEn  = trim((string)($_POST['titre_en'] ?? ''));
            $descrFr  = trim((string)($_POST['descr_fr'] ?? ''));
            $descrEn  = trim((string)($_POST['descr_en'] ?? ''));
            $difficulty = trim((string)($_POST['difficulty'] ?? 'facile'));
            $xp       = (int)($_POST['xp_gain'] ?? 10);
            $co2      = (float)($_POST['co2_kg'] ?? 0);
            $domaine  = trim((string)($_POST['domaine'] ?? 'ecologique'));
            $categorie = trim((string)($_POST['categorie'] ?? 'Général'));
            $duration = (int)($_POST['duration_days'] ?? 1);
            $maxActions = (int)($_POST['max_actions_day'] ?? 1);

            if ($titreFr === '') { echo json_encode(['success' => false, 'error' => 'Le titre FR est obligatoire']); exit; }
            if ($titreEn === '') $titreEn = $titreFr;
            if ($descrFr === '') $descrFr = 'Description';
            if ($descrEn === '') $descrEn = $descrFr;

            $stmt = $pdo->prepare('INSERT INTO challenges (titre_fr, titre_en, descr_fr, descr_en, image_url, xp_gain, co2_kg, difficulty, domaine, domaine_2, categorie, duration_days, max_actions_day, company_id, is_active) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, NULL, ?, ?, ?, NULL, 1)');
            $stmt->execute([$titreFr, $titreEn, $descrFr, $descrEn, $xp, $co2, $difficulty, $domaine, $categorie, $duration, $maxActions]);
            echo json_encode(['success' => true]); exit;
        }

        echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$types = []; $categories = []; $difficulties = [];
$rankingDept = []; $rankingCompany = []; $tasks = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $types       = $pdo->query("SELECT DISTINCT domaine FROM challenges WHERE domaine IS NOT NULL AND domaine<>'' ORDER BY domaine")->fetchAll(PDO::FETCH_COLUMN);
        $categories  = $pdo->query("SELECT DISTINCT categorie FROM challenges WHERE categorie IS NOT NULL AND categorie<>'' ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);
        $difficulties = $pdo->query("SELECT DISTINCT difficulty FROM challenges WHERE difficulty IS NOT NULL AND difficulty<>'' ORDER BY FIELD(difficulty,'facile','moyen','difficile')")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {}

    try {
        $rankingDept = $pdo->query('SELECT d.id, d.nom, d.total_xp FROM departments d ORDER BY d.total_xp DESC, d.nom ASC LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $rankingDept = []; }

    try {
        $rankingCompany = $pdo->query('SELECT id, nom, total_xp FROM companies ORDER BY total_xp DESC, id ASC LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $rankingCompany = []; }

    try {
        $tasks = $pdo->query('SELECT c.id, c.titre_fr, c.xp_gain, c.co2_kg, c.difficulty, c.domaine, c.categorie, c.duration_days, COALESCE(c.is_active,1) AS is_active, (SELECT COUNT(DISTINCT ua.user_id) FROM user_actions ua WHERE ua.challenge_id = c.id) AS users_count FROM challenges c ORDER BY c.id DESC')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $tasks = []; }
}

$firstTask = $tasks[0] ?? null;
$VISIBLE_TASKS = 5; 
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Shift Manager - Super Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root { --orange: #FF4800; --orange-light: #ff6a2f; --orange-dark: #cc3a00; }
    body { background: #fff; color: #111; }
    .rounded-full-xl { border-radius: 999px; }
    .card-radius { border-radius: 14px; }
    .soft-shadow { box-shadow: 0 8px 24px rgba(0,0,0,.08); }
    .btn-orange { background: #FF4800; color: #fff; }
    .btn-orange:hover { background: #cc3a00; }
    .badge-active { background: #fff3ee; color: #FF4800; }
    .badge-inactive { background: #f5f5f5; color: #888; }
    .header-bg { background: #FF4800; }
    .task-card { border-radius: 14px; transition: box-shadow .2s; }
    .task-card:hover { box-shadow: 0 4px 18px rgba(255,72,0,.15); }
    .rank-badge { width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.1rem; }
    .rank-1 { background:#FF4800; color:#fff; }
    .rank-2 { background:#ff7a45; color:#fff; }
    .rank-3 { background:#ffb399; color:#fff; }
    .tab-active { background:#FF4800; color:#fff; }
    .tab-inactive { background:#f3f3f3; color:#555; }
  </style>
</head>
<body class="min-h-screen">

<header class="header-bg h-16 relative">
  <div class="absolute left-0 top-0 bottom-0 w-20 md:w-64 bg-black/20 flex items-center justify-center">
    <a href="superadmin_dashboard.php" aria-label="Accueil" class="w-12 h-12 flex items-center justify-center">
      <img src="../../img/icone/shiftup-logo.png" alt="ShiftUp" class="w-10 h-10 object-contain" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
      <svg style="display:none" class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
        <path d="M12 2L4 5v6c0 5 3.5 9.7 8 11 4.5-1.3 8-6 8-11V5l-8-3z" stroke-linejoin="round" stroke-linecap="round"/>
        <text x="12" y="15.3" text-anchor="middle" font-size="9" fill="white" font-weight="700">S</text>
      </svg>
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
    <button class="md:hidden ml-2 p-2 rounded" aria-label="Menu">
      <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    </button>
  </div>
</header>

<main class="max-w-screen-2xl mx-auto px-4 md:px-8 py-8">
  <h1 class="text-4xl font-light mb-6" style="color:#FF4800">Shift Manager</h1>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <section class="space-y-6">
      <div class="bg-gray-100 card-radius p-6 soft-shadow border border-orange-100">
        <h2 class="text-2xl font-light mb-4" id="previewTitle" style="color:#FF4800"><?= $firstTask ? h($firstTask['titre_fr']) : 'Titre' ?></h2>
        <div class="space-y-3 text-[15px]">
          <div class="flex items-center justify-between"><span class="text-gray-600">Expérience :</span><span id="previewXp" class="font-semibold" style="color:#FF4800"><?= $firstTask ? h($firstTask['xp_gain']) . ' XP' : 'XP' ?></span></div>
          <div class="flex items-center justify-between"><span class="text-gray-600">CO₂ économisé :</span><span id="previewScore" class="font-semibold"><?= $firstTask ? h($firstTask['co2_kg']) . ' kg' : 'Score' ?></span></div>
          <div class="flex items-center justify-between"><span class="text-gray-600">Difficulté :</span><span id="previewLeaves" class="inline-flex items-center gap-1"><?= $firstTask ? renderLeaves(difficultyLeaves($firstTask['difficulty'])) : renderLeaves(1) ?></span></div>
          <div class="flex items-center justify-between"><span class="text-gray-600">Catégorie :</span><span id="previewCategory" class="font-medium"><?= $firstTask ? h($firstTask['categorie']) : '—' ?></span></div>
          <div class="flex items-center justify-between"><span class="text-gray-600">Durée :</span><span id="previewDuration" class="font-medium"><?= $firstTask ? h($firstTask['duration_days']) . ' jour(s)' : '—' ?></span></div>
          <div class="flex items-center justify-between"><span class="text-gray-600">Statut :</span><span id="previewStatus" class="font-medium"><?= $firstTask ? (((int)$firstTask['is_active'] === 1) ? '<span class="badge-active px-3 py-1 rounded-full text-sm">Actif</span>' : '<span class="badge-inactive px-3 py-1 rounded-full text-sm">Désactivé</span>') : '—' ?></span></div>
        </div>
      </div>

      <div class="bg-gray-100 card-radius p-6 soft-shadow border border-orange-100">
        <div class="flex items-center gap-2 mb-4">
          <button id="tabDept" onclick="switchTab('dept')" class="tab-active px-4 py-2 rounded-full-xl text-sm font-medium transition">Département</button>
          <button id="tabCompany" onclick="switchTab('company')" class="tab-inactive px-4 py-2 rounded-full-xl text-sm font-medium transition">Entreprise</button>
        </div>
        <h2 class="text-xl font-light mb-3" style="color:#FF4800">Classement</h2>
        <div id="rankDept" class="space-y-3">
          <?php if (!empty($rankingDept)): $rank=1; foreach($rankingDept as $row): ?>
            <div class="flex items-center gap-3 bg-white rounded-full-xl p-3 soft-shadow">
              <div class="rank-badge rank-<?= $rank ?>"><?= $rank ?></div>
              <div class="flex-1 font-medium truncate"><?= h($row['nom']) ?></div>
              <div class="text-sm font-bold" style="color:#FF4800"><?= number_format((float)$row['total_xp'], 0, ',', ' ') ?> XP</div>
            </div>
          <?php $rank++; endforeach; else: ?>
            <div class="text-gray-400 text-center py-4">Aucune donnée.</div>
          <?php endif; ?>
        </div>
        <div id="rankCompany" class="space-y-3 hidden">
          <?php if (!empty($rankingCompany)): $rank=1; foreach($rankingCompany as $row): ?>
            <div class="flex items-center gap-3 bg-white rounded-full-xl p-3 soft-shadow">
              <div class="rank-badge rank-<?= $rank ?>"><?= $rank ?></div>
              <div class="flex-1 font-medium truncate"><?= h($row['nom']) ?></div>
              <div class="text-sm font-bold" style="color:#FF4800"><?= number_format((float)$row['total_xp'], 0, ',', ' ') ?> XP</div>
            </div>
          <?php $rank++; endforeach; else: ?>
            <div class="text-gray-400 text-center py-4">Aucune donnée.</div>
          <?php endif; ?>
        </div>
      </div>
      <button id="openCreateBtn" class="w-full btn-orange px-6 py-4 rounded-full-xl text-lg font-semibold shadow-lg hover:scale-[1.02] transition">
        + Créer une tâche
      </button>

    </section>

    <section class="lg:col-span-2 bg-gray-100 card-radius p-6 soft-shadow border border-orange-100">
      <div class="flex items-center justify-between gap-3 mb-4">
        <h2 class="text-2xl font-light" style="color:#FF4800">Liste des tâches</h2>
        <button id="openFilterBtn" class="bg-white border border-orange-200 px-5 py-2 rounded-full-xl text-sm font-medium hover:bg-orange-50 transition" style="color:#FF4800">
          Filtre
        </button>
      </div>

      <div class="mb-4 relative">
        <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5" stroke-linecap="round"/></svg>
        <input id="taskSearch" placeholder="Rechercher une tâche…" class="w-full bg-white border border-gray-200 pl-12 pr-4 py-3 rounded-full-xl outline-none focus:border-orange-300 transition" />
      </div>

      <div id="tasksList" class="space-y-3">
        <?php if (!empty($tasks)):
          foreach ($tasks as $i => $task):
            $isActive = (int)$task['is_active'] === 1;
            $leaves = difficultyLeaves($task['difficulty']);
            $hidden = $i >= $VISIBLE_TASKS ? 'task-hidden' : '';
        ?>
          <article
            class="task-card bg-white p-4 flex items-center justify-between gap-4 cursor-pointer <?= $hidden ?>"
            style="<?= $i >= $VISIBLE_TASKS ? 'display:none' : '' ?>"
            data-id="<?= (int)$task['id'] ?>"
            data-title="<?= h($task['titre_fr']) ?>"
            data-xp="<?= h($task['xp_gain']) ?>"
            data-score="<?= h($task['co2_kg']) ?>"
            data-leaves="<?= $leaves ?>"
            data-category="<?= h($task['categorie']) ?>"
            data-duration="<?= h($task['duration_days']) ?>"
            data-status="<?= $isActive ? 'Actif' : 'Désactivé' ?>"
            data-domaine="<?= h($task['domaine']) ?>"
            data-difficulty="<?= h($task['difficulty']) ?>"
            data-users="<?= (int)$task['users_count'] ?>"
          >
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex gap-0.5"><?= renderLeaves($leaves) ?></span>
                <h3 class="text-lg font-medium truncate"><?= h($task['titre_fr']) ?></h3>
                <span class="text-xs px-2 py-0.5 rounded-full <?= $isActive ? 'badge-active' : 'badge-inactive' ?>"><?= $isActive ? 'Actif' : 'Désactivé' ?></span>
              </div>
              <div class="mt-1.5 flex flex-wrap gap-1.5 text-xs">
                <span class="bg-orange-50 text-orange-700 px-2.5 py-0.5 rounded-full"><?= h($task['categorie']) ?></span>
                <span class="bg-gray-100 text-gray-600 px-2.5 py-0.5 rounded-full"><?= h($task['domaine']) ?></span>
                <span class="bg-gray-100 text-gray-600 px-2.5 py-0.5 rounded-full"><?= h($task['duration_days']) ?> j</span>
                <span class="bg-gray-100 text-gray-600 px-2.5 py-0.5 rounded-full"><?= (int)$task['users_count'] ?> users</span>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <button class="btn-toggle bg-gray-100 hover:bg-orange-50 transition px-4 py-2 rounded-full-xl text-sm" data-id="<?= (int)$task['id'] ?>" data-active="<?= $isActive ? '1' : '0' ?>"><?= $isActive ? 'Désactiver' : 'Réactiver' ?></button>
              <button class="btn-params w-10 h-10 rounded-full bg-gray-100 hover:bg-orange-50 transition flex items-center justify-center" data-id="<?= (int)$task['id'] ?>" aria-label="Paramètres">
                <svg class="w-5 h-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
              </button>
            </div>
          </article>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <?php if (count($tasks) > $VISIBLE_TASKS): ?>
      <div class="mt-4 text-center">
        <button id="showMoreBtn" onclick="showMoreTasks()" class="btn-orange px-8 py-3 rounded-full-xl font-semibold shadow hover:scale-[1.02] transition">
          Voir plus (<?= count($tasks) - $VISIBLE_TASKS ?> tâches)
        </button>
      </div>
      <?php endif; ?>

      <?php if (empty($tasks)): ?>
        <div class="text-center text-gray-400 py-16">Aucune tâche trouvée.</div>
      <?php endif; ?>
    </section>
  </div>
</main>

<div id="createModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white p-6 card-radius w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-auto">
    <div class="flex items-center justify-between mb-5">
      <h3 class="text-2xl font-light" style="color:#FF4800">Créer une tâche</h3>
      <button class="close-modal text-3xl leading-none text-gray-400 hover:text-gray-700">&times;</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <input id="modal_title" class="border border-gray-200 p-3 rounded-xl focus:border-orange-400 outline-none" placeholder="Titre FR *" />
      <input id="modal_title_en" class="border border-gray-200 p-3 rounded-xl focus:border-orange-400 outline-none" placeholder="Titre EN" />
      <textarea id="modal_descr_fr" class="border border-gray-200 p-3 rounded-xl md:col-span-2 focus:border-orange-400 outline-none" rows="3" placeholder="Description FR"></textarea>
      <textarea id="modal_descr_en" class="border border-gray-200 p-3 rounded-xl md:col-span-2 focus:border-orange-400 outline-none" rows="2" placeholder="Description EN"></textarea>
      <select id="modal_difficulty" class="border border-gray-200 p-3 rounded-xl focus:border-orange-400 outline-none">
        <?php if (!empty($difficulties)): foreach ($difficulties as $d): ?><option><?= h($d) ?></option><?php endforeach; else: ?><option>facile</option><option>moyen</option><option>difficile</option><?php endif; ?>
      </select>
      <div id="modal_leaves_preview" class="flex items-center gap-1 border border-gray-100 p-3 rounded-xl bg-gray-50"></div>
      <input id="modal_xp" type="number" min="0" value="10" class="border border-gray-200 p-3 rounded-xl focus:border-orange-400 outline-none" placeholder="XP" />
      <input id="modal_score" type="number" step="0.01" value="0.1" class="border border-gray-200 p-3 rounded-xl focus:border-orange-400 outline-none" placeholder="Score CO2 (kg)" />
      <input id="modal_duration" type="number" min="1" value="1" class="border border-gray-200 p-3 rounded-xl focus:border-orange-400 outline-none" placeholder="Durée (jours)" />
      <input id="modal_max_actions" type="number" min="1" value="1" class="border border-gray-200 p-3 rounded-xl focus:border-orange-400 outline-none" placeholder="Max actions/jour" />
      <select id="modal_type" class="border border-gray-200 p-3 rounded-xl focus:border-orange-400 outline-none">
        <?php if (!empty($types)): foreach ($types as $t): ?><option><?= h($t) ?></option><?php endforeach; else: ?><option>ecologique</option><option>social</option><?php endif; ?>
      </select>
      <select id="modal_category" class="border border-gray-200 p-3 rounded-xl focus:border-orange-400 outline-none">
        <?php if (!empty($categories)): foreach ($categories as $c): ?><option><?= h($c) ?></option><?php endforeach; else: ?><option>Général</option><option>Mobilité</option><option>Numérique</option><option>Bureau</option><option>Recyclage</option><option>Autre</option><?php endif; ?>
      </select>
    </div>
    <div class="flex justify-end gap-2 mt-5">
      <button class="close-modal px-5 py-3 rounded-xl border text-gray-600 hover:bg-gray-50">Annuler</button>
      <button id="createBtn" class="btn-orange px-6 py-3 rounded-xl font-semibold shadow">Valider</button>
    </div>
  </div>
</div>

<div id="filterModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-40 p-4">
  <div class="bg-white p-6 card-radius w-full max-w-lg shadow-2xl">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-2xl font-light" style="color:#FF4800">Filtrer les tâches</h3>
      <button class="close-modal text-3xl leading-none text-gray-400">&times;</button>
    </div>
    <div class="space-y-3">
      <select id="filter_type" class="w-full border border-gray-200 p-3 rounded-xl focus:border-orange-400 outline-none">
        <option value="">Type / domaine</option>
        <?php foreach ($types as $t): ?><option><?= h($t) ?></option><?php endforeach; ?>
      </select>
      <select id="filter_difficulty" class="w-full border border-gray-200 p-3 rounded-xl focus:border-orange-400 outline-none">
        <option value="">Difficulté</option>
        <?php foreach ($difficulties as $d): ?><option><?= h($d) ?></option><?php endforeach; ?>
      </select>
      <select id="filter_category" class="w-full border border-gray-200 p-3 rounded-xl focus:border-orange-400 outline-none">
        <option value="">Catégorie</option>
        <?php foreach ($categories as $c): ?><option><?= h($c) ?></option><?php endforeach; ?>
      </select>
      <div class="flex justify-end gap-2 pt-2">
        <button id="filterReset" class="px-4 py-3 rounded-xl border text-gray-600 hover:bg-gray-50">Réinitialiser</button>
        <button id="filterApply" class="btn-orange px-5 py-3 rounded-xl font-semibold">Appliquer</button>
      </div>
    </div>
  </div>
</div>

<div id="paramsModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white p-6 rounded-2xl w-full max-w-md shadow-2xl relative">
    <button id="paramsClose" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-3xl leading-none">&times;</button>
    <h3 class="text-2xl font-light mb-5" style="color:#FF4800">Gestion de la tâche</h3>
    <div class="space-y-3">
      <button id="editBtn" class="w-full flex items-center justify-between p-4 bg-orange-50 hover:bg-orange-100 rounded-xl text-left transition">
        <span class="font-medium" style="color:#FF4800">Modifier la tâche</span><span style="color:#FF4800">✎</span>
      </button>
      <button id="toggleBtn" class="w-full flex items-center justify-between p-4 bg-gray-100 hover:bg-yellow-50 rounded-xl text-left transition">
        <span id="toggleText" class="font-medium">Désactiver</span><span>⟲</span>
      </button>
      <button id="deleteBtn" class="w-full flex items-center justify-between p-4 bg-red-50 hover:bg-red-100 rounded-xl text-left transition">
        <span class="font-medium text-red-700">Supprimer</span><span class="text-red-600">🗑</span>
      </button>
    </div>
    <div class="mt-5 pt-4 border-t text-sm text-gray-500 flex justify-between gap-3">
      <span>XP: <strong id="paramsXp" style="color:#FF4800"></strong></span>
      <span>CO2: <strong id="paramsScore"></strong></span>
      <span>Users: <strong id="paramsUsers"></strong></span>
    </div>
  </div>
</div>

<script>
  const tasks = Array.from(document.querySelectorAll('.task-card'));
  const createModal = document.getElementById('createModal');
  const filterModal = document.getElementById('filterModal');
  const paramsModal = document.getElementById('paramsModal');
  const modalLeaves = document.getElementById('modal_leaves_preview');
  let currentId = null, currentActive = 1;

  function leafSVG() {
    return '<svg class="w-5 h-5" style="color:#FF4800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M11 20A7 7 0 019.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10ZM2 21c0-3 1.8-5.4 5.1-6C9.5 14.5 12 13 13 12" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  }
  function renderLeaves(n) { return leafSVG().repeat(Math.max(1, Math.min(3, n))); }

  function openModal(m) { m.classList.remove('hidden'); m.classList.add('flex'); }
  function closeModal(m) { m.classList.add('hidden'); m.classList.remove('flex'); }

  function switchTab(tab) {
    document.getElementById('rankDept').classList.toggle('hidden', tab !== 'dept');
    document.getElementById('rankCompany').classList.toggle('hidden', tab !== 'company');
    document.getElementById('tabDept').className = (tab === 'dept' ? 'tab-active' : 'tab-inactive') + ' px-4 py-2 rounded-full-xl text-sm font-medium transition';
    document.getElementById('tabCompany').className = (tab === 'company' ? 'tab-active' : 'tab-inactive') + ' px-4 py-2 rounded-full-xl text-sm font-medium transition';
  }

  function setPreview(card) {
    if (!card) return;
    document.getElementById('previewTitle').textContent = card.dataset.title || 'Titre';
    document.getElementById('previewXp').textContent = (card.dataset.xp || '?') + ' XP';
    document.getElementById('previewScore').textContent = (card.dataset.score || '?') + ' kg';
    document.getElementById('previewLeaves').innerHTML = renderLeaves(parseInt(card.dataset.leaves || '1', 10));
    document.getElementById('previewCategory').textContent = card.dataset.category || '—';
    document.getElementById('previewDuration').textContent = (card.dataset.duration || '—') + ' jour(s)';
    const active = card.dataset.status === 'Actif';
    document.getElementById('previewStatus').innerHTML = active
      ? '<span class="badge-active px-3 py-1 rounded-full text-sm">Actif</span>'
      : '<span class="badge-inactive px-3 py-1 rounded-full text-sm">Désactivé</span>';
  }

  function updateCreateLeaves() {
    const v = document.getElementById('modal_difficulty').value.toLowerCase();
    const n = v.includes('diffic') ? 3 : (v.includes('moy') ? 2 : 1);
    modalLeaves.innerHTML = renderLeaves(n);
  }

  if (tasks[0]) setPreview(tasks[0]);

  document.getElementById('openCreateBtn').addEventListener('click', () => { updateCreateLeaves(); openModal(createModal); });
  document.getElementById('openFilterBtn').addEventListener('click', () => openModal(filterModal));
  document.querySelectorAll('.close-modal, #paramsClose').forEach(btn => btn.addEventListener('click', () => { closeModal(createModal); closeModal(filterModal); closeModal(paramsModal); }));
  [createModal, filterModal, paramsModal].forEach(m => m.addEventListener('click', e => { if (e.target === m) closeModal(m); }));
  document.getElementById('modal_difficulty').addEventListener('change', updateCreateLeaves);
  updateCreateLeaves();

  document.getElementById('createBtn').addEventListener('click', async () => {
    const titre = document.getElementById('modal_title').value.trim();
    if (!titre) return alert('Le titre FR est obligatoire');
    const form = new FormData();
    form.append('ajax_action', 'create');
    form.append('titre_fr', titre);
    form.append('titre_en', document.getElementById('modal_title_en').value.trim());
    form.append('descr_fr', document.getElementById('modal_descr_fr').value.trim());
    form.append('descr_en', document.getElementById('modal_descr_en').value.trim());
    form.append('difficulty', document.getElementById('modal_difficulty').value);
    form.append('xp_gain', document.getElementById('modal_xp').value);
    form.append('co2_kg', document.getElementById('modal_score').value);
    form.append('duration_days', document.getElementById('modal_duration').value);
    form.append('max_actions_day', document.getElementById('modal_max_actions').value);
    form.append('domaine', document.getElementById('modal_type').value);
    form.append('categorie', document.getElementById('modal_category').value);
    const res = await fetch(location.href, { method: 'POST', body: form });
    const data = await res.json();
    if (data.success) location.reload();
    else alert(data.error || 'Erreur création');
  });

  let allTasksVisible = false;
  function showMoreTasks() {
    document.querySelectorAll('.task-hidden').forEach(el => { el.style.display = 'flex'; el.classList.remove('task-hidden'); });
    const btn = document.getElementById('showMoreBtn');
    if (btn) btn.style.display = 'none';
    allTasksVisible = true;
  }

  function applyFilters() {
    const type = document.getElementById('filter_type').value.toLowerCase();
    const diff = document.getElementById('filter_difficulty').value.toLowerCase();
    const cat = document.getElementById('filter_category').value.toLowerCase();
    const search = document.getElementById('taskSearch').value.toLowerCase();
    let shown = 0;
    tasks.forEach(card => {
      const title = (card.dataset.title || '').toLowerCase();
      const domaine = (card.dataset.domaine || '').toLowerCase();
      const difficulty = (card.dataset.difficulty || '').toLowerCase();
      const category = (card.dataset.category || '').toLowerCase();
      const visible = (!type || domaine === type) && (!diff || difficulty === diff) && (!cat || category === cat) && (!search || title.includes(search) || category.includes(search));
      card.style.display = visible ? 'flex' : 'none';
      if (visible) shown++;
    });
    const existing = document.getElementById('emptyState');
    if (!shown && !existing) {
      const div = document.createElement('div'); div.id = 'emptyState'; div.className = 'text-center text-gray-400 py-16'; div.textContent = 'Aucune tâche trouvée.';
      document.getElementById('tasksList').appendChild(div);
    } else if (shown && existing) { existing.remove(); }
  }

  document.getElementById('taskSearch').addEventListener('input', applyFilters);
  document.getElementById('filterApply').addEventListener('click', () => { applyFilters(); closeModal(filterModal); });
  document.getElementById('filterReset').addEventListener('click', () => {
    ['filter_type','filter_difficulty','filter_category'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('taskSearch').value = '';
    applyFilters();
  });

  tasks.forEach(card => {
    card.addEventListener('click', e => { if (e.target.closest('button')) return; setPreview(card); });
  });

  async function postAction(action, id) {
    const form = new FormData();
    form.append('ajax_action', action);
    form.append('challenge_id', id);
    const res = await fetch(location.href, { method: 'POST', body: form });
    return await res.json();
  }

  document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', async e => {
      e.stopPropagation();
      const data = await postAction('toggle', btn.dataset.id);
      if (data.success) location.reload();
      else alert(data.error || 'Erreur');
    });
  });

  document.querySelectorAll('.btn-params').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      const card = btn.closest('.task-card');
      currentId = btn.dataset.id;
      currentActive = card.dataset.status === 'Actif' ? 1 : 0;
      document.getElementById('paramsXp').textContent = card.dataset.xp || '';
      document.getElementById('paramsScore').textContent = card.dataset.score || '';
      document.getElementById('paramsUsers').textContent = card.dataset.users || '0';
      document.getElementById('toggleText').textContent = currentActive ? 'Désactiver' : 'Réactiver';
      openModal(paramsModal);
    });
  });

  document.getElementById('editBtn').addEventListener('click', () => { if (!currentId) return; window.location.href = 'super_admin_shift_manager_edit.php?id=' + encodeURIComponent(currentId); });
  document.getElementById('toggleBtn').addEventListener('click', async () => { if (!currentId) return; const data = await postAction('toggle', currentId); if (data.success) location.reload(); else alert(data.error || 'Erreur'); });
  document.getElementById('deleteBtn').addEventListener('click', async () => {
    if (!currentId || !confirm('Voulez-vous vraiment supprimer cette tâche ?')) return;
    const data = await postAction('delete', currentId);
    if (data.success) location.reload();
    else alert(data.error || 'Erreur');
  });
</script>
</body>
</html>