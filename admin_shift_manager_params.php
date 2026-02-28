<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

$challenge_id = isset($_GET['challenge_id']) ? (int)$_GET['challenge_id'] : 0;
if (!$challenge_id) { echo json_encode(['success'=>false,'error'=>'ID manquant']); exit; }
if (!isset($conn)) { echo json_encode(['success'=>false,'error'=>'Connexion DB non définie']); exit; }

$stmt = $conn->prepare("SELECT titre_fr, xp_gain FROM challenges WHERE id = ?");
$stmt->bind_param('i', $challenge_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) { echo json_encode(['success'=>false,'error'=>'Tâche introuvable']); exit; }
$row = $res->fetch_assoc();

$stmt2 = $conn->prepare("SELECT COUNT(DISTINCT user_id) as users_count FROM user_actions WHERE challenge_id = ?");
$stmt2->bind_param('i', $challenge_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
$uc = 0;
if ($res2 && $res2->num_rows>0) {
    $uc = (int)$res2->fetch_assoc()['users_count'];
}

echo json_encode(['success'=>true, 'titre'=> $row['titre_fr'], 'xp'=> (int)$row['xp_gain'], 'users_count'=>$uc]);
?>