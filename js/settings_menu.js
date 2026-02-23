function toggleMenu() {
    const menu = document.getElementById('settings-menu');
    
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        setTimeout(() => menu.classList.remove('opacity-0'), 10);
    } else {
        menu.classList.add('opacity-0');
        document.body.classList.remove('overflow-hidden');    
        setTimeout(() => menu.classList.add('hidden'), 300);
    }
}