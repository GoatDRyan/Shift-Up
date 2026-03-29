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
        'showcase' => 'Vitrine à succès',
        'create_badge' => 'Création de badge',
        'settings' => 'Paramètre',
        'language' => 'Langue',
        'shift_manager' => 'Shift Manager',
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
        'badge_condition' => 'Condition d\'obtention (XP)',
        'badge_image' => 'Image du badge',
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
        'shift_manager' => 'Shift Manager',
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
        'badge_condition' => 'Unlock condition (XP)',
        'badge_image' => 'Badge image',
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
            $newPassword     = (string)($_POST['new_password'] ?? '');
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
            $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
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
            $nameFr    = trim((string)($_POST['badge_name_fr'] ?? ''));
            $descrFr   = trim((string)($_POST['badge_desc_fr'] ?? ''));
            $nameEn    = trim((string)($_POST['badge_name_en'] ?? ''));
            $descrEn   = trim((string)($_POST['badge_desc_en'] ?? ''));
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
                    $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
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
                'nom_fr' => $nameFr, 'nom_en' => $nameEn,
                'descr_fr' => $descrFr, 'descr_en' => $descrEn,
                'icon_url' => $iconPath, 'xp_threshold' => (int) $condition,
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
  <title><?= e($t['title']) ?> — Super Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root { --orange: #FF4800; }
    body { background: #fff; color: #111; }
    .page-shell { max-width: 1500px; }
    .pill { border-radius: 9999px; }
    .panel { border-radius: 18px; }
    .soft-shadow { box-shadow: 0 10px 30px rgba(0,0,0,.08); }
    .header-bg { background: #FF4800; }
    .btn-orange { background: #FF4800; color: #fff; border-radius: 9999px; transition: background .2s; }
    .btn-orange:hover { background: #cc3a00; }
    .btn-light { background: #fff3ee; color: #FF4800; border: 1.5px solid #ffd6c2; border-radius: 9999px; transition: background .2s; }
    .btn-light:hover { background: #ffe5d9; }
    .badge-slot { border-radius: 14px; }
    .badge-slot-filled { background: #fff3ee; border: 2px solid #FF4800; }
    .badge-slot-empty { background: #f5f5f5; border: 2px dashed #e5e7eb; }
    .input-orange { background: #fff; border: 1.5px solid #e5e7eb; border-radius: .75rem; outline: none; transition: border-color .2s; padding: .65rem 1rem; width: 100%; }
    .input-orange:focus { border-color: #FF4800; }
    .settings-tab-btn { border-radius: 9999px; padding: 1rem 1.5rem; font-size: 1.05rem; width: 100%; max-width: 310px; transition: background .2s, color .2s; }
    .settings-tab-active { background: #FF4800; color: #fff; }
    .settings-tab-inactive { background: #f5f5f5; color: #555; }
    .settings-tab-inactive:hover { background: #fff3ee; color: #FF4800; }
    .upload-zone { border: 2px dashed #FF4800; background: #fff3ee; border-radius: 18px; cursor: pointer; transition: background .2s; }
    .upload-zone:hover { background: #ffe5d9; }
  </style>
</head>
<body class="min-h-screen">
<header class="header-bg h-16 relative">
  <div class="absolute left-0 top-0 bottom-0 w-20 md:w-72 bg-black/20 flex items-center justify-center">
    <a href="<?= e($homeLink) ?>" aria-label="Accueil" class="w-12 h-12 flex items-center justify-center">
      <img src="../../img/icone/shiftup-logo.png" alt="ShiftUp" class="w-10 h-10 object-contain" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
      <svg style="display:none" class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
        <path d="M12 2L4 5v6c0 5 3.5 9.7 8 11 4.5-1.3 8-6 8-11V5l-8-3z" stroke-linejoin="round" stroke-linecap="round"/>
      </svg>
    </a>
  </div>
  <div class="h-full flex items-center justify-end gap-8 pr-6 pl-20 md:pl-72">
    <nav class="hidden md:flex items-center gap-8">
      <a href="<?= e($shiftLink) ?>" class="text-white font-bold hover:text-orange-200 transition"><?= e($t['shift_manager']) ?></a>
      <a href="<?= e($gestionLink) ?>" class="text-white hover:text-orange-200 transition"><?= e($t['management']) ?></a>

      <a href="super_admin_profile.php<?= e($linkLang) ?>" class="w-10 h-10 rounded-full border-2 border-white flex items-center justify-center overflow-hidden hover:bg-white/20 transition">
        <?php if ($avatarValue !== '' && preg_match('/^(https?:\/\/|uploads\/)/i', $avatarValue)): ?>
          <img src="<?= e($avatarValue) ?>" alt="Avatar" class="w-full h-full object-cover">
        <?php else: ?>
          <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="8" r="3" fill="none"/>
            <path d="M6 20c0-3 4-5 6-5s6 2 6 5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
          </svg>
        <?php endif; ?>
      </a>
    </nav>
  </div>
</header>

<main class="page-shell mx-auto px-4 md:px-8 py-10">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5 mb-8">
    <h1 class="text-4xl font-light" style="color:#FF4800"><?= e($t['title']) ?></h1>
    <div class="flex items-center gap-3 flex-wrap">
      <a href="super_admin_profile.php<?= e($linkLang) ?>&settings=1" class="btn-light px-7 py-3 text-base font-semibold"><?= e($t['settings']) ?></a>
      <a href="super_admin_profile.php<?= e($toggleLangLink) ?>" class="btn-light px-7 py-3 text-base font-semibold"><?= e($t['language']) ?> — <?= e($t['language_switch']) ?></a>
    </div>
  </div>
  <?php if ($message !== ''): ?>
    <div class="mb-6 px-5 py-4 rounded-xl <?= $messageType === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700' ?> flex items-center gap-2">
      <span><?= $messageType === 'error' ? '⚠️' : '✅' ?></span> <?= e($message) ?>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <section class="bg-gray-50 panel soft-shadow p-6 border border-orange-100 min-h-[680px]">
      <h2 class="text-3xl font-light text-center mb-8" style="color:#FF4800"><?= e($t['showcase']) ?></h2>
      <div class="grid grid-cols-5 gap-4">
        <?php foreach ($badges as $badge):
          $name = $lang === 'en' ? (string)$badge['nom_en'] : (string)$badge['nom_fr'];
          $desc = $lang === 'en' ? (string)$badge['descr_en'] : (string)$badge['descr_fr'];
          $title = trim($name . ' — ' . $desc . ' (' . $badge['xp_threshold'] . ' XP)');
        ?>
          <div class="w-full aspect-square max-w-[90px] mx-auto badge-slot badge-slot-filled flex items-center justify-center overflow-hidden soft-shadow" title="<?= e($title) ?>">
            <?php if (!empty($badge['icon_url']) && preg_match('/^(https?:\/\/|uploads\/)/i', (string)$badge['icon_url'])): ?>
              <img src="<?= e($badge['icon_url']) ?>" alt="<?= e($name) ?>" class="w-12 h-12 object-contain p-1">
            <?php else: ?>
              <svg class="w-9 h-9" style="color:#FF4800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6L12 2z" stroke-linejoin="round"/>
              </svg>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php for ($i = 0; $i < $placeholderCount; $i++): ?>
          <div class="w-full aspect-square max-w-[90px] mx-auto badge-slot badge-slot-empty"></div>
        <?php endfor; ?>
      </div>
    </section>

    <section class="bg-gray-50 panel soft-shadow p-6 border border-orange-100 min-h-[680px]">
      <h2 class="text-3xl font-light text-center mb-8" style="color:#FF4800"><?= e($t['create_badge']) ?></h2>

      <form method="post" enctype="multipart/form-data" class="space-y-5">
        <input type="hidden" name="action" value="create_badge">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1"><?= e($t['badge_name_fr']) ?></label>
            <input name="badge_name_fr" class="input-orange" placeholder="Ex: Cycliste du mois" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1"><?= e($t['badge_desc_fr']) ?></label>
            <input name="badge_desc_fr" class="input-orange" placeholder="Description courte" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1"><?= e($t['badge_name_en']) ?></label>
            <input name="badge_name_en" class="input-orange" placeholder="Ex: Cyclist of the month" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1"><?= e($t['badge_desc_en']) ?></label>
            <input name="badge_desc_en" class="input-orange" placeholder="Short description" />
          </div>
        </div>

        <div class="max-w-xs">
          <label class="block text-sm font-semibold text-gray-600 mb-1"><?= e($t['badge_condition']) ?></label>
          <input name="badge_condition" type="number" min="0" class="input-orange" placeholder="Ex: 500" />
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-2"><?= e($t['badge_image']) ?></label>
          <label for="badge_icon" class="upload-zone flex flex-col items-center justify-center py-10 w-full">
            <svg class="w-12 h-12 mb-2" style="color:#FF4800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
              <path d="M4 17V7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2Z" stroke-linejoin="round"/>
              <path d="M8 13l2.5-2.5 2 2 3-3 2.5 2.5" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16.5 6.5h4M18.5 4.5v4" stroke-linecap="round"/>
            </svg>
            <span style="color:#FF4800" class="font-medium">Cliquer pour importer une image</span>
            <span class="text-xs text-gray-400 mt-1">PNG, JPG, WEBP, GIF</span>
            <span id="badgeFileName" class="text-sm mt-2 text-gray-600"></span>
          </label>
          <input id="badge_icon" name="badge_icon" type="file" accept=".png,.jpg,.jpeg,.webp,.gif,image/png,image/jpeg,image/webp,image/gif" class="hidden">
        </div>

        <div class="text-center pt-2">
          <button class="btn-orange px-12 py-3 text-lg font-semibold shadow hover:scale-[1.02] transition"><?= e($t['add']) ?></button>
        </div>
      </form>
    </section>

  </div>
</main>

<?php if ($openSettings): ?>
<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-4">
  <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl relative px-6 md:px-10 py-8 md:py-10 border border-orange-100">
    <a href="super_admin_profile.php<?= e($linkLang) ?>" class="absolute top-4 right-4 w-12 h-12 rounded-full bg-gray-100 hover:bg-orange-100 flex items-center justify-center transition" aria-label="<?= e($t['close']) ?>">
      <svg class="w-6 h-6 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
        <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
      </svg>
    </a>

    <h2 class="text-2xl font-light mb-8" style="color:#FF4800"><?= e($t['settings']) ?></h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
      <div class="text-center">
        <p class="text-sm text-gray-500 mb-2"><?= e($t['password']) ?></p>
        <button type="button" data-tab="password" class="settings-tab-btn <?= $activeTab === 'password' ? 'settings-tab-active' : 'settings-tab-inactive' ?>"><?= e($t['change_password']) ?></button>
      </div>
      <div class="text-center">
        <p class="text-sm text-gray-500 mb-2"><?= e($t['mail']) ?></p>
        <button type="button" data-tab="email" class="settings-tab-btn <?= $activeTab === 'email' ? 'settings-tab-active' : 'settings-tab-inactive' ?>"><?= e($t['change_mail']) ?></button>
      </div>
      <div class="text-center">
        <p class="text-sm text-gray-500 mb-2"><?= e($t['logo']) ?></p>
        <button type="button" data-tab="logo" class="settings-tab-btn <?= $activeTab === 'logo' ? 'settings-tab-active' : 'settings-tab-inactive' ?>"><?= e($t['change_logo']) ?></button>
      </div>
    </div>

    <div class="bg-gray-50 rounded-2xl p-6 border border-orange-100">
      <div id="panel-email" class="settings-panel <?= $activeTab === 'email' ? '' : 'hidden' ?>">
        <form method="post" class="space-y-4 max-w-xl mx-auto">
          <input type="hidden" name="action" value="update_email">
          <div>
            <label class="block mb-1 text-sm font-semibold text-gray-600"><?= e($t['email_label']) ?></label>
            <input type="email" name="email" value="<?= e($userEmailValue) ?>" class="input-orange text-lg" />
          </div>
          <button class="btn-orange px-8 py-3 font-semibold"><?= e($t['save']) ?></button>
        </form>
      </div>

      <div id="panel-password" class="settings-panel <?= $activeTab === 'password' ? '' : 'hidden' ?>">
        <form method="post" class="space-y-4 max-w-xl mx-auto">
          <input type="hidden" name="action" value="update_password">
          <div>
            <label class="block mb-1 text-sm font-semibold text-gray-600"><?= e($t['current_password']) ?></label>
            <input type="password" name="current_password" class="input-orange" />
          </div>
          <div>
            <label class="block mb-1 text-sm font-semibold text-gray-600"><?= e($t['new_password']) ?></label>
            <input type="password" name="new_password" class="input-orange" />
          </div>
          <div>
            <label class="block mb-1 text-sm font-semibold text-gray-600"><?= e($t['confirm_password']) ?></label>
            <input type="password" name="confirm_password" class="input-orange" />
          </div>
          <button class="btn-orange px-8 py-3 font-semibold"><?= e($t['save']) ?></button>
        </form>
      </div>

      <div id="panel-logo" class="settings-panel <?= $activeTab === 'logo' ? '' : 'hidden' ?>">
        <form method="post" enctype="multipart/form-data" class="space-y-5 max-w-2xl mx-auto">
          <input type="hidden" name="action" value="update_logo">
          <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-6 items-center">
            <div class="flex flex-col items-center">
              <p class="text-sm text-gray-500 mb-3"><?= e($t['current_logo']) ?></p>
              <div class="w-48 h-40 rounded-2xl bg-white border-2 flex items-center justify-center overflow-hidden" style="border-color:#FF4800">
                <?php if ($avatarValue !== '' && preg_match('/^(https?:\/\/|uploads\/)/i', $avatarValue)): ?>
                  <img src="<?= e($avatarValue) ?>" alt="Avatar" class="w-full h-full object-contain p-3">
                <?php else: ?>
                  <svg class="w-14 h-14 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
                    <circle cx="12" cy="8" r="4"/><path d="M6 20c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke-linecap="round"/>
                  </svg>
                <?php endif; ?>
              </div>
            </div>
            <div>
              <label for="logo" class="upload-zone flex flex-col items-center py-10 px-6 cursor-pointer">
                <svg class="w-10 h-10 mb-2" style="color:#FF4800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                  <path d="M12 16V4" stroke-linecap="round"/>
                  <path d="M7 9l5-5 5 5" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M5 20h14" stroke-linecap="round"/>
                </svg>
                <span style="color:#FF4800" class="font-semibold text-base"><?= e($t['change_logo']) ?></span>
                <span class="text-xs text-gray-400 mt-1"><?= e($t['logo_help']) ?></span>
                <span id="logoFileName" class="text-sm text-gray-600 mt-1"></span>
              </label>
              <input id="logo" name="logo" type="file" accept=".png,.jpg,.jpeg,.webp,.gif,image/png,image/jpeg,image/webp,image/gif" class="hidden">
              <button class="btn-orange px-8 py-3 mt-4 font-semibold"><?= e($t['save']) ?></button>
            </div>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('[data-tab]').forEach(btn => {
  btn.addEventListener('click', function() {
    const tab = this.dataset.tab;
    document.querySelectorAll('.settings-panel').forEach(p => p.classList.add('hidden'));
    const panel = document.getElementById('panel-' + tab);
    if (panel) panel.classList.remove('hidden');
    document.querySelectorAll('[data-tab]').forEach(b => {
      b.className = b.className.replace('settings-tab-active', 'settings-tab-inactive');
    });
    this.className = this.className.replace('settings-tab-inactive', 'settings-tab-active');
  });
});


const logoInput = document.getElementById('logo');
if (logoInput) {
  logoInput.addEventListener('change', function() {
    const el = document.getElementById('logoFileName');
    if (el && this.files[0]) el.textContent = this.files[0].name;
  });
}

const badgeInput = document.getElementById('badge_icon');
if (badgeInput) {
  badgeInput.addEventListener('change', function() {
    const el = document.getElementById('badgeFileName');
    if (el && this.files[0]) el.textContent = this.files[0].name;
  });
}
</script>
</body>
</html>