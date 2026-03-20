<?php
session_start();
require_once '../../config/db_connect.php'; 

if (!isset($_GET['id'])) {
    header('Location: admin_gestion.php?msg=missing_id');
    exit;
}

$id = intval($_GET['id']);
if ($id <= 0) {
    header('Location: admin_gestion.php?msg=invalid_id');
    exit;
}

try {
    // Récupère l'état actuel
    $stmt = $pdo->prepare("SELECT est_actif FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        header('Location: admin_gestion.php?msg=not_found');
        exit;
    }

    $current = (int)$row['est_actif'];
    $new = $current ? 0 : 1;

    // Met à jour
    $u = $pdo->prepare("UPDATE users SET est_actif = :val WHERE id = :id");
    $u->execute([':val' => $new, ':id' => $id]);

    $msg = $new ? 'unbanned' : 'banned';
    header('Location: admin_gestion.php?msg=' . $msg);
    exit;

} catch (Exception $e) {
    header('Location: admin_gestion.php?msg=error');
    exit;
}