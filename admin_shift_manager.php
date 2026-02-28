<?php
session_start();
require_once 'db_connect.php';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Shift Manager - Tableau de bord</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .rounded-full-xl { border-radius: 999px; }
    .card-radius { border-radius: 12px; }
    .leaf { width:18px; height:18px; display:inline-block; margin-right:6px; vertical-align:middle; }
    .leaf svg { width:100%; height:100%; }
    :root{
      --bg:#ececec;
      --panel:#dcdcdc;
      --card:#bdbdbd;
      --accent:#9a9a9a;
    }
    body{ background: #fff; color:#111; }
  </style>
</head>
<header class="bg-gray-200 h-16 relative">
  <div class="absolute left-0 top-0 bottom-0 w-20 md:w-64 bg-gray-400 flex items-center justify-center">
    <div class="w-10 h-10 flex items-center justify-center" aria-hidden="true">
      <svg class="w-6 h-6 text-gray-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Logo Shift-Up">
        <path d="M12 2L4 5v6c0 5 3.5 9.7 8 11 4.5-1.3 8-6 8-11V5l-8-3z"
              stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" stroke-linecap="round" fill="none"/>
        <text x="12" y="15.3" text-anchor="middle" font-size="9" font-family="Segoe UI, Roboto, Arial, sans-serif"
              fill="currentColor" style="font-weight:700">S</text>
      </svg>
    </div>
  </div>

  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-20 md:pl-64 pr-6">
    <nav class="hidden md:flex items-center gap-8">
      <a href="#" class="text-gray-700 hover:text-gray-900">Shift manager</a>
      <a href="admin_gestion.php" class="text-gray-700 hover:text-gray-900">Gestion</a>
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
  <h1 class="text-3xl font-light mb-4"> Shift Manager</h1>
</div>

<?php
function leafSVG() {
    return '<span class="leaf" title="feuille"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10ZM2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path></svg></span>';
}
function renderLeaves($n){
    $n = max(0, min(3, (int)$n));
    $out = '';
    for($i=0;$i<$n;$i++) $out .= leafSVG();
    return $out;
}

$types = []; $categories = []; $difficulties = [];
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $types = $pdo->query("SELECT DISTINCT domaine FROM challenges WHERE domaine IS NOT NULL AND domaine<>''")->fetchAll(PDO::FETCH_COLUMN);
        $categories = $pdo->query("SELECT DISTINCT categorie FROM challenges WHERE categorie IS NOT NULL AND categorie<>''")->fetchAll(PDO::FETCH_COLUMN);
        $difficulties = $pdo->query("SELECT DISTINCT difficulty FROM challenges WHERE difficulty IS NOT NULL AND difficulty<>''")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e){
    }
}
?>

