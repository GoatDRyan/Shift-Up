<?php
session_start();

$db_driver = '';
$mysqli = null;

require_once '../../config/db_connect.php';

if (isset($pdo) && $pdo instanceof PDO) {
    $db_driver = 'pdo';
} elseif (isset($conn) && $conn instanceof PDO) {
    $pdo = $conn;
    $db_driver = 'pdo';
} elseif (isset($conn) && $conn instanceof mysqli) {
    $mysqli = $conn;
    $db_driver = 'mysqli';
} elseif (isset($mysqli) && $mysqli instanceof mysqli) {
    $db_driver = 'mysqli';
}

if ($db_driver === 'pdo' && isset($pdo)) {
    $ddl = "CREATE TABLE IF NOT EXISTS companies (id INT AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(255) NOT NULL, secteur VARCHAR(255) NOT NULL, logo_url VARCHAR(512), code_invite VARCHAR(10), created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    try { $pdo->exec($ddl); } catch (Exception $e) {}
} elseif ($db_driver === 'mysqli' && isset($mysqli)) {
    $ddl = "CREATE TABLE IF NOT EXISTS companies (id INT AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(255) NOT NULL, secteur VARCHAR(255) NOT NULL, logo_url VARCHAR(512), code_invite VARCHAR(10), created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    @$mysqli->query($ddl);
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = trim($_POST['nom'] ?? '');
    $secteur = trim($_POST['secteur'] ?? '');
    $code   = trim($_POST['code_invite'] ?? '');

    if ($nom === '') $errors[] = "Le nom de l'entreprise est requis.";
    if ($secteur === '') $errors[] = "Le secteur est requis.";
    if ($code === '') { $errors[] = "Le code d'invitation est requis (4 chiffres)."; }
    elseif (!preg_match('/^\d{4}$/', $code)) { $errors[] = "Le code d'invitation doit contenir exactement 4 chiffres."; }

    $logo_url = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['logo'];
        if ($file['error'] !== UPLOAD_ERR_OK) { $errors[] = "Erreur lors de l'upload du logo."; }
        else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $allowed = ['image/jpeg','image/png','image/webp','image/gif','image/svg+xml'];
            if (!in_array($mime, $allowed)) { $errors[] = "Type de fichier non autorisé."; }
            elseif ($file['size'] > 4 * 1024 * 1024) { $errors[] = "Le logo doit être inférieur à 4 MB."; }
            else {
                $uploadDir = __DIR__ . '/uploads/logos';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) { $errors[] = "Impossible de créer le dossier d'uploads."; }
                else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: (explode('/', $mime)[1] ?? 'png');
                    try { $basename = bin2hex(random_bytes(8)); } catch (Exception $e) { $basename = time() . rand(1000,9999); }
                    $filename = $basename . '.' . $ext;
                    $destination = $uploadDir . '/' . $filename;
                    if (move_uploaded_file($file['tmp_name'], $destination)) { $logo_url = 'uploads/logos/' . $filename; }
                    else { $errors[] = "Impossible de déplacer le fichier uploadé."; }
                }
            }
        }
    }
    if (empty($errors)) {
        if ($db_driver === 'pdo' && isset($pdo)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO companies (nom, secteur, logo_url, code_invite) VALUES (:nom, :secteur, :logo, :code)");
                $ok = $stmt->execute([':nom' => $nom, ':secteur' => $secteur, ':logo' => $logo_url, ':code' => $code]);
                if ($ok) { $success = "Entreprise ajoutée avec succès."; $nom = $secteur = $code = ''; }
                else { $errors[] = "Erreur base de données (PDO)."; }
            } catch (Exception $e) { $errors[] = "Erreur PDO : " . $e->getMessage(); }
        } elseif ($db_driver === 'mysqli' && isset($mysqli)) {
            $stmt = $mysqli->prepare("INSERT INTO companies (nom, secteur, logo_url, code_invite) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('ssss', $nom, $secteur, $logo_url, $code);
                if ($stmt->execute()) { $success = "Entreprise ajoutée avec succès."; $nom = $secteur = $code = ''; }
                else { $errors[] = "Erreur base de données (mysqli)."; }
                $stmt->close();
            } else { $errors[] = "Impossible de préparer la requête."; }
        } else { $errors[] = "Aucune connexion DB valide trouvée."; }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Super Admin - Créer une entreprise</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root { --orange: #FF4800; }
    body { background: #fff; }
    .rounded-full-xl { border-radius: 999px; }
    .card-radius { border-radius: 12px; }
    .header-bg { background: #FF4800; }
    .btn-orange { background: #FF4800; color: #fff; border-radius: 999px; }
    .btn-orange:hover { background: #cc3a00; }
    .input-style { width:100%; padding:.75rem 1rem; border-radius:.75rem; background:#fff; border:1.5px solid #e5e7eb; outline:none; transition:border-color .2s; }
    .input-style:focus { border-color:#FF4800; }
    .upload-zone { background:#fff3ee; border:2px dashed #FF4800; border-radius:14px; cursor:pointer; transition:background .2s; }
    .upload-zone:hover { background:#ffe5d9; }
  </style>
</head>

<header class="header-bg h-16 relative">
  <a href="superadmin_dashboard.php" class="absolute left-0 top-0 bottom-0 w-20 md:w-64 bg-black/20 flex items-center justify-center">
    <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none">
      <path d="M12 2L4 5v6c0 5 3.5 9.7 8 11 4.5-1.3 8-6 8-11V5l-8-3z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" stroke-linecap="round" fill="none"/>
      <text x="12" y="15.3" text-anchor="middle" font-size="9" fill="white" font-weight="700">S</text>
    </svg>
  </a>
  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-20 md:pl-64 pr-6">
    <nav class="hidden md:flex items-center gap-8">
      <a href="super_admin_shift_manager.php" class="text-white font-bold hover:text-orange-200 transition">Shift Manager</a>
      <a href="superadmin_gestion.php" class="text-white hover:text-orange-200 transition">Gestion</a>
      <a href="super_admin_profile.php" class="w-10 h-10 rounded-full border-2 border-white flex items-center justify-center hover:bg-white/20 transition">
        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.5" fill="none"/>
          <path d="M6 20c0-3 4-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        </svg>
      </a>
    </nav>
    <button class="md:hidden ml-2 p-2" aria-label="Menu">
      <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    </button>
  </div>
</header>

<body class="bg-white">
  <main class="max-w-screen-2xl mx-auto px-6 pb-12">
    <section class="mt-10">
      <h1 class="text-3xl font-medium mb-2" style="color:#FF4800">Ajout entreprise</h1>
      <p class="text-gray-500 mb-8">Créer une nouvelle entreprise sur la plateforme ShiftUp.</p>

      <div class="bg-gray-50 card-radius p-10 border border-orange-100 max-w-4xl mx-auto">

        <?php if (!empty($errors)): ?>
          <div class="mb-6 bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl">
            <ul class="list-disc pl-5 space-y-1"><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="mb-6 bg-green-50 border border-green-200 text-green-700 p-4 rounded-xl flex items-center gap-2">
            <span>✅</span> <?= htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data" class="space-y-8">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <label for="nom" class="block text-sm font-bold text-gray-700 mb-2">Nom de l'entreprise</label>
              <input id="nom" name="nom" value="<?= htmlspecialchars($nom ?? '') ?>" type="text" class="input-style" placeholder="Ex: Entreprise X">
            </div>
            <div>
              <label for="secteur" class="block text-sm font-bold text-gray-700 mb-2">Secteur</label>
              <input id="secteur" name="secteur" value="<?= htmlspecialchars($secteur ?? '') ?>" type="text" class="input-style" placeholder="Ex: Restauration">
            </div>
            <div>
              <label for="code_invite" class="block text-sm font-bold text-gray-700 mb-2">Code invitation (4 chiffres)</label>
              <input id="code_invite" name="code_invite" value="<?= htmlspecialchars($code ?? '') ?>" type="text" maxlength="4" class="input-style" placeholder="1234" pattern="\d{4}">
            </div>
          </div>

          <div class="flex flex-col items-center">
            <label class="block text-sm font-bold text-gray-700 mb-3">Logo</label>
            <div id="logoDrop" class="upload-zone w-full md:w-2/3 p-10 flex flex-col items-center justify-center">
              <img id="logoPreview" src="" alt="" class="mx-auto mb-3 max-h-32 object-contain hidden" />
              <div id="uploadHint" class="text-center">
                <svg class="mx-auto mb-2 w-10 h-10" style="color:#FF4800" viewBox="0 0 24 24" fill="none"><path d="M12 4v12M8 8l4-4 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="18" width="18" height="2" rx="1" fill="currentColor" opacity=".3"/></svg>
                <div style="color:#FF4800" class="font-medium">Cliquer pour parcourir ou glisser-déposer</div>
                <div class="text-xs text-gray-400 mt-1">PNG, JPG, WEBP, GIF, SVG • max 4 MB</div>
              </div>
              <div id="logoName" class="text-sm text-gray-500 mt-2"></div>
            </div>
            <input id="logoInput" name="logo" type="file" accept="image/*" class="hidden" />
          </div>

          <div class="flex justify-center">
            <button type="submit" class="btn-orange px-10 py-3 text-lg font-semibold shadow-lg hover:scale-[1.02] transition">
              Ajouter l'entreprise
            </button>
          </div>
        </form>
      </div>
    </section>
  </main>

<script>
(function(){
  const logoDrop = document.getElementById('logoDrop');
  const logoInput = document.getElementById('logoInput');
  const logoPreview = document.getElementById('logoPreview');
  const uploadHint = document.getElementById('uploadHint');
  const logoName = document.getElementById('logoName');

  logoDrop.addEventListener('click', () => logoInput.click());
  ['dragenter','dragover'].forEach(ev => { logoDrop.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); logoDrop.style.background = '#ffe5d9'; }); });
  ['dragleave','drop'].forEach(ev => { logoDrop.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); logoDrop.style.background = ''; }); });
  logoDrop.addEventListener('drop', e => { const dt = e.dataTransfer; if (dt?.files?.length) { logoInput.files = dt.files; handleFile(dt.files[0]); } });
  logoInput.addEventListener('change', () => { if (logoInput.files?.[0]) handleFile(logoInput.files[0]); });

  function handleFile(file) {
    const allowed = ['image/jpeg','image/png','image/webp','image/gif','image/svg+xml'];
    if (!allowed.includes(file.type)) { alert('Type de fichier non autorisé.'); logoInput.value = ''; return; }
    logoName.textContent = file.name;
    uploadHint.classList.add('hidden');
    logoPreview.classList.remove('hidden');
    const reader = new FileReader();
    reader.onload = ev => { logoPreview.src = ev.target.result; };
    reader.readAsDataURL(file);
  }
})();
</script>
</body>
</html>