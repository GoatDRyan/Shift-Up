<?php
if (!isset($lang)) {
    require_once __DIR__ . '/../includes/init.php';
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['403_title'] ?? '403 - Accès interdit' ?> - Shift'Up</title>
    
    <link rel="stylesheet" href="/Shift-Up/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/Shift-Up/js/tailwind-config.js"></script>
</head>
<body class="bg-brand-primary text-brand-secondary font-sans min-h-screen flex flex-col items-center justify-center p-6 text-center overflow-hidden">

    <div class="max-w-sm w-full">
        
        <div class="absolute top-10 left-0 right-0 flex justify-center -z-10 opacity-5 pointer-events-none">
            <span class="font-display font-black text-[200px] text-brand-secondary">403</span>
        </div>

        <div class="relative mb-6 mt-10">
            <div class="absolute inset-0 bg-brand-secondary/10 rounded-full blur-3xl"></div>
            <img src="/Shift-Up/img/level/error-403.png" alt="Erreur 403" class="relative w-48 h-auto mx-auto drop-shadow-2xl animate-bounce-slow">
        </div>

        <h1 class="font-display text-4xl font-black uppercase tracking-tighter mb-4 text-brand-secondary">
            <?= $t['403_heading'] ?? 'Accès Interdit' ?>
        </h1>
        
        <p class="text-sm font-bold opacity-70 mb-10 leading-relaxed text-brand-secondary">
            <?= $t['403_desc'] ?? "Tu n'as pas la permission d'accéder à cette ressource. Rapproche-toi de ton administrateur si tu penses que c'est une erreur." ?>
        </p>

        <a href="/Shift-Up/views/users/index.php" class="inline-flex items-center justify-center gap-2 w-full bg-brand-secondary text-brand-primary font-bold py-4 rounded-2xl shadow-lg hover:opacity-90 active:scale-95 transition-all">
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