<div class="max-w-screen-2xl mx-auto p-8">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-1 space-y-6">

      <div class="bg-gray-200 card-radius p-6">
        <h2 class="text-2xl mb-4">Titre</h2>

        <div class="grid grid-cols-1 gap-4">
          <div class="flex items-center justify-between">
            <span>Expérience de la tâche :</span>
            <span id="xp_shown">XP</span>
          </div>

          <div class="flex items-center justify-between">
            <span>Récompense de la tâche :</span>
            <span id="score_shown">Score</span>
          </div>

          <div class="flex items-center justify-between">
            <label>Type :</label>
            <select id="create_type" class="border rounded p-2 w-40">
              <?php if(!empty($types)) foreach($types as $t) echo '<option>'.htmlspecialchars($t).'</option>'; else echo '<option>ecologique</option><option>social</option>'; ?>
            </select>
          </div>

          <div class="flex items-center justify-between">
            <label>Catégorie :</label>
            <input id="create_category" type="text" class="border rounded p-2 w-40" placeholder="Général" />
          </div>

          <div class="flex items-center gap-2">
            <label>Durée de la tâche :</label>
            <input id="create_duration" type="number" min="1" value="1" class="border rounded p-2 w-24"/>
            <span>jours</span>
          </div>

          <div class="flex justify-center mt-4">
            <button id="openCreateBtn" class="bg-gray-400 px-6 py-2 rounded-full-xl">Crée la tâche</button>
          </div>
        </div>
      </div>

      <div>
        <h2 class="text-2xl mb-4">Classement Solo</h2>
        <div class="bg-gray-200 card-radius p-4 space-y-3">
          <?php
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                echo "<div class='text-sm text-red-600'>La connexion DB (\$pdo) n'est pas définie dans db_connect.php</div>";
            } else {
                try {
                    $sql = "SELECT u.id, u.pseudo, COUNT(ua.id) as actions FROM users u LEFT JOIN user_actions ua ON ua.user_id = u.id GROUP BY u.id ORDER BY actions DESC LIMIT 3";
                    $stmt = $pdo->query($sql);
                    $pos = 1;
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $pseudo = htmlspecialchars($row['pseudo'] ?? 'user'.$row['id']);
                        echo "<div class='flex items-center gap-4 bg-gray-300 rounded p-3'>
                                <div class='w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center'>$pos</div>
                                <div class='flex-1'>$pseudo</div>
                              </div>";
                        $pos++;
                    }
                } catch (Exception $e) {
                    echo "<div class='text-red-500'>Erreur classement</div>";
                }
            }
          ?>
        </div>
      </div>

    </div>

    <div class="lg:col-span-2 bg-gray-200 card-radius p-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl">Liste des taches :</h2>
        <button id="openFilterBtn" class="bg-gray-400 px-4 py-2 rounded card-radius">Filtre</button>
      </div>

      <div class="mb-4">
        <input id="task_search" placeholder="Rechercher une tâche" class="w-full p-3 rounded-full-xl border" />
      </div>

      <div id="tasksList" class="space-y-4">
        <?php
          if (!isset($pdo) || !($pdo instanceof PDO)) {
              echo "<div class='text-red-600'>Impossible d'afficher les tâches : \$pdo introuvable.</div>";
          } else {
              try {
                  $pdo->exec("CREATE TABLE IF NOT EXISTS disabled_challenges (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    challenge_id INT NOT NULL UNIQUE,
                    disabled_at DATETIME DEFAULT CURRENT_TIMESTAMP
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                  $sql = "SELECT c.*, IF(dc.challenge_id IS NOT NULL,1,0) as disabled,
                             (SELECT COUNT(DISTINCT ua.user_id) FROM user_actions ua WHERE ua.challenge_id=c.id) as users_count
                          FROM challenges c
                          LEFT JOIN disabled_challenges dc ON dc.challenge_id = c.id
                          ORDER BY c.id DESC";
                  $stmt = $pdo->query($sql);
                  while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      $id = (int)$r['id'];
                      $titre = htmlspecialchars($r['titre_fr'] ?? 'Tâche');
                      $difficulty = htmlspecialchars($r['difficulty'] ?? 'facile');
                      $xp = (int)($r['xp_gain'] ?? 0);
                      $score = (float)($r['co2_kg'] ?? 0);
                      $domaine = htmlspecialchars($r['domaine'] ?? '');
                      $categorie = htmlspecialchars($r['categorie'] ?? '');
                      $duration = (int)($r['duration_days'] ?? 0);
                      $disabled = (int)$r['disabled'];
                      $users_count = (int)$r['users_count'];

                      $dl = strtolower($difficulty);
                      $leafCount = 1;
                      if (strpos($dl,'diffic') !== false || $dl==='difficile') $leafCount = 3;
                      elseif (strpos($dl,'moy') !== false || $dl==='moyen') $leafCount = 2;
                      $leaves_html = renderLeaves($leafCount);

                      echo '<div class="flex items-center justify-between bg-gray-300 p-4 rounded card-radius"'
                         .' data-title="'.htmlspecialchars($titre, ENT_QUOTES).'"'
                         .' data-difficulty="'.htmlspecialchars($difficulty, ENT_QUOTES).'"'
                         .' data-xp="'.htmlspecialchars($xp, ENT_QUOTES).'"'
                         .' data-score="'.htmlspecialchars($score, ENT_QUOTES).'"'
                         .' data-domaine="'.htmlspecialchars($domaine, ENT_QUOTES).'"'
                         .' data-categorie="'.htmlspecialchars($categorie, ENT_QUOTES).'"'
                         .' data-duration="'.htmlspecialchars($duration, ENT_QUOTES).'"'
                         .' data-id="'.htmlspecialchars($id, ENT_QUOTES).'"'
                         .' data-users="'.htmlspecialchars($users_count, ENT_QUOTES).'"'
                         .'>';

                      echo '<div class="flex items-center gap-3">';
                      echo '<div class="mr-3">'.$leaves_html.'</div>';
                      echo '<div class="text-xl font-light">'.$titre.'</div>';
                      echo '</div>';

                      echo '<div class="flex items-center gap-3">';
                      echo '<button class="px-4 py-2 rounded bg-gray-100" onclick="toggleDisable('.$id.', this)">'.($disabled ? 'Réactiver' : 'Désactiver').'</button>';
                      echo '<button class="px-3 py-2 rounded bg-gray-100" onclick="openParams('.$id.')" title="Paramètres">
                              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="5" width="18" height="2" rx="1" fill="currentColor"/>
                                <rect x="3" y="11" width="18" height="2" rx="1" fill="currentColor"/>
                                <rect x="3" y="17" width="18" height="2" rx="1" fill="currentColor"/>
                              </svg>
                            </button>';
                      echo '</div>';

                      echo '</div>';
                  }
              } catch (Exception $e) {
                  echo "<div class='text-red-600'>Erreur lecture tâches</div>";
              }
          }
        ?>
      </div>
    </div>

  </div>
