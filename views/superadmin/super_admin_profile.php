<?php
session_start();

if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function fmtNumber($value, string $lang): string
{
    $value = (float) $value;
    return $lang === 'en' ? number_format($value, 0, '.', ',') : number_format($value, 0, ',', ' ');
}

function isValidImageMime(string $mime): bool
{
    return in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true);
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$lang = $_SESSION['lang'] ?? 'fr';

$pdo = new PDO('mysql:host=localhost;dbname=shiftup_db;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'super_admin' ORDER BY id ASC LIMIT 1");
    $user = $stmt->fetch();
    $userId = $user ? (int) $user['id'] : 0;
}

if (!isset($_GET['lang']) && !empty($user['language_pref']) && in_array($user['language_pref'], ['fr', 'en'], true)) {
    $lang = $user['language_pref'];
    $_SESSION['lang'] = $lang;
}

$t = [
    'fr' => [
        'title' => 'Profil entreprise',
        'showcase' => 'Vitrine à succes',
        'create_badge' => 'Création de badge',
        'settings' => 'Paramètre',
        'language' => 'Langue',
        'shift_manager' => 'Shift manager',
        'management' => 'Gestion',
        'close' => 'Fermer',
        'mail' => 'Mail',
        'password' => 'Mot de passe',
        'logo' => 'Logo',
        'change_mail' => 'Changer votre mail',
        'change_password' => 'Changer votre mot de passe',
        'change_logo' => 'Changer votre logo',
        'upload_logo' => 'Importer un logo',
        'logo_help' => 'PNG, JPG, JPEG ou WEBP. Taille recommandée : 512 × 512 px.',
        'current_logo' => 'Logo actuel',
        'save' => 'Enregistrer',
        'badge_name_fr' => 'Nom du badge FR',
        'badge_desc_fr' => 'Description FR',
        'badge_name_en' => 'Nom du badge EN',
        'badge_desc_en' => 'Description EN',
        'badge_condition' => 'Condition d’obtention',
        'badge_image' => 'Badge',
        'add' => 'Ajouter',
        'current_password' => 'Mot de passe actuel',
        'new_password' => 'Nouveau mot de passe',
        'confirm_password' => 'Confirmer le mot de passe',
        'email_label' => 'Mail',
        'required' => 'Veuillez remplir tous les champs.',
        'email_invalid' => 'Veuillez saisir un mail valide.',
        'password_wrong' => 'Le mot de passe actuel est incorrect.',
        'password_mismatch' => 'Les mots de passe ne correspondent pas.',
        'success_email' => 'Le mail a été modifié.',
        'success_password' => 'Le mot de passe a été modifié.',
        'success_logo' => 'Le logo a été mis à jour.',
        'success_badge' => 'Le badge a été créé.',
        'error_logo_type' => 'Format de logo non autorisé.',
        'error_logo_upload' => 'Veuillez sélectionner un logo valide.',
        'error_generic' => 'Une erreur est survenue.',
        'badge_create_hint' => 'Utiliser la base de données pour enregistrer le nouveau badge.',
        'language_switch' => 'English',
        'you' => 'Vous',
    ],
    'en' => [
        'title' => 'Company profile',
        'showcase' => 'Success showcase',
        'create_badge' => 'Badge creation',
        'settings' => 'Settings',
        'language' => 'Language',
        'shift_manager' => 'Shift manager',
        'management' => 'Management',
        'close' => 'Close',
        'mail' => 'Mail',
        'password' => 'Password',
        'logo' => 'Logo',
        'change_mail' => 'Change your email',
        'change_password' => 'Change your password',
        'change_logo' => 'Change your logo',
        'upload_logo' => 'Upload logo',
        'logo_help' => 'PNG, JPG, JPEG or WEBP. Recommended size: 512 × 512 px.',
        'current_logo' => 'Current logo',
        'save' => 'Save',
        'badge_name_fr' => 'Badge name FR',
        'badge_desc_fr' => 'Description FR',
        'badge_name_en' => 'Badge name EN',
        'badge_desc_en' => 'Description EN',
        'badge_condition' => 'Unlock condition',
        'badge_image' => 'Badge',
        'add' => 'Add',
        'current_password' => 'Current password',
        'new_password' => 'New password',
        'confirm_password' => 'Confirm password',
        'email_label' => 'Mail',
        'required' => 'Please fill in all fields.',
        'email_invalid' => 'Please enter a valid email.',
        'password_wrong' => 'Current password is incorrect.',
        'password_mismatch' => 'Passwords do not match.',
        'success_email' => 'Email updated.',
        'success_password' => 'Password updated.',
        'success_logo' => 'Logo updated.',
        'success_badge' => 'Badge created.',
        'error_logo_type' => 'Unsupported logo format.',
        'error_logo_upload' => 'Please select a valid logo.',
        'error_generic' => 'Something went wrong.',
        'badge_create_hint' => 'Use the database to store the new badge.',
        'language_switch' => 'Français',
        'you' => 'You',
    ],
];

