<?php
require_once '../../includes/init.php'; 

$companyId = (int)$user['company_id'];

if (!isset($_GET['id'])) {
    header('Location: admin_gestion.php?msg=missing_id');
    exit;
}

$targetId = intval($_GET['id']);

if ($targetId <= 0) {
    header('Location: admin_gestion.php?msg=invalid_id');
    exit;
}

if ($targetId === (int)$user['id']) {
    header('Location: admin_gestion.php?msg=cannot_ban_self');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT est_actif FROM users WHERE id = :id AND company_id = :company_id LIMIT 1");
    $stmt->execute([
        ':id' => $targetId,
        ':company_id' => $companyId
    ]);
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        header('Location: admin_gestion.php?msg=not_found_or_unauthorized');
        exit;
    }

    $current = (int)$row['est_actif'];
    $new = $current ? 0 : 1;

    // Met à jour le statut
    $u = $pdo->prepare("UPDATE users SET est_actif = :val WHERE id = :id AND company_id = :company_id");
    $u->execute([
        ':val' => $new, 
        ':id' => $targetId,
        ':company_id' => $companyId
    ]);

    $msg = $new ? 'unbanned' : 'banned';
    header('Location: admin_gestion.php?msg=' . $msg);
    exit;

} catch (Exception $e) {
    header('Location: admin_gestion.php?msg=error');
    exit;
}
?>