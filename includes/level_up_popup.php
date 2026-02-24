<?php 
if (isset($_SESSION['level_up'])): 
    $new_level = $_SESSION['level_up'];
    unset($_SESSION['level_up']); 
?>

<script>document.body.classList.add('overflow-hidden');</script>

<div id="levelup-modal" class="fixed inset-0 z-[200] flex items-center justify-center bg-brand-dark/80 backdrop-blur-sm p-4">
    <div class="relative w-full max-w-[320px] bg-brand-primary rounded-3xl p-8 pb-10 shadow-2xl flex flex-col items-center animate-pop">
        
        <div class="absolute -top-10 w-24 h-24 bg-brand-tertiary rounded-full flex items-center justify-center shadow-lg border-4 border-brand-primary z-10">
            <i class="fa-solid fa-arrow-up text-4xl text-brand-primary"></i>
        </div>

        <h2 class="font-display text-3xl font-black text-brand-dark mt-12 mb-1 uppercase tracking-widest text-center">Level Up !</h2>
        <p class="text-[15px] font-bold text-brand-tertiary text-center mb-6">Tu as atteint le niveau <?= htmlspecialchars($new_level) ?></p>

        <button type="button" onclick="closeLevelUpModal()" class="w-full py-4 rounded-xl bg-brand-dark text-brand-primary font-bold shadow-lg hover:bg-black transition active:scale-95 text-lg">
            Incroyable !
        </button>
    </div>
</div>

<script>
    function closeLevelUpModal() {
        document.getElementById('levelup-modal').style.display = 'none';
        document.body.classList.remove('overflow-hidden');
    }
</script>

<style>
    @keyframes popIn {
        0% { transform: scale(0.8); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }
    .animate-pop {
        animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }
</style>

<?php endif; ?>