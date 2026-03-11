<?php
session_start();
require_once('../db_connect.php');
header('Content-Type: application/json; charset=utf-8');


$challenge_id = isset($_GET['challenge_id']) ? (int)$_GET['challenge_id'] : 0;
if (!$challenge_id) { echo json_encode(['success'=>false,'error'=>'ID manquant']); exit; }
if (!isset($pdo) || !($pdo instanceof PDO)) { echo json_encode(['success'=>false,'error'=>'Connexion DB non définie']); exit; }

function tableExists(PDO $pdo, string $table): bool {
    $sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t";
    $stmt = $pdo->prepare($sql); $stmt->execute([':t'=>$table]);
    return (bool)$stmt->fetchColumn();
}
function columnExists(PDO $pdo, string $table, string $col): bool {
    $sql = "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c";
    $stmt = $pdo->prepare($sql); $stmt->execute([':t'=>$table,':c'=>$col]);
    return (bool)$stmt->fetchColumn();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM challenges WHERE id = :cid LIMIT 1");
    $stmt->execute([':cid'=>$challenge_id]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$c) { echo json_encode(['success'=>false,'error'=>'Tâche introuvable']); exit; }

    $titre = $c['titre_fr'] ?? ($c['titre'] ?? '');
    $titre_en = $c['titre_en'] ?? '';
    $descr_fr = $c['descr_fr'] ?? '';
    $descr_en = $c['descr_en'] ?? '';
    $xp = isset($c['xp_gain']) ? (int)$c['xp_gain'] : (int)($c['xp'] ?? 0);
    $score = isset($c['co2_kg']) ? (float)$c['co2_kg'] : (float)($c['score'] ?? 0);
    $duration = isset($c['duration_days']) ? (int)$c['duration_days'] : (int)($c['duration'] ?? 0);
    $domaine = $c['domaine'] ?? ($c['type'] ?? '');
    $categorie = $c['categorie'] ?? '';
    $difficulty = $c['difficulty'] ?? '';

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as users_count, COUNT(*) as completions FROM user_actions WHERE challenge_id = :cid");
    $stmt->execute([':cid'=>$challenge_id]);
    $cnt = $stmt->fetch(PDO::FETCH_ASSOC);
    $users_count = isset($cnt['users_count']) ? (int)$cnt['users_count'] : 0;
    $completions = isset($cnt['completions']) ? (int)$cnt['completions'] : 0;
    $last_users = [];
    if (tableExists($pdo,'user_actions') && tableExists($pdo,'users')) {
        $timeCol = columnExists($pdo,'user_actions','created_at') ? 'ua.created_at' : 'ua.id';
        $sql = "SELECT u.id,u.pseudo, $timeCol as ts
                FROM user_actions ua
                JOIN users u ON u.id = ua.user_id
                WHERE ua.challenge_id = :cid
                ORDER BY ts DESC
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cid'=>$challenge_id]);
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $last_users[] = ['id'=> (int)$r['id'], 'pseudo'=>$r['pseudo'], 'when'=>$r['ts']];
        }
    }
    $disabled = 0;
    if (tableExists($pdo,'disabled_challenges')) {
        $stmt = $pdo->prepare("SELECT 1 FROM disabled_challenges WHERE challenge_id = :cid LIMIT 1");
        $stmt->execute([':cid'=>$challenge_id]);
        $disabled = $stmt->fetchColumn() ? 1 : 0;
    }

    $created_at = $c['created_at'] ?? ($c['date_created'] ?? null);
    $updated_at = $c['updated_at'] ?? ($c['date_updated'] ?? null);
    $by_department = [];
    if (tableExists($pdo,'users') && columnExists($pdo,'users','department')) {
        $sql = "SELECT u.department as dept, COUNT(DISTINCT u.id) as cnt
                FROM user_actions ua
                JOIN users u ON u.id = ua.user_id
                WHERE ua.challenge_id = :cid
                GROUP BY u.department
                ORDER BY cnt DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cid'=>$challenge_id]);
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $by_department[] = ['department'=>$r['dept'],'count'=> (int)$r['cnt']];
        }
    }

    $response = [
        'success' => true,
        'id' => $challenge_id,
        'titre' => $titre,
        'titre_en' => $titre_en,
        'descr_fr' => $descr_fr,
        'descr_en' => $descr_en,
        'xp' => $xp,
        'score' => $score,
        'duration_days' => $duration,
        'domaine' => $domaine,
        'categorie' => $categorie,
        'difficulty' => $difficulty,
        'users_count' => $users_count,
        'completions' => $completions,
        'last_users' => $last_users,
        'disabled' => $disabled,
        'created_at' => $created_at,
        'updated_at' => $updated_at,
        'by_department' => $by_department,
        'can_edit' => true,
        'can_delete' => true
    ];

    echo json_encode($response);
    exit;
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    exit;
}
?>