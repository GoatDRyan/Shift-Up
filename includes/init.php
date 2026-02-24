<?php
session_start();

// 1. Connexion à la base de données
require_once __DIR__ . '/../db_connect.php';

// 2. Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// 3. Chargement des fonctions
if (file_exists(__DIR__ . '/functions.php')) { 
    require_once __DIR__ . '/functions.php'; 
} elseif (file_exists(__DIR__ . '/../functions.php')) { 
    require_once __DIR__ . '/../functions.php'; 
}

// 4. Récupération des infos utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Sécurité si l'utilisateur a été supprimé entre temps
if (!$user) { 
    header("Location: logout.php"); 
    exit(); 
}

// 5. Gestion de la langue
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $lang_choice = $_GET['lang'];
    $pdo->prepare("UPDATE users SET language_pref = ? WHERE id = ?")->execute([$lang_choice, $user_id]);
    $_SESSION['lang'] = $lang_choice;
    $user['language_pref'] = $lang_choice;
}

$lang = $_SESSION['lang'] ?? ($user['language_pref'] ?? 'fr');
$t = require_once __DIR__ . "/../lang/$lang.php";

// 6. CALCUL DU NIVEAU
$levelData = get_level_data($user['points_rank']);

$userLevel       = $levelData['niveau_actuel'];
$userLevelTitle  = $levelData['titre_actuel'];
$userXp          = $levelData['xp_actuel'];
$nextLevelXp     = $levelData['xp_prochain'];
$xpPercent       = $levelData['pourcentage'];

$pseudo = $user['pseudo'] ?? "Joueur";
$money  = $user['points_wallet'] ?? 0;
$current_page = basename($_SERVER['PHP_SELF']);
?>