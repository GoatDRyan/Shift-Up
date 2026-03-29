<?php
require_once '../includes/init.php';

$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_pass = $_POST['old_pass'] ?? '';
    $new_pass = $_POST['new_pass'] ?? '';
    $confirm_pass = $_POST['confirm_pass'] ?? '';

    if (!password_verify($old_pass, $user['password_hash'])) {
        $message = $t['msg_pass_wrong'] ?? "L'ancien mot de passe est incorrect.";
        $msg_type = 'error';
    } 
    elseif ($new_pass !== $confirm_pass) {
        $message = $t['msg_pass_match'] ?? "Les nouveaux mots de passe ne correspondent pas.";
        $msg_type = 'error';
    } 
    elseif (strlen($new_pass) < 6) {
        $message = $t['msg_pass_length'] ?? "Le mot de passe doit faire au moins 6 caractères.";
        $msg_type = 'error';
    } 
    else {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        
        if ($stmt->execute([$new_hash, $user_id])) {
            $user['password_hash'] = $new_hash; 
            $message = $t['msg_pass_success'] ?? "Mot de passe modifié avec succès !";
            $msg_type = 'success';
        } else {
            $message = $t['msg_error'] ?? "Une erreur est survenue lors de la mise à jour.";
            $msg_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['password'] ?? 'Mot de passe' ?> - Shift'Up</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/tailwind-config.js"></script>
</head>
<body class="bg-brand-card text-brand-dark font-sans h-screen flex flex-col">
    
    <header class="p-4 flex items-center shadow-sm bg-brand-primary relative z-10">
        <a href="../views/users/profil.php" class="text-brand-dark text-xl w-10 h-10 flex items-center justify-center rounded-full hover:bg-brand-secondary hover:text-brand-primary transition active:scale-95">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h1 class="font-display font-bold text-xl ml-4"><?= $t['password'] ?? 'Mot de passe' ?></h1>
    </header>

    <main class="flex-1 p-4 flex flex-col items-center mt-10">
        <div class="w-full max-w-sm bg-brand-primary rounded-3xl p-6 shadow-lg border border-brand-border/50">
            
            <?php if($message): ?>
                <div class="mb-6 p-4 rounded-2xl text-sm font-bold text-center shadow-inner <?= $msg_type === 'success' ? 'bg-brand-success/10 text-brand-success' : 'bg-red-500/10 text-red-500' ?>">
                    <?php if($msg_type === 'success'): ?>
                        <i class="fa-solid fa-check-circle mr-1"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="flex flex-col gap-4">
                <div>
                    <label class="text-[10px] font-bold text-brand-dark uppercase tracking-wider mb-2 block">
                        <?= $t['old_password'] ?? 'Ancien mot de passe' ?>
                    </label>
                    <input type="password" name="old_pass" class="w-full bg-brand-card border-2 border-brand-border/50 rounded-2xl px-4 py-3 text-brand-dark font-bold focus:outline-none focus:border-brand-secondary transition" required>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-brand-dark uppercase tracking-wider mb-2 block mt-2">
                        <?= $t['new_password'] ?? 'Nouveau mot de passe' ?>
                    </label>
                    <input type="password" name="new_pass" class="w-full bg-brand-card border-2 border-brand-border/50 rounded-2xl px-4 py-3 text-brand-dark font-bold focus:outline-none focus:border-brand-secondary transition" required minlength="6">
                </div>

                <div>
                    <label class="text-[10px] font-bold text-brand-dark uppercase tracking-wider mb-2 block">
                        <?= $t['confirm_password'] ?? 'Confirmer le mot de passe' ?>
                    </label>
                    <input type="password" name="confirm_pass" class="w-full bg-brand-card border-2 border-brand-border/50 rounded-2xl px-4 py-3 text-brand-dark font-bold focus:outline-none focus:border-brand-secondary transition" required minlength="6">
                </div>
                
                <button type="submit" class="w-full bg-brand-secondary text-brand-primary font-bold py-4 rounded-2xl mt-2 hover:opacity-90 transition active:scale-95 shadow-lg">
                    <?= $t['btn_update'] ?? 'Mettre à jour' ?>
                </button>
            </form>
        </div>
    </main>
</body>
</html>