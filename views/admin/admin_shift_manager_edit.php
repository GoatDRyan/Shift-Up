<?php
require_once '../../includes/init.php';

$companyId = (int)$user['company_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    header('Location: admin_shift_manager.php');
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre_fr = trim($_POST['titre_fr'] ?? '');
    $titre_en = trim($_POST['titre_en'] ?? '');
    $xp_gain = (int)($_POST['xp_gain'] ?? 0);
    $co2_kg = (float)($_POST['co2_kg'] ?? 0);
    $domaine = trim($_POST['domaine'] ?? '');
    $categorie = trim($_POST['categorie'] ?? '');
    $difficulty = trim($_POST['difficulty'] ?? 'facile');
    $descr_fr = trim($_POST['descr_fr'] ?? '');
    $descr_en = trim($_POST['descr_en'] ?? '');
    $duration_days = (int)($_POST['duration_days'] ?? 1);
    $max_actions_day = (int)($_POST['max_actions_day'] ?? 1);

    try {
        $sql = "UPDATE challenges SET 
                titre_fr = ?, titre_en = ?, xp_gain = ?, co2_kg = ?, 
                domaine = ?, categorie = ?, difficulty = ?, 
                descr_fr = ?, descr_en = ?, duration_days = ?, max_actions_day = ? 
                WHERE id = ? AND company_id = ?";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $titre_fr, $titre_en, $xp_gain, $co2_kg, 
            $domaine, $categorie, $difficulty, 
            $descr_fr, $descr_en, $duration_days, $max_actions_day, 
            $id, $companyId
        ]);
        
        $message = "<div class='bg-green-100 text-green-700 p-4 rounded-xl mb-6 shadow-sm border border-green-200 text-center font-medium'>La tâche a été mise à jour avec succès !</div>";
    } catch (Exception $e) {
        $message = "<div class='bg-red-100 text-red-700 p-4 rounded-xl mb-6 shadow-sm border border-red-200 text-center font-medium'>Erreur lors de la mise à jour.</div>";
    }
}

