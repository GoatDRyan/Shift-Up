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
        
        $message = "<div class='bg-green-100 text-green-700 p-4 rounded-xl mb-6'>La tâche a été mise à jour avec succès !</div>";
    } catch (Exception $e) {
        $message = "<div class='bg-red-100 text-red-700 p-4 rounded-xl mb-6'>Erreur : " . $e->getMessage() . "</div>";
    }
}

$stmt = $pdo->prepare("SELECT * FROM challenges WHERE id = ?");
$stmt->execute([$id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    die("Tâche introuvable.");
}
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Modifier la tâche - Shift Manager</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<header class="bg-gray-200 h-16 relative mb-8">
  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-between px-6">
    <a href="super_admin_shift_manager.php" class="flex items-center gap-2 text-gray-700 hover:text-black transition">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
      Retour à la liste
    </a>
    <span class="font-bold text-gray-800">Mode Édition</span>
  </div>
</header>

<div class="max-w-3xl mx-auto p-6">
  <h1 class="text-3xl font-bold mb-8">Modifier : <?= htmlspecialchars($task['titre_fr']) ?></h1>

  <?= $message ?>

  <form method="POST" class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200 space-y-6">
    
    <div>
      <label class="block text-sm font-bold text-gray-700 mb-2">Titre de la tâche (FR)</label>
      <input type="text" name="titre_fr" value="<?= htmlspecialchars($task['titre_fr']) ?>" required
             class="w-full p-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gray-400 outline-none">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">Gain d'XP</label>
        <input type="number" name="xp_gain" value="<?= $task['xp_gain'] ?>" required
               class="w-full p-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gray-400 outline-none">
      </div>
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">Score CO2 (kg)</label>
        <input type="number" step="0.01" name="co2_kg" value="<?= $task['co2_kg'] ?>" required
               class="w-full p-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gray-400 outline-none">
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">Domaine</label>
        <input type="text" name="domaine" value="<?= htmlspecialchars($task['domaine']) ?>"
               class="w-full p-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gray-400 outline-none">
      </div>
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">Catégorie</label>
        <input type="text" name="categorie" value="<?= htmlspecialchars($task['categorie']) ?>"
               class="w-full p-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gray-400 outline-none">
      </div>
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">Difficulté</label>
        <select name="difficulty" class="w-full p-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gray-400 outline-none">
          <option value="Facile" <?= $task['difficulty'] == 'Facile' ? 'selected' : '' ?>>Facile (1 feuille)</option>
          <option value="Moyen" <?= $task['difficulty'] == 'Moyen' ? 'selected' : '' ?>>Moyen (2 feuilles)</option>
          <option value="Difficile" <?= $task['difficulty'] == 'Difficile' ? 'selected' : '' ?>>Difficile (3 feuilles)</option>
        </select>
      </div>
    </div>

    <div>
      <label class="block text-sm font-bold text-gray-700 mb-2">Description</label>
      <textarea name="descr_fr" rows="4" 
                class="w-full p-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gray-400 outline-none"><?= htmlspecialchars($task['descr_fr']) ?></textarea>
    </div>

    <div class="flex items-center justify-end gap-4 pt-4">
      <a href="super_admin_shift_manager.php" class="px-6 py-3 text-gray-600 hover:underline">Annuler</a>
      <button type="submit" class="bg-gray-800 text-white px-10 py-3 rounded-full font-bold shadow-lg hover:bg-black transition">
        Enregistrer les modifications
      </button>
    </div>

  </form>
</div>

</body>
</html>