function toggleMenu() {
    const menu = document.getElementById('settings-menu');
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        setTimeout(() => menu.classList.remove('opacity-0'), 10);
    } else {
        menu.classList.add('opacity-0');
        setTimeout(() => menu.classList.add('hidden'), 300);
    }
}