<?php
require_once 'includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $q1 = isset($_POST['q1']) ? (float)$_POST['q1'] : 0;
    $q2 = isset($_POST['q2']) ? (float)$_POST['q2'] : 0;
    $q3 = isset($_POST['q3']) ? (float)$_POST['q3'] : 0;

    $total_footprint = 2000 + $q1 + $q2 + $q3;
    $stmt = $pdo->prepare("UPDATE users SET initial_footprint_kg = ? WHERE id = ?");
    $stmt->execute([$total_footprint, $user_id]);
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['quiz_title'] ?? 'Bilan Carbone' ?> - Shift'Up</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/tailwind-config.js"></script>
    <style>
        input[type="radio"] { display: none; }
        input[type="radio"]:checked + label {
            background-color: #111827;
            color: #ffffff;
            border-color: #111827;
        }
    </style>
</head>
<body class="bg-brand-card text-brand-dark font-sans h-screen flex flex-col overflow-hidden">
    
    <header class="p-4 pt-8 flex items-center justify-between relative z-10">
        <a href="index.php" class="text-brand-tertiary text-xl w-10 h-10 flex items-center justify-center rounded-full hover:bg-brand-border transition">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="flex gap-2">
            <div id="dot-1" class="w-3 h-3 rounded-full bg-brand-dark transition-colors"></div>
            <div id="dot-2" class="w-3 h-3 rounded-full bg-brand-border transition-colors"></div>
            <div id="dot-3" class="w-3 h-3 rounded-full bg-brand-border transition-colors"></div>
        </div>
        <div class="w-10"></div> </header>

    <div class="text-center px-6 mt-4 mb-6">
        <h1 class="font-display font-bold text-3xl"><?= $t['quiz_title'] ?? 'Bilan Carbone' ?></h1>
        <p class="text-brand-tertiary text-sm mt-1"><?= $t['quiz_subtitle'] ?? 'Découvrons ton point de départ.' ?></p>
    </div>

    <main class="flex-1 px-4 overflow-y-auto pb-24">
        <form id="quiz-form" method="POST" action="">
            
            <div id="step-1" class="fade-in flex flex-col gap-4">
                <h2 class="text-lg font-bold mb-2 text-center"><?= $t['q1_title'] ?? 'Comment te déplaces-tu ?' ?></h2>
                
                <input type="radio" name="q1" id="q1_a1" value="2500" required onclick="autoNext(1)">
                <label for="q1_a1" class="bg-brand-primary border-2 border-brand-border p-5 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4">
                    <i class="fa-solid fa-car text-2xl w-8 text-center"></i> <?= $t['q1_a1'] ?? 'En voiture' ?>
                </label>

                <input type="radio" name="q1" id="q1_a2" value="800" onclick="autoNext(1)">
                <label for="q1_a2" class="bg-brand-primary border-2 border-brand-border p-5 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4">
                    <i class="fa-solid fa-bus text-2xl w-8 text-center"></i> <?= $t['q1_a2'] ?? 'Transports' ?>
                </label>

                <input type="radio" name="q1" id="q1_a3" value="0" onclick="autoNext(1)">
                <label for="q1_a3" class="bg-brand-primary border-2 border-brand-border p-5 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4">
                    <i class="fa-solid fa-bicycle text-2xl w-8 text-center"></i> <?= $t['q1_a3'] ?? 'Vélo / Marche' ?>
                </label>
            </div>

            <div id="step-2" class="hidden flex flex-col gap-4">
                <h2 class="text-lg font-bold mb-2 text-center"><?= $t['q2_title'] ?? 'Alimentation ?' ?></h2>
                
                <input type="radio" name="q2" id="q2_a1" value="2000" onclick="autoNext(2)">
                <label for="q2_a1" class="bg-brand-primary border-2 border-brand-border p-5 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4">
                    <i class="fa-solid fa-drumstick-bite text-2xl w-8 text-center"></i> <?= $t['q2_a1'] ?? 'Viande' ?>
                </label>

                <input type="radio" name="q2" id="q2_a2" value="1200" onclick="autoNext(2)">
                <label for="q2_a2" class="bg-brand-primary border-2 border-brand-border p-5 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4">
                    <i class="fa-solid fa-egg text-2xl w-8 text-center"></i> <?= $t['q2_a2'] ?? 'Limité' ?>
                </label>

                <input type="radio" name="q2" id="q2_a3" value="500" onclick="autoNext(2)">
                <label for="q2_a3" class="bg-brand-primary border-2 border-brand-border p-5 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4">
                    <i class="fa-solid fa-leaf text-2xl w-8 text-center"></i> <?= $t['q2_a3'] ?? 'Végé / Végan' ?>
                </label>
            </div>

            <div id="step-3" class="hidden flex flex-col gap-4">
                <h2 class="text-lg font-bold mb-2 text-center"><?= $t['q3_title'] ?? 'Logement ?' ?></h2>
                
                <input type="radio" name="q3" id="q3_a1" value="3000" onclick="showSubmit()">
                <label for="q3_a1" class="bg-brand-primary border-2 border-brand-border p-5 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4">
                    <i class="fa-solid fa-fire-flame-simple text-2xl w-8 text-center"></i> <?= $t['q3_a1'] ?? 'Gaz / Fioul' ?>
                </label>

                <input type="radio" name="q3" id="q3_a2" value="1500" onclick="showSubmit()">
                <label for="q3_a2" class="bg-brand-primary border-2 border-brand-border p-5 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4">
                    <i class="fa-solid fa-plug text-2xl w-8 text-center"></i> <?= $t['q3_a2'] ?? 'Électricité' ?>
                </label>

                <input type="radio" name="q3" id="q3_a3" value="500" onclick="showSubmit()">
                <label for="q3_a3" class="bg-brand-primary border-2 border-brand-border p-5 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4">
                    <i class="fa-solid fa-house-chimney-window text-2xl w-8 text-center"></i> <?= $t['q3_a3'] ?? 'PAC / Bois' ?>
                </label>
            </div>

            <div id="submit-container" class="hidden mt-8">
                <button type="submit" class="w-full bg-brand-accent text-brand-primary font-bold py-4 rounded-full hover:bg-brand-accentdark transition shadow-lg text-lg fade-in">
                    <?= $t['btn_finish'] ?? 'Terminer' ?>
                </button>
            </div>

        </form>
    </main>

    <script>
        function autoNext(currentStep) {
            setTimeout(() => {
                document.getElementById('step-' + currentStep).classList.add('hidden');
                const nextStep = currentStep + 1;
                const nextDiv = document.getElementById('step-' + nextStep);
                nextDiv.classList.remove('hidden');
                nextDiv.classList.add('fade-in');
                document.getElementById('dot-' + nextStep).classList.replace('bg-brand-border', 'bg-brand-dark');
            }, 250);
        }

        function showSubmit() {
            setTimeout(() => {
                document.getElementById('submit-container').classList.remove('hidden');
            }, 250);
        }
    </script>
</body>
</html>