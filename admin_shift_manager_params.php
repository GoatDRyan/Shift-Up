<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

$challenge_id = isset($_GET['challenge_id']) ? (int)$_GET['challenge_id'] : 0;
if (!$challenge_id) { echo json_encode(['success'=>false,'error'=>'ID manquant']); exit; }
if (!isset($pdo) || !($pdo instanceof PDO)) { echo json_encode(['success'=>false,'error'=>'Connexion DB non définie (db_connect.php)']); exit; }

try {
    $stmt = $pdo->prepare("SELECT titre_fr, xp_gain FROM challenges WHERE id = :cid");
    $stmt->execute([':cid'=>$challenge_id]);
    $row = $stmt->fetch();
    if (!$row) { echo json_encode(['success'=>false,'error'=>'Tâche introuvable']); exit; }

    $stmt2 = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as users_count FROM user_actions WHERE challenge_id = :cid");
    $stmt2->execute([':cid'=>$challenge_id]);
    $res2 = $stmt2->fetch();
    $uc = (int)($res2['users_count'] ?? 0);

    echo json_encode(['success'=>true, 'titre'=> $row['titre_fr'], 'xp'=> (int)$row['xp_gain'], 'users_count'=>$uc]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}