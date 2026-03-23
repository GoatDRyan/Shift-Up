<?php 
session_start();
require_once '../../config/db_connect.php';

?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Shift Manager - Tableau de bord</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    body { font-family: 'Inter', sans-serif; background-color: #f9fafb; }
    .rounded-full-xl { border-radius: 999px; }
    .card-radius { border-radius: 20px; }
    .leaf { width:18px; height:18px; display:inline-block; margin-right:4px; vertical-align:middle; }
    .leaf svg { width:100%; height:100%; }
    .card-shadow { box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
        ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-thumb { background: #FF4800; border-radius: 10px; }
  </style>
</head>
<body class="text-gray-900">

<header class="bg-[#FF4800] h-16 sticky top-0 z-50 shadow-md">
  <div class="absolute left-0 top-0 bottom-0 w-20 md:w-64 bg-[#FF4800] flex items-center justify-center border-r border-orange-600">
    <a href="admin_dashboard.php">
      <div class="w-10 h-10 flex items-center justify-center" aria-hidden="true">
        <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2L4 5v6c0 5 3.5 9.7 8 11 4.5-1.3 8-6 8-11V5l-8-3z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" fill="none"/>
          <text x="12" y="15.5" text-anchor="middle" font-size="10" fill="currentColor" style="font-weight:900">S</text>
        </svg>
      </div>
    </a>
  </div>

  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-20 md:pl-64 pr-8">
    <nav class="hidden md:flex items-center gap-10">
      <a href="admin_shift_manager_modif.php" class="text-white font-semibold border-b-2 border-white pb-1">Shift manager</a>
      <a href="admin_gestion.php" class="text-white/80 hover:text-white font-semibold transition-colors">Gestion</a>
      <a href="admin_profile.php" class="w-10 h-10 rounded-full border-2 border-white/50 hover:border-white flex items-center justify-center transition-all">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      </a>
    </nav>
  </div>
</header>

<div class="max-w-screen-2xl mx-auto px-8 pt-8">
  <h1 class="text-4xl font-black text-gray-900 tracking-tight italic uppercase">Shift <span class="text-[#FF4800]">Manager</span></h1>
</div>

<?php
function leafSVG() {
    return '<span class="leaf" title="feuille"><svg xmlns="http://www.w3.org/2000/svg" class="text-[#FF4800]" viewBox="0 0 24 24" fill="currentColor"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10ZM2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path></svg></span>';
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
    } catch (Exception $e){ }
}
?>

<div class="max-w-screen-2xl mx-auto p-8">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <div class="lg:col-span-1 space-y-8">
      
      <div class="bg-white card-radius p-8 card-shadow border border-gray-100">
        <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
            <span class="w-2 h-6 bg-[#FF4800] rounded-full"></span> Nouveau Challenge
        </h2>

        <div class="space-y-5">
          <div class="flex items-center justify-between bg-gray-50 p-3 rounded-xl">
            <span class="text-sm font-semibold text-gray-500">Expérience :</span>
            <span id="xp_shown" class="font-bold text-[#FF4800]">-- XP</span>
          </div>

          <div class="flex items-center justify-between bg-gray-50 p-3 rounded-xl">
            <span class="text-sm font-semibold text-gray-500">Récompense :</span>
            <span id="score_shown" class="font-bold text-green-600">-- CO₂</span>
          </div>

          <div class="space-y-1">
            <label class="text-xs font-bold uppercase text-gray-400 ml-1">Domaine</label>
            <select id="create_type" class="w-full bg-white border border-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-orange-500 outline-none transition-all">
              <?php if(!empty($types)) foreach($types as $t) echo '<option>'.htmlspecialchars($t).'</option>'; else echo '<option>ecologique</option><option>social</option>'; ?>
            </select>
          </div>

          <div class="space-y-1">
            <label class="text-xs font-bold uppercase text-gray-400 ml-1">Catégorie</label>
            <input id="create_category" type="text" class="w-full border border-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Ex: Mobilité" />
          </div>

          <div class="flex items-center gap-3">
            <div class="flex-1 space-y-1">
                <label class="text-xs font-bold uppercase text-gray-400 ml-1">Durée</label>
                <div class="relative">
                    <input id="create_duration" type="number" min="1" value="1" class="w-full border border-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-orange-500 outline-none"/>
                    <span class="absolute right-3 top-3 text-gray-400">jours</span>
                </div>
            </div>
          </div>

          <button id="openCreateBtn" class="w-full bg-[#FF4800] hover:bg-[#e64100] text-white font-bold py-4 rounded-2xl shadow-lg shadow-orange-200 transition-all hover:-translate-y-1 active:scale-95">
            Démarrer la création
          </button>
        </div>
      </div>

      <div>
        <h2 class="text-xl font-bold mb-4 px-2 italic">Classement <span class="text-[#FF4800]">Solo</span></h2>
        <div class="bg-white card-radius p-6 card-shadow border border-gray-100 space-y-4">
          <?php
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                echo "<div class='text-sm text-red-600 italic text-center'>Connexion perdue...</div>";
            } else {
                try {
                    $sql = "SELECT u.id, u.pseudo, COUNT(ua.id) as actions FROM users u LEFT JOIN user_actions ua ON ua.user_id = u.id GROUP BY u.id ORDER BY actions DESC LIMIT 3";
                    $stmt = $pdo->query($sql);
                    $pos = 1;
                    $colors = ['bg-orange-100 text-[#FF4800]', 'bg-gray-100 text-gray-600', 'bg-orange-50 text-orange-400'];
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $pseudo = htmlspecialchars($row['pseudo'] ?? 'user'.$row['id']);
                        $colorClass = $colors[$pos-1] ?? 'bg-gray-50';
                        echo "<div class='flex items-center gap-4 p-3 rounded-2xl hover:bg-gray-50 transition-colors cursor-default border border-transparent hover:border-gray-100'>
                                <div class='w-10 h-10 rounded-full $colorClass flex items-center justify-center font-black text-lg'>$pos</div>
                                <div class='flex-1 font-bold text-gray-700'>$pseudo</div>
                                <div class='text-xs font-bold text-gray-400'>".(int)$row['actions']." act.</div>
                              </div>";
                        $pos++;
                    }
                } catch (Exception $e) { echo "<div class='text-red-500'>Erreur classement</div>"; }
            }
          ?>
        </div>
      </div>
    </div>

    <div class="lg:col-span-2 bg-white card-radius p-8 card-shadow border border-gray-100">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <h2 class="text-2xl font-bold">Liste des tâches</h2>
        <button id="openFilterBtn" class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-6 py-2.5 rounded-full font-bold text-gray-600 transition-all">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
          Filtrer
        </button>
      </div>

      <div class="relative mb-8">
        <span class="absolute left-4 top-3.5 text-gray-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </span>
        <input id="task_search" placeholder="Rechercher par titre..." class="w-full pl-12 pr-6 py-3.5 rounded-2xl bg-gray-50 border border-transparent focus:bg-white focus:border-[#FF4800] outline-none transition-all shadow-inner" />
      </div>

      <div id="tasksList" class="space-y-3">
        <?php
          if (!isset($pdo) || !($pdo instanceof PDO)) {
              echo "<div class='text-red-600 p-4 text-center border-2 border-dashed rounded-xl'>\$pdo introuvable.</div>";
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

                      echo '<div class="flex items-center justify-between bg-white p-5 rounded-2xl border border-gray-100 hover:border-orange-200 hover:shadow-md transition-all group"'
                         .' data-title="'.htmlspecialchars($titre, ENT_QUOTES).'"'
                         .' data-difficulty="'.htmlspecialchars($difficulty, ENT_QUOTES).'"'
                         .' data-xp="'.htmlspecialchars($xp, ENT_QUOTES).'"'
                         .' data-score="'.htmlspecialchars($score, ENT_QUOTES).'"'
                         .' data-domaine="'.htmlspecialchars($domaine, ENT_QUOTES).'"'
                         .' data-categorie="'.htmlspecialchars($categorie, ENT_QUOTES).'"'
                         .' data-duration="'.htmlspecialchars($duration, ENT_QUOTES).'"'
                         .' data-id="'.htmlspecialchars($id, ENT_QUOTES).'"'
                         .' data-users="'.htmlspecialchars($users_count, ENT_QUOTES).'"'
                         .'>' ;

                      echo '<div class="flex items-center gap-4">';
                      echo '<div>'.$leaves_html.'</div>';
                      echo '<div>';
                      echo '<div class="text-lg font-bold text-gray-800 group-hover:text-[#FF4800] transition-colors">'.$titre.'</div>';
                      echo '<div class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-0.5">'.$categorie.' • '.$xp.' XP</div>';
                      echo '</div>';
                      echo '</div>';

                      echo '<div class="flex items-center gap-3">';
                      echo '<button class="px-5 py-2 rounded-full font-bold text-sm transition-all ' . ($disabled ? 'bg-gray-100 text-gray-400 hover:bg-green-100 hover:text-green-600' : 'bg-orange-50 text-[#FF4800] hover:bg-orange-100') . '" onclick="toggleDisable('.$id.', this)">'.($disabled ? 'Réactiver' : 'Désactiver').'</button>';
                      echo '<button class="p-2.5 rounded-full bg-gray-50 text-gray-400 hover:bg-gray-900 hover:text-white transition-all shadow-sm" onclick="openParams('.$id.')" title="Paramètres">
                              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                            </button>';
                      echo '</div>';

                      echo '</div>';
                  }
              } catch (Exception $e) { echo "<div class='text-red-600'>Erreur lecture tâches</div>"; }
          }
        ?>
      </div>
    </div>

  </div>
