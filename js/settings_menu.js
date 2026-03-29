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

function updateThemeBtn() {
    const btn = document.getElementById('theme-toggle-btn');
    if (document.documentElement.classList.contains('dark')) {
        btn.innerHTML = `<i class="fa-solid fa-moon mr-1"></i> ${textDark}`;
    } else {
        btn.innerHTML = `<i class="fa-solid fa-sun mr-1"></i> ${textLight}`;
    }
}

function toggleTheme() {
    document.documentElement.classList.toggle('dark');
    if (document.documentElement.classList.contains('dark')) {
        localStorage.setItem('theme', 'dark');
    } else {
        localStorage.setItem('theme', 'light');
    }
    updateThemeBtn();
}

if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
} else {
    document.documentElement.classList.remove('dark');
}
updateThemeBtn();
