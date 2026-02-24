<script src="js/settings_menu.js"></script>
<div id="settings-menu" class="fixed inset-0 bg-brand-dark/90 z-50 hidden flex items-center justify-center transition-opacity duration-300 opacity-0">
    
    <div class="relative w-11/12 max-w-sm rounded-3xl bg-brand-secondary flex flex-col p-6 shadow-xl">
        
        <button onclick="toggleMenu()" class="absolute top-4 right-4 bg-brand-tertiary/20 hover:bg-brand-tertiary/40 text-brand-dark rounded-full w-8 h-8 flex items-center justify-center transition-colors">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>

        <div class="grid grid-cols-2 gap-x-4 gap-y-6 w-full mt-10">
            
            <div class="flex flex-col items-center">
                <span class="text-[10px] font-semibold text-brand-dark mb-1"><?= $t['mode'] ?? 'Mode' ?></span>
                <button onclick="toggleTheme()" id="theme-toggle-btn" class="w-full bg-brand-primary py-2 rounded-full shadow-sm text-sm font-medium text-brand-dark hover:bg-brand-card transition-colors">
                    <?= $t['mode_light'] ?? 'Clair' ?> </button>
            </div>

            <div class="flex flex-col items-center">
                <span class="text-[10px] font-semibold text-brand-dark mb-1"><?= $t['language'] ?? 'Langue' ?></span>
                <a href="?lang=<?= $lang == 'fr' ? 'en' : 'fr' ?>" class="w-full bg-brand-primary py-2 rounded-full shadow-sm text-sm font-medium text-center text-brand-dark hover:bg-brand-card transition-colors block">
                    <?= $lang == 'fr' ? 'English' : 'Français' ?>
                </a>
            </div>

            <div class="flex flex-col items-center">
                <span class="text-[10px] font-semibold text-brand-dark mb-1"><?= $t['name'] ?? 'Nom' ?></span>
                <a href="edit/edit_name.php" class="w-full bg-brand-primary py-2 rounded-full shadow-sm text-sm font-medium text-center text-brand-dark hover:bg-brand-card transition-colors block">
                    <?= $t['btn_change'] ?? 'Changer' ?>
                </a>
            </div>

            <div class="flex flex-col items-center">
                <span class="text-[10px] font-semibold text-brand-dark mb-1"><?= $t['password'] ?? 'Mot de passe' ?></span>
                <a href="edit/edit_password.php" class="w-full bg-brand-primary py-2 rounded-full shadow-sm text-[12px] font-medium text-center text-brand-dark hover:bg-brand-card transition-colors block">
                    <?= $t['btn_modify'] ?? 'Modifier' ?>
                </a>
            </div>

        </div>

        <div class="grid grid-cols-2 gap-x-4 gap-y-3 w-full mt-24">
            <button class="w-full bg-brand-primary py-2 rounded-full shadow-sm text-[13px] font-medium text-brand-dark hover:bg-brand-card transition-colors">
                <?= $t['help_support'] ?? 'Assistance' ?>
            </button>
            <button class="w-full bg-brand-primary py-2 rounded-full shadow-sm text-[13px] font-medium text-brand-dark hover:bg-brand-card transition-colors">
                <?= $t['privacy'] ?? 'Confidentialité' ?>
            </button>
            
            <a href="logout.php" class="w-full bg-brand-primary py-2 rounded-full shadow-sm text-[13px] font-medium text-center text-brand-dark hover:bg-brand-card transition-colors block leading-loose">
                <?= $t['logout'] ?? 'Se déconnecter' ?>
            </a>
            <button class="w-full bg-brand-primary py-2 rounded-full shadow-sm text-[13px] font-medium text-brand-dark hover:bg-brand-card transition-colors">
                <?= $t['terms'] ?? 'Conditions' ?>
            </button>
        </div>

        <div class="mt-6 ml-1">
            <span class="text-[10px] font-semibold text-brand-dark">
                <?= $t['player_id'] ?? 'Id du joueur :' ?> <?= htmlspecialchars($user_id) ?>
            </span>
        </div>

    </div>
</div>
