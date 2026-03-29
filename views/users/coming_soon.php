<?php
require_once '../../includes/init.php';
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['coming_soon_title'] ?? 'Bientôt disponible' ?> - Shift'Up</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../../js/tailwind-config.js"></script>
</head>
<body class="bg-brand-primary text-brand-secondary font-sans min-h-screen flex flex-col items-center justify-center p-6 text-center">

    <div class="max-w-sm w-full">
        <div class="relative mb-8">
            <div class="absolute inset-0 bg-brand-secondary/10 rounded-full blur-3xl"></div>
            <img src="../../img/level/sad.png" alt="Sad Mascotte" class="relative w-48 h-48 mx-auto drop-shadow-2xl animate-bounce-slow">
        </div>

        <h1 class="font-display text-4xl font-black uppercase tracking-tighter mb-4">
            <?= $t['ops'] ?? 'Oups...' ?>
        </h1>
        
        <p class="text-lg font-bold opacity-90 mb-2">
            <?= $t['feature_not_ready'] ?? "Cette fonctionnalité n'est pas encore disponible." ?>
        </p>
        
        <p class="text-sm opacity-70 mb-10 leading-relaxed">
            <?= $t['feature_work_in_progress'] ?? "Nos équipes (et notre mascotte) travaillent dur pour vous proposer cette nouveauté très bientôt !" ?>
        </p>

        <a href="index.php" class="inline-flex items-center justify-center gap-2 w-full bg-brand-secondary text-brand-primary font-bold py-4 rounded-2xl shadow-lg hover:opacity-90 active:scale-95 transition-all">
            <i class="fa-solid fa-house"></i>
            <?= $t['btn_back_home'] ?? "Retour à l'accueil" ?>
        </a>
    </div>

    <style>
        @keyframes bounce-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-bounce-slow {
            animation: bounce-slow 3s ease-in-out infinite;
        }
    </style>

</body>
</html>