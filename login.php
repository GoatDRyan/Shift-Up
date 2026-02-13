<?php
session_start();
require_once 'db_connect.php';

$error = null;

// TRAITEMENT DU FORMULAIRE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        // 1. On cherche l'utilisateur par son email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // 2. Vérification du mot de passe
        // Note: password_verify compare le mot de passe clair avec le hash de la BDD
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // 3. Connexion réussie : On remplit la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['pseudo'] = $user['pseudo'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['department_id'] = $user['department_id'];
            
            // Gestion de la langue
            $_SESSION['lang'] = $user['language_pref'] ?? 'fr';

            // 4. Redirection selon le rôle
            if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
                header("Location: admin_dashboard.php"); // Page Admin (à créer)
            } else {
                header("Location: index.php"); // Page Joueur
            }
            exit();

        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Shift'Up</title>
    <?php include 'tailwindcss.html'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-black text-white font-sans h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-md bg-gray-900 border border-gray-800 rounded-2xl p-8 shadow-2xl shadow-white/5">
        
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-white text-black rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                Logo
            </div>
            <h1 class="text-2xl font-bold tracking-tight">Bienvenue sur Shift'Up</h1>
            <p class="text-gray-400 text-sm mt-2">Connectez-vous pour réduire votre empreinte.</p>
            
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-500 text-sm p-3 rounded-lg mb-6 text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Email Professionnel</label>
                <input type="email" name="email" required 
                    class="w-full bg-black border border-gray-700 rounded-lg p-3 text-white focus:outline-none focus:border-white transition placeholder-gray-600"
                    placeholder="nom@entreprise.com">
            </div>

            <div class="mb-10">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Mot de passe</label>
                <div class="relative">
                    <input type="password" name="password" required id="passwordInput"
                        class="w-full bg-black border border-gray-700 rounded-lg p-3 text-white focus:outline-none focus:border-white transition placeholder-gray-600"
                        placeholder="••••••••">
                    <button type="button" onclick="togglePassword()" class="absolute right-3 top-3 text-gray-500 hover:text-white">
                        <i class="fa-regular fa-eye" id="eyeIcon"></i>
                    </button>
                    <div class="mt-6 text-center">
                        <a href="#" class="text-[10px] text-gray-500 absolute right-1 top-12 hover:text-white underline">Mot de passe oublié ?</a>
                    </div>
                </div>
            </div>

            <button type="submit" 
                class="w-full mb-6 bg-white text-black text-sm font-bold py-2 rounded-full hover:bg-gray-200 transition transform active:scale-95 shadow-lg shadow-white/10">
                Se connecter
            </button>
        </form>
            <a href="register.php">
            <button class="w-full mb-6 bg-white text-black text-sm font-bold py-2 rounded-full hover:bg-gray-200 transition transform active:scale-95 shadow-lg shadow-white/10">S'inscrire</button>
            </a>
        
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