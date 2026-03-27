<?php
session_start();

if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fmtNumber($value, string $lang): string
{
    $value = (float)$value;
    return $lang === 'en' ? number_format($value, 0, '.', ',') : number_format($value, 0, ',', ' ');
}

$userId = $_SESSION['user_id'] ?? 1;
$lang = $_SESSION['lang'] ?? 'fr';

$pdo = new PDO('mysql:host=localhost;dbname=shiftup_db;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    $stmt = $pdo->query('SELECT * FROM users ORDER BY id ASC LIMIT 1');
    $user = $stmt->fetch();
    $userId = $user ? (int)$user['id'] : 1;
}

if (!$user) {
    die('Utilisateur introuvable.');
}

if (!isset($_GET['lang']) && !empty($user['language_pref']) && in_array($user['language_pref'], ['fr', 'en'], true)) {
    $lang = $user['language_pref'];
    $_SESSION['lang'] = $lang;
}

$stmt = $pdo->prepare('SELECT * FROM companies WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $user['company_id']]);
$company = $stmt->fetch();

if (!$company) {
    $stmt = $pdo->query('SELECT * FROM companies ORDER BY total_xp DESC, id ASC LIMIT 1');
    $company = $stmt->fetch();
}

$companyId = (int)($company['id'] ?? 0);

$translations = [
    'fr' => [
        'title' => 'Profil entreprise',
        'badges' => 'Vitrine à succès',
        'ranking' => 'Classement entreprise',
        'rank' => 'Rang',
        'points' => 'points',
        'you' => 'Vous',
        'settings' => 'Paramètres',
        'language' => 'Langue',
        'shift_manager' => 'Shift manager',
        'management' => 'Gestion',
        'close' => 'Fermer',
        'company_name' => 'Nom',
        'company_mail' => 'Mail',
        'company_code' => 'Code entreprise',
        'company_password' => 'Mot de passe',
        'company_logo' => 'Logo',
        'change_company_name' => "Changer le nom de l'entreprise",
        'change_company_logo' => "Changer le logo de l'entreprise",
        'upload_logo' => 'Importer un logo',
        'current_logo' => 'Logo actuel',
        'logo_help' => 'PNG, JPG, JPEG ou WEBP. Taille recommandée : 512 × 512 px.',
        'change_mail' => 'Changer votre mail',
        'change_company_code' => "Changer le code entreprise",
        'change_password' => 'Changer votre mot de passe',
        'current_password' => 'Mot de passe actuel',
        'new_password' => 'Nouveau mot de passe',
        'confirm_password' => 'Confirmer le mot de passe',
        'save' => 'Enregistrer',
        'success_name' => "Le nom de l’entreprise a été modifié.",
        'success_mail' => 'Le mail a été modifié.',
        'success_code' => 'Le code entreprise a été modifié.',
        'success_password' => 'Le mot de passe a été modifié.',
        'success_logo' => 'Le logo de l’entreprise a été mis à jour.',
        'error_logo_type' => 'Format de logo non autorisé.',
        'error_logo_upload' => 'Veuillez sélectionner un logo valide.',
        'error_generic' => 'Une erreur est survenue.',
        'error_password' => 'Le mot de passe actuel est incorrect.',
        'error_confirm' => 'Les mots de passe ne correspondent pas.',
        'error_required' => 'Veuillez remplir tous les champs.',
        'language_switch' => 'English'
    ],
    'en' => [
        'title' => 'Company profile',
        'badges' => 'Achievement showcase',
        'ranking' => 'Company ranking',
        'rank' => 'Rank',
        'points' => 'points',
        'you' => 'You',
        'settings' => 'Settings',
        'language' => 'Language',
        'shift_manager' => 'Shift manager',
        'management' => 'Management',
        'close' => 'Close',
        'company_name' => 'Name',
        'company_mail' => 'Mail',
        'company_code' => 'Company code',
        'company_password' => 'Password',
        'company_logo' => 'Logo',
        'change_company_name' => 'Change company name',
        'change_company_logo' => 'Change company logo',
        'upload_logo' => 'Upload logo',
        'current_logo' => 'Current logo',
        'logo_help' => 'PNG, JPG, JPEG or WEBP. Recommended size: 512 × 512 px.',
        'change_mail' => 'Change your email',
        'change_company_code' => 'Change company code',
        'change_password' => 'Change your password',
        'current_password' => 'Current password',
        'new_password' => 'New password',
        'confirm_password' => 'Confirm password',
        'save' => 'Save',
        'success_name' => 'Company name updated.',
        'success_mail' => 'Email updated.',
        'success_code' => 'Company code updated.',
        'success_password' => 'Password updated.',
        'success_logo' => 'Company logo updated.',
        'error_logo_type' => 'Unsupported logo format.',
        'error_logo_upload' => 'Please select a valid logo.',
        'error_generic' => 'Something went wrong.',
        'error_password' => 'Current password is incorrect.',
        'error_confirm' => 'Passwords do not match.',
        'error_required' => 'Please fill in all fields.',
        'language_switch' => 'Français'
    ]
];

$t = $translations[$lang] ?? $translations['fr'];
$linkLang = '?lang=' . $lang;
$toggleLangLink = '?lang=' . ($lang === 'fr' ? 'en' : 'fr');

$message = '';
$messageType = '';
$openSettings = isset($_GET['settings']) && $_GET['settings'] === '1';
$activeTab = $_GET['tab'] ?? 'name';

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirectTab = 'name';

    try {
        if ($action === 'update_company_name') {
            $redirectTab = 'name';
            $newName = trim($_POST['company_name'] ?? '');
            if ($newName === '') {
                throw new RuntimeException($t['error_required']);
            }
            $stmt = $pdo->prepare('UPDATE companies SET nom = :nom WHERE id = :id');
            $stmt->execute(['nom' => $newName, 'id' => $companyId]);
            $_SESSION['flash_message'] = $t['success_name'];
            $_SESSION['flash_type'] = 'success';
        } elseif ($action === 'update_email') {
            $redirectTab = 'email';
            $newEmail = trim($_POST['email'] ?? '');
            if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException($t['error_required']);
            }
            $stmt = $pdo->prepare('UPDATE users SET email = :email WHERE id = :id');
            $stmt->execute(['email' => $newEmail, 'id' => $userId]);
            $_SESSION['flash_message'] = $t['success_mail'];
            $_SESSION['flash_type'] = 'success';
        } elseif ($action === 'update_company_code') {
            $redirectTab = 'code';
            $newCode = trim($_POST['company_code'] ?? '');
            if ($newCode === '') {
                throw new RuntimeException($t['error_required']);
            }
            $stmt = $pdo->prepare('UPDATE companies SET code_invite = :code WHERE id = :id');
            $stmt->execute(['code' => $newCode, 'id' => $companyId]);
            $_SESSION['flash_message'] = $t['success_code'];
            $_SESSION['flash_type'] = 'success';
        } elseif ($action === 'update_password') {
            $redirectTab = 'password';
            $currentPassword = (string)($_POST['current_password'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                throw new RuntimeException($t['error_required']);
            }

            if (!password_verify($currentPassword, $user['password_hash'])) {
                throw new RuntimeException($t['error_password']);
            }

            if ($newPassword !== $confirmPassword) {
                throw new RuntimeException($t['error_confirm']);
            }

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $stmt->execute(['hash' => $hash, 'id' => $userId]);
            $_SESSION['flash_message'] = $t['success_password'];
            $_SESSION['flash_type'] = 'success';
        } elseif ($action === 'update_company_logo') {
            $redirectTab = 'logo';

            if (!isset($_FILES['company_logo']) || !is_array($_FILES['company_logo'])) {
                throw new RuntimeException($t['error_logo_upload']);
            }

            $file = $_FILES['company_logo'];

            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException($t['error_logo_upload']);
            }

            $tmpPath = (string)($file['tmp_name'] ?? '');
            if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                throw new RuntimeException($t['error_logo_upload']);
            }

            $allowedMime = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
            ];

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpPath) ?: '';
            if (!isset($allowedMime[$mimeType])) {
                throw new RuntimeException($t['error_logo_type']);
            }

            $uploadDir = __DIR__ . '/uploads/company_logos';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                throw new RuntimeException($t['error_generic']);
            }

            $filename = 'company_' . $companyId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mimeType];
            $destination = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($tmpPath, $destination)) {
                throw new RuntimeException($t['error_generic']);
            }

            $relativePath = 'uploads/company_logos/' . $filename;
            $stmt = $pdo->prepare('UPDATE companies SET logo_url = :logo WHERE id = :id');
            $stmt->execute(['logo' => $relativePath, 'id' => $companyId]);

            $_SESSION['flash_message'] = $t['success_logo'];
            $_SESSION['flash_type'] = 'success';
        } else {
            throw new RuntimeException($t['error_generic']);
        }

        header('Location: admin_profile.php?lang=' . $lang . '&settings=1&tab=' . $redirectTab);
        exit;
    } catch (Throwable $ex) {
        $_SESSION['flash_message'] = $ex->getMessage();
        $_SESSION['flash_type'] = 'error';
        header('Location: admin_profile.php?lang=' . $lang . '&settings=1&tab=' . $redirectTab);
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT b.id, b.nom_fr, b.nom_en, b.descr_fr, b.descr_en, b.icon_url, b.xp_threshold,
            CASE WHEN ub.badge_id IS NULL THEN 0 ELSE 1 END AS obtained
    FROM badges b
    LEFT JOIN user_badges ub
      ON ub.badge_id = b.id AND ub.user_id = :user_id
    ORDER BY b.xp_threshold ASC, b.id ASC
");
$stmt->execute(['user_id' => $userId]);
$badges = $stmt->fetchAll();

$stmt = $pdo->query('SELECT id, nom, total_xp FROM companies ORDER BY total_xp DESC, id ASC');
$companies = $stmt->fetchAll();

$currentRank = 1;
foreach ($companies as $index => $row) {
    if ((int)$row['id'] === $companyId) {
        $currentRank = $index + 1;
        break;
    }
}

$topCompanies = array_slice($companies, 0, 5);

$companyNameValue = (string)($company['nom'] ?? '');
$companyCodeValue = (string)($company['code_invite'] ?? '');
$companyLogoValue = (string)($company['logo_url'] ?? '');
$userEmailValue = (string)($user['email'] ?? '');
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin Profil | ShiftUp</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root { --primary: #FF4800; }
    body { background-color: #ffffff; color: #111; font-family: 'Inter', system-ui, sans-serif; }
    .bg-primary { background-color: var(--primary); }
    .text-primary { color: var(--primary); }
    .border-primary { border-color: var(--primary); }
    .rounded-full-xl { border-radius: 9999px; }
    .card-radius { border-radius: 20px; }
    .soft-shadow { box-shadow: 0 10px 30px rgba(255, 72, 0, 0.08); }
    .badge-square { aspect-ratio: 1/1; transition: transform 0.2s; }
    .badge-square:hover { transform: scale(1.05); }
    .settings-tab.active { background-color: var(--primary); color: white; }
  </style>
</head>
<body class="min-h-screen">

<header class="bg-[#FF4800] h-24 relative shadow-lg">
  <div class="absolute left-0 top-0 bottom-0 w-24 md:w-72 bg-black/10 flex items-center justify-center border-r border-white/10">
    <a href="admin_dashboard.php<?= e($linkLang) ?>" aria-label="Accueil" class="w-12 h-12 flex items-center justify-center">
      <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <path d="M12 2L4 5v6c0 5 3.5 9.7 8 11 4.5-1.3 8-6 8-11V5l-8-3z" stroke-linejoin="round" stroke-linecap="round"/>
        <text x="12" y="15.3" text-anchor="middle" font-size="9" font-family="Arial" fill="currentColor" style="font-weight:900">S</text>
      </svg>
    </a>
  </div>
  
  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-24 md:pl-72 pr-6">
    <nav class="flex items-center gap-8 text-[17px]">
      <a href="admin_shift_manager.php<?= e($linkLang) ?>" class="font-bold text-white hover:opacity-80 transition-opacity"><?= e($t['shift_manager']) ?></a>
      <a href="admin_gestion.php<?= e($linkLang) ?>" class="font-medium text-white hover:opacity-80 transition-opacity"><?= e($t['management']) ?></a>
      <a href="admin_profile.php" class="w-11 h-11 rounded-full border-2 border-white flex items-center justify-center hover:bg-white/10 transition">
        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.5" fill="none"/>
          <path d="M6 20c0-3 4-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        </svg>
      </a>
    </nav>
  </div>
</header>

<main class="max-w-[1500px] mx-auto px-4 md:px-6 py-12">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-12">
    <h1 class="text-4xl font-extrabold tracking-tight text-gray-900"><?= e($t['title']) ?></h1>
    <div class="flex items-center gap-4">
      <a href="admin_profile.php<?= e($linkLang) ?>&settings=1" class="bg-[#FF4800] hover:bg-[#e64100] transition-all text-white font-bold px-8 py-4 rounded-full-xl text-lg shadow-md hover:shadow-lg">
        <?= e($t['settings']) ?>
      </a>
      <a href="admin_profile.php<?= e($toggleLangLink) ?>" class="bg-gray-100 hover:bg-gray-200 transition-colors text-gray-700 font-semibold px-8 py-4 rounded-full-xl text-lg">
        <?= e($t['language']) ?>
      </a>
    </div>
  </div>

  <?php if ($message !== ''): ?>
    <div class="mb-8 px-6 py-4 rounded-2xl shadow-sm border-l-4 <?= $messageType === 'error' ? 'bg-red-50 border-red-500 text-red-800' : 'bg-green-50 border-green-500 text-green-800' ?>">
      <?= e($message) ?>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-[0.95fr_1.25fr] gap-10">
    <section class="bg-white p-8 card-radius soft-shadow border border-gray-100">
      <div class="flex items-center justify-center mb-10">
        <h2 class="text-2xl font-bold text-gray-800"><?= e($t['badges']) ?></h2>
      </div>

      <div class="grid grid-cols-5 gap-4 md:gap-6">
        <?php foreach ($badges as $badge): ?>
          <?php
            $obtained = (int)$badge['obtained'] === 1;
            $name = $lang === 'en' ? $badge['nom_en'] : $badge['nom_fr'];
            $desc = $lang === 'en' ? $badge['descr_en'] : $badge['descr_fr'];
            $title = trim($name . ' - ' . $desc);
          ?>
          <div class="badge-square w-full max-w-[86px] mx-auto rounded-2xl relative overflow-hidden flex items-center justify-center <?= $obtained ? 'bg-orange-50 border-2 border-[#FF4800]' : 'bg-gray-50 border border-gray-200 grayscale opacity-40' ?>" title="<?= e($title) ?>">
            <?php if (!empty($badge['icon_url'])): ?>
              <img src="<?= e($badge['icon_url']) ?>" alt="<?= e($name) ?>" class="w-12 h-12 object-contain">
            <?php else: ?>
              <?php if ($obtained): ?>
                <svg class="w-8 h-8 text-[#FF4800]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              <?php else: ?>
                <svg class="w-8 h-8 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M7 11V8a5 5 0 0110 0v3" stroke-linecap="round" stroke-linejoin="round"/>
                  <rect x="5" y="11" width="14" height="9" rx="2" />
                </svg>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="bg-white p-8 card-radius soft-shadow border border-gray-100 min-h-[600px]">
      <div class="flex items-start justify-between gap-4 mb-12">
        <h2 class="text-2xl font-bold text-gray-800"><?= e($t['ranking']) ?></h2>
        <div class="bg-orange-100 text-[#FF4800] px-4 py-2 rounded-full font-bold text-lg">
          <?= e($t['rank']) ?> #<?= e($currentRank) ?>
        </div>
      </div>

      <div class="space-y-6">
        <?php foreach ($topCompanies as $index => $row): ?>
          <?php $isCurrent = (int)$row['id'] === $companyId; ?>
          <div class="flex items-center gap-4 group">
            <div class="w-12 h-12 flex-shrink-0 rounded-full <?= $isCurrent ? 'bg-[#FF4800] text-white' : 'bg-gray-100 text-gray-500' ?> flex items-center justify-center font-black text-lg shadow-sm">
              <?= e($index + 1) ?>
            </div>

            <div class="flex-1 rounded-[24px] h-20 flex items-center px-8 border-2 transition-all <?= $isCurrent ? 'border-[#FF4800] bg-orange-50/30 shadow-md' : 'border-gray-100 bg-white group-hover:border-gray-200' ?>">
              <div class="flex-1 text-xl font-bold text-gray-800">
                <?= e($isCurrent ? $t['you'] : $row['nom']) ?>
              </div>
              <div class="text-xl font-black <?= $isCurrent ? 'text-[#FF4800]' : 'text-gray-400' ?>">
                <?= e(fmtNumber($row['total_xp'], $lang)) ?> <span class="text-xs uppercase tracking-widest ml-1"><?= e($t['points']) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</main>

<?php if ($openSettings): ?>
<div class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 px-4">
  <div class="bg-white w-full max-w-[1100px] rounded-[30px] shadow-2xl relative overflow-hidden">
    <div class="bg-gray-50 px-10 py-6 border-b flex items-center justify-between">
        <h3 class="text-2xl font-bold"><?= e($t['settings']) ?></h3>
        <a href="admin_profile.php<?= e($linkLang) ?>" class="w-12 h-12 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center transition-colors">
          <svg class="w-6 h-6 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
          </svg>
        </a>
    </div>

    <div class="p-8 md:p-12">
        <div class="flex flex-wrap justify-center gap-4 mb-10">
          <?php 
            $tabs = [
                'name' => $t['company_name'], 
                'email' => $t['company_mail'], 
                'code' => $t['company_code'], 
                'logo' => $t['company_logo'], 
                'password' => $t['company_password']
            ];
            foreach($tabs as $key => $label): 
          ?>
            <button type="button" data-tab="<?= $key ?>" class="settings-tab px-6 py-3 rounded-full font-bold transition-all border-2 <?= $activeTab === $key ? 'bg-[#FF4800] border-[#FF4800] text-white' : 'border-gray-200 text-gray-600 hover:border-[#FF4800] hover:text-[#FF4800]' ?>">
                <?= e($label) ?>
            </button>
          <?php endforeach; ?>
        </div>

        <div class="bg-gray-50 rounded-3xl p-8 border border-gray-100">
          <div id="panel-name" class="settings-panel <?= $activeTab === 'name' ? '' : 'hidden' ?>">
            <form method="post" class="max-w-md mx-auto space-y-4">
              <input type="hidden" name="action" value="update_company_name">
              <label class="block font-bold text-gray-700"><?= e($t['change_company_name']) ?></label>
              <input name="company_name" value="<?= e($companyNameValue) ?>" class="w-full rounded-2xl px-6 py-4 border-2 border-gray-200 focus:border-[#FF4800] outline-none transition-all" />
              <button class="w-full bg-[#FF4800] text-white font-bold px-6 py-4 rounded-2xl shadow-lg"><?= e($t['save']) ?></button>
            </form>
          </div>

          <div id="panel-email" class="settings-panel <?= $activeTab === 'email' ? '' : 'hidden' ?>">
            <form method="post" class="max-w-md mx-auto space-y-4">
              <input type="hidden" name="action" value="update_email">
              <label class="block font-bold text-gray-700"><?= e($t['change_mail']) ?></label>
              <input type="email" name="email" value="<?= e($userEmailValue) ?>" class="w-full rounded-2xl px-6 py-4 border-2 border-gray-200 focus:border-[#FF4800] outline-none transition-all" />
              <button class="w-full bg-[#FF4800] text-white font-bold px-6 py-4 rounded-2xl shadow-lg"><?= e($t['save']) ?></button>
            </form>
          </div>

          <div id="panel-code" class="settings-panel <?= $activeTab === 'code' ? '' : 'hidden' ?>">
            <form method="post" class="max-w-md mx-auto space-y-4">
              <input type="hidden" name="action" value="update_company_code">
              <label class="block font-bold text-gray-700"><?= e($t['change_company_code']) ?></label>
              <input name="company_code" value="<?= e($companyCodeValue) ?>" class="w-full rounded-2xl px-6 py-4 border-2 border-gray-200 focus:border-[#FF4800] outline-none transition-all" />
              <button class="w-full bg-[#FF4800] text-white font-bold px-6 py-4 rounded-2xl shadow-lg"><?= e($t['save']) ?></button>
            </form>
          </div>

          <div id="panel-logo" class="settings-panel <?= $activeTab === 'logo' ? '' : 'hidden' ?>">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="update_company_logo">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
                <div class="text-center">
                  <span class="block mb-4 font-bold text-gray-500 uppercase text-xs"><?= e($t['current_logo']) ?></span>
                  <div class="w-48 h-48 mx-auto rounded-3xl bg-white flex items-center justify-center overflow-hidden border-2 border-dashed border-gray-300">
                    <?php if (!empty($companyLogoValue)): ?>
                      <img src="<?= e($companyLogoValue) ?>" class="w-full h-full object-contain p-4">
                    <?php else: ?>
                      <div class="text-gray-300">Aucun logo</div>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="space-y-4">
                  <label for="company_logo" class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-[#FF4800] bg-orange-50 rounded-3xl cursor-pointer hover:bg-orange-100 transition-colors">
                    <svg class="w-10 h-10 text-[#FF4800] mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v12m8-8l-8-8-8 8m16 12H4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="text-sm font-bold text-[#FF4800]"><?= e($t['upload_logo']) ?></span>
                  </label>
                  <input id="company_logo" name="company_logo" type="file" class="hidden">
                  <button class="w-full bg-[#FF4800] text-white font-bold px-6 py-4 rounded-2xl"><?= e($t['save']) ?></button>
                </div>
              </div>
            </form>
          </div>

          <div id="panel-password" class="settings-panel <?= $activeTab === 'password' ? '' : 'hidden' ?>">
            <form method="post" class="max-w-md mx-auto space-y-4">
              <input type="hidden" name="action" value="update_password">
              <input type="password" name="current_password" placeholder="<?= e($t['current_password']) ?>" class="w-full rounded-2xl px-6 py-4 border-2 border-gray-200 focus:border-[#FF4800] outline-none" />
              <input type="password" name="new_password" placeholder="<?= e($t['new_password']) ?>" class="w-full rounded-2xl px-6 py-4 border-2 border-gray-200 focus:border-[#FF4800] outline-none" />
              <input type="password" name="confirm_password" placeholder="<?= e($t['confirm_password']) ?>" class="w-full rounded-2xl px-6 py-4 border-2 border-gray-200 focus:border-[#FF4800] outline-none" />
              <button class="w-full bg-[#FF4800] text-white font-bold px-6 py-4 rounded-2xl shadow-lg"><?= e($t['save']) ?></button>
            </form>
          </div>
        </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('.settings-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const tab = this.getAttribute('data-tab');
        
        document.querySelectorAll('.settings-tab').forEach(b => {
            b.classList.remove('bg-[#FF4800]', 'text-white', 'border-[#FF4800]');
            b.classList.add('border-gray-200', 'text-gray-600');
        });
        this.classList.add('bg-[#FF4800]', 'text-white', 'border-[#FF4800]');
        this.classList.remove('border-gray-200', 'text-gray-600');

        document.querySelectorAll('.settings-panel').forEach(function(panel) {
            panel.classList.add('hidden');
        });
        const selected = document.getElementById('panel-' + tab);
        if (selected) selected.classList.remove('hidden');
    });
});
</script>
</body>
</html>