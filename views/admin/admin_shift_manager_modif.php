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
    return '<svg class="w-5 h-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M11 20A7 7 0 019.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10ZM2 21c0-3 1.8-5.4 5.1-6C9.5 14.5 12 13 13 12" stroke-linecap="round" stroke-linejoin="round"/></svg>';
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
    } catch (Throwable $e) {
        // ignore
    }
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
            $titreFr = trim((string)($_POST['titre_fr'] ?? ''));
            $titreEn = trim((string)($_POST['titre_en'] ?? ''));
            $descrFr = trim((string)($_POST['descr_fr'] ?? ''));
            $descrEn = trim((string)($_POST['descr_en'] ?? ''));
            $difficulty = trim((string)($_POST['difficulty'] ?? 'facile'));
            $xp = (int)($_POST['xp_gain'] ?? 10);
            $co2 = (float)($_POST['co2_kg'] ?? 0);
            $domaine = trim((string)($_POST['domaine'] ?? 'ecologique'));
            $categorie = trim((string)($_POST['categorie'] ?? 'Général'));
            $duration = (int)($_POST['duration_days'] ?? 1);
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
                VALUES
                (?, ?, ?, ?, NULL, ?, ?, ?, ?, NULL, ?, ?, ?, ?, 1)'
            );
            $stmt->execute([
                $titreFr,
                $titreEn,
                $descrFr,
                $descrEn,
                $xp,
                $co2,
                $difficulty,
                $domaine,
                $categorie,
                $duration,
                $maxActions,
                $companyId,
            ]);

            echo json_encode(['success' => true]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$types = [];
$categories = [];
$difficulties = [];
$ranking = [];
$tasks = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $types = $pdo->query("SELECT DISTINCT domaine FROM challenges WHERE domaine IS NOT NULL AND domaine <> '' ORDER BY domaine")->fetchAll(PDO::FETCH_COLUMN);
        $categories = $pdo->query("SELECT DISTINCT categorie FROM challenges WHERE categorie IS NOT NULL AND categorie <> '' ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);
        $difficulties = $pdo->query("SELECT DISTINCT difficulty FROM challenges WHERE difficulty IS NOT NULL AND difficulty <> '' ORDER BY difficulty")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        // ignore
    }

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
        $ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $ranking = [];
    }

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
    } catch (Throwable $e) {
        $tasks = [];
    }
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
    body { background:#fff; color:#111; }
    .rounded-full-xl { border-radius:9999px; }
    .card-radius { border-radius:14px; }
    .soft-shadow { box-shadow:0 8px 24px rgba(0,0,0,.08); }
  </style>
</head>
<body class="min-h-screen">
<header class="bg-gray-200 h-24 relative">
  <div class="absolute left-0 top-0 bottom-0 w-24 md:w-72 bg-gray-400 flex items-center justify-center">
    <a href="admin_dashboard.php" aria-label="Accueil" class="w-12 h-12 flex items-center justify-center">
      <svg class="w-8 h-8 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
        <path d="M12 2L4 5v6c0 5 3.5 9.7 8 11 4.5-1.3 8-6 8-11V5l-8-3z" stroke-linejoin="round" stroke-linecap="round"/>
        <text x="12" y="15.3" text-anchor="middle" font-size="9" font-family="Segoe UI, Roboto, Arial, sans-serif" fill="currentColor" style="font-weight:700">S</text>
      </svg>
    </a>
  </div>
  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-24 md:pl-72 pr-6">
    <nav class="hidden md:flex items-center gap-10 text-[17px]">
      <a href="admin_shift_manager.php" class="font-medium">Shift manager</a>
      <a href="admin_gestion.php">Gestion</a>
      <div class="w-11 h-11 rounded-full border-2 border-gray-900 flex items-center justify-center">
        <svg class="w-7 h-7 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
          <circle cx="12" cy="8" r="3"/>
          <path d="M6 20c0-3 4-5 6-5s6 2 6 5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
    </nav>
  </div>
</header>

<main class="max-w-screen-2xl mx-auto px-4 md:px-8 py-8">
  <h1 class="text-4xl font-light mb-6">Shift Manager</h1>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <section class="space-y-6">
      <div class="bg-gray-200 card-radius p-6 soft-shadow">
        <h2 class="text-3xl font-light" id="previewTitle"><?php echo $firstTask ? h($firstTask['titre_fr']) : 'Titre'; ?></h2>
        <div class="mt-4 space-y-4 text-[15px]">
          <div class="flex items-center justify-between gap-4"><span>Expérience de la tâche :</span><span id="previewXp" class="font-medium"><?php echo $firstTask ? h($firstTask['xp_gain']) : 'XP'; ?></span></div>
          <div class="flex items-center justify-between gap-4"><span>Récompense de la tâche :</span><span id="previewScore" class="font-medium"><?php echo $firstTask ? h($firstTask['co2_kg']) : 'Score'; ?></span></div>
          <div class="flex items-center justify-between gap-4"><span>Difficulté :</span><span id="previewLeaves" class="inline-flex items-center gap-1"><?php echo $firstTask ? renderLeaves(difficultyLeaves($firstTask['difficulty'])) : renderLeaves(1); ?></span></div>
          <div class="flex items-center justify-between gap-4"><span>Catégorie :</span><span id="previewCategory" class="font-medium"><?php echo $firstTask ? h($firstTask['categorie']) : '—'; ?></span></div>
          <div class="flex items-center justify-between gap-4"><span>Durée :</span><span id="previewDuration" class="font-medium"><?php echo $firstTask ? h($firstTask['duration_days']) . ' jour(s)' : '—'; ?></span></div>
          <div class="flex items-center justify-between gap-4"><span>Statut :</span><span id="previewStatus" class="font-medium"><?php echo $firstTask ? (((int)$firstTask['is_active'] === 1) ? 'Actif' : 'Désactivé') : '—'; ?></span></div>
        </div>
      </div>

      <div class="bg-gray-200 card-radius p-6 soft-shadow">
        <div class="flex items-center justify-between gap-3 mb-4">
          <h2 class="text-3xl font-light">Classement</h2>
          <span class="bg-gray-300 px-5 py-2 rounded-full-xl">Département</span>
        </div>
        <div class="space-y-3">
          <?php if (!empty($ranking)): ?>
            <?php $rank = 1; foreach ($ranking as $row): ?>
              <div class="flex items-center gap-4 bg-gray-300 rounded-full-xl p-3">
                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center font-semibold"><?php echo $rank; ?></div>
                <div class="flex-1 font-medium"><?php echo h($row['nom']); ?></div>
                <div class="text-sm font-bold"><?php echo number_format((float)$row['total_points'], 0, ',', ' '); ?> pts</div>
              </div>
            <?php $rank++; endforeach; ?>
          <?php else: ?>
            <div class="text-gray-500 text-center py-4">Aucune donnée de classement.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="bg-gray-200 card-radius p-6 soft-shadow">
        <h2 class="text-2xl mb-4">Créer une tâche</h2>
        <button id="openCreateBtn" class="w-full bg-gray-400 px-6 py-3 rounded-full-xl text-lg">Créer la tâche</button>
      </div>
    </section>

    <section class="lg:col-span-2 bg-gray-200 card-radius p-6 soft-shadow">
      <div class="flex items-center justify-between gap-3 mb-4">
        <h2 class="text-3xl font-light">Liste des tâches</h2>
        <button id="openFilterBtn" class="bg-gray-400 px-6 py-3 rounded-full-xl text-lg">Filtre</button>
      </div>

      <div class="mb-4">
        <div class="relative">
          <svg class="w-6 h-6 absolute left-5 top-1/2 -translate-y-1/2 text-gray-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="11" cy="11" r="7"/>
            <path d="M20 20l-3.5-3.5" stroke-linecap="round"/>
          </svg>
          <input id="taskSearch" placeholder="Rechercher une tâche" class="w-full bg-gray-400/80 placeholder-gray-800 px-16 py-4 rounded-full-xl outline-none text-lg" />
        </div>
      </div>

      <div id="tasksList" class="space-y-4 max-h-[70vh] overflow-auto pr-1">
        <?php if (!empty($tasks)): ?>
          <?php foreach ($tasks as $task): ?>
            <?php
              $isActive = (int)$task['is_active'] === 1;
              $leaves = difficultyLeaves($task['difficulty']);
            ?>
            <article
              class="task-card bg-gray-300 p-4 md:p-5 flex items-center justify-between gap-4 cursor-pointer"
              data-id="<?php echo (int)$task['id']; ?>"
              data-title="<?php echo h($task['titre_fr']); ?>"
              data-xp="<?php echo h($task['xp_gain']); ?>"
              data-score="<?php echo h($task['co2_kg']); ?>"
              data-leaves="<?php echo $leaves; ?>"
              data-category="<?php echo h($task['categorie']); ?>"
              data-duration="<?php echo h($task['duration_days']); ?>"
              data-status="<?php echo $isActive ? 'Actif' : 'Désactivé'; ?>"
              data-domaine="<?php echo h($task['domaine']); ?>"
              data-difficulty="<?php echo h($task['difficulty']); ?>"
            >
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-3 flex-wrap">
                  <div class="flex items-center shrink-0"><?php echo renderLeaves($leaves); ?></div>
                  <h3 class="text-2xl md:text-[28px] font-light truncate"><?php echo h($task['titre_fr']); ?></h3>
                  <span class="text-xs px-3 py-1 rounded-full-xl <?php echo $isActive ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>"><?php echo $isActive ? 'Actif' : 'Désactivé'; ?></span>
                </div>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                  <span class="bg-gray-100 px-3 py-1 rounded-full-xl"><?php echo h($task['categorie']); ?></span>
                  <span class="bg-gray-100 px-3 py-1 rounded-full-xl"><?php echo h($task['domaine']); ?></span>
                  <span class="bg-gray-100 px-3 py-1 rounded-full-xl"><?php echo h($task['duration_days']); ?> j</span>
                  <span class="bg-gray-100 px-3 py-1 rounded-full-xl"><?php echo (int)$task['users_count']; ?> users</span>
                </div>
              </div>

              <div class="flex items-center gap-3 shrink-0">
                <button class="btn-toggle bg-gray-100 hover:bg-white transition px-5 py-3 rounded-full-xl" data-id="<?php echo (int)$task['id']; ?>" data-active="<?php echo $isActive ? '1' : '0'; ?>"><?php echo $isActive ? 'Désactiver' : 'Réactiver'; ?></button>
                <button class="btn-params w-14 h-14 rounded-full-xl bg-gray-100 hover:bg-white transition flex items-center justify-center" data-id="<?php echo (int)$task['id']; ?>" aria-label="Paramètres">
                  <svg class="w-7 h-7 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v.01M12 12v.01M12 19v.01" stroke-linecap="round"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                </button>
              </div>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center text-gray-600 py-16">Aucune tâche trouvée.</div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</main>

<!-- Modal création -->
<div id="createModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white p-6 card-radius w-full max-w-2xl shadow-xl max-h-[90vh] overflow-auto">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-2xl font-light">Créer une tâche</h3>
      <button class="close-modal text-3xl leading-none">&times;</button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <input id="modal_title" class="border p-3 rounded-xl" placeholder="Titre FR *" />
      <input id="modal_title_en" class="border p-3 rounded-xl" placeholder="Titre EN" />
      <textarea id="modal_descr_fr" class="border p-3 rounded-xl md:col-span-2" rows="3" placeholder="Description FR"></textarea>
      <textarea id="modal_descr_en" class="border p-3 rounded-xl md:col-span-2" rows="2" placeholder="Description EN"></textarea>
      <select id="modal_difficulty" class="border p-3 rounded-xl">
        <?php if (!empty($difficulties)): foreach ($difficulties as $d): ?>
          <option><?php echo h($d); ?></option>
        <?php endforeach; else: ?>
          <option>facile</option><option>moyen</option><option>difficile</option>
        <?php endif; ?>
      </select>
      <div id="modal_leaves_preview" class="flex items-center gap-1 border p-3 rounded-xl bg-gray-50"></div>
      <input id="modal_xp" type="number" min="0" value="10" class="border p-3 rounded-xl" placeholder="XP" />
      <input id="modal_score" type="number" step="0.01" value="0.1" class="border p-3 rounded-xl" placeholder="Score CO2" />
      <input id="modal_duration" type="number" min="1" value="1" class="border p-3 rounded-xl" placeholder="Durée (jours)" />
      <input id="modal_max_actions" type="number" min="1" value="1" class="border p-3 rounded-xl" placeholder="Max actions/jour" />
      <select id="modal_type" class="border p-3 rounded-xl">
        <?php if (!empty($types)): foreach ($types as $t): ?>
          <option><?php echo h($t); ?></option>
        <?php endforeach; else: ?>
          <option>ecologique</option><option>social</option>
        <?php endif; ?>
      </select>
      <input id="modal_category" class="border p-3 rounded-xl" placeholder="Catégorie" />
    </div>

    <div class="flex justify-end gap-2 mt-5">
      <button class="close-modal px-5 py-3 rounded-xl border">Annuler</button>
      <button id="createBtn" class="px-5 py-3 rounded-xl bg-gray-900 text-white">Valider</button>
    </div>
  </div>
</div>

<!-- Modal filtre -->
<div id="filterModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-40 p-4">
  <div class="bg-white p-6 card-radius w-full max-w-lg shadow-xl">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-2xl font-light">Filtrer</h3>
      <button class="close-modal text-3xl leading-none">&times;</button>
    </div>
    <div class="space-y-3">
      <select id="filter_type" class="w-full border p-3 rounded-xl">
        <option value="">Type / domaine</option>
        <?php foreach ($types as $t): ?><option><?php echo h($t); ?></option><?php endforeach; ?>
      </select>
      <select id="filter_difficulty" class="w-full border p-3 rounded-xl">
        <option value="">Difficulté</option>
        <?php foreach ($difficulties as $d): ?><option><?php echo h($d); ?></option><?php endforeach; ?>
      </select>
      <select id="filter_category" class="w-full border p-3 rounded-xl">
        <option value="">Catégorie</option>
        <?php foreach ($categories as $c): ?><option><?php echo h($c); ?></option><?php endforeach; ?>
      </select>
      <div class="flex justify-end gap-2 pt-2">
        <button id="filterReset" class="px-4 py-3 rounded-xl border">Réinitialiser</button>
        <button id="filterApply" class="px-4 py-3 rounded-xl bg-gray-900 text-white">Appliquer</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal paramètres -->
<div id="paramsModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white p-6 rounded-2xl w-full max-w-md shadow-xl relative">
    <button id="paramsClose" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-3xl leading-none">&times;</button>
    <h3 class="text-2xl font-light mb-4">Gestion de la tâche</h3>

    <div class="space-y-3">
      <button id="editBtn" class="w-full flex items-center justify-between p-4 bg-gray-100 hover:bg-gray-200 rounded-xl text-left">
        <span class="font-medium">Modifier la tâche</span>
        <span>✎</span>
      </button>
      <button id="toggleBtn" class="w-full flex items-center justify-between p-4 bg-gray-100 hover:bg-yellow-50 rounded-xl text-left">
        <span id="toggleText" class="font-medium">Désactiver</span>
        <span>⟲</span>
      </button>
      <button id="deleteBtn" class="w-full flex items-center justify-between p-4 bg-red-50 hover:bg-red-100 rounded-xl text-left">
        <span class="font-medium text-red-700">Supprimer</span>
        <span class="text-red-600">🗑</span>
      </button>
    </div>

    <div class="mt-6 pt-4 border-t text-sm text-gray-500 flex justify-between gap-3">
      <span>XP: <strong id="paramsXp"></strong></span>
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
  let currentId = null;
  let currentActive = 1;

  function leafSVG() {
    return '<svg class="w-5 h-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M11 20A7 7 0 019.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10ZM2 21c0-3 1.8-5.4 5.1-6C9.5 14.5 12 13 13 12" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  }

  function renderLeaves(n) {
    return leafSVG().repeat(Math.max(1, Math.min(3, n)));
  }

  function openModal(modal) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  function closeModal(modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  function setPreview(card) {
    if (!card) return;
    document.getElementById('previewTitle').textContent = card.dataset.title || 'Titre';
    document.getElementById('previewXp').textContent = card.dataset.xp || 'XP';
    document.getElementById('previewScore').textContent = card.dataset.score || 'Score';
    document.getElementById('previewLeaves').innerHTML = renderLeaves(parseInt(card.dataset.leaves || '1', 10));
    document.getElementById('previewCategory').textContent = card.dataset.category || '—';
    document.getElementById('previewDuration').textContent = (card.dataset.duration || '—') + ' jour(s)';
    document.getElementById('previewStatus').textContent = card.dataset.status || '—';
  }

  function updateCreateLeaves() {
    const difficulty = document.getElementById('modal_difficulty').value.toLowerCase();
    const leaves = difficulty.includes('diffic') ? 3 : (difficulty.includes('moy') ? 2 : 1);
    modalLeaves.innerHTML = renderLeaves(leaves);
  }

  if (tasks[0]) setPreview(tasks[0]);

  document.getElementById('openCreateBtn').addEventListener('click', () => {
    updateCreateLeaves();
    openModal(createModal);
  });

  document.getElementById('openFilterBtn').addEventListener('click', () => openModal(filterModal));

  document.querySelectorAll('.close-modal, #paramsClose').forEach(btn => {
    btn.addEventListener('click', () => {
      closeModal(createModal);
      closeModal(filterModal);
      closeModal(paramsModal);
    });
  });

  [createModal, filterModal, paramsModal].forEach(modal => {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal(modal);
    });
  });

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

  function applyFilters() {
    const type = document.getElementById('filter_type').value.toLowerCase();
    const diff = document.getElementById('filter_difficulty').value.toLowerCase();
    const cat = document.getElementById('filter_category').value.toLowerCase();
    const search = document.getElementById('taskSearch').value.toLowerCase();

    tasks.forEach(card => {
      const title = (card.dataset.title || '').toLowerCase();
      const domaine = (card.dataset.domaine || '').toLowerCase();
      const difficulty = (card.dataset.difficulty || '').toLowerCase();
      const category = (card.dataset.category || '').toLowerCase();

      const visible =
        (!type || domaine === type) &&
        (!diff || difficulty === diff) &&
        (!cat || category === cat) &&
        (!search || title.includes(search) || category.includes(search));

      card.style.display = visible ? 'flex' : 'none';
    });

    const visibleTasks = tasks.filter(card => card.style.display !== 'none');
    const existing = document.getElementById('emptyState');
    if (!visibleTasks.length && !existing) {
      const div = document.createElement('div');
      div.id = 'emptyState';
      div.className = 'text-center text-gray-600 py-16';
      div.textContent = 'Aucune tâche trouvée.';
      document.getElementById('tasksList').appendChild(div);
    } else if (visibleTasks.length && existing) {
      existing.remove();
    }
  }

  document.getElementById('taskSearch').addEventListener('input', applyFilters);
  document.getElementById('filterApply').addEventListener('click', () => {
    applyFilters();
    closeModal(filterModal);
  });
  document.getElementById('filterReset').addEventListener('click', () => {
    document.getElementById('filter_type').value = '';
    document.getElementById('filter_difficulty').value = '';
    document.getElementById('filter_category').value = '';
    document.getElementById('taskSearch').value = '';
    applyFilters();
  });

  tasks.forEach(card => {
    card.addEventListener('click', (e) => {
      if (e.target.closest('button')) return;
      setPreview(card);
    });
  });

  async function postAction(action, id) {
    const form = new FormData();
    form.append('ajax_action', action);
    form.append('challenge_id', id);
    const res = await fetch(location.href, { method: 'POST', body: form });
    return await res.json();
  }

  document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const data = await postAction('toggle', btn.dataset.id);
      if (data.success) location.reload();
      else alert(data.error || 'Erreur');
    });
  });

  document.querySelectorAll('.btn-params').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const card = btn.closest('.task-card');
      currentId = btn.dataset.id;
      currentActive = parseInt(card.dataset.status === 'Actif' ? '1' : '0', 10);

      document.getElementById('paramsXp').textContent = card.dataset.xp || '';
      document.getElementById('paramsScore').textContent = card.dataset.score || '';
      document.getElementById('paramsUsers').textContent = card.dataset.users || '0';
      document.getElementById('toggleText').textContent = currentActive ? 'Désactiver' : 'Réactiver';
      openModal(paramsModal);
    });
  });

  document.getElementById('editBtn').addEventListener('click', () => {
    if (!currentId) return;
    window.location.href = 'admin_shift_manager_edit.php?id=' + encodeURIComponent(currentId);
  });

  document.getElementById('toggleBtn').addEventListener('click', async () => {
    if (!currentId) return;
    const data = await postAction('toggle', currentId);
    if (data.success) location.reload();
    else alert(data.error || 'Erreur');
  });

  document.getElementById('deleteBtn').addEventListener('click', async () => {
    if (!currentId || !confirm('Voulez-vous vraiment supprimer cette tâche ?')) return;
    const data = await postAction('delete', currentId);
    if (data.success) location.reload();
    else alert(data.error || 'Erreur');
  });
</script>
</body>
</html>
