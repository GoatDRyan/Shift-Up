<?php
session_start();
require_once('../db_connect.php');
header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    echo json_encode(['success'=>false,'error'=>'Payload JSON invalide']); exit;
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo json_encode(['success'=>false,'error'=>'Connexion DB non définie']); exit;
}

$titre_fr      = trim($body['titre_fr'] ?? '');
$titre_en      = trim($body['titre_en'] ?? $titre_fr);
$descr_fr      = trim($body['descr_fr'] ?? '');
$descr_en      = trim($body['descr_en'] ?? '');
$difficulty    = trim($body['difficulty'] ?? 'facile');
$xp_gain       = (int)($body['xp_gain'] ?? 0);
$co2_kg        = (float)($body['score'] ?? 0.0);
$duration_days = (int)($body['duration_days'] ?? 1);
$domaine       = trim($body['domaine'] ?? '');
$categorie     = trim($body['categorie'] ?? '');
$max_actions_day = isset($body['max_actions_day']) ? (int)$body['max_actions_day'] : 1;
$company_id    = isset($body['company_id']) && $body['company_id'] !== '' ? (int)$body['company_id'] : null;
$created_by    = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if ($titre_fr === '') {
    echo json_encode(['success'=>false,'error'=>'Titre (FR) requis']); exit;
}

try {
    $sql = "INSERT INTO challenges 
      (titre_fr, titre_en, descr_fr, descr_en, xp_gain, co2_kg, difficulty, domaine, categorie, duration_days, max_actions_day, company_id, created_by)
      VALUES
      (:titre_fr, :titre_en, :descr_fr, :descr_en, :xp_gain, :co2_kg, :difficulty, :domaine, :categorie, :duration_days, :max_actions_day, :company_id, :created_by)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':titre_fr', $titre_fr, PDO::PARAM_STR);
    $stmt->bindValue(':titre_en', $titre_en ?: $titre_fr, PDO::PARAM_STR);
    $stmt->bindValue(':descr_fr', $descr_fr, PDO::PARAM_STR);
    $stmt->bindValue(':descr_en', $descr_en, PDO::PARAM_STR);
    $stmt->bindValue(':xp_gain', $xp_gain, PDO::PARAM_INT);
    $stmt->bindValue(':co2_kg', $co2_kg);
    $stmt->bindValue(':difficulty', $difficulty, PDO::PARAM_STR);
    $stmt->bindValue(':domaine', $domaine, PDO::PARAM_STR);
    $stmt->bindValue(':categorie', $categorie, PDO::PARAM_STR);
    $stmt->bindValue(':duration_days', $duration_days, PDO::PARAM_INT);
    $stmt->bindValue(':max_actions_day', $max_actions_day, PDO::PARAM_INT);
    if ($company_id === null) $stmt->bindValue(':company_id', null, PDO::PARAM_NULL);
    else $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    if ($created_by === null) $stmt->bindValue(':created_by', null, PDO::PARAM_NULL);
    else $stmt->bindValue(':created_by', $created_by, PDO::PARAM_INT);

    $stmt->execute();
    echo json_encode(['success'=>true,'insert_id'=>$pdo->lastInsertId()]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    exit;
}
?>