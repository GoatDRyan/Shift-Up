<?php
session_start();
require_once '../../config/db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true);
$challenge_id = isset($body['challenge_id']) ? (int)$body['challenge_id'] : 0;
if (!$challenge_id) { echo json_encode(['success'=>false,'error'=>'ID invalide']); exit; }

if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo json_encode(['success'=>false,'error'=>'Connexion DB non définie (db_connect.php)']); exit;
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS disabled_challenges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        challenge_id INT NOT NULL UNIQUE,
        disabled_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stmt = $pdo->prepare("SELECT id FROM disabled_challenges WHERE challenge_id = :cid");
    $stmt->execute([':cid'=>$challenge_id]);
    $row = $stmt->fetch();
    if ($row) {
        $del = $pdo->prepare("DELETE FROM disabled_challenges WHERE challenge_id = :cid");
        $del->execute([':cid'=>$challenge_id]);
        echo json_encode(['success'=>true,'action'=>'enabled']);
    } else {
        $ins = $pdo->prepare("INSERT INTO disabled_challenges (challenge_id) VALUES (:cid)");
        $ins->execute([':cid'=>$challenge_id]);
        echo json_encode(['success'=>true,'action'=>'disabled']);
    }
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
?>