</div>

<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white p-6 rounded card-radius w-11/12 md:w-1/3">
    <h3 class="text-xl mb-4">Créer une tâche</h3>
    <div class="space-y-3">
      <input id="modal_title" placeholder="Titre de la tâche (FR)" class="w-full border p-2 rounded" />
      <textarea id="modal_descr_fr" placeholder="Description (FR)" class="w-full border p-2 rounded" rows="3"></textarea>
      <textarea id="modal_descr_en" placeholder="Description (EN)" class="w-full border p-2 rounded" rows="2"></textarea>

      <div class="flex gap-2 items-center">
        <select id="modal_difficulty" class="border p-2 rounded w-1/2">
          <?php
            if (!empty($difficulties)) {
                foreach($difficulties as $d) echo '<option>'.htmlspecialchars($d).'</option>';
            } else {
                echo '<option>facile</option><option>moyen</option><option>difficile</option>';
            }
          ?>
        </select>
        <div id="modal_difficulty_preview" class="ml-2"></div>

        <input id="modal_xp" type="number" min="0" value="10" class="border p-2 rounded w-1/2" placeholder="XP">
      </div>

      <div class="flex gap-2">
        <input id="modal_score" type="number" step="0.01" value="0.1" class="border p-2 rounded w-1/2" placeholder="Score">
        <input id="modal_duration" type="number" min="1" value="1" class="border p-2 rounded w-1/2" placeholder="Durée (jours)">
      </div>

      <div class="flex gap-2">
        <select id="modal_type" class="border p-2 rounded w-1/2">
          <?php if(!empty($types)) foreach($types as $t) echo '<option>'.htmlspecialchars($t).'</option>'; else echo '<option>ecologique</option><option>social</option>'; ?>
        </select>
        <input id="modal_category" type="text" class="border p-2 rounded w-1/2" placeholder="Catégorie">
      </div>

      <div class="flex justify-end gap-2 mt-4">
        <button id="createCancel" class="px-4 py-2 rounded border">Annuler</button>
        <button id="createValidate" class="px-4 py-2 rounded bg-blue-600 text-white">Valider</button>
      </div>
    </div>
  </div>
</div>

<div id="filterModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-40">
  <div class="bg-white p-6 rounded card-radius w-11/12 md:w-1/4">
    <h3 class="text-xl mb-4">Filtrer</h3>
    <div class="space-y-3">
      <select id="filter_type" class="w-full border p-2 rounded">
        <option value="">-- Type (domaine) --</option>
        <?php foreach($types as $t) echo '<option>'.htmlspecialchars($t).'</option>'; ?>
      </select>
      <select id="filter_difficulty" class="w-full border p-2 rounded">
        <option value="">-- Difficulté --</option>
        <?php if(!empty($difficulties)) foreach($difficulties as $d) echo '<option>'.htmlspecialchars($d).'</option>'; ?>
      </select>
      <input id="filter_category" class="w-full border p-2 rounded" placeholder="Catégorie" />
      <div class="flex justify-end gap-2 mt-4">
        <button id="filterReset" class="px-4 py-2 rounded border">Réinitialiser</button>
        <button id="filterApply" class="px-4 py-2 rounded bg-blue-600 text-white">Appliquer</button>
      </div>
    </div>
  </div>
</div>

<div id="paramsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white p-6 rounded card-radius w-11/12 md:w-1/3">
    <h3 class="text-xl mb-4">Paramètres de la tâche</h3>
    <div id="paramsBody" class="space-y-2">
      <div><strong>Titre :</strong> <span id="p_title"></span></div>
      <div><strong>XP :</strong> <span id="p_xp"></span></div>
      <div><strong>Nombre de personnes :</strong> <span id="p_users"></span></div>
    </div>
    <div class="flex justify-end mt-4">
      <button id="paramsClose" class="px-4 py-2 rounded border">Fermer</button>
    </div>
  </div>
</div>