</div>

<div id="createModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden items-center justify-center z-[100] p-4">
  <div class="bg-white p-8 card-radius w-full max-w-xl shadow-2xl animate-in fade-in zoom-in duration-200 max-h-[90vh] overflow-y-auto">
    <h3 class="text-2xl font-black mb-6 flex items-center justify-between italic">
        CRÉER UNE TÂCHE
        <span class="text-[#FF4800] text-sm not-italic font-bold">SHIFT UP</span>
    </h3>
    <div class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input id="modal_title" placeholder="Titre (FR)" class="w-full border p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none font-medium" />
        <input id="modal_title_en" placeholder="Titre (EN)" class="w-full border p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none" />
      </div>
      <textarea id="modal_descr_fr" placeholder="Description détaillée (FR)" class="w-full border p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none" rows="3"></textarea>
      <textarea id="modal_descr_en" placeholder="Description détaillée (EN)" class="w-full border p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none" rows="2"></textarea>

      <div class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[150px] space-y-1">
            <label class="text-[10px] font-bold uppercase text-gray-400">Difficulté</label>
            <div class="flex items-center gap-3 bg-gray-50 border p-3 rounded-xl">
                <select id="modal_difficulty" class="bg-transparent outline-none flex-1 font-bold">
                  <?php
                    if (!empty($difficulties)) { foreach($difficulties as $d) echo '<option>'.htmlspecialchars($d).'</option>'; } 
                    else { echo '<option>facile</option><option>moyen</option><option>difficile</option>'; }
                  ?>
                </select>
                <div id="modal_difficulty_preview" class="flex items-center"></div>
            </div>
        </div>
        <div class="w-32 space-y-1">
            <label class="text-[10px] font-bold uppercase text-gray-400">Gain XP</label>
            <input id="modal_xp" type="number" value="10" class="w-full border p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none font-bold" placeholder="XP">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1">
            <label class="text-[10px] font-bold uppercase text-gray-400">Valeur CO₂ (kg)</label>
            <input id="modal_score" type="number" step="0.01" value="0.1" class="w-full border p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none font-bold text-green-600">
        </div>
        <div class="space-y-1">
            <label class="text-[10px] font-bold uppercase text-gray-400">Durée estimée</label>
            <input id="modal_duration" type="number" min="1" value="1" class="w-full border p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none font-bold">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1">
            <label class="text-[10px] font-bold uppercase text-gray-400">Domaine</label>
            <select id="modal_type" class="w-full border p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none">
              <?php if(!empty($types)) foreach($types as $t) echo '<option>'.htmlspecialchars($t).'</option>'; else echo '<option>ecologique</option><option>social</option>'; ?>
            </select>
        </div>
        <div class="space-y-1">
            <label class="text-[10px] font-bold uppercase text-gray-400">Catégorie</label>
            <input id="modal_category" type="text" class="w-full border p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Catégorie">
        </div>
      </div>

      <div class="flex justify-end gap-3 mt-8">
        <button id="createCancel" class="px-6 py-3 rounded-xl font-bold border-2 border-gray-100 hover:bg-gray-50 transition-colors">ANNULER</button>
        <button id="createValidate" class="px-8 py-3 rounded-xl bg-[#FF4800] text-white font-black shadow-lg shadow-orange-200 hover:scale-105 transition-all">CRÉER LE DÉFI</button>
      </div>
    </div>
  </div>
