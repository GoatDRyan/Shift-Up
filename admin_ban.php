<?php
session_start();
require_once 'db_connect.php'; 

$currentUserId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

function redirect_back($msg = null) {
    $loc = 'admin_gestion.php';
    if ($msg) $loc .= '?msg=' . urlencode($msg);
    header('Location: ' . $loc);
    exit;
}

if (!$currentUserId) {
    redirect_back('accès_non_autorisé');
}

$targetId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($targetId <= 0) redirect_back('utilisateur_invalide');

try {
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $currentUserId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif (isset($conn)) {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param('i', $currentUserId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
    } else {
        redirect_back('erreur_connexion_db');
    }

    $currentRole = $row['role'] ?? null;
    if (!in_array($currentRole, ['admin', 'super_admin'], true)) {
        redirect_back('droits_insuffisants');
    }

    if ($currentUserId == $targetId) {
        redirect_back('action_interdite_sur_soi_meme');
    }

    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT est_actif, role FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $targetId]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $conn->prepare("SELECT est_actif, role FROM users WHERE id = ?");
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        $res = $stmt->get_result();
        $t = $res->fetch_assoc();
    }

    if (!$t) redirect_back('utilisateur_introuvable');

    if (($t['role'] ?? '') === 'super_admin' && $currentRole !== 'super_admin') {
        redirect_back('impossible_bannir_super_admin');
    }

    $currentActive = (int) ($t['est_actif'] ?? 1);
    $newActive = $currentActive ? 0 : 1;

    if (isset($pdo)) {
        $stmt = $pdo->prepare("UPDATE users SET est_actif = :val WHERE id = :id");
        $stmt->execute([':val' => $newActive, ':id' => $targetId]);
    } else {
        $stmt = $conn->prepare("UPDATE users SET est_actif = ? WHERE id = ?");
        $stmt->bind_param('ii', $newActive, $targetId);
        $stmt->execute();
    }

    $msg = $newActive ? 'utilisateur_retabli' : 'utilisateur_banni';
    redirect_back($msg);

} catch (Exception $e) {
    redirect_back('erreur_operation');
}