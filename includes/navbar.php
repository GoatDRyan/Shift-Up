<nav class="fixed bottom-0 w-full bg-brand-primary border-t border-brand-border h-20 max-[375px]:h-16 flex justify-around items-center px-2 z-40 pb-safe shadow-[0_-4px_10px_rgba(0,0,0,0.02)]">
        
    <a href="shop.php" class="group flex flex-col items-center w-14 transition">
        <img class="h-[24px] mb-1 group-hover:hidden <?= $current_page == 'shop.php' ? 'hidden' : 'block' ?>" src="../../img/icone/icone-boutique.svg" alt="Boutique">
        <img class="h-[24px] mb-1 hidden group-hover:block <?= $current_page == 'shop.php' ? '!block' : '' ?>" src="../../img/icone/icone-boutique-hover.svg" alt="Boutique">
        <span class="text-[10px] font-bold text-brand-tertiary group-hover:text-brand-dark transition-colors <?= $current_page == 'shop.php' ? '!text-brand-dark' : '' ?>"><?= $t['nav_shop'] ?></span>
    </a>

    <a href="defis.php" class="group flex flex-col items-center w-14 transition">
        <img class="h-[28px] mb-1 group-hover:hidden <?= $current_page == 'defis.php' ? 'hidden' : 'block' ?>" src="../../img/icone/icone-defis.svg" alt="Défis">
        <img class="h-[28px] mb-1 hidden group-hover:block <?= $current_page == 'defis.php' ? '!block' : '' ?>" src="../../img/icone/icone-defis-hover.svg" alt="Défis">
        <span class="text-[10px] font-bold text-brand-tertiary group-hover:text-brand-dark transition-colors <?= $current_page == 'defis.php' ? '!text-brand-dark' : '' ?>"><?= $t['nav_defs'] ?></span>
    </a>

    <a href="index.php" class="group relative -top-6 flex flex-col items-center justify-center w-16 h-16 bg-brand-primary rounded-full border-brand-primary shadow-lg shadow-brand-dark/20 transition transform hover:scale-105">
        <img class="h-[26px] group-hover:hidden <?= $current_page == 'index.php' ? 'hidden' : 'block' ?>" src="../../img/icone/icone-home.svg" alt="Accueil">
        <img class="h-[26px] hidden group-hover:block <?= $current_page == 'index.php' ? '!block' : '' ?>" src="../../img/icone/icone-home-hover.svg" alt="Accueil">
    </a>

    <a href="#" class="group flex flex-col items-center w-14 transition">
        <img class="h-[28px] mb-1 group-hover:hidden <?= $current_page == 'reseau.php' ? 'hidden' : 'block' ?>" src="../../img/icone/icone-reseau.svg" alt="Réseau">
        <img class="h-[28px] mb-1 hidden group-hover:block <?= $current_page == 'reseau.php' ? '!block' : '' ?>" src="../../img/icone/icone-reseau-hover.svg" alt="Réseau">
        <span class="text-[10px] font-bold text-brand-tertiary group-hover:text-brand-dark transition-colors <?= $current_page == 'reseau.php' ? '!text-brand-dark' : '' ?>"><?= $t['nav_social'] ?></span>
    </a>

    <a href="profil.php" class="group flex flex-col items-center w-14 transition">
        <img class="h-[28px] mb-1 group-hover:hidden <?= $current_page == 'profil.php' ? 'hidden' : 'block' ?>" src="../../img/icone/icone-compte.svg" alt="Profil">
        <img class="h-[28px] mb-1 hidden group-hover:block <?= $current_page == 'profil.php' ? '!block' : '' ?>" src="../../img/icone/icone-compte-hover.svg" alt="Profil">
        <span class="text-[10px] font-bold text-brand-tertiary group-hover:text-brand-dark transition-colors <?= $current_page == 'profil.php' ? '!text-brand-dark' : '' ?>"><?= $t['nav_prof'] ?></span>
    </a>
</nav>