</div>

<div id="filterModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden items-center justify-center z-[90] p-4">
  <div class="bg-white p-8 card-radius w-full max-w-md shadow-2xl">
    <h3 class="text-xl font-bold mb-6">Affiner la liste</h3>
    <div class="space-y-4">
      <select id="filter_type" class="w-full border border-gray-200 p-3 rounded-xl outline-none focus:ring-2 focus:ring-orange-500">
        <option value="">-- Tous les domaines --</option>
        <?php foreach($types as $t) echo '<option>'.htmlspecialchars($t).'</option>'; ?>
      </select>
      <select id="filter_difficulty" class="w-full border border-gray-200 p-3 rounded-xl outline-none focus:ring-2 focus:ring-orange-500">
        <option value="">-- Toutes les difficultés --</option>
        <?php if(!empty($difficulties)) foreach($difficulties as $d) echo '<option>'.htmlspecialchars($d).'</option>'; ?>
      </select>
      <input id="filter_category" class="w-full border border-gray-200 p-3 rounded-xl outline-none focus:ring-2 focus:ring-orange-500" placeholder="Catégorie précise" />
      
      <div class="flex justify-between gap-3 mt-8">
        <button id="filterReset" class="text-gray-400 font-bold hover:text-gray-600">Réinitialiser</button>
        <button id="filterApply" class="bg-[#FF4800] text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-orange-100">Appliquer</button>
      </div>
    </div>
  </div>