$t = $t[$lang] ?? $t['fr'];
$linkLang = '?lang=' . $lang;
$toggleLangLink = '?lang=' . ($lang === 'fr' ? 'en' : 'fr');
$homeLink = 'superadmin_dashboard.php' . $linkLang;
$shiftLink = 'super_admin_shift_manager.php' . $linkLang;
$gestionLink = 'superadmin_gestion.php' . $linkLang;

$message = '';
$messageType = '';
$openSettings = isset($_GET['settings']) && $_GET['settings'] === '1';
$activeTab = $_GET['tab'] ?? 'password';

if (isset($_SESSION['flash_message'])) {
    $message = (string) $_SESSION['flash_message'];
    $messageType = (string) ($_SESSION['flash_type'] ?? 'success');
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_email') {
            $newEmail = trim((string)($_POST['email'] ?? ''));
            if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException($t['email_invalid']);
            }

            $stmt = $pdo->prepare('UPDATE users SET email = :email WHERE id = :id');
            $stmt->execute(['email' => $newEmail, 'id' => $userId]);

            $_SESSION['flash_message'] = $t['success_email'];
            $_SESSION['flash_type'] = 'success';
            header('Location: super_admin_profile.php' . $linkLang . '&settings=1&tab=email');
            exit;
        }

        if ($action === 'update_password') {
            $currentPassword = (string)($_POST['current_password'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                throw new RuntimeException($t['required']);
            }
            if (!password_verify($currentPassword, (string)$user['password_hash'])) {
                throw new RuntimeException($t['password_wrong']);
            }
            if ($newPassword !== $confirmPassword) {
                throw new RuntimeException($t['password_mismatch']);
            }

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $stmt->execute(['hash' => $hash, 'id' => $userId]);

            $_SESSION['flash_message'] = $t['success_password'];
            $_SESSION['flash_type'] = 'success';
            header('Location: super_admin_profile.php' . $linkLang . '&settings=1&tab=password');
            exit;
        }

        if ($action === 'update_logo') {
            if (!isset($_FILES['logo']) || !is_array($_FILES['logo'])) {
                throw new RuntimeException($t['error_logo_upload']);
            }

            $file = $_FILES['logo'];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException($t['error_logo_upload']);
            }

            $tmpPath = (string)($file['tmp_name'] ?? '');
            if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                throw new RuntimeException($t['error_logo_upload']);
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpPath) ?: '';
            $allowedMime = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
            ];
            if (!isValidImageMime($mimeType) || !isset($allowedMime[$mimeType])) {
                throw new RuntimeException($t['error_logo_type']);
            }

            $uploadDir = __DIR__ . '/uploads/super_admin_logos';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                throw new RuntimeException($t['error_generic']);
            }

            $filename = 'super_admin_' . $userId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mimeType];
            $destination = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($tmpPath, $destination)) {
                throw new RuntimeException($t['error_generic']);
            }

            $relativePath = 'uploads/super_admin_logos/' . $filename;
            $stmt = $pdo->prepare('UPDATE users SET avatar_url = :avatar WHERE id = :id');
            $stmt->execute(['avatar' => $relativePath, 'id' => $userId]);

            $_SESSION['flash_message'] = $t['success_logo'];
            $_SESSION['flash_type'] = 'success';
            header('Location: super_admin_profile.php' . $linkLang . '&settings=1&tab=logo');
            exit;
        }

        if ($action === 'create_badge') {
            $nameFr = trim((string)($_POST['badge_name_fr'] ?? ''));
            $descrFr = trim((string)($_POST['badge_desc_fr'] ?? ''));
            $nameEn = trim((string)($_POST['badge_name_en'] ?? ''));
            $descrEn = trim((string)($_POST['badge_desc_en'] ?? ''));
            $condition = trim((string)($_POST['badge_condition'] ?? ''));

            if ($nameFr === '' || $descrFr === '' || $nameEn === '' || $descrEn === '' || $condition === '') {
                throw new RuntimeException($t['required']);
            }
            if (!is_numeric($condition)) {
                throw new RuntimeException($t['error_generic']);
            }

            $iconPath = '';
            if (isset($_FILES['badge_icon']) && is_array($_FILES['badge_icon']) && ($file = $_FILES['badge_icon']) && (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)) {
                $tmpPath = (string)($file['tmp_name'] ?? '');
                if ($tmpPath !== '' && is_uploaded_file($tmpPath)) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($tmpPath) ?: '';
                    $allowedMime = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        'image/gif' => 'gif',
                    ];
                    if (isset($allowedMime[$mimeType])) {
                        $uploadDir = __DIR__ . '/uploads/badges';
                        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                            throw new RuntimeException($t['error_generic']);
                        }
                        $filename = 'badge_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mimeType];
                        $destination = $uploadDir . '/' . $filename;
                        if (!move_uploaded_file($tmpPath, $destination)) {
                            throw new RuntimeException($t['error_generic']);
                        }
                        $iconPath = 'uploads/badges/' . $filename;
                    }
                }
            }

            $stmt = $pdo->prepare('INSERT INTO badges (nom_fr, nom_en, descr_fr, descr_en, icon_url, xp_threshold, challenge_required_id) VALUES (:nom_fr, :nom_en, :descr_fr, :descr_en, :icon_url, :xp_threshold, :challenge_required_id)');
            $stmt->execute([
                'nom_fr' => $nameFr,
                'nom_en' => $nameEn,
                'descr_fr' => $descrFr,
                'descr_en' => $descrEn,
                'icon_url' => $iconPath,
                'xp_threshold' => (int) $condition,
                'challenge_required_id' => null,
            ]);

            $_SESSION['flash_message'] = $t['success_badge'];
            $_SESSION['flash_type'] = 'success';
            header('Location: super_admin_profile.php' . $linkLang);
            exit;
        }

        throw new RuntimeException($t['error_generic']);
    } catch (Throwable $ex) {
        $_SESSION['flash_message'] = $ex->getMessage();
        $_SESSION['flash_type'] = 'error';
        header('Location: super_admin_profile.php' . $linkLang . ($openSettings ? '&settings=1&tab=' . urlencode($activeTab) : ''));
        exit;
    }
}

