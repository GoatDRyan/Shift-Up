<div id="settings-menu" class="fixed inset-0 bg-brand-dark/95 z-50 hidden flex flex-col justify-center items-center opacity-0 transition-opacity duration-300">
    <button onclick="toggleMenu()" class="absolute top-6 right-6 text-brand-primary text-2xl">
        <i class="fa-solid fa-xmark"></i>
    </button>
    <h2 class="font-display text-brand-primary text-2xl font-bold mb-6"><?= $t['settings'] ?></h2>
    <p class="text-brand-tertiary text-sm mb-3"><?= $t['choose_lang'] ?></p>
    <div class="flex gap-4 mb-8">
        <a href="?lang=fr" class="border border-brand-tertiary px-6 py-2 rounded-full text-sm font-bold transition <?= $lang == 'fr' ? 'bg-brand-primary text-brand-dark' : 'text-brand-tertiary hover:text-brand-primary' ?>">Français 🇫🇷</a>
        <a href="?lang=en" class="border border-brand-tertiary px-6 py-2 rounded-full text-sm font-bold transition <?= $lang == 'en' ? 'bg-brand-primary text-brand-dark' : 'text-brand-tertiary hover:text-brand-primary' ?>">English 🇬🇧</a>
    </div>
    <nav class="flex flex-col gap-6 text-center text-xl w-full px-10 text-brand-primary">
        <a href="#" class="hover:text-brand-tertiary border-b border-brand-tertiary pb-4"><?= $t['account'] ?></a>
        <a href="#" class="hover:text-brand-tertiary border-b border-brand-tertiary pb-4"><?= $t['privacy'] ?></a>
        <a href="logout.php" class="text-brand-accent mt-4 font-bold"><?= $t['logout'] ?></a>
    </nav>
</div>