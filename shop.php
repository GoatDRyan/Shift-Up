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
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift'Up</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/tailwind-config.js"></script>
</head>
<body class="bg-brand-card text-brand-dark font-sans overflow-x-hidden pb-24">
    <header class="bg-brand-primary text-brand-light p-4 shadow-md">

        <div class="absolute right-5 top-16 flex gap-2 max-[375px]:gap-1 z-50">
            <button class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                <i class="fa-solid fa-newspaper text-xl max-[375px]:text-base"></i>
            </button>
            <button onclick="toggleMenu()" class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                <i class="fa-solid fa-bars text-xl max-[375px]:text-base"></i>
            </button>
        </div>

        <div class="relative h-[200px] top-[-35px] flex items-center justify-center -mx-4 overflow-hidden mt-2 z-10">
            
            <div class="absolute left-[-10%] w-[68%] h-32 max-[375px]:h-28 max-[320px]:h-24 bg-brand-secondary skew-tile cursor-pointer flex items-center justify-end pr-8 max-[375px]:pr-4 transition-colors">
                <div class="unskew text-right flex flex-col justify-center">
                    <h2 class="font-display text-4xl max-[375px]:text-3xl max-[320px]:text-2xl font-bold text-brand-dark leading-none mb-1"><?= htmlspecialchars($pseudo) ?></h2>
                    <p class="text-xl max-[375px]:text-lg max-[320px]:text-base font-semibold text-brand-dark leading-none"><?= htmlspecialchars($deptName) ?></p>
                </div>
            </div>
            <div class="absolute left-0 top-40 w-[68%] bg-brand-tertiary py-2 pl-8 pr-6 flex items-center shadow-sm" style="clip-path: polygon(0 0, 100% 0, 92% 50%, 100% 100%, 0 100%);">
                <span class="text-xl text-brand-primary"><i class="fa-solid fa-coins mr-2"></i><?= number_format($money, 0, '.', ' ') ?></span>
            </div>
        </div>
    </header>
    <main class="px-4 max-[375px]:px-2 pt-10">
        <div class="flex flex-col items-end absolute right-0 w-full">
            <div class="absolute right-0 h-7 bg-brand-tertiary text-brand-primary w-[65%] text-center text-[19px] shadow-sm tracking-wide">
                Boutique
            </div>
            <div class="absolute top-7 right-3 bg-brand-border text-gray-900 text-[11px] py-0.5 pl-6 pr-4 w-[50%] text-center mr-6" style="clip-path: polygon(6% 0, 100% 0, 94% 100%, 12% 100%); mt-[-1px]">
                Fin dans : 10j 14h
            </div>
        </div>
    </main>
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/settings_menu.php'; ?>
</body>