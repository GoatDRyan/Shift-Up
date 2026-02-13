<?php
session_start();
require_once 'db_connect.php';

$error = null;
$expected_fields = ['pseudo', 'mail', 'password', 'password_confirm'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_valid = true;
    $data = [];

    // 1. BOUCLE DE VALIDATION : On vérifie si tout est rempli
    foreach ($expected_fields as $field) {
        if (isset($_POST[$field]) && !empty(trim($_POST[$field]))) {
            $data[$field] = trim($_POST[$field]);
        } else {
            $is_valid = false;
        }
    }

    // 2. VÉRIFICATIONS SPÉCIFIQUES
    if ($is_valid) {
        if (!filter_var($data['mail'], FILTER_VALIDATE_EMAIL)) {
            $error = "Le format de l'adresse email est invalide.";
        } elseif ($data['password'] !== $data['password_confirm']) {
            $error = "Les mots de passe ne correspondent pas.";
        } elseif (strlen($data['password']) < 8) {
            $error = "Le mot de passe doit faire au moins 8 caractères.";
        } elseif (!isset($_POST['cgu'])) {
            $error = "Vous devez accepter les CGU.";
        } else {
            // Tout est bon : on fusionne avec les infos de l'étape 1
            $_SESSION['registration'] = array_merge($_SESSION['registration'] ?? [], [
                'pseudo' => $data['pseudo'],
                'mail' => $data['mail'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT) // On crypte ici !
            ]);

            $sql_check = "SELECT id FROM users WHERE mail = ? OR pseudo = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("ss", $_SESSION['registration']['mail'], $_SESSION['registration']['pseudo']);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($result_check->num_rows > 0) {
                $error = "Un utilisateur avec ce pseudo ou cet email existe deja.";
            } else {
                $stmt_check->close();
            }

            // 3. INSERTION EN BDD
            $sql = "INSERT INTO users (prenom, nom, company_id, departement_id, pseudo, mail, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", 
                $_SESSION['registration']['prenom'], 
                $_SESSION['registration']['nom'], 
                $company['id'], 
                $dep['id'],
                $_SESSION['registration']['pseudo'],
                $_SESSION['registration']['mail'],
                $_SESSION['registration']['password']
            );
            $stmt->execute();
            // On redirige vers la page de confirmation
            header("Location: index.php");
            exit();
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'tailwindcss.html'; ?>
    <title>Inscription - Shift'Up</title>
</head>
<body class="bg-black text-white font-sans h-screen flex flex-col items-center justify-center px-4">
    <div class="text-center flex flex-col items-center justify-center mb-8">
        <div class="w-60 h-3 bg-white rounded-full flex items-center mx-auto  mt-4 overflow-hidden">
            <div class="w-40 h-full bg-green-500 rounded-full"></div>
        </div>
        <h2 class="mt-6 text-3xl font-bold tracking-tight text-white">
            Inscription
        </h2>
        <?php if ($error): ?>
            <div class="w-[90%] mt-4 p-3 bg-red-900/30 border border-red-500 text-red-500 text-sm rounded-md">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
    </div>
        
    <form action="register_process.php" method="POST" class="w-[75%] max-w-md">
        <div class="space-y-4">
            <div>
                <label for="pseudo" class="block text-sm font-medium text-gray-400">Pseudo *</label>
                <input type="text" id="pseudo" name="pseudo" value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>" required 
                       class="mt-1 block w-full bg-gray-900 border border-gray-700 rounded-md py-2 px-3 text-white focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            
            <div>
                <label for="mail" class="block text-sm font-medium text-gray-400">Email *</label>
                <input type="email" id="mail" name="mail" value="<?= htmlspecialchars($_POST['mail'] ?? '') ?>" required 
                       class="mt-1 block w-full bg-gray-900 border border-gray-700 rounded-md py-2 px-3 text-white focus:ring-2 focus:ring-green-500 outline-none">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-400">Mot de passe *</label>
                <input type="password" id="password" name="password" required 
                       class="mt-1 block w-full bg-gray-900 border border-gray-700 rounded-md py-2 px-3 text-white focus:ring-2 focus:ring-green-500 outline-none">
            </div>

            <div>
                <label for="password_confirm" class="block text-sm font-medium text-gray-400">Confirmer le mot de passe *</label>
                <input type="password" id="password_confirm" name="password_confirm" required 
                       class="mt-1 block w-full bg-gray-900 border border-gray-700 rounded-md py-2 px-3 text-white focus:ring-2 focus:ring-green-500 outline-none">
            </div>

            <div class="flex space-x-2 pt-2">
                <input type="checkbox" id="cgu" name="cgu" required class="h-4 w-4 text-green-600 bg-gray-900 border-gray-700 rounded focus:ring-green-500">
                <label for="cgu" class="text-xs text-gray-400">J'accepte les conditions générales d'utilisation *</label>
            </div>
        </div>
        <button type="submit" class="mt-8 w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-full transition duration-300 shadow-lg shadow-green-900/20 uppercase tracking-wider">
            Finaliser l'inscription
        </button>
    </form>
</body>
</html>