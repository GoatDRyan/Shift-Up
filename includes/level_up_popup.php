<?php 
if (isset($_SESSION['level_up'])): 
    $new_level = $_SESSION['level_up'];
    unset($_SESSION['level_up']); 
?>

<div id="levelup-modal" class="fixed inset-0 z-[250] flex items-center justify-center bg-brand-dark/90 backdrop-blur-sm p-4 transition-opacity duration-300">
    <div class="relative w-full max-w-sm bg-brand-primary rounded-3xl p-8 pb-10 shadow-2xl flex flex-col items-center animate-pop">

        <img class="w-32 h-32 mb-2 -mt-16 drop-shadow-xl" src="../../img/level/mascotte-levelUp.svg" alt="Level Up">

        <h2 class="font-display text-3xl font-black text-brand-secondary uppercase tracking-widest text-center mt-4 mb-2">
            <?= htmlspecialchars($t['level_up_title'] ?? 'Niveau Supérieur !') ?>
        </h2>
        
        <p class="text-brand-secondary text-center text-sm font-bold mb-6 opacity-80">
            <?= htmlspecialchars($t['level_up_desc'] ?? 'Félicitations, vous venez d\'atteindre le niveau') ?> 
            <span class="text-brand-dark text-4xl block mt-2 font-black"><?= htmlspecialchars($new_level) ?></span>
        </p>

        <button type="button" onclick="closeLevelUpModal()" class="w-full py-4 rounded-xl bg-brand-secondary text-brand-primary font-bold shadow-lg hover:opacity-90 transition active:scale-95 text-lg">
            <?= htmlspecialchars($t['btn_amazing'] ?? 'Incroyable !') ?>
        </button>
    </div>
</div>

<script>
    document.body.classList.add('overflow-hidden');

    function closeLevelUpModal() {
        const modal = document.getElementById('levelup-modal');
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.classList.remove('overflow-hidden');
        }, 300);
    }
</script>

<style>
    @keyframes popIn {
        0% { transform: scale(0.8) translateY(20px); opacity: 0; }
        100% { transform: scale(1) translateY(0); opacity: 1; }
    }
    .animate-pop {
        animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }
</style>

<?php endif; ?>