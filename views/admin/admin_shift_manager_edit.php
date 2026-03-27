<?php
session_start();
require_once '../../config/db_connect.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: admin_shift_manager.php');
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
        
        $message = "<div class='bg-green-100 text-green-700 p-4 rounded-xl mb-6 shadow-sm border border-green-200 text-center font-medium'>La tâche a été mise à jour avec succès !</div>";
    } catch (Exception $e) {
        $message = "<div class='bg-red-100 text-red-700 p-4 rounded-xl mb-6 shadow-sm border border-red-200 text-center font-medium'>Erreur : " . $e->getMessage() . "</div>";
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
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
    body { font-family: 'Inter', sans-serif; }
    .bg-main-orange { background-color: #FF4800; }
    .text-main-orange { color: #FF4800; }
    .border-main-orange { border-color: #FF4800; }
    .focus-ring-orange:focus { ring-color: #FF4800; outline-color: #FF4800; }
  </style>
</head>
<body class="bg-white text-gray-900 min-h-screen">

<header class="bg-main-orange h-20 relative shadow-md">
  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-between px-8">
    
    <div class="flex items-center gap-6">
        <a href="admin_shift_manager.php" class="flex items-center gap-2 text-white/90 hover:text-white transition group">
            <svg class="w-6 h-6 transform group-hover:-translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div class="h-8 w-[1px] bg-white/30 hidden md:block"></div>
        <nav class="flex items-center gap-8 ml-2">
            <a href="admin_shift_manager.php" class="text-white font-bold text-lg hover:opacity-80 transition">Shift Manager</a>
            <a href="admin_gestion.php" class="text-white font-medium text-lg hover:opacity-80 transition">Gestion</a>
        </nav>
    </div>

    <div class="flex items-center gap-4">
        <span class="text-white/80 text-sm font-medium hidden sm:block italic">Mode Édition</span>
        <a href="admin_profile.php" class="w-10 h-10 rounded-full border-2 border-white flex items-center justify-center text-white hover:bg-white hover:text-main-orange transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
        </a>
    </div>
  </div>
</header>

<div class="max-w-4xl mx-auto p-8">
  <div class="flex items-center gap-4 mb-8">
      <div class="w-2 h-10 bg-main-orange rounded-full"></div>
      <h1 class="text-3xl font-extrabold tracking-tight">Modifier : <span class="text-main-orange"><?= htmlspecialchars($task['titre_fr']) ?></span></h1>
  </div>

  <?= $message ?>

  <form method="POST" class="bg-white p-10 rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.04)] border border-gray-100 space-y-8">
    
    <div class="space-y-2">
      <label class="block text-sm font-bold text-gray-600 ml-1">Titre de la tâche (FR)</label>
      <input type="text" name="titre_fr" value="<?= htmlspecialchars($task['titre_fr']) ?>" required
             class="w-full p-4 rounded-2xl bg-gray-50 border border-transparent focus:border-main-orange focus:bg-white focus:ring-4 focus:ring-orange-100 outline-none transition-all text-lg">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="space-y-2">
        <label class="block text-sm font-bold text-gray-600 ml-1">Gain d'XP</label>
        <div class="relative">
            <input type="number" name="xp_gain" value="<?= $task['xp_gain'] ?>" required
                   class="w-full p-4 pl-12 rounded-2xl bg-gray-50 border border-transparent focus:border-main-orange focus:bg-white focus:ring-4 focus:ring-orange-100 outline-none transition-all">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-main-orange font-bold">★</span>
        </div>
      </div>
      <div class="space-y-2">
        <label class="block text-sm font-bold text-gray-600 ml-1">Score CO2 (kg)</label>
        <div class="relative">
            <input type="number" step="0.01" name="co2_kg" value="<?= $task['co2_kg'] ?>" required
                   class="w-full p-4 pl-12 rounded-2xl bg-gray-50 border border-transparent focus:border-main-orange focus:bg-white focus:ring-4 focus:ring-orange-100 outline-none transition-all">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-main-orange font-bold">☁</span>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="space-y-2">
        <label class="block text-sm font-bold text-gray-600 ml-1">Domaine</label>
        <input type="text" name="domaine" value="<?= htmlspecialchars($task['domaine']) ?>"
               class="w-full p-4 rounded-2xl bg-gray-50 border border-transparent focus:border-main-orange focus:bg-white focus:ring-4 focus:ring-orange-100 outline-none transition-all">
      </div>
      <div class="space-y-2">
        <label class="block text-sm font-bold text-gray-600 ml-1">Catégorie</label>
        <input type="text" name="categorie" value="<?= htmlspecialchars($task['categorie']) ?>"
               class="w-full p-4 rounded-2xl bg-gray-50 border border-transparent focus:border-main-orange focus:bg-white focus:ring-4 focus:ring-orange-100 outline-none transition-all">
      </div>
      <div class="space-y-2">
        <label class="block text-sm font-bold text-gray-600 ml-1">Difficulté</label>
        <select name="difficulty" class="w-full p-4 rounded-2xl bg-gray-50 border border-transparent focus:border-main-orange focus:bg-white focus:ring-4 focus:ring-orange-100 outline-none transition-all appearance-none cursor-pointer">
          <option value="Facile" <?= $task['difficulty'] == 'Facile' ? 'selected' : '' ?>>Facile (1 feuille)</option>
          <option value="Moyen" <?= $task['difficulty'] == 'Moyen' ? 'selected' : '' ?>>Moyen (2 feuilles)</option>
          <option value="Difficile" <?= $task['difficulty'] == 'Difficile' ? 'selected' : '' ?>>Difficile (3 feuilles)</option>
        </select>
      </div>
    </div>

    <div class="space-y-2">
      <label class="block text-sm font-bold text-gray-600 ml-1">Description</label>
      <textarea name="descr_fr" rows="5" 
                class="w-full p-4 rounded-2xl bg-gray-50 border border-transparent focus:border-main-orange focus:bg-white focus:ring-4 focus:ring-orange-100 outline-none transition-all"><?= htmlspecialchars($task['descr_fr']) ?></textarea>
    </div>

    <div class="flex items-center justify-end gap-6 pt-6 border-t border-gray-100">
      <a href="admin_shift_manager.php" class="px-6 py-3 text-gray-500 font-semibold hover:text-main-orange transition">Annuler</a>
      <button type="submit" class="bg-main-orange text-white px-12 py-4 rounded-2xl font-bold shadow-[0_10px_20px_rgba(255,72,0,0.3)] hover:scale-[1.02] active:scale-[0.98] transition-all">
        Enregistrer les modifications
      </button>
    </div>

  </form>
</div>

</body>
</html>