</div>

<div id="paramsModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden items-center justify-center z-[110] p-4">
  <div class="bg-white p-8 card-radius w-full max-w-sm shadow-2xl border-t-8 border-[#FF4800]">
    <h3 class="text-2xl font-black mb-6 italic">STATISTIQUES</h3>
    <div id="paramsBody" class="space-y-4">
      <div class="bg-gray-50 p-4 rounded-2xl">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Titre du challenge</p>
          <p id="p_title" class="font-bold text-gray-800 text-lg"></p>
      </div>
      <div class="grid grid-cols-2 gap-4">
          <div class="bg-gray-50 p-4 rounded-2xl">
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Points XP</p>
              <p id="p_xp" class="text-2xl font-black text-[#FF4800]"></p>
          </div>
          <div class="bg-gray-50 p-4 rounded-2xl">
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Impact CO₂</p>
              <p id="p_score" class="text-2xl font-black text-green-600"></p>
          </div>
      </div>
      <div class="bg-orange-50 border border-orange-100 p-4 rounded-2xl flex items-center justify-between">
          <span class="font-bold text-gray-600 italic">Nombre de complétions :</span>
          <span id="p_users" class="text-2xl font-black text-[#FF4800]"></span>
      </div>
    </div>
    <div class="mt-8">
      <button id="paramsClose" class="w-full py-4 rounded-2xl bg-gray-900 text-white font-bold hover:bg-black transition-colors">FERMER</button>
    </div>
  </div>
</div>

