<?php
session_start();

// Connexion BDD
require_once __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Les fonctions
if (file_exists(__DIR__ . '/functions.php')) { require_once __DIR__ . '/functions.php'; } 
elseif (file_exists(__DIR__ . '/../functions.php')) { require_once __DIR__ . '/../functions.php'; }

// Langue
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $new = $_GET['lang'];
    $pdo->prepare("UPDATE users SET language_pref = ? WHERE id = ?")->execute([$new, $user_id]);
    $_SESSION['lang'] = $new;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) { 
    header("Location: logout.php"); 
    exit(); 
}

// Chargement du dictionnaire
$lang = $_SESSION['lang'] ?? ($user['language_pref'] ?? 'fr');
$t = require_once __DIR__ . "/../lang/$lang.php";

// Variables globales utiles
$pseudo = $user['pseudo'] ?? "Joueur";
$money = $user['points_wallet'] ?? 0;
$current_page = basename($_SERVER['PHP_SELF']);
?>