<?php
require_once '../../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

$companyId = (int)$user['company_id'];

$challenge_id = isset($_GET['challenge_id']) ? (int)$_GET['challenge_id'] : 0;
if (!$challenge_id) { 
    echo json_encode(['success' => false, 'error' => 'ID manquant']); 
    exit; 
}

try {
    $stmt = $pdo->prepare("SELECT titre_fr, xp_gain, co2_kg FROM challenges WHERE id = :cid AND (company_id = :company_id OR company_id IS NULL) LIMIT 1");
    $stmt->execute([
        ':cid' => $challenge_id,
        ':company_id' => $companyId
    ]);
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { 
        echo json_encode(['success' => false, 'error' => 'Tâche introuvable ou accès non autorisé']); 
        exit; 
    }

    $score = (float)($row['co2_kg'] ?? 0.0);
    $xp = (int)($row['xp_gain'] ?? 0);
    $stmt2 = $pdo->prepare("
        SELECT COUNT(DISTINCT ua.user_id) as users_count 
        FROM user_actions ua 
        JOIN users u ON ua.user_id = u.id 
        WHERE ua.challenge_id = :cid AND u.company_id = :company_id
    ");
    $stmt2->execute([
        ':cid' => $challenge_id,
        ':company_id' => $companyId
    ]);
    
    $res2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    $uc = (int)($res2['users_count'] ?? 0);

    echo json_encode([
      'success' => true,
      'titre' => $row['titre_fr'],
      'xp' => $xp,
      'score' => $score,
      'users_count' => $uc
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur serveur lors de la récupération des données.']);
}
?>