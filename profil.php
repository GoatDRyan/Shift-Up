<?php

require_once 'includes/init.php';


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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/tailwind-config.js"></script>
</head>
<body class="bg-brand-card text-brand-dark font-sans overflow-x-hidden pb-24">

    <header class="top-0 w-full h-40 p-4 pb-0 relative z-40 shadow-sm">
        <div class="flex items-center gap-2 max-[375px]:gap-1 mb-4 z-50 relative">

            <div class="absolute right-0 top-8 flex gap-2 max-[375px]:gap-1 z-50">
                <button class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                    <i class="fa-solid fa-newspaper text-xl max-[375px]:text-base"></i>
                </button>
                <button onclick="toggleMenu()" class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                    <i class="fa-solid fa-bars text-xl max-[375px]:text-base"></i>
                </button>
            </div>
            
            <div id="banner"
                class="absolute top-0 left-[-13%] w-[68%] h-32 max-[375px]:h-28 max-[320px]:h-24 bg-brand-secondary skew-tile cursor-pointer flex items-center justify-end pr-8 max-[375px]:pr-4 transition-colors z-20">
                <div class="unskew text-right flex flex-col justify-center">
                    <h2 class="font-display text-3xl max-[375px]:text-2xl font-bold text-brand-dark leading-none mb-1 transition-colors"><?= htmlspecialchars($pseudo) ?></h2>
                    <h3 class="font-display text-2xl max-[375px]:text-xs font-bold text-brand-dark transition-colors">Département</h3>
                </div>
            </div>
            <div id="badge" class="absolute left-[-13%] top-24 w-[75%] h-10 max-[375px]:h-10 max-[320px]:h-10 z-50 bg-brand-tertiary pl-20 grid gap-2 max-[375px]:gap-1 grid-cols-4  items-center justify-center text-brand-dark shadow-sm active:scale-95 transition" style="clip-path: polygon(0 0, 100% 0, 92% 50%, 100% 100%, 0 100%)">
                <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition"></div>
                <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition"></div>
                <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition"></div>

            </div>
            
            <button class="absolute right-5 top-24 flex gap-2 max-[375px]:gap-1 z-50 w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                <i class="fa-solid fa-newspaper text-xl max-[375px]:text-base"></i>
            </button>
        </div>
    </header>
    
    <?php include 'includes/settings_menu.php'; ?>

    <main class="p-4 mt-4">
        <div class="flex items-center gap-2 max-[375px]:gap-1 mb-4 z-50 relative">
            <div id="badge" class="absolute right-[-13%] top-2 w-[75%] h-10 max-[375px]:h-10 max-[320px]:h-10 z-50 bg-brand-tertiary pl-20 grid gap-2 max-[375px]:gap-1 grid-cols-4  items-center justify-center text-brand-dark shadow-sm active:scale-95 transition" style="clip-path: polygon(8% 50%, 0 0, 100% 0, 100% 100%, 0% 100%)">
                <h3>Solo</h3>
            </div>
    </main>

    <?php include 'includes/level_up_popup.php'; ?>
    <?php include 'includes/settings_menu.php'; ?>
    <?php include 'includes/navbar.php'; ?>
</body>
</html>