<?php



require_once '../../includes/init.php';

$stmt = $pdo->prepare("
    SELECT u.*, d.nom as department_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.id 
    WHERE u.id = ?
");

$stmt->execute([$user_id]);
$user = $stmt->fetch();
$deptName = $user['department_name'] ?? "Sans département";

$stmtStreak = $pdo->prepare("SELECT max_streak FROM users WHERE id = ?");
$stmtStreak->execute([$user_id]);
$streak = $stmtStreak->fetch()['max_streak'];


$stmtRank = $pdo->prepare("SELECT points_rank FROM users WHERE id = ?");
$stmtRank->execute([$user_id]);
$rank = $stmtRank->fetch()['points_rank'];
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift'Up</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../js/tailwind-config.js"></script>
</head>

<body class="bg-brand-card text-brand-dark font-sans overflow-x-hidden pb-24">
    <header class="bg-brand-primary text-brand-card p-4 shadow-md relative z-40">

        <div class="absolute right-5 top-16 flex gap-2 max-[375px]:gap-1 z-50">
            <button class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-secondary rounded-xl flex items-center justify-center text-brand-card shadow-sm active:scale-95 transition">
                <img src="../../img/icone/icone-notification-blanc.svg" alt="icone notification" class="w-5 max-[375px]:w-4"></img>
            </button>
            <button onclick="toggleMenu()" class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-secondary rounded-xl flex items-center justify-center text-brand-card shadow-sm active:scale-95 transition">
                <img src="../../img/icone/icone-parametre-blanc.svg" alt="icone parametre" class="w-5 max-[375px]:w-4"></img>
            </button>
        </div>
        <button class="absolute right-12 top-[60%] w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-card shadow-sm active:scale-95 transition">
            <img src="../../img/icone/icone-crayon-vide.svg" alt="icone crayon" class="w-5 max-[375px]:w-4"></img>
        </button>

        <div class="relative h-56 top-[-35px] flex items-center justify-center -mx-4 overflow-hidden mt-2 z-10">
            
            <div class="absolute left-[-10%] w-[68%] h-32 max-[375px]:h-28 max-[320px]:h-24 bg-brand-secondary skew-tile cursor-pointer flex items-center justify-end pr-8 max-[375px]:pr-4 transition-colors">
                <div class="unskew text-right flex flex-col justify-center">
                    <h2 class="font-display text-4xl max-[375px]:text-3xl max-[320px]:text-2xl font-bold text-brand-card leading-none mb-1"><?= htmlspecialchars($pseudo) ?></h2>
                    <p class="text-xl max-[375px]:text-lg max-[320px]:text-base font-semibold text-brand-card leading-none"><?= htmlspecialchars($deptName) ?></p>
                </div>
            </div>
            
            <div class="absolute left-0 top-40 w-[65%]  bg-brand-dark p-2 pl-8 flex items-center justify-center text-brand-card shadow-sm" style="clip-path: polygon(0 0, 100% 0, 92% 50%, 100% 100%, 0 100%);">
                 <div class="grid gap-2 max-[375px]:gap-2 grid-cols-5 items-center justify-center text-brand-card shadow-sm">
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-card shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-card shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-medal text-xl max-[375px]:text-base"></i>
                    </div>
                    <div class="w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 bg-brand-border rounded-xl flex items-center justify-center text-brand-card shadow-sm active:scale-95 transition">
                        <i class="fa-solid fa-trophy text-xl max-[375px]:text-base"></i>
                    </div>
                    <img src="../../img/level/icone-level-1.svg" alt="icone level 1" class="ml-4 w-11 h-11 max-[375px]:w-9 max-[375px]:h-9 flex items-center justify-center text-brand-card shadow-sm active:scale-95 transition"></img>
                </div>
            </div>
        </div>
    </header>

    <main class="p-4 mt-6 flex flex-col gap-10 z-10 relative">
        
        <div class="relative w-full">
            <div class="absolute right-[-16px] top-[-20px] w-[75%] h-10 max-[375px]:h-10 max-[320px]:h-10 z-30 bg-brand-secondary pl-20 flex items-center text-brand-card shadow-sm" style="clip-path: polygon(8% 50%, 0 0, 100% 0, 100% 100%, 0% 100%)">
                <h3 class="font-bold">Solo</h3>
            </div>
            
            <div class="mt-8 w-full text-center grid gap-5 max-[375px]:gap-5 grid-cols-2 items-center justify-center text-brand-card shadow-sm">
                <div class="flex flex-col items-center gap-1 bg-brand-dark p-4 rounded-xl">
                    <h3 class="text-md font-bold underline">Total parties jouées</h3>
                    <span class="text-xl font-bold">123</span>
                </div>
                <div class="flex flex-col items-center gap-1 bg-brand-dark p-4 rounded-xl">
                    <h3 class="text-md font-bold underline">Top streak</h3>
                    <span class="text-xl font-bold"><?= number_format($streak, 0, ',', ' ') ?></span>
                </div>
            </div>
        </div>

        <div class="relative w-full">
            <div class="absolute right-[-16px] top-[-20px] w-[75%] h-10 max-[375px]:h-10 max-[320px]:h-10 z-30 bg-brand-secondary pl-20 flex items-center text-brand-card shadow-sm" style="clip-path: polygon(8% 50%, 0 0, 100% 0, 100% 100%, 0% 100%)">
                <h3 class="font-bold">Shift League</h3>
            </div>
            
            <div class="mt-8 w-full text-center grid gap-5 max-[375px]:gap-5 grid-cols-2 items-center justify-center text-brand-card shadow-sm">
                <div class="flex flex-col items-center gap-1 bg-brand-dark p-4 rounded-xl">
                    <h3 class="text-md font-bold underline">Total de score</h3>
                    <span class="text-xl font-bold"><?= number_format($rank, 0, ',', ' ') ?></span>
                </div>
                <div class="flex flex-col items-center gap-1 bg-brand-dark p-4 rounded-xl">
                    <h3 class="text-md font-bold underline">Top classement</h3>
                    <span class="text-xl font-bold">4</span>
                </div>
            </div>
        </div>

        <div class="relative w-full">
            <div class="absolute right-[-16px] top-[-20px] w-[75%] h-10 max-[375px]:h-10 max-[320px]:h-10 z-30 bg-brand-secondary pl-20 flex items-center text-brand-card shadow-sm" style="clip-path: polygon(8% 50%, 0 0, 100% 0, 100% 100%, 0% 100%)">
                <h3 class="font-bold">Badge</h3>
            </div>
            
            <div class="badge-slider mt-10 overflow-x-scroll flex gap-6 px-2 snap-x snap-mandatory scrollbar-hide overflow-y-hidden" style="scrollbar-width: none;">
        
                <!-- Badge 1 -->
                <div class="badge-card snap-center shrink-0 w-[80%] bg-brand-secondary rounded-xl p-1 flex flex-col items-center justify-center">
                    <img src="../../img/carte/carte-trier.svg" class="w-full h-full" />
                </div>

                <!-- Badge 2 -->
                <div class="badge-card snap-center shrink-0 w-[80%] bg-brand-secondary rounded-xl p-1 flex flex-col items-center justify-center">
                    <img src="../../img/carte/carte-covoiturage.svg" class="w-full h-full" />
                </div>

                <!-- Badge 3 -->
                <div class="badge-card snap-center shrink-0 w-[80%] bg-brand-secondary rounded-xl p-3 flex flex-col items-center justify-center">
                    <img src="../../img/carte/carte-covoiturage2.svg" class="w-full h-full" />
                </div>

                <!-- Badge 4 -->
                <div class="badge-card snap-center shrink-0 w-[80%] bg-brand-secondary rounded-xl p-1 flex flex-col items-center justify-center">
                    <img src="../../img/carte/carte-mediateur.svg" class="w-full h-full" />
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../../includes/level_up_popup.php'; ?>
    <?php include '../../includes/settings_menu.php'; ?>
    <?php include '../../includes/navbar.php'; ?>

    <script>
        const slider = document.querySelector('.badge-slider');
        const cards = document.querySelectorAll('.badge-card');

        function updateCards() {
            const center = slider.scrollLeft + slider.offsetWidth / 2;

            cards.forEach(card => {
                const cardCenter = card.offsetLeft + card.offsetWidth / 2;
                const distance = Math.abs(center - cardCenter);

                const scale = Math.max(0.92, 1 - distance / 500);
                const offset = Math.min(25, distance / 12);

                card.style.transform = `translateY(${offset}px) scale(${scale})`;
                card.style.opacity = scale;

                if (distance < 80) {
                    card.classList.add("active");
                } else {
                    card.classList.remove("active");
                }
            });
        }

        slider.addEventListener('scroll', updateCards);
        updateCards();

    </script>
</body>
</html>