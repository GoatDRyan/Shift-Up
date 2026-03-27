<?php
session_start();
require_once '../../config/db_connect.php';

// 1. Gestion de la langue AVANT la connexion
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'fr';
$t_path = __DIR__ . "/../../lang/$lang.php";
$t = file_exists($t_path) ? require $t_path : [];

$error = null;

// TRAITEMENT DU FORMULAIRE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['pseudo'] = $user['pseudo'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['department_id'] = $user['department_id'];

            $_SESSION['lang'] = $user['language_pref'] ?? $lang;

            if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
                header("Location: admin/admin_dashboard.php"); 
            } else {
                header("Location: ../../views/users/index.php"); 
            }
            exit();

        } else {
            $error = $t['err_invalid_credentials'] ?? "Email ou mot de passe incorrect.";
        }
    } else {
        $error = $t['err_empty_fields'] ?? "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['login_page_title'] ?? "Connexion - Shift'Up" ?></title>
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
        <a href="?lang=fr" class="text-xs font-bold uppercase transition <?= $lang === 'fr' ? 'text-brand-secondary opacity-100' : 'text-brand-secondary opacity-50 hover:opacity-100' ?>">FR</a>
        <span class="text-brand-secondary opacity-30 text-[10px]">|</span>
        <a href="?lang=en" class="text-xs font-bold uppercase transition <?= $lang === 'en' ? 'text-brand-secondary opacity-100' : 'text-brand-secondary opacity-50 hover:opacity-100' ?>">EN</a>
    </div>

    <div class="w-full max-w-md">
        
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-brand-secondary text-brand-primary rounded-2xl flex items-center justify-center mx-auto mb-6 text-3xl transform rotate-3">
                <i class="fa-solid fa-leaf -rotate-3"></i>
            </div>
            <h1 class="font-display text-4xl font-black tracking-tight text-brand-secondary mb-2">Shift'Up</h1>
            <p class="text-brand-secondary opacity-80 text-sm font-bold"><?= $t['login_subtitle'] ?? "Connectez-vous pour réduire votre empreinte." ?></p>
        </div>

        <?php if ($error): ?>
            <div class="bg-brand-secondary text-brand-primary font-bold text-sm p-4 rounded-xl mb-8 text-center flex items-center justify-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div>
                <label class="block text-[10px] font-bold text-brand-dark uppercase tracking-widest mb-2 pl-1"><?= $t['email_label'] ?? "Email Professionnel" ?></label>
                <div class="relative flex items-center">
                    <div class="absolute left-4 pointer-events-none text-brand-secondary opacity-60">
                        <i class="fa-regular fa-envelope"></i>
                    </div>
                    <input type="email" name="email" required 
                        class="w-full bg-transparent border-2 border-brand-secondary/30 rounded-xl py-4 pl-11 pr-4 text-brand-secondary focus:outline-none focus:border-brand-secondary transition font-medium placeholder-brand-secondary/40"
                        placeholder="<?= $t['email_placeholder'] ?? "nom@entreprise.com" ?>">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-brand-dark uppercase tracking-widest mb-2 pl-1"><?= $t['password_label'] ?? "Mot de passe" ?></label>
                <div class="relative flex items-center">
                    <div class="absolute left-4 pointer-events-none text-brand-secondary opacity-60">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <input type="password" name="password" required id="passwordInput"
                        class="w-full bg-transparent border-2 border-brand-secondary/30 rounded-xl py-4 pl-11 pr-12 text-brand-secondary focus:outline-none focus:border-brand-secondary transition font-medium placeholder-brand-secondary/40"
                        placeholder="••••••••">
                    <button type="button" onclick="togglePassword()" class="absolute right-4 text-brand-secondary opacity-60 hover:opacity-100 transition">
                        <i class="fa-regular fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
                <div class="mt-3 text-right">
                    <a href="forgot_password.php" class="text-[10px] font-bold text-brand-secondary underline opacity-80 hover:opacity-100 transition"><?= $t['forgot_password'] ?? "Mot de passe oublié ?" ?></a>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" 
                    class="w-full bg-brand-secondary text-brand-primary text-sm font-bold py-4 rounded-xl hover:opacity-90 transition active:scale-95 mb-6 flex justify-center items-center gap-2">
                    <?= $t['btn_login'] ?? "Se connecter" ?> <i class="fa-solid fa-arrow-right"></i>
                </button>
                
                <div class="relative flex items-center py-2 mb-6">
                    <div class="flex-grow h-[1px] bg-brand-secondary opacity-20"></div>
                    <span class="flex-shrink-0 mx-4 text-brand-secondary opacity-60 text-xs font-bold uppercase"><?= $t['or'] ?? "Ou" ?></span>
                    <div class="flex-grow h-[1px] bg-brand-secondary opacity-20"></div>
                </div>

                <a href="register.php" 
                    class="w-full block text-center bg-transparent border-2 border-brand-secondary text-brand-secondary text-sm font-bold py-4 rounded-xl hover:bg-brand-secondary hover:text-brand-primary transition active:scale-95">
                    <?= $t['btn_register'] ?? "Créer un compte" ?>
                </a>
            </div>
        </form>
        
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('eyeIcon');
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