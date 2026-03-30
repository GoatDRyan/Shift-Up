<?php
require_once '../../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

$companyId = (int)$user['company_id'];

$body = json_decode(file_get_contents('php://input'), true);
$challenge_id = isset($body['challenge_id']) ? (int)$body['challenge_id'] : 0;

if (!$challenge_id) { 
    echo json_encode(['success' => false, 'error' => 'ID invalide']); 
    exit; 
}

try {
    $stmtCheck = $pdo->prepare("SELECT COALESCE(is_active, 1) as is_active FROM challenges WHERE id = :cid AND company_id = :company_id LIMIT 1");
    $stmtCheck->execute([
        ':cid' => $challenge_id,
        ':company_id' => $companyId
    ]);
    
    $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Défi introuvable ou non autorisé.']);
        exit;
    }

    $currentStatus = (int)$row['is_active'];
    $nextStatus = $currentStatus ? 0 : 1;

    $stmtUpdate = $pdo->prepare("UPDATE challenges SET is_active = :status WHERE id = :cid AND company_id = :company_id");
    $stmtUpdate->execute([
        ':status' => $nextStatus,
        ':cid' => $challenge_id,
        ':company_id' => $companyId
    ]);

    echo json_encode([
        'success' => true, 
        'action' => $nextStatus ? 'enabled' : 'disabled'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur serveur lors de la mise à jour.']);
}
?>