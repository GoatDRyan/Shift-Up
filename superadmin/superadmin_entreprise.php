<?php
session_start();

$db_driver = '';
$mysqli = null;

require_once('../db_connect.php');

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
    $ddl = "CREATE TABLE IF NOT EXISTS companies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        secteur VARCHAR(255) NOT NULL,
        logo_url VARCHAR(512),
        code_invite VARCHAR(10),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    try { $pdo->exec($ddl); } catch (Exception $e) { /* ignore */ }
} elseif ($db_driver === 'mysqli' && isset($mysqli)) {
    $ddl = "CREATE TABLE IF NOT EXISTS companies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        secteur VARCHAR(255) NOT NULL,
        logo_url VARCHAR(512),
        code_invite VARCHAR(10),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    @$mysqli->query($ddl);
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $secteur = trim($_POST['secteur'] ?? '');
    $code = trim($_POST['code_invite'] ?? '');

    if ($nom === '') $errors[] = "Le nom de l'entreprise est requis.";
    if ($secteur === '') $errors[] = "Le secteur est requis.";
    if ($code === '') {
        $errors[] = "Le code d'invitation est requis (4 chiffres).";
    } elseif (!preg_match('/^\d{4}$/', $code)) {
        $errors[] = "Le code d'invitation doit contenir exactement 4 chiffres.";
    }

    $logo_url = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['logo'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Erreur lors de l'upload du logo.";
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowed = ['image/jpeg','image/png','image/webp','image/gif','image/svg+xml'];
            if (!in_array($mime, $allowed)) {
                $errors[] = "Type de fichier non autorisé. Autorisé: JPG, PNG, WEBP, GIF, SVG.";
            } elseif ($file['size'] > 4 * 1024 * 1024) {
                $errors[] = "Le logo doit être inférieur à 4 MB.";
            } else {
                $uploadDir = __DIR__ . '/uploads/logos';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                    $errors[] = "Impossible de créer le dossier d'uploads côté serveur.";
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: (explode('/', $mime)[1] ?? 'png');
                    try { $basename = bin2hex(random_bytes(8)); } catch (Exception $e) { $basename = time() . rand(1000,9999); }
                    $filename = $basename . '.' . $ext;
                    $destination = $uploadDir . '/' . $filename;
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $logo_url = 'uploads/logos/' . $filename;
                    } else {
                        $errors[] = "Impossible de déplacer le fichier uploadé.";
                    }
                }
            }
        }
    }
    if (empty($errors)) {
        if ($db_driver === 'pdo' && isset($pdo)) {
            try {
                $sql = "INSERT INTO companies (nom, secteur, logo_url, code_invite) VALUES (:nom, :secteur, :logo, :code)";
                $stmt = $pdo->prepare($sql);
                $ok = $stmt->execute([
                    ':nom' => $nom,
                    ':secteur' => $secteur,
                    ':logo' => $logo_url,
                    ':code' => $code,
                ]);
                if ($ok) {
                    $success = "Entreprise ajoutée avec succès.";
                    $nom = $secteur = $code = '';
                } else {
                    $errors[] = "Erreur base de données (PDO).";
                }
            } catch (Exception $e) {
                $errors[] = "Erreur PDO : " . $e->getMessage();
            }
        } elseif ($db_driver === 'mysqli' && isset($mysqli)) {
            $sql = "INSERT INTO companies (nom, secteur, logo_url, code_invite) VALUES (?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ssss', $nom, $secteur, $logo_url, $code);
                if ($stmt->execute()) {
                    $success = "Entreprise ajoutée avec succès.";
                    $nom = $secteur = $code = '';
                } else {
                    $errors[] = "Erreur base de données (mysqli).";
                }
                $stmt->close();
            } else {
                $errors[] = "Impossible de préparer la requête (mysqli).";
            }
        } else {
            $errors[] = "Aucune connexion DB valide trouvée. Vérifie ton db_connect.php (doit exposer \$pdo ou \$conn en mysqli/PDO).";
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title> Super Admin - Tableau de bord</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .rounded-full-xl { border-radius: 999px; }
    .card-radius { border-radius: 12px; }
  </style>
</head>

<header class="bg-gray-200 h-16 relative">
  <div class="absolute left-0 top-0 bottom-0 w-20 md:w-64 bg-gray-400 flex items-center justify-center">
    <div class="w-10 h-10 flex items-center justify-center" aria-hidden="true">
                  <a href="superadmin/superadmin_dashboard.php">
      <svg class="w-6 h-6 text-gray-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Logo Shift-Up">
        <path d="M12 2L4 5v6c0 5 3.5 9.7 8 11 4.5-1.3 8-6 8-11V5l-8-3z"
              stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" stroke-linecap="round" fill="none"/>
        <text x="12" y="15.3" text-anchor="middle" font-size="9" font-family="Segoe UI, Roboto, Arial, sans-serif"
              fill="currentColor" style="font-weight:700">S</text>
      </svg>
</a>
    </div>
  </div>

  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-20 md:pl-64 pr-6">
    <nav class="hidden md:flex items-center gap-8">
      <a href="superadmin/super_admin_shift_manager.php" class="text-gray-700 hover:text-gray-900">Shift manager</a>
      <a href="superadmin/superadmin_gestion.php" class="text-gray-700 hover:text-gray-900">Gestion</a>
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

<body class="bg-white">
  <main class="max-w-screen-2xl mx-auto px-6">
    <section class="mt-10">
      <h1 class="text-3xl font-medium text-gray-800 mb-8">Ajout entreprise</h1>

      <div class="bg-gray-200 card-radius p-12 mx-8" style="border-radius:14px;">
        <?php if (!empty($errors)): ?>
          <div class="mb-6">
            <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded">
              <ul class="list-disc pl-5">
                <?php foreach ($errors as $err): ?>
                  <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="mb-6">
            <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded">
              <?= htmlspecialchars($success) ?>
            </div>
          </div>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data" class="space-y-8">
          <div class="grid grid-cols-3 gap-8 items-end">
            <div class="flex flex-col items-start">
              <label for="nom" class="mb-3 text-gray-700">Nom de l’entreprise</label>
              <input id="nom" name="nom" value="<?= htmlspecialchars($nom ?? '') ?>" type="text"
                     class="w-full py-3 px-4 rounded-md bg-gray-300 placeholder-gray-600 focus:outline-none" placeholder="Ex: Entreprise X">
            </div>

            <div class="flex flex-col items-start">
              <label for="secteur" class="mb-3 text-gray-700">Secteur</label>
              <input id="secteur" name="secteur" value="<?= htmlspecialchars($secteur ?? '') ?>" type="text"
                     class="w-full py-3 px-4 rounded-md bg-gray-300 placeholder-gray-600 focus:outline-none" placeholder="Ex: Restauration">
            </div>

            <div class="flex flex-col items-start">
              <label for="code_invite" class="mb-3 text-gray-700">Code invitation (4 chiffres)</label>
              <input id="code_invite" name="code_invite" value="<?= htmlspecialchars($code ?? '') ?>" type="text" maxlength="4"
                     class="w-full py-3 px-4 rounded-md bg-gray-300 placeholder-gray-600 focus:outline-none" placeholder="1234" pattern="\d{4}">
            </div>
          </div>

          <div class="flex flex-col items-center">
            <label class="mb-4 text-gray-700">Logo</label>
            <div id="logoDrop" class="w-3/5 bg-gray-300 rounded-xl p-8 flex items-center justify-center cursor-pointer" style="min-height:160px;">
              <div class="text-center">
                <img id="logoPreview" src="<?= htmlspecialchars($logo_url ?? '') ?>" alt="" class="mx-auto mb-3 max-h-36 object-contain <?= empty($logo_url) ? 'hidden' : '' ?>" />
                <div id="uploadHint" class="<?= empty($logo_url) ? 'block' : 'hidden' ?>">
                  <svg class="mx-auto mb-2 w-10 h-10 text-gray-700" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect x="3" y="6" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.2" fill="none"/>
                    <path d="M7 10l3 3 5-5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <circle cx="19" cy="7" r="2" stroke="currentColor" stroke-width="1.2" fill="none"/>
                  </svg>
                  <div class="text-gray-700">Cliquer pour parcourir les fichiers ou déposer</div>
                </div>
                <div id="logoName" class="text-sm text-gray-600 mt-2"></div>
              </div>
            </div>
            <input id="logoInput" name="logo" type="file" accept="image/*" class="hidden" />
          </div>

          <div class="flex justify-center">
            <button type="submit" class="mt-6 px-8 py-3 rounded-full-xl bg-gray-400 text-black shadow">
              Ajouter
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

  ['dragenter','dragover'].forEach(ev => {
    logoDrop.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); logoDrop.classList.add('ring','ring-2','ring-gray-400'); });
  });
  ['dragleave','drop'].forEach(ev => {
    logoDrop.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); logoDrop.classList.remove('ring','ring-2','ring-gray-400'); });
  });
  logoDrop.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    if (dt && dt.files && dt.files.length) {
      logoInput.files = dt.files;
      handleFile(dt.files[0]);
    }
  });

  logoInput.addEventListener('change', () => {
    if (logoInput.files && logoInput.files[0]) handleFile(logoInput.files[0]);
  });

  function handleFile(file) {
    const allowed = ['image/jpeg','image/png','image/webp','image/gif','image/svg+xml'];
    if (!allowed.includes(file.type)) { alert('Type de fichier non autorisé. JPG/PNG/WebP/GIF/SVG seulement.'); logoInput.value = ''; return; }
    logoName.textContent = file.name;
    uploadHint.style.display = 'none';
    logoPreview.style.display = 'block';
    const reader = new FileReader();
    reader.onload = (ev) => { logoPreview.src = ev.target.result; };
    reader.readAsDataURL(file);
  }
})();
</script>
</body>
</html>