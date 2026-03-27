<?php
session_start();
require_once '../../config/db_connect.php';

// 1. Gestion de la langue
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'fr';
$t_path = __DIR__ . "/../../lang/$lang.php";
$t = file_exists($t_path) ? require $t_path : [];

$error = null;
$success = null;
$token_valid = false;
$user_id = null;

// 2. Récupération et vérification du Jeton
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if (empty($token)) {
    $error = $t['err_invalid_token'] ?? "Lien de réinitialisation manquant.";
} else {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = :token AND reset_expires_at > NOW()");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    if ($user) {
        $token_valid = true;
        $user_id = $user['id'];
    } else {
        $error = $t['err_invalid_token'] ?? "Ce lien est invalide ou a expiré.";
    }
}

// 3. Traitement du nouveau mot de passe
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        $error = $t['err_empty_fields'] ?? "Veuillez remplir tous les champs.";
    } elseif ($new_password !== $confirm_password) {
        $error = $t['err_passwords_match'] ?? "Les mots de passe ne correspondent pas.";
    } elseif (strlen($new_password) < 8) {
        $error = $t['err_password_length'] ?? "Le mot de passe doit faire au moins 8 caractères.";
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);

        $update = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
        
        if ($update->execute([$hash, $user_id])) {
            $success = $t['success_password_reset'] ?? "Votre mot de passe a été réinitialisé avec succès !";
            $token_valid = false; // On cache le formulaire
        } else {
            $error = "Une erreur serveur est survenue.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['reset_page_title'] ?? "Nouveau mot de passe - Shift'Up" ?></title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../../js/tailwind-config.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-brand-primary text-brand-secondary font-sans min-h-screen flex items-center justify-center p-4 relative">

    <div class="absolute top-6 right-6 flex items-center gap-2 z-50">
        <a href="?lang=fr&token=<?= htmlspecialchars($token) ?>" class="text-xs font-bold uppercase transition <?= $lang === 'fr' ? 'text-brand-secondary opacity-100' : 'text-brand-secondary opacity-50 hover:opacity-100' ?>">FR</a>
        <span class="text-brand-secondary opacity-30 text-[10px]">|</span>
        <a href="?lang=en&token=<?= htmlspecialchars($token) ?>" class="text-xs font-bold uppercase transition <?= $lang === 'en' ? 'text-brand-secondary opacity-100' : 'text-brand-secondary opacity-50 hover:opacity-100' ?>">EN</a>
    </div>

    <div class="w-full max-w-md">
        
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-brand-secondary text-brand-primary rounded-2xl flex items-center justify-center mx-auto mb-6 text-3xl">
                <i class="fa-solid fa-unlock-keyhole"></i>
            </div>
            <h1 class="font-display text-4xl font-black tracking-tight text-brand-secondary mb-2"><?= $t['reset_title'] ?? "Sécurité" ?></h1>
            <p class="text-brand-secondary opacity-80 text-sm font-bold"><?= $t['reset_subtitle'] ?? "Créez votre nouveau mot de passe." ?></p>
        </div>

        <?php if ($error): ?>
            <div class="bg-brand-secondary text-brand-primary font-bold text-sm p-4 rounded-xl mb-8 text-center flex items-center justify-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-brand-secondary text-brand-primary font-bold text-sm p-8 rounded-xl mb-8 text-center flex flex-col items-center gap-4">
                <i class="fa-solid fa-circle-check text-5xl"></i>
                <p class="text-lg"><?= htmlspecialchars($success) ?></p>
                <a href="login.php" class="mt-4 bg-brand-primary text-brand-secondary px-8 py-3 rounded-xl text-sm transition hover:opacity-80 active:scale-95 shadow-lg">
                    <?= $t['btn_back_login'] ?? "Retour à la connexion" ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($token_valid && !$success): ?>
        <form method="POST" action="" class="space-y-6">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div>
                <label class="block text-[10px] font-bold text-brand-secondary uppercase tracking-widest mb-2 pl-1"><?= $t['new_password_label'] ?? "Nouveau mot de passe" ?></label>
                <div class="relative flex items-center">
                    <div class="absolute left-4 pointer-events-none text-brand-secondary opacity-60">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <input type="password" name="new_password" required id="newPasswordInput"
                        class="w-full bg-transparent border-2 border-brand-secondary/30 rounded-xl py-4 pl-11 pr-12 text-brand-secondary focus:outline-none focus:border-brand-secondary transition font-medium placeholder-brand-secondary/40"
                        placeholder="••••••••">
                    <button type="button" onclick="togglePassword('newPasswordInput', 'eyeIcon1')" class="absolute right-4 text-brand-secondary opacity-60 hover:opacity-100 transition">
                        <i class="fa-regular fa-eye" id="eyeIcon1"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-brand-secondary uppercase tracking-widest mb-2 pl-1"><?= $t['confirm_password_label'] ?? "Confirmer le mot de passe" ?></label>
                <div class="relative flex items-center">
                    <div class="absolute left-4 pointer-events-none text-brand-secondary opacity-60">
                        <i class="fa-solid fa-check-double"></i>
                    </div>
                    <input type="password" name="confirm_password" required id="confirmPasswordInput"
                        class="w-full bg-transparent border-2 border-brand-secondary/30 rounded-xl py-4 pl-11 pr-12 text-brand-secondary focus:outline-none focus:border-brand-secondary transition font-medium placeholder-brand-secondary/40"
                        placeholder="••••••••">
                    <button type="button" onclick="togglePassword('confirmPasswordInput', 'eyeIcon2')" class="absolute right-4 text-brand-secondary opacity-60 hover:opacity-100 transition">
                        <i class="fa-regular fa-eye" id="eyeIcon2"></i>
                    </button>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" 
                    class="w-full bg-brand-secondary text-brand-primary text-sm font-bold py-4 rounded-xl hover:opacity-90 transition active:scale-95 mb-6 flex justify-center items-center gap-2">
                    <?= $t['btn_save_password'] ?? "Enregistrer" ?> <i class="fa-solid fa-check"></i>
                </button>
            </div>
        </form>
        <?php endif; ?>

        <?php if (!$token_valid && !$success): ?>
            <div class="pt-4">
                <a href="forgot_password.php" class="w-full block text-center bg-transparent border-2 border-brand-secondary text-brand-secondary text-sm font-bold py-4 rounded-xl hover:bg-brand-secondary hover:text-brand-primary transition active:scale-95">
                    <?= $t['btn_try_again'] ?? "Générer un nouveau lien" ?>
                </a>
            </div>
        <?php endif; ?>
        
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>