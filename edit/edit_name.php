<?php
require_once '../includes/init.php';

$message = '';
$msg_type = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['new_name'] ?? '');
    if (strlen($new_name) < 3 || strlen($new_name) > 20) {
        $message = $t['msg_name_length'] ?? "Le nom doit faire entre 3 et 20 caractères.";
        $msg_type = 'error';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET pseudo = ? WHERE id = ?");
        
        if ($stmt->execute([$new_name, $user_id])) {
            $user['pseudo'] = $new_name; // Mise à jour de la variable locale
            $message = $t['msg_name_success'] ?? "Ton pseudo a été mis à jour !";
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
    <title><?= $t['name'] ?? 'Nom' ?> - Shift'Up</title>
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
        <h1 class="font-display font-bold text-xl ml-4"><?= $t['name'] ?? 'Nom' ?></h1>
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

            <form method="POST" action="" class="flex flex-col gap-5">
                <div>
                    <label class="text-[10px] font-bold text-brand-tertiary uppercase tracking-wider mb-2 block">
                        <?= $t['new_name'] ?? 'Nouveau pseudo' ?>
                    </label>
                    <input type="text" name="new_name" value="<?= htmlspecialchars($user['pseudo']) ?>" class="w-full bg-brand-card border-2 border-brand-border/50 rounded-2xl px-4 py-3 text-brand-dark font-bold focus:outline-none focus:border-brand-secondary transition" required minlength="3" maxlength="20">
                </div>
                
                <button type="submit" class="w-full bg-brand-secondary text-brand-primary font-bold py-4 rounded-2xl mt-2 hover:opacity-90 transition active:scale-95 shadow-lg">
                    <?= $t['btn_save'] ?? 'Enregistrer' ?>
                </button>
            </form>
        </div>
    </main>
</body>
</html>