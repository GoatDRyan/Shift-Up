<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['challenge_id'])) {
    header("Location: defis.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$challenge_id = (int) $_POST['challenge_id'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM challenges WHERE id = :id");
    $stmt->execute(['id' => $challenge_id]);
    $defi = $stmt->fetch();

    if (!$defi) throw new Exception("Défi introuvable.");

    $stmt_today = $pdo->prepare("SELECT COUNT(*) FROM user_actions WHERE user_id = :uid AND challenge_id = :cid AND DATE(date_action) = CURDATE()");
    $stmt_today->execute(['uid' => $user_id, 'cid' => $challenge_id]);
    $actions_aujourdhui = $stmt_today->fetchColumn();

    if ($actions_aujourdhui >= $defi['max_actions_day']) {
        $pdo->commit();
        $_SESSION['flash_message'] = "Limite atteinte pour aujourd'hui.";
        header("Location: defis.php");
        exit();
    }

    $insert = $pdo->prepare("INSERT INTO user_actions (user_id, challenge_id, date_action) VALUES (:uid, :cid, NOW())");
    $insert->execute(['uid' => $user_id, 'cid' => $challenge_id]);

    $sql_streak_logic = "
        current_streak = CASE 
            WHEN last_activity = SUBDATE(CURDATE(), 1) THEN current_streak + 1 
            WHEN last_activity = CURDATE() THEN current_streak 
            ELSE 1 
        END,
        last_activity = CURDATE()
    ";


    if ($defi['duration_days'] <= 1) {
        $sql = "UPDATE users SET 
                points_rank = points_rank + :xp, 
                points_wallet = points_wallet + :xp, 
                total_carbon_saved = total_carbon_saved + :co2,
                $sql_streak_logic 
                WHERE id = :uid";

        $update = $pdo->prepare($sql);
        $update->execute(['xp' => $defi['xp_gain'], 'co2' => $defi['co2_kg'], 'uid' => $user_id]);

        $pdo->commit();
        $_SESSION['flash_message'] = "Validé ! Vous gagnez " . $defi['xp_gain'] . " XP.";
    }

    else {
        $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM user_actions WHERE user_id = :uid AND challenge_id = :cid");
        $stmt_total->execute(['uid' => $user_id, 'cid' => $challenge_id]);
        $total_fait = $stmt_total->fetchColumn();
        $objectif = $defi['duration_days'];

        if ($total_fait < $objectif) {
            $sql = "UPDATE users SET $sql_streak_logic WHERE id = :uid";
            $update = $pdo->prepare($sql);
            $update->execute(['uid' => $user_id]);

            $pdo->commit();
            $_SESSION['flash_message'] = "Progression enregistrée : $total_fait / $objectif.";
        }
        
        elseif ($total_fait == $objectif) {
            
            $sql = "UPDATE users SET 
                points_rank = points_rank + :xp, 
                points_wallet = points_wallet + :xp, 
                total_carbon_saved = total_carbon_saved + :co2,
                $sql_streak_logic
                WHERE id = :uid";

            $update = $pdo->prepare($sql);
            $update->execute(['xp' => $defi['xp_gain'], 'co2' => $defi['co2_kg'], 'uid' => $user_id]);

            $msg_badge = "";
            $stmt_badge = $pdo->prepare("SELECT * FROM badges WHERE challenge_required_id = :cid");
            $stmt_badge->execute(['cid' => $challenge_id]);
            $badge = $stmt_badge->fetch();

            if ($badge) {
                $insert_badge = $pdo->prepare("INSERT IGNORE INTO user_badges (user_id, badge_id, obtained_at) VALUES (:uid, :bid, NOW())");
                $insert_badge->execute(['uid' => $user_id, 'bid' => $badge['id']]);
                if ($insert_badge->rowCount() > 0) $msg_badge = " + Badge" . $badge['nom_fr'];
            }

            $pdo->commit();
            $_SESSION['flash_message'] = "DÉFI TERMINÉ ! " . $defi['xp_gain'] . " XP" . $msg_badge;
        }
        
        else {
            $pdo->commit();
            $_SESSION['flash_message'] = "Déjà terminé !";
        }
    }

    header("Location: defis.php");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash_message'] = "Erreur technique : " . $e->getMessage();
    header("Location: defis.php");
    exit();
}
?>