<script>
  // Fonctions de support visuel
  function leafSVG(){
    return '<span class="leaf" title="feuille"><svg xmlns="http://www.w3.org/2000/svg" class="text-[#FF4800]" viewBox="0 0 24 24" fill="currentColor"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10ZM2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path></svg></span>';
  }

  // Modales
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

  // Event Listeners (Logique préservée à 100%)
  openCreateBtn.addEventListener('click', ()=> {
    createModal.classList.remove('hidden');
    createModal.classList.add('flex');
    updateCreateDifficultyPreview();
  });
  createCancel.addEventListener('click', ()=> {
    createModal.classList.add('hidden');
    createModal.classList.remove('flex');
  });

  openFilterBtn.addEventListener('click', ()=> {
    filterModal.classList.remove('hidden');
    filterModal.classList.add('flex');
  });
  filterReset.addEventListener('click', ()=> {
    document.getElementById('filter_type').value = '';
    document.getElementById('filter_difficulty').value = '';
    document.getElementById('filter_category').value = '';
    applyFilters();
  });
  filterApply.addEventListener('click', ()=> {
    applyFilters();
    filterModal.classList.add('hidden');
    filterModal.classList.remove('flex');
  });

  paramsClose.addEventListener('click', ()=> {
    paramsModal.classList.add('hidden');
    paramsModal.classList.remove('flex');
  });

  // Fermeture au clic extérieur
  [createModal, filterModal, paramsModal].forEach(modal => {
    modal.addEventListener('click', (ev) => {
      if (ev.target === modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }
    });
  });

  // Aperçu difficulté
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

  // Validation Création (Logique préservée)
  createValidate.addEventListener('click', ()=>{
    const titre = document.getElementById('modal_title').value.trim();
    const titre_en = document.getElementById('modal_title_en').value.trim();
    const descr_fr = document.getElementById('modal_descr_fr').value.trim();
    const descr_en = document.getElementById('modal_descr_en').value.trim();
    const difficulty = document.getElementById('modal_difficulty').value;
    const xp = document.getElementById('modal_xp').value;
    const score = document.getElementById('modal_score').value;
    const duration = document.getElementById('modal_duration').value;
    const type = document.getElementById('modal_type').value;
    const category = document.getElementById('modal_category').value;

    if (!titre) { alert('Titre (FR) requis'); return; }

    fetch('admin_shift_manager_create.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        titre_fr: titre, titre_en: titre_en, descr_fr: descr_fr, descr_en: descr_en,
        difficulty: difficulty, xp_gain: xp, score: score, duration_days: duration,
        domaine: type, categorie: category
      })
    })
    .then(r=>r.json())
    .then(data=>{
      if (data.success) location.reload();
      else alert('Erreur création: '+(data.error||''));
    })
    .catch(err=> { console.error(err); alert('Erreur réseau'); });
  });

  // Filtres (Logique préservée)
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

  // Actions (Désactiver / Params) (Logique préservée)
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
        location.reload(); // Rechargement pour mettre à jour les styles
      } else {
        alert('Erreur: ' + (data.error||''));
      }
    })
    .catch(err=> { btn.disabled = false; console.error(err); alert('Erreur réseau'); });
  }

  function openParams(id){
    fetch('admin_shift_manager_params.php?challenge_id=' + encodeURIComponent(id), { 
      method:'GET', headers:{ 'Accept':'application/json' } 
    })
    .then(r=>{ if (!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
    .then(data=>{
      if (data.success) {
        document.getElementById('p_title').innerText = data.titre;
        document.getElementById('p_xp').innerText = data.xp + ' XP';
        document.getElementById('p_score').innerText = ((typeof data.score === 'number') ? data.score.toFixed(2) : data.score) + ' kg';
        document.getElementById('p_users').innerText = data.users_count;
        paramsModal.classList.remove('hidden');
        paramsModal.classList.add('flex');
      } else { alert('Erreur: ' + (data.error||'')); }
    })
    .catch(err=> { console.error(err); alert('Erreur réseau'); });
  }
</script>
</body>
</html>