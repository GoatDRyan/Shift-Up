<?php
session_start();

// 1. Connexion à la base de données
require_once __DIR__ . '/../config/db_connect.php';

// 2. Chargement des fonctions
if (file_exists(__DIR__ . '/functions.php')) { 
    require_once __DIR__ . '/functions.php'; 
} elseif (file_exists(__DIR__ . '/../functions.php')) { 
    require_once __DIR__ . '/../functions.php'; 
}

// 3. Liste des pages publiques
$current_page = basename($_SERVER['PHP_SELF']);
$public_pages = ['login.php', 'register.php', 'forgot_password.php', 'reset_password.php', 'logout.php', '403.php', '404.php', '500.php'];

// 4. Gestion de la langue
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'fr';
$t = require_once __DIR__ . "/../lang/$lang.php";


// ==========================================
// 5. VÉRIFICATION DE L'AUTHENTIFICATION
// ==========================================
if (!isset($_SESSION['user_id'])) {
    if (!in_array($current_page, $public_pages)) {
        header("Location: /Shift-Up/views/users/login.php");
        exit();
    } else {
        return; 
    }
}

// ==========================================
// 6. DONNÉES UTILISATEUR
// ==========================================
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) { 
    header("Location: /Shift-Up/views/users/logout.php"); 
    exit(); 
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $pdo->prepare("UPDATE users SET language_pref = ? WHERE id = ?")->execute([$_GET['lang'], $user_id]);
    $user['language_pref'] = $_GET['lang'];
} elseif (!isset($_SESSION['lang']) && isset($user['language_pref'])) {
    $_SESSION['lang'] = $user['language_pref'];
    $lang = $user['language_pref'];
    $t = require_once __DIR__ . "/../lang/$lang.php";
}

// Calcul du niveau
if (function_exists('get_level_data')) {
    $levelData = get_level_data($user['points_rank']);
    $userLevel       = $levelData['niveau_actuel'];
    $userLevelTitle  = $levelData['titre_actuel'];
    $userXp          = $levelData['xp_actuel'];
    $nextLevelXp     = $levelData['xp_prochain'];
    $xpPercent       = $levelData['pourcentage'];
}

$pseudo = $user['pseudo'] ?? "Joueur";
$money  = $user['points_wallet'] ?? 0;


// ==========================================
// 7. GESTION DES AUTORISATIONS (ERREUR 403)
// ==========================================
$request_uri = $_SERVER['REQUEST_URI'];
$user_role = $user['role'] ?? 'shifter';

// A. Protection de l'espace SUPER ADMIN
if (strpos($request_uri, '/views/superadmin/') !== false && $user_role !== 'super_admin') {
    http_response_code(403);
    require_once __DIR__ . '/../errors/403.php';
    exit();
}

// B. Protection de l'espace ADMIN
if (strpos($request_uri, '/views/admin/') !== false && !in_array($user_role, ['admin', 'super_admin'])) {
    http_response_code(403);
    require_once __DIR__ . '/../errors/403.php';
    exit();
}

// C. Protection des utilisateurs désactivés
if (isset($user['est_actif']) && $user['est_actif'] == 0 && strpos($request_uri, 'logout.php') === false) {
    http_response_code(403);
    require_once __DIR__ . '/../errors/403.php';
    exit();
}
?>