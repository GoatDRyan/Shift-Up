<?php
session_start();
require_once 'db_connect.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_gestion.php?msg=invalid_method');
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    header('Location: admin_gestion.php?msg=invalid_id');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);

    header('Location: admin_gestion.php?msg=deleted');
    exit;
} catch (Exception $e) {
    header('Location: admin_gestion.php?msg=error');
    exit;
}