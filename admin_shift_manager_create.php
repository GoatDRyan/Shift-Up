<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    echo json_encode(['success'=>false,'error'=>'Données manquantes']); exit;
}

$titre_fr = $body['titre_fr'] ?? '';
$difficulty = $body['difficulty'] ?? 'facile';
$xp_gain = (int)($body['xp_gain'] ?? 10);
$co2_kg = (float)($body['score'] ?? 0.0);
$duration_days = (int)($body['duration_days'] ?? 1);
$domaine = $body['domaine'] ?? 'ecologique';
$categorie = $body['categorie'] ?? 'Général';

if (!$titre_fr) {
    echo json_encode(['success'=>false,'error'=>'Titre requis']); exit;
}

if (!isset($conn)) {
    echo json_encode(['success'=>false,'error'=>'Connexion DB non définie (db_connect.php)']); exit;
}

$stmt = $conn->prepare("INSERT INTO challenges (titre_fr, titre_en, descr_fr, descr_en, xp_gain, co2_kg, difficulty, domaine, categorie, duration_days, max_actions_day, company_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NULL)");
if (!$stmt) {
    echo json_encode(['success'=>false,'error'=>$conn->error]); exit;
}
$descr = '';
$titre_en = $titre_fr; $descr_en='';
$stmt->bind_param('ssssddsssii', $titre_fr, $titre_en, $descr, $descr_en, $xp_gain, $co2_kg, $difficulty, $domaine, $categorie, $duration_days);
$ok = $stmt->execute();
if ($ok) {
    echo json_encode(['success'=>true, 'insert_id'=>$stmt->insert_id]);
} else {
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
}
?>