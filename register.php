<?php
session_start();
require_once 'db_connect.php';

$error = null;
$expected_fields = ['prenom', 'nom', 'code_entreprise', 'departement'];

// On ne traite que si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_valid = true;
    $data = [];

    // BOUCLE DE VALIDATION
    foreach ($expected_fields as $field) {
        if (isset($_POST[$field]) && !empty(trim($_POST[$field]))) {
            $data[$field] = trim($_POST[$field]);
        } else {
            $is_valid = false;
        }
    }

    if ($is_valid) {
        // VÉRIFICATIONS SPÉCIFIQUES
        $sql_check_company = "SELECT id FROM companies WHERE code = ?";
        $stmt_check_company = $conn->prepare($sql_check_company);
        $stmt_check_company->bind_param("s", $_SESSION['registration']['code_entreprise']);
        $stmt_check_company->execute();
        $result_check_company = $stmt_check_company->get_result();
        if ($result_check_company->num_rows === 0) {
            $error = "Le code entreprise est invalide.";
        } else {
            $company = $result_check_company->fetch_assoc();
            $_SESSION['registration']['code_entreprise'] = $company['id']; // On remplace le code par l'id de l'entreprise
            $stmt_check_company->close();
        }


        $sql_check_dept = "SELECT id FROM departments WHERE id = ? AND company_id = ?";
        $stmt_check_dept = $conn->prepare($sql_check_dept);
        $stmt_check_dept->bind_param("ss", $_SESSION['registration']['departement'], $_SESSION['registration']['code_entreprise']);
        $stmt_check_dept->execute();
        $result_check_dept = $stmt_check_dept->get_result();            
        if ($result_check_dept->num_rows === 0) {
            $error = "Le département sélectionné est invalide pour cette entreprise.";
        } else {
            $dep = $result_check_dept->fetch_assoc();
            $_SESSION['registration']['departement'] = $dep['id'];
            $stmt_check_dept->close(); 

        }
        $_SESSION['registration'] = $data;
        header("Location: register_process.php");
        exit();
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'tailwindcss.html'; ?>
    <title>Inscription - Shift'Up</title>
</head>
<body class="bg-black text-white font-sans min-h-screen flex flex-col items-center justify-center px-4">
    <div class="text-center mb-8 flex flex-col items-center justify-center">
        <div class="w-60 h-3 bg-white rounded-full overflow-hidden mx-auto mb-4 mt-4">
            <div class="w-1/3 rounded-full h-full bg-green-500"></div>
        </div>
        
        <h2 class="mt-6 text-3xl font-bold tracking-tight text-white">Inscription</h2>
        
        <?php if ($error): ?>
            <div class="w-[85%]  mt-4 p-3 bg-red-900/50 border border-red-500 text-red-200 text-sm rounded-lg">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
    </div>
        
    <form action="" method="POST" class="w-[75%]">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-400">Prénom *</label>
                <input type="text" name="prenom" 
                       value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" 
                       class="mt-1 block w-full bg-gray-900 border border-gray-700 rounded-md py-2 px-3 text-white focus:ring-2 focus:ring-green-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400">Nom *</label>
                <input type="text" name="nom" 
                       value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                       class="mt-1 block w-full bg-gray-900 border border-gray-700 rounded-md py-2 px-3 text-white focus:ring-2 focus:ring-green-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400">Code entreprise *</label>
                <input type="text" name="code_entreprise" 
                       value="<?= htmlspecialchars($_POST['code_entreprise'] ?? '') ?>"
                       class="mt-1 block w-full bg-gray-900 border border-gray-700 rounded-md py-2 px-3 text-white focus:ring-2 focus:ring-green-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400">Département *</label>
                <select name="departement" class="mt-1 block w-full bg-gray-900 border border-gray-700 rounded-md py-2 px-3 text-white focus:ring-2 focus:ring-green-500 outline-none">
                    <?php
                    $deps = ['informatique', 'marketing', 'finance', 'ressources_humaines', 'autre'];
                    foreach ($deps as $d): 
                        $selected = (isset($_POST['departement']) && $_POST['departement'] == $d) ? 'selected' : '';
                    ?>
                        <option value="<?= $d ?>" <?= $selected ?>><?= ucfirst(str_replace('_', ' ', $d)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" class="mt-8 w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-full transition duration-300 shadow-lg shadow-green-900/20">
            Étape suivante
        </button>
    </form>
</body>
</html>