<?php
session_start();
require_once '../../config/db_connect.php';

// Gestion de la langue
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'fr';
$t_path = __DIR__ . "/../../lang/$lang.php";
$t = file_exists($t_path) ? require $t_path : [];

$error = null;
$expected_fields = ['prenom', 'nom', 'code_entreprise', 'departement'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_valid = true;
    $data = [];

    foreach ($expected_fields as $field) {
        if (isset($_POST[$field]) && !empty(trim($_POST[$field]))) {
            $data[$field] = trim($_POST[$field]);
        } else {
            $is_valid = false;
        }
    }

    if ($is_valid) {
        $stmt_check_company = $pdo->prepare("SELECT id FROM companies WHERE code_invite = ?");
        $stmt_check_company->execute([$data['code_entreprise']]);
        $company = $stmt_check_company->fetch();

        if (!$company) {
            $error = $t['err_invalid_company'] ?? "Le code entreprise est invalide.";
        } else {
            $data['code_entreprise'] = $company['id'];
            
            $stmt_check_dept = $pdo->prepare("SELECT id FROM departments WHERE id = ? AND company_id = ?");
            $stmt_check_dept->execute([$data['departement'], $company['id']]);
            $dep = $stmt_check_dept->fetch();
            
            if (!$dep) {
                $error = $t['err_invalid_dept'] ?? "Le département sélectionné est invalide pour cette entreprise.";
            } else {
                $data['departement'] = $dep['id'];
                
                $_SESSION['registration'] = $data;
                header("Location: register_process.php");
                exit();
            }
        }
    } else {
        $error = $t['err_empty_fields'] ?? "Veuillez remplir tous les champs obligatoires.";
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../../js/tailwind-config.js"></script>
    <title><?= $t['reg_step1_title'] ?? "Inscription - Étape 1" ?></title>
</head>
<body class="bg-brand-primary text-brand-secondary font-sans min-h-screen flex items-center justify-center p-4 relative">

    <div class="absolute top-6 right-6 flex items-center gap-2 z-50">
        <a href="?lang=fr" class="text-xs font-bold uppercase transition <?= $lang === 'fr' ? 'text-brand-secondary opacity-100' : 'text-brand-secondary opacity-50 hover:opacity-100' ?>">FR</a>
        <span class="text-brand-secondary opacity-30 text-[10px]">|</span>
        <a href="?lang=en" class="text-xs font-bold uppercase transition <?= $lang === 'en' ? 'text-brand-secondary opacity-100' : 'text-brand-secondary opacity-50 hover:opacity-100' ?>">EN</a>
    </div>

    <div class="w-full max-w-md">
        <div class="text-center flex flex-col items-center justify-center mb-8">
            <div class="w-48 h-2 bg-brand-secondary/20 rounded-full overflow-hidden mx-auto mb-6">
                <div class="w-1/2 h-full bg-brand-secondary rounded-full"></div>
            </div>
            
            <h2 class="text-4xl font-display font-black tracking-tight text-brand-secondary mb-2">
                <?= $t['register'] ?? "Inscription" ?>
            </h2>
            <p class="text-brand-secondary opacity-80 text-sm font-bold"><?= $t['reg_step1_desc'] ?? "Commençons par faire connaissance." ?></p>
        </div>

        <?php if ($error): ?>
            <div class="bg-brand-secondary text-brand-primary font-bold text-sm p-4 rounded-xl mb-8 text-center flex items-center justify-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
            
        <form action="" method="POST" class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-brand-secondary uppercase tracking-widest mb-2 pl-1"><?= $t['firstname'] ?? "Prénom" ?> *</label>
                    <input type="text" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required
                        class="w-full bg-transparent border-2 border-brand-secondary/30 rounded-xl py-3 px-4 text-brand-secondary focus:outline-none focus:border-brand-secondary transition font-medium placeholder-brand-secondary/40">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-brand-secondary uppercase tracking-widest mb-2 pl-1"><?= $t['lastname'] ?? "Nom" ?> *</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required
                        class="w-full bg-transparent border-2 border-brand-secondary/30 rounded-xl py-3 px-4 text-brand-secondary focus:outline-none focus:border-brand-secondary transition font-medium placeholder-brand-secondary/40">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-brand-secondary uppercase tracking-widest mb-2 pl-1"><?= $t['company_code'] ?? "Code entreprise" ?> *</label>
                <div class="relative flex items-center">
                    <div class="absolute left-4 pointer-events-none text-brand-secondary opacity-60"><i class="fa-solid fa-building"></i></div>
                    
                    <input type="text" id="codeInput" name="code_entreprise" value="<?= htmlspecialchars($_POST['code_entreprise'] ?? '') ?>" required
                        class="w-full bg-transparent border-2 border-brand-secondary/30 rounded-xl py-4 pl-11 pr-4 text-brand-secondary focus:outline-none focus:border-brand-secondary transition font-medium placeholder-brand-secondary/40">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-brand-secondary uppercase tracking-widest mb-2 pl-1"><?= $t['department'] ?? "Département" ?> *</label>
                <div class="relative flex items-center">
                    <div class="absolute left-4 pointer-events-none text-brand-secondary opacity-60"><i class="fa-solid fa-users"></i></div>
                    
                    <select id="deptSelect" name="departement" required disabled class="w-full bg-brand-primary border-2 border-brand-secondary/30 rounded-xl py-4 pl-11 pr-4 text-brand-secondary focus:outline-none focus:border-brand-secondary transition font-medium appearance-none disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="" disabled selected>Entrez d'abord un code entreprise...</option>
                    </select>

                    <div class="absolute right-4 pointer-events-none text-brand-secondary opacity-60"><i class="fa-solid fa-chevron-down"></i></div>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-brand-secondary text-brand-primary text-sm font-bold py-4 rounded-xl hover:opacity-90 transition active:scale-95 flex justify-center items-center gap-2">
                    <?= $t['btn_next_step'] ?? "Étape suivante" ?> <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
            
            <div class="text-center mt-4">
                <a href="login.php" class="text-[10px] font-bold text-brand-secondary opacity-80 hover:opacity-100 transition"><?= $t['already_have_account'] ?? "J'ai déjà un compte" ?></a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('codeInput');
            const deptSelect = document.getElementById('deptSelect');
            
            const previousDept = "<?= htmlspecialchars($_POST['departement'] ?? '') ?>";

            function loadDepartments(code, preselect = '') {
                if(code.trim() === '') {
                    deptSelect.innerHTML = '<option value="" disabled selected>Entrez d\'abord un code entreprise...</option>';
                    deptSelect.disabled = true;
                    return;
                }

                deptSelect.innerHTML = '<option value="" disabled selected>Recherche...</option>';

                fetch('get_departments.php?code=' + encodeURIComponent(code))
                    .then(response => response.json())
                    .then(data => {
                        deptSelect.innerHTML = '<option value="" disabled selected>Sélectionnez votre département...</option>';
                        
                        if(data.length > 0) {
                            deptSelect.disabled = false;
                            data.forEach(dep => {
                                const option = document.createElement('option');
                                option.value = dep.id;
                                option.textContent = dep.nom;
                                if(preselect && preselect == dep.id) {
                                    option.selected = true;
                                }
                                deptSelect.appendChild(option);
                            });
                        } else {
                            deptSelect.innerHTML = '<option value="" disabled selected>Code entreprise invalide</option>';
                            deptSelect.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Erreur AJAX:', error);
                        deptSelect.innerHTML = '<option value="" disabled selected>Erreur de connexion</option>';
                    });
            }

            codeInput.addEventListener('input', function() {
                loadDepartments(this.value);
            });


            if(codeInput.value.trim() !== '') {
                loadDepartments(codeInput.value, previousDept);
            }
        });
    </script>
</body>
</html>