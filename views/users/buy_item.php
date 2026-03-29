<?php
require_once '../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reward_id'])) {
    $reward_id = (int)$_POST['reward_id'];

    // 1. Récupérer les détails de l'item et le solde du joueur
    $stmt = $pdo->prepare("SELECT cost, nom FROM rewards WHERE id = ?");
    $stmt->execute([$reward_id]);
    $item = $stmt->fetch();

    if ($item && $user['points_wallet'] >= $item['cost']) {
        try {
            $pdo->beginTransaction();

            // 2. Soustraire l'argent
            $update_wallet = $pdo->prepare("UPDATE users SET points_wallet = points_wallet - ? WHERE id = ?");
            $update_wallet->execute([$item['cost'], $user_id]);

            // 3. Ajouter à l'inventaire
            $add_inv = $pdo->prepare("INSERT INTO user_inventory (user_id, reward_id) VALUES (?, ?)");
            $add_inv->execute([$user_id, $reward_id]);

            $pdo->commit();
            header("Location: shop.php?success=1");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: shop.php?error=server");
        }
    } else {
        header("Location: shop.php?error=no_money");
    }
}