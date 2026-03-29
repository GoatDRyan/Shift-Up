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

// Sécurité : si l'utilisateur arrive ici sans avoir fait l'étape 1
if (!isset($_SESSION['registration']['code_entreprise'])) {
    header("Location: register.php");
    exit();
}

$error = null;
// ATTENTION : 'email' au lieu de 'mail' pour correspondre à login.php et la BDD
$expected_fields = ['pseudo', 'email', 'password', 'password_confirm'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_valid = true;
    $data = [];

    foreach ($expected_fields as $field) {
        if (isset($_POST[$field]) && !empty(trim($_POST[$field]))) {
            $data[$field] = trim($_POST[$field]);
        } else {
            $is_valid = false;
        }
    }

    if ($is_valid) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = $t['err_invalid_email'] ?? "Le format de l'adresse email est invalide.";
        } elseif ($data['password'] !== $data['password_confirm']) {
            $error = $t['err_passwords_match'] ?? "Les mots de passe ne correspondent pas.";
        } elseif (strlen($data['password']) < 8) {
            $error = $t['err_password_length'] ?? "Le mot de passe doit faire au moins 8 caractères.";
        } elseif (!isset($_POST['cgu'])) {
            $error = $t['err_cgu'] ?? "Vous devez accepter les CGU.";
        } else {
            // CORRECTION : Passage en PDO et vérification des doublons
            $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR pseudo = ?");
            $stmt_check->execute([$data['email'], $data['pseudo']]);
            
            if ($stmt_check->fetch()) {
                $error = $t['err_user_exists'] ?? "Un utilisateur avec ce pseudo ou cet email existe déjà.";
            } else {
                // Tout est bon : on insère !
                $hash = password_hash($data['password'], PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO users (prenom, nom, company_id, department_id, pseudo, email, password_hash, language_pref) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                $success = $stmt->execute([
                    $_SESSION['registration']['prenom'], 
                    $_SESSION['registration']['nom'], 
                    $_SESSION['registration']['code_entreprise'], // L'ID récupéré à l'étape 1
                    $_SESSION['registration']['departement'],     // L'ID récupéré à l'étape 1
                    $data['pseudo'],
                    $data['email'],
                    $hash,
                    $lang
                ]);

                if ($success) {
                    // Nettoyage de la session et redirection vers la connexion
                    unset($_SESSION['registration']);
                    header("Location: login.php?registered=1");
                    exit();
                } else {
                    $error = "Erreur serveur lors de la création du compte.";
                }
            }
        }
    } else {
        $error = $t['err_empty_fields'] ?? "Veuillez remplir tous les champs obligatoires.";
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../../js/tailwind-config.js"></script>
    <title><?= $t['reg_step2_title'] ?? "Inscription - Étape 2" ?></title>
</head>
<body class="bg-brand-primary text-brand-secondary font-sans min-h-screen flex items-center justify-center p-4 relative">

    <div class="absolute top-6 right-6 flex items-center gap-2 z-50">
        <a href="?lang=fr" class="text-xs font-bold uppercase transition <?= $lang === 'fr' ? 'text-brand-secondary opacity-100' : 'text-brand-secondary opacity-50 hover:opacity-100' ?>">FR</a>
        <span class="text-brand-secondary opacity-30 text-[10px]">|</span>
        <a href="?lang=en" class="text-xs font-bold uppercase transition <?= $lang === 'en' ? 'text-brand-secondary opacity-100' : 'text-brand-secondary opacity-50 hover:opacity-100' ?>">EN</a>
    </div>

    <div class="w-full max-w-md">
        <div class="text-center flex flex-col items-center justify-center mb-8">
            <div class="w-48 h-2 bg-brand-secondary/20 rounded-full overflow-hidden mx-auto mb-6">
                <div class="w-full h-full bg-brand-secondary rounded-full"></div>
            </div>
            
            <h2 class="text-4xl font-display font-black tracking-tight text-brand-secondary mb-2">
                <?= $t['register'] ?? "Inscription" ?>
            </h2>
            <p class="text-brand-secondary opacity-80 text-sm font-bold"><?= $t['reg_step2_desc'] ?? "Sécurisons votre compte." ?></p>
        </div>

        <?php if ($error): ?>
            <div class="bg-brand-secondary text-brand-primary font-bold text-sm p-4 rounded-xl mb-8 text-center flex items-center justify-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
            
        <form action="" method="POST" class="space-y-6">
            
            <div>
                <label class="block text-[10px] font-bold text-brand-secondary uppercase tracking-widest mb-2 pl-1">Pseudo *</label>
                <div class="relative flex items-center">
                    <div class="absolute left-4 pointer-events-none text-brand-secondary opacity-60"><i class="fa-solid fa-user"></i></div>
                    <input type="text" name="pseudo" value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>" required
                        class="w-full bg-transparent border-2 border-brand-secondary/30 rounded-xl py-4 pl-11 pr-4 text-brand-secondary focus:outline-none focus:border-brand-secondary transition font-medium placeholder-brand-secondary/40">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-brand-secondary uppercase tracking-widest mb-2 pl-1"><?= $t['email_label'] ?? "Email Professionnel" ?> *</label>
                <div class="relative flex items-center">
                    <div class="absolute left-4 pointer-events-none text-brand-secondary opacity-60"><i class="fa-regular fa-envelope"></i></div>
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required
                        class="w-full bg-transparent border-2 border-brand-secondary/30 rounded-xl py-4 pl-11 pr-4 text-brand-secondary focus:outline-none focus:border-brand-secondary transition font-medium placeholder-brand-secondary/40">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-brand-secondary uppercase tracking-widest mb-2 pl-1"><?= $t['password_label'] ?? "Mot de passe" ?> *</label>
                    <input type="password" name="password" required
                        class="w-full bg-transparent border-2 border-brand-secondary/30 rounded-xl py-3 px-4 text-brand-secondary focus:outline-none focus:border-brand-secondary transition font-medium placeholder-brand-secondary/40">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-brand-secondary uppercase tracking-widest mb-2 pl-1 text-center truncate"><?= $t['confirm_password_label'] ?? "Confirmer" ?> *</label>
                    <input type="password" name="password_confirm" required
                        class="w-full bg-transparent border-2 border-brand-secondary/30 rounded-xl py-3 px-4 text-brand-secondary focus:outline-none focus:border-brand-secondary transition font-medium placeholder-brand-secondary/40">
                </div>
            </div>

            <div class="flex items-start gap-3 pt-2">
                <div class="flex items-center h-5">
                    <input type="checkbox" id="cgu" name="cgu" required 
                        class="w-4 h-4 border-2 border-brand-secondary/30 rounded bg-transparent appearance-none checked:bg-brand-secondary checked:border-brand-secondary cursor-pointer transition relative flex items-center justify-center
                        after:content-[''] after:absolute after:hidden checked:after:block after:w-1.5 after:h-2.5 after:border-r-2 after:border-b-2 after:border-brand-primary after:rotate-45 after:-translate-y-0.5">
                </div>
                <label for="cgu" class="text-xs text-brand-secondary opacity-80 cursor-pointer font-medium">
                    <?= $t['accept_cgu'] ?? "J'accepte les conditions générales d'utilisation" ?> *
                </label>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-brand-secondary text-brand-primary text-sm font-bold py-4 rounded-xl hover:opacity-90 transition active:scale-95 flex justify-center items-center gap-2">
                    <?= $t['btn_finish_register'] ?? "Finaliser l'inscription" ?> <i class="fa-solid fa-check"></i>
                </button>
            </div>
            
            <div class="text-center mt-4">
                <a href="register.php" class="text-[10px] font-bold text-brand-secondary opacity-80 hover:opacity-100 transition"><?= $t['btn_back'] ?? "Retour à l'étape précédente" ?></a>
            </div>
        </form>
    </div>
</body>
</html>