<?php
session_start();
require_once '../../config/db_connect.php';

// Gestion de la langue
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'fr';
$t_path = __DIR__ . "/../../lang/$lang.php";
$t = file_exists($t_path) ? require $t_path : [];

$message = null;
$error = null;
$reset_link = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        // Vérifier si l'email existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));

            // CORRECTION DU FUSEAU HORAIRE ICI : On utilise DATE_ADD de MySQL
            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            $update->execute([$token, $user['id']]);

            $reset_link = "reset_password.php?token=" . $token;
            
            $message = $t['success_link_sent'] ?? "Un lien de réinitialisation a été généré.";
        } else {
            // Par sécurité, on dit la même chose même si l'email n'existe pas
            $message = $t['success_link_sent'] ?? "Si cet email existe, un lien a été généré.";
        }
    } else {
        $error = $t['err_empty_fields'] ?? "Veuillez entrer votre adresse email.";
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['forgot_page_title'] ?? "Mot de passe oublié - Shift'Up" ?></title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../../js/tailwind-config.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-brand-primary text-brand-secondary font-sans min-h-screen flex items-center justify-center p-4 relative">

    <div class="absolute top-6 right-6 flex items-center gap-2 z-50">
        <a href="?lang=fr" class="text-xs font-bold uppercase transition <?= $lang === 'fr' ? 'text-brand-secondary opacity-100' : 'text-brand-secondary opacity-50 hover:opacity-100' ?>">FR</a>
        <span class="text-brand-secondary opacity-30 text-[10px]">|</span>
        <a href="?lang=en" class="text-xs font-bold uppercase transition <?= $lang === 'en' ? 'text-brand-secondary opacity-100' : 'text-brand-secondary opacity-50 hover:opacity-100' ?>">EN</a>
    </div>

    <div class="w-full max-w-md">
        
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-brand-secondary text-brand-primary rounded-2xl flex items-center justify-center mx-auto mb-6 text-3xl">
                <i class="fa-solid fa-key"></i>
            </div>
            <h1 class="font-display text-4xl font-black tracking-tight text-brand-secondary mb-2"><?= $t['forgot_title'] ?? "Oups..." ?></h1>
            <p class="text-brand-secondary opacity-80 text-sm font-bold"><?= $t['forgot_subtitle'] ?? "Entrez votre email pour réinitialiser votre mot de passe." ?></p>
        </div>

        <?php if ($error): ?>
            <div class="bg-brand-secondary text-brand-primary font-bold text-sm p-4 rounded-xl mb-8 text-center flex items-center justify-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($message && !$reset_link): ?>
            <div class="bg-brand-secondary text-brand-primary font-bold text-sm p-4 rounded-xl mb-8 text-center flex items-center justify-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($reset_link): ?>
            <div class="bg-green-500/20 border-2 border-green-500 text-green-700 font-bold text-sm p-4 rounded-xl mb-8 text-center flex flex-col gap-2">
                <span><i class="fa-solid fa-laptop-code"></i> Mode test local activé :</span>
                <a href="<?= $reset_link ?>" class="underline break-all">Cliquez ici pour changer le mot de passe</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div>
                <label class="block text-[10px] font-bold text-brand-secondary uppercase tracking-widest mb-2 pl-1"><?= $t['email_label'] ?? "Email Professionnel" ?></label>
                <div class="relative flex items-center">
                    <div class="absolute left-4 pointer-events-none text-brand-secondary opacity-60">
                        <i class="fa-regular fa-envelope"></i>
                    </div>
                    <input type="email" name="email" required 
                        class="w-full bg-transparent border-2 border-brand-secondary/30 rounded-xl py-4 pl-11 pr-4 text-brand-secondary focus:outline-none focus:border-brand-secondary transition font-medium placeholder-brand-secondary/40"
                        placeholder="<?= $t['email_placeholder'] ?? "nom@entreprise.com" ?>">
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" 
                    class="w-full bg-brand-secondary text-brand-primary text-sm font-bold py-4 rounded-xl hover:opacity-90 transition active:scale-95 mb-6 flex justify-center items-center gap-2">
                    <?= $t['btn_generate_link'] ?? "Générer le lien" ?> <i class="fa-solid fa-paper-plane"></i>
                </button>

                <a href="login.php" 
                    class="w-full block text-center bg-transparent border-2 border-brand-secondary text-brand-secondary text-sm font-bold py-4 rounded-xl hover:bg-brand-secondary hover:text-brand-primary transition active:scale-95">
                    <?= $t['btn_back_login'] ?? "Retour à la connexion" ?>
                </a>
            </div>
        </form>
        
    </div>

</body>
</html>