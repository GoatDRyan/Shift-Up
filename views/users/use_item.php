<?php
require_once '../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inventory_id'])) {
    $inv_id = (int)$_POST['inventory_id'];
    $stmt = $pdo->prepare("
        SELECT ui.id, r.type, r.boost_value, r.duration_hours, r.nom 
        FROM user_inventory ui 
        JOIN rewards r ON ui.reward_id = r.id 
        WHERE ui.id = ? AND ui.user_id = ? AND ui.is_used = 0
    ");
    $stmt->execute([$inv_id, $user_id]);
    $item = $stmt->fetch();

    if ($item) {
        try {
            $pdo->beginTransaction();

            $update_inv = $pdo->prepare("UPDATE user_inventory SET is_used = 1 WHERE id = ?");
            $update_inv->execute([$inv_id]);

            if ($item['type'] === 'streak_freeze') {
                $stmt_upd = $pdo->prepare("UPDATE users SET streak_freezes = streak_freezes + 1 WHERE id = ?");
                $stmt_upd->execute([$user_id]);
                $_SESSION['flash_message'] = "Gel de série activé !";

            } elseif ($item['type'] === 'xp_boost') {
                $ends_at = date('Y-m-d H:i:s', strtotime("+{$item['duration_hours']} hours"));
                $stmt_upd = $pdo->prepare("UPDATE users SET xp_boost_multiplier = ?, xp_boost_ends_at = ? WHERE id = ?");
                $stmt_upd->execute([$item['boost_value'], $ends_at, $user_id]);
                $_SESSION['flash_message'] = "Boost XP activé jusqu'à " . date('H:i', strtotime($ends_at));
            }

            $pdo->commit();
            header("Location: shop.php?used=1");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: shop.php?error=use_failed");
        }
    } else {
        header("Location: shop.php?error=not_found");
    }
}