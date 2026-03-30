<?php
require_once '../../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

$companyId = (int)$user['company_id'];

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || !is_array($body)) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes ou invalides.']); 
    exit;
}

$titre_fr      = trim($body['titre_fr'] ?? '');
$titre_en      = trim($body['titre_en'] ?? ''); 
$descr_fr      = trim($body['descr_fr'] ?? '');
$descr_en      = trim($body['descr_en'] ?? '');
$difficulty    = trim($body['difficulty'] ?? 'facile');
$xp_gain       = (int)($body['xp_gain'] ?? 0);
$co2_kg        = (float)($body['score'] ?? 0.0);
$duration_days = (int)($body['duration_days'] ?? 1);
$domaine       = trim($body['domaine'] ?? 'ecologique');
$categorie     = trim($body['categorie'] ?? 'Général');

if ($titre_fr === '') {
    echo json_encode(['success' => false, 'error' => 'Le titre (FR) est requis.']); 
    exit;
}

if ($titre_en === '') $titre_en = $titre_fr;
if ($descr_fr === '') $descr_fr = 'Description';
if ($descr_en === '') $descr_en = $descr_fr;

try {
    $sql = "INSERT INTO challenges 
            (titre_fr, titre_en, descr_fr, descr_en, xp_gain, co2_kg, difficulty, domaine, categorie, duration_days, max_actions_day, company_id, is_active)
            VALUES 
            (:titre_fr, :titre_en, :descr_fr, :descr_en, :xp_gain, :co2_kg, :difficulty, :domaine, :categorie, :duration_days, 1, :company_id, 1)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':titre_fr'      => $titre_fr,
        ':titre_en'      => $titre_en,
        ':descr_fr'      => $descr_fr,
        ':descr_en'      => $descr_en,
        ':xp_gain'       => $xp_gain,
        ':co2_kg'        => $co2_kg,
        ':difficulty'    => $difficulty,
        ':domaine'       => $domaine,
        ':categorie'     => $categorie,
        ':duration_days' => $duration_days,
        ':company_id'    => $companyId
    ]);
    
    echo json_encode(['success' => true, 'insert_id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur serveur lors de la création du défi.']);
}
?>