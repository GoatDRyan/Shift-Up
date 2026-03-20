<?php
require_once 'includes/init.php';

$stmt = $pdo->prepare("
    SELECT u.*, d.nom as department_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$deptName = $user['department_name'] ?? "Sans département";

$pseudo = $user['pseudo'] ?? "Shifter";
$money = $user['points_wallet'] ?? 0;
$company_id = $user['company_id'] ?? null;

$rewards = [];
if ($company_id) {
    $stmt_shop = $pdo->prepare("SELECT * FROM rewards WHERE company_id = ?");
    $stmt_shop->execute([$company_id]);
    $rewards = $stmt_shop->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?? 'fr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift'Up - Boutique</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/tailwind-config.js"></script>
</head>
<body class="bg-brand-card text-brand-dark font-sans overflow-x-hidden pb-24">
    <header class="bg-brand-primary text-brand-light p-4 shadow-md">

        <!-- Boutons d'action -->
        <div class="absolute right-5 top-16 flex gap-2 max-[375px]:gap-1 z-50">
            <button class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-secondary rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                <img class="w-6 h-6" src="img/icone/icone-notification-blanc.svg" alt="">
            </button>
            <button onclick="toggleMenu()" class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-secondary rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                <img class="w-6 h-6" src="img/icone/icone-parametre-blanc.svg" alt="Paramètres">
            </button>
        </div>

        <!-- En-tête profil -->
        <div class="relative h-[200px] top-[-35px] flex items-center justify-center -mx-4 overflow-hidden mt-2 z-10">
            <div class="absolute left-[-10%] w-[68%] h-32 max-[375px]:h-28 max-[320px]:h-24 bg-brand-secondary skew-tile cursor-pointer flex items-center justify-end pr-8 max-[375px]:pr-4 transition-colors">
                <div class="unskew text-right flex flex-col justify-center">
                    <h2 class="font-display text-4xl max-[375px]:text-3xl max-[320px]:text-2xl font-bold text-brand-primary leading-none mb-1"><?= htmlspecialchars($pseudo) ?></h2>
                    <p class="text-xl max-[375px]:text-lg max-[320px]:text-base font-semibold text-brand-primary leading-none"><?= htmlspecialchars($deptName) ?></p>
                </div>
            </div>
            <div class="absolute left-0 top-40 w-[68%] bg-brand-tertiary py-2 pl-8 pr-6 flex items-center shadow-sm" style="clip-path: polygon(0 0, 100% 0, 92% 50%, 100% 100%, 0 100%);">
                <span class="text-xl text-brand-secondary font-bold flex items-center">
                    <img src="img/icone/mascotte-monnaie.svg" alt="Monnaie" class="w-6 h-6 mr-2">
                    <?= number_format($money, 0, '.', ' ') ?>
                </span>
            </div>
        </div>
    </header>

    <main class="px-4 max-[375px]:px-2 pt-10">
        <div class="flex flex-col items-end absolute right-0 w-full">
            <div class="absolute right-0 h-7 bg-brand-secondary text-brand-primary w-[65%] text-center text-[19px] shadow-sm tracking-wide" style="clip-path: polygon(8% 50%, 0 0, 100% 0, 100% 100%, 0% 100%)">
                Boutique
            </div>
            <div class="absolute top-7 right-3 bg-brand-dark text-brand-primary text-[11px] py-0.5 pl-6 pr-4 w-[50%] text-center mr-6" style="clip-path: polygon(83.5% 50%, 100% 100%, 0% 100%, 14.75% 50%, 0% 0%, 100% 0%); mt-[-1px]">
                Fin dans : <span class="text-brand-secondary">10j 14h</span>
            </div>
        </div>

        <div class="mt-20">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-10">
                <?php if (empty($rewards)): ?>
                    <p class="text-center col-span-full opacity-50 mt-10">Aucun objet disponible pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($rewards as $item): ?>
                        <div class="bg-brand-primary rounded-3xl shadow-md p-4 flex flex-col items-center">
                            
                            <h3 class="text-sm font-bold text-brand-secondary mb-2 text-left w-full h-10 flex items-center">
                                <?= htmlspecialchars($item['nom']) ?>
                            </h3>
                            
                            <div class="relative mb-4 bg-brand-dark/10 p-2 rounded-3xl w-full flex justify-center">
                                <?php if (!empty($item['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="Image objet" class="w-full h-full object-cover rounded-3xl">
                                <?php else: ?>
                                    <i class="fa-solid fa-coins text-2xl text-brand-dark relative z-10"></i>
                                <?php endif; ?>
                            </div>

                            <button class="w-40 flex items-center justify-center gap-2 bg-brand-dark text-brand-primary font-bold py-2 rounded-3xl hover:bg-brand-secondary hover:text-brand-dark transition-all group shadow-lg active:scale-95">
                                <img class="w-5 h-5 group-hover:brightness-0 transition" src="img/icone/mascotte-monnaie.svg" alt="monnaie">
                                <?= number_format($item['cost'], 0, '.', ' ') ?>
                            </button>
                            
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/settings_menu.php'; ?>
</body>
</html>