<?php
session_start();
require_once '../../config/db_connect.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: super_admin_shift_manager.php');
    exit;
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre_fr = $_POST['titre_fr'];
    $xp_gain = $_POST['xp_gain'];
    $co2_kg = $_POST['co2_kg'];
    $domaine = $_POST['domaine'];
    $categorie = $_POST['categorie'];
    $difficulty = $_POST['difficulty'];
    $descr_fr = $_POST['descr_fr'];

    try {
        $sql = "UPDATE challenges SET 
                titre_fr = ?, 
                xp_gain = ?, 
                co2_kg = ?, 
                domaine = ?, 
                categorie = ?, 
                difficulty = ?, 
                descr_fr = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titre_fr, $xp_gain, $co2_kg, $domaine, $categorie, $difficulty, $descr_fr, $id]);
        $message = "<div class='bg-green-50 border border-green-200 text-green-700 p-4 rounded-xl mb-6'>La tâche a été mise à jour avec succès !</div>";
    } catch (Exception $e) {
        $message = "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl mb-6'>Erreur : " . $e->getMessage() . "</div>";
    }
}

$stmt = $pdo->prepare("SELECT * FROM challenges WHERE id = ?");
$stmt->execute([$id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) die("Tâche introuvable.");

$categories = [];
try {
    $categories = $pdo->query("SELECT DISTINCT categorie FROM challenges WHERE categorie IS NOT NULL AND categorie<>'' ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

$difficulties_list = ['facile', 'moyen', 'difficile'];
$types_list = [];
try {
    $types_list = $pdo->query("SELECT DISTINCT domaine FROM challenges WHERE domaine IS NOT NULL AND domaine<>'' ORDER BY domaine")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Modifier la tâche - Shift Manager</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root { --orange: #FF4800; }
    body { background: #fff; }
    .rounded-full-xl { border-radius: 999px; }
    .header-bg { background: #FF4800; }
    .btn-orange { background: #FF4800; color: #fff; }
    .btn-orange:hover { background: #cc3a00; }
    .input-style { width:100%; padding:.75rem 1rem; border-radius:.75rem; border:1px solid #e5e7eb; outline:none; transition:border-color .2s; }
    .input-style:focus { border-color: #FF4800; }
  </style>
</head>
<body class="bg-gray-50">

<header class="header-bg h-16 relative mb-8">
  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-between px-6">
    <a href="super_admin_shift_manager.php" class="flex items-center gap-2 text-white hover:text-orange-200 transition">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
      Retour à la liste
    </a>
    <span class="font-bold text-white">Mode Édition</span>
    <nav class="hidden md:flex items-center gap-6">
      <a href="super_admin_shift_manager.php" class="text-white hover:text-orange-200 transition">Shift Manager</a>
      <a href="superadmin_gestion.php" class="text-white hover:text-orange-200 transition">Gestion</a>
      <a href="super_admin_profile.php" class="w-9 h-9 rounded-full border-2 border-white flex items-center justify-center hover:bg-white/20 transition">
        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.5" fill="none"/>
          <path d="M6 20c0-3 4-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        </svg>
      </a>
    </nav>
  </div>
</header>

<div class="max-w-3xl mx-auto p-6">
  <h1 class="text-3xl font-bold mb-2" style="color:#FF4800">Modifier la tâche</h1>
  <p class="text-gray-500 mb-6"><?= htmlspecialchars($task['titre_fr']) ?></p>

  <?= $message ?>

  <form method="POST" class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 space-y-6">

    <div>
      <label class="block text-sm font-bold text-gray-700 mb-2">Titre de la tâche (FR)</label>
      <input type="text" name="titre_fr" value="<?= htmlspecialchars($task['titre_fr']) ?>" required class="input-style">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">Gain d'XP</label>
        <input type="number" name="xp_gain" value="<?= $task['xp_gain'] ?>" required class="input-style">
      </div>
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">Score CO2 (kg)</label>
        <input type="number" step="0.01" name="co2_kg" value="<?= $task['co2_kg'] ?>" required class="input-style">
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">Domaine</label>
        <select name="domaine" class="input-style">
          <?php if (!empty($types_list)): foreach ($types_list as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>" <?= $task['domaine'] == $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
          <?php endforeach; else: ?>
            <option value="ecologique" <?= $task['domaine'] == 'ecologique' ? 'selected' : '' ?>>ecologique</option>
            <option value="social" <?= $task['domaine'] == 'social' ? 'selected' : '' ?>>social</option>
          <?php endif; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">Catégorie</label>
        <select name="categorie" class="input-style">
          <?php if (!empty($categories)): foreach ($categories as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $task['categorie'] == $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; else: ?>
            <option value="Général" <?= $task['categorie'] == 'Général' ? 'selected' : '' ?>>Général</option>
            <option value="Mobilité" <?= $task['categorie'] == 'Mobilité' ? 'selected' : '' ?>>Mobilité</option>
            <option value="Numérique" <?= $task['categorie'] == 'Numérique' ? 'selected' : '' ?>>Numérique</option>
            <option value="Bureau" <?= $task['categorie'] == 'Bureau' ? 'selected' : '' ?>>Bureau</option>
            <option value="Recyclage" <?= $task['categorie'] == 'Recyclage' ? 'selected' : '' ?>>Recyclage</option>
            <option value="Autre" <?= $task['categorie'] == 'Autre' ? 'selected' : '' ?>>Autre</option>
          <?php endif; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">Difficulté</label>
        <select name="difficulty" class="input-style">
          <?php foreach ($difficulties_list as $d): ?>
            <option value="<?= $d ?>" <?= $task['difficulty'] == $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div>
      <label class="block text-sm font-bold text-gray-700 mb-2">Description</label>
      <textarea name="descr_fr" rows="4" class="input-style"><?= htmlspecialchars($task['descr_fr']) ?></textarea>
    </div>

    <div class="flex items-center justify-end gap-4 pt-4">
      <a href="super_admin_shift_manager.php" class="px-6 py-3 text-gray-600 hover:underline">Annuler</a>
      <button type="submit" class="btn-orange px-10 py-3 rounded-full-xl font-bold shadow-lg hover:scale-[1.02] transition">
        Enregistrer les modifications
      </button>
    </div>

  </form>
</div>

</body>
</html>