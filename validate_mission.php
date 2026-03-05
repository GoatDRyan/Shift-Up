<?php
require_once 'includes/init.php';

if (!isset($user_id) || !isset($_POST['challenge_id'])) {
    header("Location: defis.php");
    exit();
}

$challenge_id = (int) $_POST['challenge_id'];
$is_success = false;

try {
    $pdo->beginTransaction();

    // Récupération du défi
    $stmt = $pdo->prepare("SELECT * FROM challenges WHERE id = :id");
    $stmt->execute(['id' => $challenge_id]);
    $defi = $stmt->fetch();

    if (!$defi) throw new Exception("Défi introuvable.");

    // --- VÉRIFICATION DE LA LIMITE DU JOUR ---
    $stmt_today = $pdo->prepare("SELECT COUNT(*) FROM user_actions WHERE user_id = :uid AND challenge_id = :cid AND DATE(date_action) = CURDATE()");
    $stmt_today->execute(['uid' => $user_id, 'cid' => $challenge_id]);
    $actions_aujourdhui = $stmt_today->fetchColumn();

    if ($actions_aujourdhui >= $defi['max_actions_day']) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = "Limite atteinte pour aujourd'hui.";
        header("Location: defis.php");
        exit();
    }

    // --- VÉRIFICATION DE LA LIMITE TOTALE (Si défi sur plusieurs jours) ---
    $objectif = (int)($defi['duration_days'] ?? 1);
    $total_fait = 0;
    
    if ($objectif > 1) {
        $stmt_total = $pdo->prepare("SELECT COUNT(DISTINCT DATE(date_action)) FROM user_actions WHERE user_id = :uid AND challenge_id = :cid");
        $stmt_total->execute(['uid' => $user_id, 'cid' => $challenge_id]);
        $total_fait = (int)$stmt_total->fetchColumn();

        if ($total_fait >= $objectif) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = "Ce défi est déjà totalement terminé !";
            header("Location: defis.php");
            exit();
        }
    }

    $insert = $pdo->prepare("INSERT INTO user_actions (user_id, challenge_id, date_action) VALUES (:uid, :cid, NOW())");
    $insert->execute(['uid' => $user_id, 'cid' => $challenge_id]);
    
    $is_success = true;
    $total_fait++;

    // Logique de série (Streak)
    $sql_streak_logic = "
        current_streak = CASE 
            WHEN last_activity = SUBDATE(CURDATE(), 1) THEN current_streak + 1 
            WHEN last_activity = CURDATE() THEN current_streak 
            ELSE 1 
        END,
        last_activity = CURDATE()
    ";

    // --- CALCUL DU NIVEAU (AVANT XP) POUR LE POPUP LEVEL UP ---
    $old_level = 1;
    if (function_exists('get_level_data')) {
        $old_level = get_level_data($user['points_rank'])['niveau_actuel'];
    }

    // --- ATTRIBUTION DES RÉCOMPENSES (Uniquement si Objectif atteint) ---
    if ($objectif <= 1 || $total_fait == $objectif) {
        $sql = "UPDATE users SET 
                points_rank = points_rank + :xp, 
                points_wallet = points_wallet + :xp, 
                total_carbon_saved = total_carbon_saved + :co2,
                $sql_streak_logic 
                WHERE id = :uid";

        $update = $pdo->prepare($sql);
        $update->execute(['xp' => $defi['xp_gain'], 'co2' => $defi['co2_kg'], 'uid' => $user_id]);

        if (function_exists('get_level_data')) {
            $nouveau_total_xp = $user['points_rank'] + $defi['xp_gain'];
            $new_level = get_level_data($nouveau_total_xp)['niveau_actuel'];
            
            if ($new_level > $old_level) {
                $_SESSION['level_up'] = $new_level;
            }
        }

        // 3. Vérification des Badges
        $msg_badge = "";
        $stmt_badge = $pdo->prepare("SELECT * FROM badges WHERE challenge_required_id = :cid");
        $stmt_badge->execute(['cid' => $challenge_id]);
        $badge = $stmt_badge->fetch();

        if ($badge) {
            $insert_badge = $pdo->prepare("INSERT IGNORE INTO user_badges (user_id, badge_id, obtained_at) VALUES (:uid, :bid, NOW())");
            $insert_badge->execute(['uid' => $user_id, 'bid' => $badge['id']]);
            if ($insert_badge->rowCount() > 0) {
                $msg_badge = " + Badge débloqué !";
            }
        }

        $_SESSION['flash_message'] = "DÉFI TERMINÉ ! " . $defi['xp_gain'] . " XP gagnés !" . $msg_badge;

    } else {
        $sql = "UPDATE users SET $sql_streak_logic WHERE id = :uid";
        $update = $pdo->prepare($sql);
        $update->execute(['uid' => $user_id]);

        $_SESSION['flash_message'] = "Progression enregistrée : $total_fait / $objectif jours.";
    }

    $pdo->commit();

    // Redirection finale
    if ($is_success) {
        header("Location: defis.php?success=1");
    } else {
        header("Location: defis.php");
    }
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash_message'] = "Erreur technique : " . $e->getMessage();
    header("Location: defis.php");
    exit();
}
?>