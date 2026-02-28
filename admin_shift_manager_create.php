<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    echo json_encode(['success'=>false,'error'=>'Données manquantes']); exit;
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo json_encode(['success'=>false,'error'=>'Connexion DB non définie (db_connect.php)']); exit;
}

$titre_fr = trim($body['titre_fr'] ?? '');
$difficulty = trim($body['difficulty'] ?? 'facile');
$xp_gain = (int)($body['xp_gain'] ?? 0);
$co2_kg = (float)($body['score'] ?? 0);
$duration_days = (int)($body['duration_days'] ?? 1);
$domaine = trim($body['domaine'] ?? '');
$categorie = trim($body['categorie'] ?? '');

if ($titre_fr === '') {
    echo json_encode(['success'=>false,'error'=>'Titre requis']); exit;
}

try {
    // Insert minimal required columns; adapte si ta table a colonnes différentes
    $sql = "INSERT INTO challenges (titre_fr, titre_en, descr_fr, descr_en, xp_gain, co2_kg, difficulty, domaine, categorie, duration_days, max_actions_day, company_id)
            VALUES (:titre_fr, :titre_en, :descr_fr, :descr_en, :xp_gain, :co2_kg, :difficulty, :domaine, :categorie, :duration_days, 1, NULL)";
    $stmt = $pdo->prepare($sql);
    $titre_en = $titre_fr;
    $descr_fr = '';
    $descr_en = '';
    $stmt->execute([
        ':titre_fr' => $titre_fr,
        ':titre_en' => $titre_en,
        ':descr_fr' => $descr_fr,
        ':descr_en' => $descr_en,
        ':xp_gain' => $xp_gain,
        ':co2_kg' => $co2_kg,
        ':difficulty' => $difficulty,
        ':domaine' => $domaine,
        ':categorie' => $categorie,
        ':duration_days' => $duration_days
    ]);
    echo json_encode(['success'=>true, 'insert_id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
?>