$stmt = $pdo->query("SELECT id, nom_fr, nom_en, descr_fr, descr_en, icon_url, xp_threshold, challenge_required_id FROM badges ORDER BY xp_threshold ASC, id ASC");
$badges = $stmt->fetchAll();

$showcaseSlots = 25;
$avatarValue = (string)($user['avatar_url'] ?? '');
$userEmailValue = (string)($user['email'] ?? '');
$placeholderCount = max(0, $showcaseSlots - count($badges));
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($t['title']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { background: #fff; color: #111; }
    .page-shell { max-width: 1500px; }
    .pill { border-radius: 9999px; }
    .panel { border-radius: 18px; }
    .soft-shadow { box-shadow: 0 10px 30px rgba(0,0,0,.08); }
  </style>
</head>
<body class="min-h-screen">
<header class="bg-[#e0e0e0] h-[98px] relative">
  <div class="absolute left-0 top-0 bottom-0 w-[280px] bg-[#8f8f8f] flex items-center justify-center">
    <a href="<?= e($homeLink) ?>" aria-label="Accueil" class="w-12 h-12 flex items-center justify-center">
      <svg class="w-8 h-8 text-[#171717]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
        <path d="M12 3L4.5 6.2V12c0 4.7 3.1 8.9 7.5 10 4.4-1.1 7.5-5.3 7.5-10V6.2L12 3Z" stroke-linejoin="round"/>
        <path d="M9 13.5h6" stroke-linecap="round"/>
        <path d="M15.5 4.5v5M13 7h5" stroke-linecap="round"/>
      </svg>
    </a>
  </div>
  <div class="h-full flex items-center justify-end gap-10 pr-8 pl-[300px]">
    <nav class="hidden md:flex items-center gap-10 text-[17px] text-[#111]">
      <a href="<?= e($shiftLink) ?>" class="font-medium"><?= e($t['shift_manager']) ?></a>
      <a href="<?= e($gestionLink) ?>"><?= e($t['management']) ?></a>
      <a href="super_admin_profile.php<?= e($linkLang) ?>" class="w-12 h-12 rounded-full border-4 border-[#111] flex items-center justify-center overflow-hidden bg-[#e0e0e0]">
        <?php if ($avatarValue !== '' && preg_match('/^(https?:\/\/|uploads\/)/i', $avatarValue)): ?>
          <img src="<?= e($avatarValue) ?>" alt="Avatar" class="w-full h-full object-cover">
        <?php else: ?>
          <svg class="w-7 h-7 text-[#111]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <circle cx="12" cy="8" r="3.2" />
            <path d="M5.5 20c0-3.4 3.1-5.5 6.5-5.5s6.5 2.1 6.5 5.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        <?php endif; ?>
      </a>
    </nav>
  </div>
</header>

<main class="page-shell mx-auto px-4 md:px-6 py-10">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-8">
    <h1 class="text-[34px] font-normal"><?= e($t['title']) ?></h1>
    <div class="flex items-center gap-6">
      <a href="super_admin_profile.php<?= e($linkLang) ?>&settings=1" class="bg-[#d5d5d5] hover:bg-[#cfcfcf] transition px-8 py-5 pill text-[18px] min-w-[160px] text-center"><?= e($t['settings']) ?></a>
      <a href="super_admin_profile.php<?= e($toggleLangLink) ?>" class="bg-[#d5d5d5] hover:bg-[#cfcfcf] transition px-8 py-5 pill text-[18px] min-w-[160px] text-center"><?= e($t['language']) ?></a>
    </div>
  </div>

  <?php if ($message !== ''): ?>
    <div class="mb-6 px-4 py-3 rounded-xl <?= $messageType === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
      <?= e($message) ?>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-[0.98fr_1.27fr] gap-5">
    <section class="bg-[#d9d9d9] panel soft-shadow p-4 md:p-6 min-h-[720px]">
      <div class="flex justify-center mb-10">
        <h2 class="text-[34px] font-normal"><?= e($t['showcase']) ?></h2>
      </div>

      <div class="grid grid-cols-5 gap-5">
        <?php foreach ($badges as $badge): ?>
          <?php
            $name = $lang === 'en' ? (string)$badge['nom_en'] : (string)$badge['nom_fr'];
            $desc = $lang === 'en' ? (string)$badge['descr_en'] : (string)$badge['descr_fr'];
            $title = trim($name . ' - ' . $desc);
          ?>
          <div class="w-full aspect-square max-w-[86px] mx-auto rounded-xl bg-[#a7a7a7] flex items-center justify-center overflow-hidden soft-shadow" title="<?= e($title) ?>">
            <?php if (!empty($badge['icon_url']) && preg_match('/^(https?:\/\/|uploads\/)/i', (string)$badge['icon_url'])): ?>
              <img src="<?= e($badge['icon_url']) ?>" alt="<?= e($name) ?>" class="w-10 h-10 object-contain">
            <?php else: ?>
              <svg class="w-8 h-8 text-[#222]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M4 17V7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2Z" stroke-linejoin="round"/>
                <path d="M8 13l2.5-2.5 2 2 3-3 2.5 2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15.5 6.5h4M17.5 4.5v4" stroke-linecap="round"/>
              </svg>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <?php for ($i = 0; $i < $placeholderCount; $i++): ?>
          <div class="w-full aspect-square max-w-[86px] mx-auto rounded-xl bg-[#a7a7a7] flex items-center justify-center overflow-hidden soft-shadow"></div>
        <?php endfor; ?>
      </div>
    </section>

    <section class="bg-[#d9d9d9] panel soft-shadow p-4 md:p-6 min-h-[720px]">
      <div class="text-center mb-8">
        <h2 class="text-[34px] font-normal"><?= e($t['create_badge']) ?></h2>
      </div>

      <form method="post" enctype="multipart/form-data" class="space-y-7">
        <input type="hidden" name="action" value="create_badge">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
          <div>
            <div class="text-center mb-2 text-[18px]"><?= e($t['badge_name_fr']) ?></div>
            <input name="badge_name_fr" class="w-full h-[40px] rounded-[10px] bg-[#a7a7a7] px-4 outline-none" />
          </div>
          <div>
            <div class="text-center mb-2 text-[18px]"><?= e($t['badge_desc_fr']) ?></div>
            <input name="badge_desc_fr" class="w-full h-[40px] rounded-[10px] bg-[#a7a7a7] px-4 outline-none" />
          </div>
          <div>
            <div class="text-center mb-2 text-[18px]"><?= e($t['badge_name_en']) ?></div>
            <input name="badge_name_en" class="w-full h-[40px] rounded-[10px] bg-[#a7a7a7] px-4 outline-none" />
          </div>
          <div>
            <div class="text-center mb-2 text-[18px]"><?= e($t['badge_desc_en']) ?></div>
            <input name="badge_desc_en" class="w-full h-[40px] rounded-[10px] bg-[#a7a7a7] px-4 outline-none" />
          </div>
        </div>

        <div class="max-w-[480px] mx-auto">
          <div class="text-center mb-2 text-[18px]"><?= e($t['badge_condition']) ?></div>
          <input name="badge_condition" type="number" min="0" class="w-full h-[40px] rounded-[10px] bg-[#a7a7a7] px-4 outline-none" />
        </div>

        <div class="text-center mt-1">
          <div class="mb-3 text-[18px]"><?= e($t['badge_image']) ?></div>
          <label for="badge_icon" class="block w-full max-w-[720px] mx-auto h-[200px] rounded-[20px] bg-[#a7a7a7] flex items-center justify-center cursor-pointer overflow-hidden">
            <svg class="w-16 h-16 text-[#222]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
              <path d="M4 17V7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2Z" stroke-linejoin="round"/>
              <path d="M8 13l2.5-2.5 2 2 3-3 2.5 2.5" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16.5 6.5h4M18.5 4.5v4" stroke-linecap="round"/>
            </svg>
          </label>
          <input id="badge_icon" name="badge_icon" type="file" accept=".png,.jpg,.jpeg,.webp,.gif,image/png,image/jpeg,image/webp,image/gif" class="hidden">
        </div>

        <div class="text-center pt-1">
          <button class="bg-[#a7a7a7] hover:bg-[#9b9b9b] transition px-16 py-3 pill text-[18px]"> <?= e($t['add']) ?> </button>
        </div>
      </form>
    </section>
  </div>
</main>

<?php if ($openSettings): ?>
<div class="fixed inset-0 bg-black/45 flex items-center justify-center z-50 px-4">
  <div class="bg-[#d0d0d0] w-full max-w-[1180px] rounded-[18px] shadow-2xl relative px-6 md:px-10 py-8 md:py-10 min-h-[620px]">
    <a href="super_admin_profile.php<?= e($linkLang) ?>" class="absolute top-5 right-5 w-16 h-16 rounded-full bg-[#bfbfbf] flex items-center justify-center" aria-label="<?= e($t['close']) ?>">
      <svg class="w-10 h-10 text-[#111]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
        <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
      </svg>
    </a>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8 mt-14">
      <div class="text-center">
        <div class="mb-3 text-[18px]"><?= e($t['password']) ?></div>
        <button type="button" data-tab="password" class="settings-tab w-full max-w-[310px] mx-auto bg-[#efefef] hover:bg-white transition px-4 py-4 pill text-[18px]"><?= e($t['change_password']) ?></button>
      </div>

      <div class="text-center">
        <div class="mb-3 text-[18px]"><?= e($t['mail']) ?></div>
        <button type="button" data-tab="email" class="settings-tab w-full max-w-[310px] mx-auto bg-[#efefef] hover:bg-white transition px-4 py-4 pill text-[18px]"><?= e($t['change_mail']) ?></button>
      </div>

      <div class="text-center md:col-span-2 md:max-w-[340px] md:mx-auto">
        <div class="mb-3 text-[18px]"><?= e($t['logo']) ?></div>
        <button type="button" data-tab="logo" class="settings-tab w-full bg-[#efefef] hover:bg-white transition px-4 py-4 pill text-[18px]"><?= e($t['change_logo']) ?></button>
      </div>
    </div>

    <div class="mt-10 bg-[#e3e3e3] rounded-[18px] p-5 md:p-8">
      <div id="panel-email" class="settings-panel <?= $activeTab === 'email' ? '' : 'hidden' ?>">
        <form method="post" class="space-y-4 max-w-[720px] mx-auto">
          <input type="hidden" name="action" value="update_email">
          <div>
            <label class="block mb-2 text-sm"><?= e($t['email_label']) ?></label>
            <input type="email" name="email" value="<?= e($userEmailValue) ?>" class="w-full rounded-full px-5 py-4 text-lg bg-[#efefef] outline-none" />
          </div>
          <button class="bg-[#111] text-white px-6 py-3 rounded-full"><?= e($t['save']) ?></button>
        </form>
      </div>

      <div id="panel-password" class="settings-panel <?= $activeTab === 'password' ? '' : 'hidden' ?>">
        <form method="post" class="space-y-4 max-w-[720px] mx-auto">
          <input type="hidden" name="action" value="update_password">
          <div>
            <label class="block mb-2 text-sm"><?= e($t['current_password']) ?></label>
            <input type="password" name="current_password" class="w-full rounded-full px-5 py-4 text-lg bg-[#efefef] outline-none" />
          </div>
          <div>
            <label class="block mb-2 text-sm"><?= e($t['new_password']) ?></label>
            <input type="password" name="new_password" class="w-full rounded-full px-5 py-4 text-lg bg-[#efefef] outline-none" />
          </div>
          <div>
            <label class="block mb-2 text-sm"><?= e($t['confirm_password']) ?></label>
            <input type="password" name="confirm_password" class="w-full rounded-full px-5 py-4 text-lg bg-[#efefef] outline-none" />
          </div>
          <button class="bg-[#111] text-white px-6 py-3 rounded-full"><?= e($t['save']) ?></button>
        </form>
      </div>

      <div id="panel-logo" class="settings-panel <?= $activeTab === 'logo' ? '' : 'hidden' ?>">
        <form method="post" enctype="multipart/form-data" class="space-y-5 max-w-[920px] mx-auto">
          <input type="hidden" name="action" value="update_logo">
          <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-6 items-center">
            <div class="flex flex-col items-center">
              <div class="mb-3 text-sm"><?= e($t['current_logo']) ?></div>
              <div class="w-[220px] h-[180px] rounded-[20px] bg-[#efefef] flex items-center justify-center overflow-hidden border border-[#c9c9c9]">
                <?php if ($avatarValue !== '' && preg_match('/^(https?:\/\/|uploads\/)/i', $avatarValue)): ?>
                  <img src="<?= e($avatarValue) ?>" alt="Avatar" class="w-full h-full object-contain p-4">
                <?php else: ?>
                  <svg class="w-16 h-16 text-[#222]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M4 17V7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2Z" stroke-linejoin="round"/>
                    <path d="M8 13l2.5-2.5 2 2 3-3 2.5 2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M16.5 6.5h4M18.5 4.5v4" stroke-linecap="round"/>
                  </svg>
                <?php endif; ?>
              </div>
            </div>

            <div class="space-y-4">
              <label class="block text-sm"><?= e($t['upload_logo']) ?></label>
              <label for="logo" class="block w-full cursor-pointer rounded-[18px] border-2 border-dashed border-[#7d7d7d] bg-[#efefef] px-6 py-10 text-center hover:bg-white transition">
                <div class="flex flex-col items-center gap-3">
                  <svg class="w-14 h-14 text-[#222]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M12 16V4" stroke-linecap="round"/>
                    <path d="M7 9l5-5 5 5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M5 20h14a2 2 0 002-2v-4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M3 14v4a2 2 0 002 2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <span class="text-lg font-medium"><?= e($t['change_logo']) ?></span>
                  <span class="text-sm text-[#444]"><?= e($t['logo_help']) ?></span>
                </div>
              </label>
              <input id="logo" name="logo" type="file" accept=".png,.jpg,.jpeg,.webp,.gif,image/png,image/jpeg,image/webp,image/gif" class="hidden">
              <button class="bg-[#111] text-white px-6 py-3 rounded-full"><?= e($t['save']) ?></button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('.settings-tab').forEach(function (btn) {
  btn.addEventListener('click', function () {
    const tab = this.getAttribute('data-tab');
    document.querySelectorAll('.settings-panel').forEach(function (panel) {
      panel.classList.add('hidden');
    });
    const selected = document.getElementById('panel-' + tab);
    if (selected) selected.classList.remove('hidden');
  });
});
</script>
</body>
</html>
