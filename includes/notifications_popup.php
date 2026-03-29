<div id="notifications-modal" class="fixed inset-0 z-[250] hidden flex items-end sm:items-center justify-center bg-brand-dark/80 backdrop-blur-sm transition-opacity duration-300 opacity-0">
    
    <div id="notifications-content" class="bg-brand-primary w-full max-w-md rounded-t-3xl sm:rounded-3xl p-6 pb-10 relative shadow-2xl transform translate-y-full transition-transform duration-300 flex flex-col max-h-[85vh]">
        
        <button onclick="closeNotifications()" class="absolute top-5 right-5 w-8 h-8 bg-brand-secondary/10 rounded-full flex items-center justify-center text-brand-secondary hover:bg-brand-secondary hover:text-brand-primary transition active:scale-95">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <h2 class="text-center text-xl mb-6 font-display font-black uppercase tracking-widest text-brand-secondary">
            <?= $t['notifications_title'] ?? 'Annonces' ?>
        </h2>

        <div class="flex-1 flex flex-col items-center justify-center py-10 opacity-50">
            <i class="fa-regular fa-bell-slash text-4xl text-brand-dark mb-4"></i>
            <p class="text-sm font-bold text-brand-dark text-center">
                <?= $t['no_notifications'] ?? "Aucune annonce pour le moment." ?>
            </p>
        </div>
            
    </div>
</div>

<script>
    function openNotifications() {
        const modal = document.getElementById('notifications-modal');
        const content = document.getElementById('notifications-content');
        
        document.body.classList.add('overflow-hidden');
        modal.classList.remove('hidden');
        
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('translate-y-full');
        }, 10);
    }

    function closeNotifications() {
        const modal = document.getElementById('notifications-modal');
        const content = document.getElementById('notifications-content');
        
        modal.classList.add('opacity-0');
        content.classList.add('translate-y-full');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }, 300);
    }
</script>