$stmt = $pdo->prepare("SELECT * FROM challenges WHERE id = ? AND company_id = ?");
$stmt->execute([$id, $companyId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Action non autorisée</h2><p>Ce défi n'existe pas ou n'appartient pas à votre entreprise.</p><a href='admin_shift_manager.php'>Retour</a></div>");
}
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Modifier la tâche - Shift Manager</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap');
    body { font-family: 'Inter', sans-serif; }
    .bg-main-orange { background-color: #FF4800; }
    .text-main-orange { color: #FF4800; }
    .border-main-orange { border-color: #FF4800; }
    .focus-ring-orange:focus { ring-color: #FF4800; outline-color: #FF4800; }
  </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">

<header class="bg-main-orange h-20 relative shadow-md">
  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-between px-8">
    <div class="flex items-center gap-6">
        <a href="admin_shift_manager.php" class="flex items-center gap-2 text-white/90 hover:text-white transition group bg-white/10 p-2 rounded-full">
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
        <span class="text-white/80 text-sm font-bold tracking-widest uppercase hidden sm:block">Mode Édition</span>
        <a href="admin_profile.php" class="w-10 h-10 rounded-full border-2 border-white flex items-center justify-center text-white hover:bg-white hover:text-main-orange transition">
            <i class="fa-solid fa-user"></i>
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

  <form method="POST" class="bg-white p-10 rounded-[30px] shadow-2xl border-t-8 border-main-orange space-y-8">
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2">
          <label class="block text-xs font-black uppercase tracking-widest text-gray-400 ml-1">Titre de la tâche (FR) *</label>
          <input type="text" name="titre_fr" value="<?= htmlspecialchars($task['titre_fr']) ?>" required
                 class="w-full p-4 rounded-2xl bg-gray-50 border border-gray-200 focus:border-main-orange outline-none transition-all font-bold">
        </div>
        <div class="space-y-2">
          <label class="block text-xs font-black uppercase tracking-widest text-gray-400 ml-1">Titre de la tâche (EN)</label>
          <input type="text" name="titre_en" value="<?= htmlspecialchars($task['titre_en'] ?? '') ?>" 
                 class="w-full p-4 rounded-2xl bg-gray-50 border border-gray-200 focus:border-main-orange outline-none transition-all font-bold">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="space-y-2">
        <label class="block text-xs font-black uppercase tracking-widest text-gray-400 ml-1">Gain d'XP *</label>
        <div class="relative">
            <input type="number" name="xp_gain" value="<?= (int)$task['xp_gain'] ?>" required
                   class="w-full p-4 pl-12 rounded-2xl bg-gray-50 border border-gray-200 focus:border-main-orange outline-none transition-all font-bold">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-main-orange"><i class="fa-solid fa-star"></i></span>
        </div>
      </div>
      <div class="space-y-2">
        <label class="block text-xs font-black uppercase tracking-widest text-gray-400 ml-1">Score CO₂ (kg) *</label>
        <div class="relative">
            <input type="number" step="0.01" name="co2_kg" value="<?= (float)$task['co2_kg'] ?>" required
                   class="w-full p-4 pl-12 rounded-2xl bg-gray-50 border border-gray-200 focus:border-main-orange outline-none transition-all font-bold">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-green-500"><i class="fa-solid fa-leaf"></i></span>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="space-y-2 md:col-span-2">
        <label class="block text-xs font-black uppercase tracking-widest text-gray-400 ml-1">Catégorie</label>
        <select name="categorie" class="w-full p-4 rounded-2xl bg-gray-50 border border-gray-200 focus:border-main-orange outline-none transition-all font-bold">
            <option <?= $task['categorie'] == 'Général' ? 'selected' : '' ?>>Général</option>
            <option <?= $task['categorie'] == 'Transport' ? 'selected' : '' ?>>Transport</option>
            <option <?= $task['categorie'] == 'Alimentation' ? 'selected' : '' ?>>Alimentation</option>
            <option <?= $task['categorie'] == 'Déchets' ? 'selected' : '' ?>>Déchets</option>
        </select>
      </div>
      <div class="space-y-2">
        <label class="block text-xs font-black uppercase tracking-widest text-gray-400 ml-1">Durée (Jours)</label>
        <input type="number" name="duration_days" value="<?= (int)$task['duration_days'] ?>" required
               class="w-full p-4 rounded-2xl bg-gray-50 border border-gray-200 focus:border-main-orange outline-none transition-all font-bold">
      </div>
      <div class="space-y-2">
        <label class="block text-xs font-black uppercase tracking-widest text-gray-400 ml-1">Actions Max/Jour</label>
        <input type="number" name="max_actions_day" value="<?= (int)$task['max_actions_day'] ?>" required
               class="w-full p-4 rounded-2xl bg-gray-50 border border-gray-200 focus:border-main-orange outline-none transition-all font-bold">
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="space-y-2">
        <label class="block text-xs font-black uppercase tracking-widest text-gray-400 ml-1">Domaine</label>
        <select name="domaine" class="w-full p-4 rounded-2xl bg-gray-50 border border-gray-200 focus:border-main-orange outline-none transition-all font-bold">
            <option value="ecologique" <?= strtolower($task['domaine']) == 'ecologique' ? 'selected' : '' ?>>Écologique</option>
            <option value="social" <?= strtolower($task['domaine']) == 'social' ? 'selected' : '' ?>>Social</option>
        </select>
      </div>
      <div class="space-y-2">
        <label class="block text-xs font-black uppercase tracking-widest text-gray-400 ml-1">Difficulté</label>
        <select name="difficulty" class="w-full p-4 rounded-2xl bg-gray-50 border border-gray-200 focus:border-main-orange outline-none transition-all font-bold cursor-pointer">
          <option value="facile" <?= strtolower($task['difficulty']) == 'facile' ? 'selected' : '' ?>>Facile</option>
          <option value="moyen" <?= strtolower($task['difficulty']) == 'moyen' ? 'selected' : '' ?>>Moyen</option>
          <option value="difficile" <?= strtolower($task['difficulty']) == 'difficile' ? 'selected' : '' ?>>Difficile</option>
        </select>
      </div>
    </div>

    <div class="space-y-2">
      <label class="block text-xs font-black uppercase tracking-widest text-gray-400 ml-1">Description (FR)</label>
      <textarea name="descr_fr" rows="3" 
                class="w-full p-4 rounded-2xl bg-gray-50 border border-gray-200 focus:border-main-orange outline-none transition-all font-medium"><?= htmlspecialchars($task['descr_fr']) ?></textarea>
    </div>

    <div class="space-y-2">
      <label class="block text-xs font-black uppercase tracking-widest text-gray-400 ml-1">Description (EN)</label>
      <textarea name="descr_en" rows="3" 
                class="w-full p-4 rounded-2xl bg-gray-50 border border-gray-200 focus:border-main-orange outline-none transition-all font-medium"><?= htmlspecialchars($task['descr_en'] ?? '') ?></textarea>
    </div>

    <div class="flex items-center justify-end gap-6 pt-8 border-t border-gray-100">
      <a href="admin_shift_manager.php" class="px-6 py-3 text-gray-400 font-bold hover:text-main-orange transition">Annuler</a>
      <button type="submit" class="bg-main-orange text-white uppercase tracking-widest px-10 py-4 rounded-2xl font-black shadow-lg shadow-orange-200 hover:scale-[1.02] active:scale-[0.98] transition-all">
        Mettre à jour
      </button>
    </div>

  </form>
</div>

</body>
</html>