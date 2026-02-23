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
            $user['pseudo'] = $new_name;
            $message = $t['msg_name_success'] ?? "Ton nom a été mis à jour !";
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
        <a href="../index.php" class="text-brand-dark text-xl w-10 h-10 flex items-center justify-center rounded-full hover:bg-brand-secondary transition">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h1 class="font-display font-bold text-xl ml-4"><?= $t['name'] ?? 'Nom' ?></h1>
    </header>

    <main class="flex-1 p-4 flex flex-col items-center mt-10">
        <div class="w-full max-w-sm bg-brand-primary rounded-3xl p-6 shadow-sm border border-brand-border">
            
            <?php if($message): ?>
                <div class="mb-6 p-3 rounded-xl text-sm font-bold text-center <?= $msg_type === 'success' ? 'bg-brand-success/20 text-brand-success' : 'bg-red-100 text-red-600' ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="flex flex-col gap-4">
                <div>
                    <label class="text-[10px] font-bold text-brand-tertiary uppercase tracking-wider mb-2 block">
                        <?= $t['new_name'] ?? 'Nouveau pseudo' ?>
                    </label>
                    <input type="text" name="new_name" value="<?= htmlspecialchars($user['pseudo']) ?>" class="w-full bg-brand-card border border-brand-border rounded-xl px-4 py-3 text-brand-dark font-bold focus:outline-none focus:border-brand-dark transition" required>
                </div>
                
                <button type="submit" class="w-full bg-brand-dark text-brand-primary font-bold py-3 rounded-xl mt-4 hover:bg-black transition shadow-lg">
                    <?= $t['btn_save'] ?? 'Enregistrer' ?>
                </button>
            </form>
        </div>
    </main>
</body>
</html>