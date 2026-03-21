<?php
session_start();
require_once '../../config/db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || !is_array($body)) {
    echo json_encode(['success'=>false,'error'=>'Données manquantes ou invalides']); exit;
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo json_encode(['success'=>false,'error'=>'Connexion DB non définie (db_connect.php)']); exit;
}

$titre_fr = trim($body['titre_fr'] ?? '');
$titre_en = trim($body['titre_en'] ?? ''); 
$descr_fr = trim($body['descr_fr'] ?? '');
$descr_en = trim($body['descr_en'] ?? '');
$difficulty = trim($body['difficulty'] ?? 'facile');
$xp_gain = (int)($body['xp_gain'] ?? 0);
$co2_kg = (float)($body['score'] ?? 0.0);
$duration_days = (int)($body['duration_days'] ?? 1);
$domaine = trim($body['domaine'] ?? '');
$categorie = trim($body['categorie'] ?? '');

if ($titre_fr === '') {
    echo json_encode(['success'=>false,'error'=>'Titre (FR) requis']); exit;
}

if ($titre_en === '') $titre_en = $titre_fr;

try {
    $sql = "INSERT INTO challenges (titre_fr, titre_en, descr_fr, descr_en, xp_gain, co2_kg, difficulty, domaine, categorie, duration_days, max_actions_day, company_id)
            VALUES (:titre_fr, :titre_en, :descr_fr, :descr_en, :xp_gain, :co2_kg, :difficulty, :domaine, :categorie, :duration_days, 1, NULL)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':titre_fr'=>$titre_fr,
        ':titre_en'=>$titre_en,
        ':descr_fr'=>$descr_fr,
        ':descr_en'=>$descr_en,
        ':xp_gain'=>$xp_gain,
        ':co2_kg'=>$co2_kg,
        ':difficulty'=>$difficulty,
        ':domaine'=>$domaine,
        ':categorie'=>$categorie,
        ':duration_days'=>$duration_days
    ]);
    echo json_encode(['success'=>true,'insert_id'=>$pdo->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
?>