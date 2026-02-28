<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
$challenge_id = isset($body['challenge_id']) ? (int)$body['challenge_id'] : 0;
if (!$challenge_id) {
    echo json_encode(['success'=>false,'error'=>'ID invalide']); exit;
}

if (!isset($conn)) {
    echo json_encode(['success'=>false,'error'=>'Connexion DB non définie']); exit;
}

// s'assurer de l'existence de la table
$conn->query("CREATE TABLE IF NOT EXISTS disabled_challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT NOT NULL UNIQUE,
    disabled_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$stmt = $conn->prepare("SELECT id FROM disabled_challenges WHERE challenge_id = ?");
$stmt->bind_param('i', $challenge_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    $del = $conn->prepare("DELETE FROM disabled_challenges WHERE challenge_id = ?");
    $del->bind_param('i', $challenge_id);
    $ok = $del->execute();
    if ($ok) echo json_encode(['success'=>true,'action'=>'enabled']);
    else echo json_encode(['success'=>false,'error'=>$conn->error]);
} else {
    $ins = $conn->prepare("INSERT INTO disabled_challenges (challenge_id) VALUES (?)");
    $ins->bind_param('i', $challenge_id);
    $ok = $ins->execute();
    if ($ok) echo json_encode(['success'=>true,'action'=>'disabled']);
    else echo json_encode(['success'=>false,'error'=>$ins->error]);
}
?>