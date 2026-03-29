<?php
require_once '../../includes/init.php';

$stmt = $pdo->prepare("SELECT u.*, d.nom as department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$deptName = $user['department_name'] ?? ($t['no_dept'] ?? "Sans département");
$money = $user['points_wallet'] ?? 0;

// 1. Récupérer les items du shop
$stmt_shop = $pdo->prepare("SELECT * FROM rewards WHERE company_id = ? OR company_id IS NULL ORDER BY cost ASC");
$stmt_shop->execute([$user['company_id']]);
$rewards = $stmt_shop->fetchAll();

// 2. Récupérer l'inventaire du joueur
$stmt_inv = $pdo->prepare("SELECT ui.*, r.nom, r.image_url, r.type FROM user_inventory ui JOIN rewards r ON ui.reward_id = r.id WHERE ui.user_id = ? AND ui.is_used = 0");
$stmt_inv->execute([$user_id]);
$inventory = $stmt_inv->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift'Up - Boutique</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../../js/tailwind-config.js"></script>
</head>
<body class="bg-brand-card text-brand-dark font-sans overflow-x-hidden pb-24 text-white">
    <header class="bg-brand-primary p-4 shadow-md relative z-40">
        <div class="absolute right-5 top-16 flex gap-2 z-50">
            <button onclick="openNotifications()" class="w-11 h-11 bg-brand-secondary rounded-xl flex items-center justify-center shadow-sm active:scale-95 transition">
                <img class="w-8 h-8" src="../../img/icone/icone-notification-blanc.svg" alt="Notifications">
            </button>
            <button onclick="toggleMenu()" class="w-11 h-11 bg-brand-secondary rounded-xl flex items-center justify-center shadow-sm active:scale-95 transition">
                <img class="w-8 h-8" src="../../img/icone/icone-parametre-blanc.svg" alt="Paramètres">
            </button>
        </div>

        <div class="relative h-[200px] top-[-35px] flex items-center justify-center -mx-4 overflow-hidden mt-2 z-10">
            <div class="absolute left-[-10%] w-[68%] h-32 bg-brand-secondary skew-tile flex items-center justify-end pr-8">
                <div class="unskew text-right flex flex-col justify-center">
                    <h2 class="font-display text-4xl font-bold text-brand-primary leading-none mb-1"><?= htmlspecialchars($user['pseudo']) ?></h2>
                    <p class="text-xl font-semibold text-brand-primary leading-none"><?= htmlspecialchars($deptName) ?></p>
                </div>
            </div>
            <div class="absolute left-0 top-40 w-[68%] bg-brand-dark py-2 pl-8 pr-6 flex items-center shadow-sm" style="clip-path: polygon(0 0, 100% 0, 92% 50%, 100% 100%, 0 100%);">
                <span class="text-xl text-brand-primary font-bold flex items-center">
                    <img src="../../img/icone/mascotte-monnaie.svg" alt="Monnaie" class="w-6 h-6 mr-2">
                    <?= number_format($money, 0, '.', ' ') ?>
                </span>
            </div>
        </div>
    </header>

    <main class="px-4 pt-10">
        <div class="flex gap-4 mb-8">
            <button onclick="switchTab('shop')" id="btn-shop" class="flex-1 py-3 bg-brand-secondary text-brand-primary font-bold rounded-2xl shadow-lg transition active:scale-95">Boutique</button>
            <button onclick="switchTab('inv')" id="btn-inv" class="flex-1 py-3 bg-brand-dark text-brand-primary font-bold rounded-2xl shadow-lg transition active:scale-95">Inventaire (<?= count($inventory) ?>)</button>
        </div>

        <div id="section-shop" class="grid grid-cols-2 gap-4">
            <?php foreach ($rewards as $item): ?>
                <div class="bg-brand-primary rounded-3xl shadow-lg p-4 flex flex-col items-center">
                    <h3 class="text-[12px] font-bold text-brand-secondary mb-3 text-center h-8 flex items-center"><?= htmlspecialchars($item['nom']) ?></h3>
                    <div class="aspect-square w-full mb-4 bg-brand-card rounded-2xl flex items-center justify-center">
                        <img src="<?= !empty($item['image_url']) ? $item['image_url'] : '../../img/icone/mascotte-monnaie.svg' ?>" class="w-16 h-16 object-contain">
                    </div>
                    <form action="buy_item.php" method="POST" class="w-full">
                        <input type="hidden" name="reward_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-brand-dark text-brand-primary font-bold py-2 rounded-2xl <?= ($money < $item['cost']) ? 'opacity-50 grayscale cursor-not-allowed' : 'active:scale-95' ?>">
                            <img class="w-4 h-4" src="../../img/icone/mascotte-monnaie.svg">
                            <?= number_format($item['cost'], 0, '.', ' ') ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="section-inv" class="hidden grid grid-cols-2 gap-4">
            <?php if (empty($inventory)): ?>
                <p class="text-center col-span-full opacity-50 py-10 text-brand-dark font-bold">Ton inventaire est vide.</p>
            <?php else: ?>
                <?php foreach ($inventory as $own): ?>
                    <div class="bg-brand-secondary rounded-3xl shadow-lg p-4 flex flex-col items-center">
                        <h3 class="text-[12px] font-bold text-brand-primary mb-3 text-center h-8 flex items-center">
                            <?= htmlspecialchars($own['nom']) ?>
                        </h3>
                        
                        <div class="w-16 h-16 mb-4 flex items-center justify-center">
                            <img src="<?= !empty($own['image_url']) ? $own['image_url'] : '../../img/icone/mascotte-monnaie.svg' ?>" class="max-w-full max-h-full object-contain">
                        </div>

                        <form action="use_item.php" method="POST" class="w-full">
                            <input type="hidden" name="inventory_id" value="<?= $own['id'] ?>">
                            <button type="submit" class="w-full py-2 bg-brand-primary text-brand-secondary font-bold rounded-xl text-xs active:scale-95 transition-transform">
                                <?= $t['btn_use'] ?? 'Utiliser' ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function switchTab(tab) {
            const sShop = document.getElementById('section-shop');
            const sInv = document.getElementById('section-inv');
            const bShop = document.getElementById('btn-shop');
            const bInv = document.getElementById('btn-inv');

            if (tab === 'shop') {
                sShop.classList.remove('hidden'); sInv.classList.add('hidden');
                bShop.classList.replace('bg-brand-dark', 'bg-brand-secondary');
                bInv.classList.replace('bg-brand-secondary', 'bg-brand-dark');
            } else {
                sInv.classList.remove('hidden'); sShop.classList.add('hidden');
                bInv.classList.replace('bg-brand-dark', 'bg-brand-secondary');
                bShop.classList.replace('bg-brand-secondary', 'bg-brand-dark');
            }
        }
    </script>
    <?php include '../../includes/navbar.php'; ?>
    <?php include '../../includes/settings_menu.php'; ?>
    <?php include '../../includes/notifications_popup.php'; ?>
</body>
</html>