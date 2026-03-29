<script src="../../js/settings_menu.js"></script>
<div id="settings-menu" class="fixed inset-0 backdrop-blur-md bg-brand-dark/60 z-50 hidden flex items-center justify-center transition-opacity duration-300 opacity-0">
    
    <div class="relative w-11/12 max-w-sm rounded-3xl bg-brand-primary flex flex-col p-6 shadow-xl">
        
        <button onclick="toggleMenu()" class="absolute top-4 right-4 bg-brand-secondary hover:bg-brand-tertiary/40 text-brand-dark rounded-full w-8 h-8 flex items-center justify-center transition-colors">
            <i class="fa-solid fa-xmark text-lg text-brand-primary"></i>
        </button>

        <div class="grid grid-cols-2 gap-x-4 gap-y-6 w-full mt-10">
            <div class="col-span-2 flex items-center justify-center mb-4">
                <h2 class="text-display font-bold text-brand-secondary text-3xl"><?= $t['settings']?></h2>
            </div>
            <div class="flex flex-col items-center">
                <span class="text-[10px] font-semibold text-brand-dark mb-1"><?= $t['mode'] ?? 'Mode' ?></span>
                <button onclick="toggleTheme()" id="theme-toggle-btn" class="w-full bg-brand-secondary py-2 rounded-full shadow-lg text-sm font-medium text-brand-primary hover:bg-brand-secondary/20 transition-colors">
                    
                    <span class="block dark:hidden">
                        <?= $t['mode_dark'] ?? 'Sombre' ?>
                    </span>
                    
                    <span class="hidden dark:block">
                        <?= $t['mode_light'] ?? 'Clair' ?>
                    </span>
                    
                </button>
            </div>

            <div class="flex flex-col items-center">
                <span class="text-[10px] font-semibold text-brand-dark mb-1"><?= $t['language'] ?? 'Langue' ?></span>
                <a href="?lang=<?= $lang == 'fr' ? 'en' : 'fr' ?>" class="w-full bg-brand-secondary py-2 rounded-full shadow-lg text-sm font-medium text-center text-brand-primary hover:bg-brand-secondary/20 transition-colors block">
                    <?= $lang == 'fr' ? 'English' : 'Français' ?>
                </a>
            </div>

            <div class="flex flex-col items-center">
                <span class="text-[10px] font-semibold text-brand-dark mb-1"><?= $t['name'] ?? 'Nom' ?></span>
                <a href="../../edit/edit_name.php" class="w-full bg-brand-secondary py-2 rounded-full shadow-lg text-sm font-medium text-center text-brand-primary hover:bg-brand-secondary/20 transition-colors block">
                    <?= $t['btn_change'] ?? 'Changer' ?>
                </a>
            </div>

            <div class="flex flex-col items-center">
                <span class="text-[10px] font-semibold text-brand-dark mb-1"><?= $t['password'] ?? 'Mot de passe' ?></span>
                <a href="../../edit/edit_password.php" class="w-full bg-brand-secondary py-2 rounded-full shadow-lg text-[12px] font-medium text-center text-brand-primary hover:bg-brand-secondary/20 transition-colors block">
                    <?= $t['btn_modify'] ?? 'Modifier' ?>
                </a>
            </div>

        </div>

        <div class="grid grid-cols-2 gap-x-4 gap-y-3 w-full mt-24">
            <button class="w-full bg-brand-secondary py-2 rounded-full shadow-lg text-[13px] font-medium text-brand-primary hover:bg-brand-secondary/20 transition-colors">
                <?= $t['help_support'] ?? 'Assistance' ?>
            </button>
            <button class="w-full bg-brand-secondary py-2 rounded-full shadow-lg text-[13px] font-medium text-brand-primary hover:bg-brand-secondary/20 transition-colors">
                <?= $t['privacy'] ?? 'Confidentialité' ?>
            </button>

            <a href="logout.php" class="w-full bg-brand-secondary py-2 rounded-full shadow-lg text-[13px] font-medium text-center text-brand-primary hover:bg-brand-secondary/20 transition-colors block leading-loose">
                <?= $t['logout'] ?? 'Se déconnecter' ?>
            </a>
            <button class="w-full bg-brand-secondary py-2 rounded-full shadow-lg text-[13px] font-medium text-brand-primary hover:bg-brand-secondary/20 transition-colors">
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
