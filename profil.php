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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <button class="absolute right-11 top-[16.5%] w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
            <i class="fa-solid fa-newspaper text-xl max-[375px]:text-base"></i>
        </button>

        <div class="relative h-[200px] top-[-35px] flex items-center justify-center -mx-4 overflow-hidden mt-2 z-10">
            
            <div class="absolute left-[-10%] w-[68%] h-32 max-[375px]:h-28 max-[320px]:h-24 bg-brand-secondary skew-tile cursor-pointer flex items-center justify-end pr-8 max-[375px]:pr-4 transition-colors">
                <div class="unskew text-right flex flex-col justify-center">
                    <h2 class="font-display text-4xl max-[375px]:text-3xl max-[320px]:text-2xl font-bold text-brand-dark leading-none mb-1"><?= htmlspecialchars($pseudo) ?></h2>
                    <p class="text-xl max-[375px]:text-lg max-[320px]:text-base font-semibold text-brand-dark leading-none"><?= htmlspecialchars($deptName) ?></p>
                </div>
            </div>
            <div class="absolute left-0 top-40 w-[60%] h-30 bg-brand-tertiary pl-8 flex items-center justify-center text-brand-dark shadow-sm" style="clip-path: polygon(0 0, 100% 0, 92% 50%, 100% 100%, 0 100%);">
                 <div class="grid gap-5 max-[375px]:gap-5 grid-cols-4 items-center justify-center text-brand-dark shadow-sm">
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-medal text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="p-4 mt-4">
        <div class="flex flex-col gap-2 max-[375px]:gap-1 mb-4 z-50 relative">
            <div class="absolute right-[-13%] top-1 w-[75%] h-10 max-[375px]:h-10 max-[320px]:h-10 z-30 bg-brand-tertiary pl-20 grid gap-2 max-[375px]:gap-1 grid-cols-4 align-center items-center justify-center text-brand-dark shadow-sm active:scale-95 transition" style="clip-path: polygon(8% 50%, 0 0, 100% 0, 100% 100%, 0% 100%)">
                <h3>Solo</h3>
            </div>
            <div class="mt-20 w-full h-auto text-center grid gap-5 max-[375px]:gap-5 grid-cols-2 items-center justify-center text-brand-dark shadow-sm">
                <div class="flex flex-col items-center gap-1 bg-brand-secondary p-4 rounded-xl">
                    <h3 class="text-md font-bold underline">Total parties jouées</h3>
                    <span class="text-xl font-bold">123</span>
                </div>
                <div class="flex flex-col items-center gap-1 bg-brand-secondary p-4 rounded-xl">
                    <h3 class="text-md font-bold underline">Total victoires</h3>
                    <span class="text-xl font-bold">45</span>
                </div>
            </div>
            <div class="absolute right-[-13%] top-[25%] w-[75%] h-10 max-[375px]:h-10 max-[320px]:h-10 z-30 bg-brand-tertiary pl-20 align-center items-center justify-center text-brand-dark shadow-sm active:scale-95 transition" style="clip-path: polygon(8% 50%, 0 0, 100% 0, 100% 100%, 0% 100%)">
                <h3>Shift League</h3>
            </div>
            <div class="mt-20 w-full h-auto text-center grid gap-5 max-[375px]:gap-5 grid-cols-2 items-center justify-center text-brand-dark shadow-sm">
                <div class="flex flex-col items-center gap-1 bg-brand-secondary p-4 rounded-xl">
                    <h3 class="text-md font-bold underline">Top score</h3>
                    <span class="text-xl font-bold">123</span>
                </div>
                <div class="flex flex-col items-center gap-1 bg-brand-secondary p-4 rounded-xl">
                    <h3 class="text-md font-bold underline">Top classement</h3>
                    <span class="text-xl font-bold">4</span>
                </div>
            </div>
            <div class="absolute right-[-13%] top-[48%] w-[75%] h-10 max-[375px]:h-10 max-[320px]:h-10 z-30 bg-brand-tertiary pl-20 align-center items-center justify-center text-brand-dark shadow-sm active:scale-95 transition" style="clip-path: polygon(8% 50%, 0 0, 100% 0, 100% 100%, 0% 100%)">
                <h3>Badge</h3>
            </div>
            <div class="mt-20 flex flex-col items-center gap-5 bg-brand-secondary p-4 rounded-xl">
                <div class="grid gap-5 max-[375px]:gap-5 grid-cols-5 items-center justify-center text-brand-dark shadow-sm">
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-medal text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-medal text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                </div>
                <div class="grid gap-5 max-[375px]:gap-5 grid-cols-5 items-center justify-center text-brand-dark shadow-sm">
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-medal text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-medal text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                </div>
                <div class="grid gap-5 max-[375px]:gap-5 grid-cols-5 items-center justify-center text-brand-dark shadow-sm">
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-medal text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-medal text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                </div>
                <div class="grid gap-5 max-[375px]:gap-5 grid-cols-5 items-center justify-center text-brand-dark shadow-sm">
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-medal text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-medal text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                </div>
                <div class="grid gap-5 max-[375px]:gap-5 grid-cols-5 items-center justify-center text-brand-dark shadow-sm">
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-medal text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-medal text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-dark shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/settings_menu.php'; ?>
    <?php include 'includes/level_up_popup.php'; ?>

</body>
</html>


