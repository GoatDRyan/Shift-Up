<?php
require_once '../../includes/init.php'; 

$companyId = (int)$user['company_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_gestion.php?msg=invalid_method');
    exit;
}

$targetId = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($targetId <= 0) {
    header('Location: admin_gestion.php?msg=invalid_id');
    exit;
}

if ($targetId === (int)$user['id']) {
    header('Location: admin_gestion.php?msg=cannot_delete_self');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND company_id = :company_id");
    $stmt->execute([
        ':id' => $targetId,
        ':company_id' => $companyId
    ]);

    if ($stmt->rowCount() === 0) {
        header('Location: admin_gestion.php?msg=not_found_or_unauthorized');
        exit;
    }

    header('Location: admin_gestion.php?msg=deleted');
    exit;

} catch (Exception $e) {
    header('Location: admin_gestion.php?msg=error');
    exit;
}
?>