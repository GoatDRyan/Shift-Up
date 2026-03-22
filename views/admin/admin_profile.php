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
        'badges' => 'Vitrine à succes',
        'ranking' => 'Classement entreprise',
        'rank' => 'Rang',
        'points' => 'points',
        'you' => 'Vous',
        'settings' => 'Paramètre',
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
  <title>Admin Profil</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { background:#fff; color:#111; }
    .rounded-full-xl { border-radius:9999px; }
    .card-radius { border-radius:14px; }
    .soft-shadow { box-shadow:0 8px 24px rgba(0,0,0,.08); }
    .badge-square { aspect-ratio:1/1; }
  </style>
</head>
<body class="min-h-screen">
<header class="bg-gray-200 h-24 relative">
  <div class="absolute left-0 top-0 bottom-0 w-24 md:w-72 bg-gray-400 flex items-center justify-center">
    <a href="admin_dashboard.php<?= e($linkLang) ?>" aria-label="Accueil" class="w-12 h-12 flex items-center justify-center">
      <svg class="w-8 h-8 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
        <path d="M12 2L4 5v6c0 5 3.5 9.7 8 11 4.5-1.3 8-6 8-11V5l-8-3z" stroke-linejoin="round" stroke-linecap="round"/>
        <text x="12" y="15.3" text-anchor="middle" font-size="9" font-family="Segoe UI, Roboto, Arial, sans-serif" fill="currentColor" style="font-weight:700">S</text>
      </svg>
    </a>
  </div>
  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-24 md:pl-72 pr-6">
    <nav class="hidden md:flex items-center gap-10 text-[17px]">
      <a href="admin_shift_manager.php<?= e($linkLang) ?>" class="font-medium"><?= e($t['shift_manager']) ?></a>
      <a href="admin_gestion.php<?= e($linkLang) ?>"><?= e($t['management']) ?></a>
      <button type="button" class="w-11 h-11 rounded-full border-2 border-gray-900 flex items-center justify-center" aria-label="Profil">
        <svg class="w-7 h-7 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
          <circle cx="12" cy="8" r="3"/>
          <path d="M6 20c0-3 4-5 6-5s6 2 6 5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </nav>
  </div>
</header>

<main class="max-w-[1500px] mx-auto px-4 md:px-6 py-10">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-8">
    <h1 class="text-3xl md:text-[34px] font-normal"><?= e($t['title']) ?></h1>
    <div class="flex items-center gap-4">
      <a href="admin_profile.php<?= e($linkLang) ?>&settings=1" class="bg-gray-300 hover:bg-gray-400 transition-colors text-gray-900 px-6 md:px-8 py-4 rounded-full-xl text-lg md:text-xl min-w-[160px] text-center">
        <?= e($t['settings']) ?>
      </a>
      <a href="admin_profile.php<?= e($toggleLangLink) ?>" class="bg-gray-300 hover:bg-gray-400 transition-colors text-gray-900 px-6 md:px-8 py-4 rounded-full-xl text-lg md:text-xl min-w-[160px] text-center">
        <?= e($t['language']) ?>
      </a>
    </div>
  </div>

  <?php if ($message !== ''): ?>
    <div class="mb-6 px-4 py-3 rounded-xl <?= $messageType === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
      <?= e($message) ?>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-[0.95fr_1.25fr] gap-6">
    <section class="bg-gray-200 p-4 md:p-6 card-radius soft-shadow">
      <div class="flex items-center justify-center mb-10">
        <h2 class="text-3xl font-normal"><?= e($t['badges']) ?></h2>
      </div>

      <div class="grid grid-cols-5 gap-5 md:gap-6">
        <?php foreach ($badges as $badge): ?>
          <?php
            $obtained = (int)$badge['obtained'] === 1;
            $name = $lang === 'en' ? $badge['nom_en'] : $badge['nom_fr'];
            $desc = $lang === 'en' ? $badge['descr_en'] : $badge['descr_fr'];
            $title = trim($name . ' - ' . $desc);
          ?>
          <div class="badge-square w-full max-w-[86px] mx-auto rounded-xl relative overflow-hidden flex items-center justify-center <?= $obtained ? 'bg-gray-400 ring-2 ring-emerald-500 ring-offset-2 ring-offset-gray-200' : 'bg-gray-400 opacity-45' ?>" title="<?= e($title) ?>">
            <?php if (!empty($badge['icon_url'])): ?>
              <img src="<?= e($badge['icon_url']) ?>" alt="<?= e($name) ?>" class="w-10 h-10 object-contain">
            <?php else: ?>
              <?php if ($obtained): ?>
                <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              <?php else: ?>
                <svg class="w-7 h-7 text-white opacity-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path d="M7 11V8a5 5 0 0110 0v3" stroke-linecap="round" stroke-linejoin="round"/>
                  <rect x="5" y="11" width="14" height="9" rx="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="bg-gray-200 p-4 md:p-6 card-radius soft-shadow min-h-[760px]">
      <div class="flex items-start justify-between gap-4 mb-10">
        <h2 class="text-3xl font-normal"><?= e($t['ranking']) ?></h2>
        <div class="text-3xl font-normal whitespace-nowrap">
          <?= e($t['rank']) ?> : <?= e($currentRank) ?>
        </div>
      </div>

      <div class="space-y-8">
        <?php foreach ($topCompanies as $index => $row): ?>
          <?php $isCurrent = (int)$row['id'] === $companyId; ?>
          <div class="relative">
            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-16 h-16 rounded-full bg-[#d9d9d9] flex items-center justify-center text-gray-800 text-sm z-10 shadow-sm">
              <?= e($index + 1) ?>
            </div>

            <div class="ml-8 bg-[#a8a8a8] rounded-[22px] h-20 md:h-24 flex items-center px-8 md:px-12 <?= $isCurrent ? 'ring-2 ring-emerald-500' : '' ?>">
              <div class="flex-1 text-center text-xl md:text-3xl font-normal">
                <?= e($isCurrent ? $t['you'] : $row['nom']) ?>
              </div>
              <div class="min-w-[150px] text-right text-xl md:text-3xl font-normal">
                <?= e(fmtNumber($row['total_xp'], $lang)) ?> <?= e($t['points']) ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</main>

<?php if ($openSettings): ?>
<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-4">
  <div class="bg-gray-300 w-full max-w-[1180px] rounded-[18px] shadow-2xl relative px-6 md:px-10 py-8 md:py-10">
    <a href="admin_profile.php<?= e($linkLang) ?>" class="absolute top-5 right-5 w-16 h-16 rounded-full bg-gray-400 flex items-center justify-center" aria-label="<?= e($t['close']) ?>">
      <svg class="w-10 h-10 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
        <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
      </svg>
    </a>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8 mt-10">
      <div class="text-center">
        <div class="mb-3 text-lg"><?= e($t['company_name']) ?></div>
        <button type="button" data-tab="name" class="settings-tab w-full max-w-[300px] mx-auto bg-gray-100 hover:bg-gray-200 transition px-4 py-4 rounded-full text-xl">
          <?= e($t['change_company_name']) ?>
        </button>
      </div>

      <div class="text-center">
        <div class="mb-3 text-lg"><?= e($t['company_mail']) ?></div>
        <button type="button" data-tab="email" class="settings-tab w-full max-w-[300px] mx-auto bg-gray-100 hover:bg-gray-200 transition px-4 py-4 rounded-full text-xl">
          <?= e($t['change_mail']) ?>
        </button>
      </div>

      <div class="text-center">
        <div class="mb-3 text-lg"><?= e($t['company_code']) ?></div>
        <button type="button" data-tab="code" class="settings-tab w-full max-w-[300px] mx-auto bg-gray-100 hover:bg-gray-200 transition px-4 py-4 rounded-full text-xl">
          <?= e($t['change_company_code']) ?>
        </button>
      </div>

      <div class="text-center">
        <div class="mb-3 text-lg"><?= e($t['company_logo']) ?></div>
        <button type="button" data-tab="logo" class="settings-tab w-full max-w-[300px] mx-auto bg-gray-100 hover:bg-gray-200 transition px-4 py-4 rounded-full text-xl">
          <?= e($t['change_company_logo']) ?>
        </button>
      </div>

      <div class="text-center">
        <div class="mb-3 text-lg"><?= e($t['company_password']) ?></div>
        <button type="button" data-tab="password" class="settings-tab w-full max-w-[300px] mx-auto bg-gray-100 hover:bg-gray-200 transition px-4 py-4 rounded-full text-xl">
          <?= e($t['change_password']) ?>
        </button>
      </div>
    </div>

    <div class="mt-10 bg-gray-200 rounded-[18px] p-5 md:p-8">
      <div id="panel-name" class="settings-panel <?= $activeTab === 'name' ? '' : 'hidden' ?>">
        <form method="post" class="space-y-4">
          <input type="hidden" name="action" value="update_company_name">
          <div>
            <label class="block mb-2 text-sm"><?= e($t['company_name']) ?></label>
            <input name="company_name" value="<?= e($companyNameValue) ?>" class="w-full rounded-full px-5 py-4 text-lg bg-gray-100 outline-none" />
          </div>
          <button class="bg-gray-900 text-white px-6 py-3 rounded-full"><?= e($t['save']) ?></button>
        </form>
      </div>

      <div id="panel-email" class="settings-panel <?= $activeTab === 'email' ? '' : 'hidden' ?>">
        <form method="post" class="space-y-4">
          <input type="hidden" name="action" value="update_email">
          <div>
            <label class="block mb-2 text-sm"><?= e($t['company_mail']) ?></label>
            <input type="email" name="email" value="<?= e($userEmailValue) ?>" class="w-full rounded-full px-5 py-4 text-lg bg-gray-100 outline-none" />
          </div>
          <button class="bg-gray-900 text-white px-6 py-3 rounded-full"><?= e($t['save']) ?></button>
        </form>
      </div>

      <div id="panel-code" class="settings-panel <?= $activeTab === 'code' ? '' : 'hidden' ?>">
        <form method="post" class="space-y-4">
          <input type="hidden" name="action" value="update_company_code">
          <div>
            <label class="block mb-2 text-sm"><?= e($t['company_code']) ?></label>
            <input name="company_code" value="<?= e($companyCodeValue) ?>" class="w-full rounded-full px-5 py-4 text-lg bg-gray-100 outline-none" />
          </div>
          <button class="bg-gray-900 text-white px-6 py-3 rounded-full"><?= e($t['save']) ?></button>
        </form>
      </div>

      <div id="panel-logo" class="settings-panel <?= $activeTab === 'logo' ? '' : 'hidden' ?>">
        <form method="post" enctype="multipart/form-data" class="space-y-5">
          <input type="hidden" name="action" value="update_company_logo">
          <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-6 items-center">
            <div class="flex flex-col items-center">
              <div class="mb-3 text-sm"><?= e($t['current_logo']) ?></div>
              <div class="w-[210px] h-[170px] rounded-[20px] bg-gray-100 flex items-center justify-center overflow-hidden border border-gray-300">
                <?php if (!empty($companyLogoValue) && (preg_match('/^https?:\/\//i', $companyLogoValue) || str_starts_with($companyLogoValue, 'uploads/'))): ?>
                  <img src="<?= e($companyLogoValue) ?>" alt="<?= e($company['nom'] ?? $t['company_logo']) ?>" class="w-full h-full object-contain p-4">
                <?php else: ?>
                  <svg class="w-16 h-16 text-gray-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M4 17V7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2z" stroke-linejoin="round"/>
                    <path d="M8 13l2.5-2.5 2.5 2.5 2-2 3 3" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="9" cy="9" r="1.25" fill="currentColor" stroke="none"/>
                  </svg>
                <?php endif; ?>
              </div>
            </div>

            <div class="space-y-4">
              <label class="block text-sm"><?= e($t['upload_logo']) ?></label>
              <label for="company_logo" class="block w-full cursor-pointer rounded-[18px] border-2 border-dashed border-gray-500 bg-gray-100 px-6 py-10 text-center hover:bg-gray-50 transition">
                <div class="flex flex-col items-center gap-3">
                  <svg class="w-14 h-14 text-gray-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M12 16V4" stroke-linecap="round"/>
                    <path d="M7 9l5-5 5 5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M5 20h14a2 2 0 002-2v-4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M3 14v4a2 2 0 002 2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <span class="text-lg font-medium"><?= e($t['change_company_logo']) ?></span>
                  <span class="text-sm text-gray-700"><?= e($t['logo_help']) ?></span>
                </div>
              </label>
              <input id="company_logo" name="company_logo" type="file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="hidden">
              <button class="bg-gray-900 text-white px-6 py-3 rounded-full"><?= e($t['save']) ?></button>
            </div>
          </div>
        </form>
      </div>

      <div id="panel-password" class="settings-panel <?= $activeTab === 'password' ? '' : 'hidden' ?>">
        <form method="post" class="space-y-4">
          <input type="hidden" name="action" value="update_password">
          <div>
            <label class="block mb-2 text-sm"><?= e($t['current_password']) ?></label>
            <input type="password" name="current_password" class="w-full rounded-full px-5 py-4 text-lg bg-gray-100 outline-none" />
          </div>
          <div>
            <label class="block mb-2 text-sm"><?= e($t['new_password']) ?></label>
            <input type="password" name="new_password" class="w-full rounded-full px-5 py-4 text-lg bg-gray-100 outline-none" />
          </div>
          <div>
            <label class="block mb-2 text-sm"><?= e($t['confirm_password']) ?></label>
            <input type="password" name="confirm_password" class="w-full rounded-full px-5 py-4 text-lg bg-gray-100 outline-none" />
          </div>
          <button class="bg-gray-900 text-white px-6 py-3 rounded-full"><?= e($t['save']) ?></button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('.settings-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const tab = this.getAttribute('data-tab');
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