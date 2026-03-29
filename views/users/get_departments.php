<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

$code = $_GET['code'] ?? '';

if (empty($code)) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM companies WHERE code_invite = ?");
$stmt->execute([$code]);
$company = $stmt->fetch();

if (!$company) {
    echo json_encode([]);
    exit;
}

$stmt_dep = $pdo->prepare("SELECT id, nom FROM departments WHERE company_id = ? ORDER BY nom ASC");
$stmt_dep->execute([$company['id']]);
$departments = $stmt_dep->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($departments);
?>