<script>
  function leafSVG(){
    return '<span class="leaf" title="feuille"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10ZM2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path></svg></span>';
  }

  const createModal = document.getElementById('createModal');
  const openCreateBtn = document.getElementById('openCreateBtn');
  const createCancel = document.getElementById('createCancel');
  const createValidate = document.getElementById('createValidate');

  const filterModal = document.getElementById('filterModal');
  const openFilterBtn = document.getElementById('openFilterBtn');
  const filterApply = document.getElementById('filterApply');
  const filterReset = document.getElementById('filterReset');

  const paramsModal = document.getElementById('paramsModal');
  const paramsClose = document.getElementById('paramsClose');

  openCreateBtn.addEventListener('click', ()=> { createModal.classList.remove('hidden'); updateCreateDifficultyPreview(); });
  createCancel.addEventListener('click', ()=> createModal.classList.add('hidden'));

  openFilterBtn.addEventListener('click', ()=> filterModal.classList.remove('hidden'));
  filterReset.addEventListener('click', ()=> {
    document.getElementById('filter_type').value = '';
    document.getElementById('filter_difficulty').value = '';
    document.getElementById('filter_category').value = '';
    applyFilters();
  });
  filterApply.addEventListener('click', ()=> { applyFilters(); filterModal.classList.add('hidden'); });

  paramsClose.addEventListener('click', ()=> paramsModal.classList.add('hidden'));

  const modalDifficulty = document.getElementById('modal_difficulty');
  const modalDifficultyPreview = document.getElementById('modal_difficulty_preview');
  function getLeafCountFromDifficulty(value) {
    const v = (value||'').toLowerCase();
    if (v.includes('diffic')) return 3;
    if (v.includes('moy')) return 2;
    return 1;
  }
  function updateCreateDifficultyPreview(){
    const val = modalDifficulty.value;
    const leaves = getLeafCountFromDifficulty(val);
    modalDifficultyPreview.innerHTML = '';
    for (let i=0;i<leaves;i++) modalDifficultyPreview.innerHTML += leafSVG();
  }
  modalDifficulty.addEventListener('change', updateCreateDifficultyPreview);

  createValidate.addEventListener('click', ()=>{
    const titre = document.getElementById('modal_title').value.trim();
    const descr_fr = document.getElementById('modal_descr_fr').value.trim();
    const descr_en = document.getElementById('modal_descr_en').value.trim();
    const difficulty = document.getElementById('modal_difficulty').value;
    const xp = document.getElementById('modal_xp').value;
    const score = document.getElementById('modal_score').value;
    const duration = document.getElementById('modal_duration').value;
    const type = document.getElementById('modal_type').value;
    const category = document.getElementById('modal_category').value;

    if (!titre) { alert('Titre requis'); return; }

    fetch('admin_shift_manager_create.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        titre_fr: titre,
        descr_fr: descr_fr,
        descr_en: descr_en,
        difficulty: difficulty,
        xp_gain: xp,
        score: score,
        duration_days: duration,
        domaine: type,
        categorie: category
      })
    })
    .then(r=>r.json())
    .then(data=>{
      if (data.success) location.reload();
      else alert('Erreur création: '+(data.error||''));
    })
    .catch(err=> { console.error(err); alert('Erreur réseau'); });
  });

  function applyFilters(){
    const t = document.getElementById('filter_type').value;
    const d = document.getElementById('filter_difficulty').value;
    const c = document.getElementById('filter_category').value.toLowerCase();
    const search = document.getElementById('task_search').value.toLowerCase();

    document.querySelectorAll('#tasksList > div').forEach(div=>{
      const title = (div.dataset.title||'').toLowerCase();
      const domaine = (div.dataset.domaine||'').toLowerCase();
      const difficulty = (div.dataset.difficulty||'').toLowerCase();
      const category = (div.dataset.categorie||'').toLowerCase();

      let visible = true;
      if (t && domaine !== t) visible = false;
      if (d && difficulty !== d) visible = false;
      if (c && !category.includes(c)) visible = false;
      if (search && !title.includes(search)) visible = false;

      div.style.display = visible ? 'flex' : 'none';
    });
  }
  document.getElementById('task_search').addEventListener('input', applyFilters);

  function toggleDisable(id, btn) {
    btn.disabled = true;
    fetch('admin_shift_manager_desactiver.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ challenge_id: id })
    })
    .then(r=>r.json())
    .then(data=>{
      btn.disabled = false;
      if (data.success) {
        btn.innerText = (data.action === 'disabled') ? 'Réactiver' : 'Désactiver';
      } else {
        alert('Erreur: ' + (data.error||''));
      }
    })
    .catch(err=> { btn.disabled = false; console.error(err); alert('Erreur réseau'); });
  }

  function openParams(id){
    fetch('admin_shift_manager_params.php?challenge_id=' + encodeURIComponent(id), { method:'GET', headers:{ 'Accept':'application/json' } })
      .then(r=>{
        if (!r.ok) throw new Error('HTTP '+r.status);
        return r.json();
      })
      .then(data=>{
        if (data.success) {
          document.getElementById('p_title').innerText = data.titre;
          document.getElementById('p_xp').innerText = data.xp;
          document.getElementById('p_users').innerText = data.users_count;
          paramsModal.classList.remove('hidden');
        } else {
          alert('Erreur: ' + (data.error||''));
        }
      })
      .catch(err=> { console.error(err); alert('Erreur réseau'); });
  }

  updateCreateDifficultyPreview();
</script>

</body>
</html>