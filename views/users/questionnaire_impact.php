<?php
require_once '../../includes/init.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. DÉFINITION DES VALEURS AUTORISÉES (en entiers stricts)
    $allowed_q1 = [3500, 2000, 800, 0];
    $allowed_q2 = [4000, 1500, 500, 0];
    $allowed_q3 = [3000, 1500, 900, 400];
    $allowed_q4 = [3500, 2000, 600];
    $allowed_q5 = [2500, 1500, 500];

    // 2. RÉCUPÉRATION ET CASTING EN ENTIERS
    $q1 = isset($_POST['q1']) ? (int)$_POST['q1'] : null;
    $q2 = isset($_POST['q2']) ? (int)$_POST['q2'] : null;
    $q3 = isset($_POST['q3']) ? (int)$_POST['q3'] : null;
    $q4 = isset($_POST['q4']) ? (int)$_POST['q4'] : null;
    $q5 = isset($_POST['q5']) ? (int)$_POST['q5'] : null;

    // 3. VALIDATION SERVEUR STRICTE (Plus de valeurs par défaut "biaisées")
    if (
        in_array($q1, $allowed_q1, true) &&
        in_array($q2, $allowed_q2, true) &&
        in_array($q3, $allowed_q3, true) &&
        in_array($q4, $allowed_q4, true) &&
        in_array($q5, $allowed_q5, true)
    ) {
        // Part incompressible (Services publics, infrastructures, santé...)
        $services_societaux = 1500; 

        // Calcul du score total
        $total_footprint = $services_societaux + $q1 + $q2 + $q3 + $q4 + $q5;

        // Sauvegarde en BDD
        $stmt = $pdo->prepare("UPDATE users SET initial_footprint_kg = ? WHERE id = ?");
        $stmt->execute([$total_footprint, $user_id]);
        
        header("Location: index.php");
        exit();
    } else {
        // Quelqu'un a essayé de manipuler le formulaire ou a oublié une question
        $error = $t['err_quiz_invalid'] ?? "Veuillez répondre à toutes les questions avec des valeurs valides.";
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['quiz_title'] ?? 'Bilan Carbone' ?> - Shift'Up</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../../js/tailwind-config.js"></script>
    <style>
        input[type="radio"] { display: none; }
        input[type="radio"]:checked + label {
            background-color: var(--brand-secondary, #111827);
            color: var(--brand-primary, #ffffff);
            border-color: var(--brand-secondary, #111827);
        }
    </style>
</head>
<body class="bg-brand-primary text-brand-secondary font-sans h-screen flex flex-col overflow-hidden">
    
    <header class="p-4 pt-8 flex items-center justify-between relative z-10">
        <a href="index.php" class="text-brand-secondary opacity-60 hover:opacity-100 text-xl w-10 h-10 flex items-center justify-center rounded-full transition">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="flex gap-1.5">
            <div id="dot-1" class="w-2.5 h-2.5 rounded-full bg-brand-secondary transition-colors"></div>
            <div id="dot-2" class="w-2.5 h-2.5 rounded-full bg-brand-secondary/30 transition-colors"></div>
            <div id="dot-3" class="w-2.5 h-2.5 rounded-full bg-brand-secondary/30 transition-colors"></div>
            <div id="dot-4" class="w-2.5 h-2.5 rounded-full bg-brand-secondary/30 transition-colors"></div>
            <div id="dot-5" class="w-2.5 h-2.5 rounded-full bg-brand-secondary/30 transition-colors"></div>
        </div>
        <div class="w-10"></div> 
    </header>

    <div class="text-center px-6 mt-2 mb-4">
        <h1 class="font-display font-black text-4xl"><?= $t['quiz_title'] ?? 'Bilan Carbone' ?></h1>
        <p class="text-brand-secondary opacity-80 text-sm mt-2 font-bold leading-tight">
            <?= $t['quiz_subtitle'] ?? 'Évaluons votre empreinte annuelle.' ?>
            <br>
            <span class="text-[10px] font-normal opacity-70">
                <?= $t['quiz_societal'] ?? '(Inclut une part fixe de 1,5t pour les services publics)' ?>
            </span>
        </p>
    </div>

    <?php if ($error): ?>
        <div class="mx-4 bg-brand-secondary text-brand-primary font-bold text-sm p-4 rounded-xl mb-4 text-center flex items-center justify-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <main class="flex-1 px-4 overflow-y-auto pb-24 custom-scrollbar">
        <form id="quiz-form" method="POST" action="">
            
            <div id="step-1" class="fade-in flex flex-col gap-3">
                <h2 class="text-lg font-bold mb-2 text-center uppercase tracking-widest text-[10px]"><?= $t['q1_title'] ?? '1. Trajets quotidiens ?' ?></h2>
                
                <input type="radio" name="q1" id="q1_a1" value="3500" required onclick="autoNext(1)">
                <label for="q1_a1" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-car text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q1_a1'] ?? 'Voiture thermique (plus de 30km/jour ou seul)' ?></span>
                </label>

                <input type="radio" name="q1" id="q1_a2" value="2000" onclick="autoNext(1)">
                <label for="q1_a2" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-car-battery text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q1_a2'] ?? 'Voiture électrique ou thermique occasionnel (<10km/j)' ?></span>
                </label>

                <input type="radio" name="q1" id="q1_a3" value="800" onclick="autoNext(1)">
                <label for="q1_a3" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-bus text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q1_a3'] ?? 'Transports en commun ou Covoiturage régulier' ?></span>
                </label>

                <input type="radio" name="q1" id="q1_a4" value="0" onclick="autoNext(1)">
                <label for="q1_a4" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-person-biking text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q1_a4'] ?? 'Vélo, marche ou 100% télétravail' ?></span>
                </label>
            </div>

            <div id="step-2" class="hidden flex flex-col gap-3">
                <h2 class="text-lg font-bold mb-2 text-center uppercase tracking-widest text-[10px]"><?= $t['q2_title'] ?? '2. Voyages en avion ?' ?></h2>
                
                <input type="radio" name="q2" id="q2_a1" value="4000" onclick="autoNext(2)">
                <label for="q2_a1" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-plane-departure text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q2_a1'] ?? 'Fréquent ou vols long-courriers (> 5h)' ?></span>
                </label>

                <input type="radio" name="q2" id="q2_a2" value="1500" onclick="autoNext(2)">
                <label for="q2_a2" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-plane text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q2_a2'] ?? 'Occasionnel (1 à 3 vols Europe/an)' ?></span>
                </label>

                <input type="radio" name="q2" id="q2_a3" value="500" onclick="autoNext(2)">
                <label for="q2_a3" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-plane-arrival text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q2_a3'] ?? 'Rare (1 vol court aller-retour par an max)' ?></span>
                </label>

                <input type="radio" name="q2" id="q2_a4" value="0" onclick="autoNext(2)">
                <label for="q2_a4" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-plane-slash text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q2_a4'] ?? 'Jamais, je reste au sol !' ?></span>
                </label>
            </div>

            <div id="step-3" class="hidden flex flex-col gap-3">
                <h2 class="text-lg font-bold mb-2 text-center uppercase tracking-widest text-[10px]"><?= $t['q3_title'] ?? '3. Alimentation ?' ?></h2>
                
                <input type="radio" name="q3" id="q3_a1" value="3000" onclick="autoNext(3)">
                <label for="q3_a1" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-burger text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q3_a1'] ?? 'Viande rouge très régulière (3+ repas/sem)' ?></span>
                </label>

                <input type="radio" name="q3" id="q3_a2" value="1500" onclick="autoNext(3)">
                <label for="q3_a2" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-drumstick-bite text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q3_a2'] ?? 'Viande blanche / Poisson régulier' ?></span>
                </label>

                <input type="radio" name="q3" id="q3_a3" value="900" onclick="autoNext(3)">
                <label for="q3_a3" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-egg text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q3_a3'] ?? 'Végétarien (œufs, produits laitiers)' ?></span>
                </label>

                <input type="radio" name="q3" id="q3_a4" value="400" onclick="autoNext(3)">
                <label for="q3_a4" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-leaf text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q3_a4'] ?? 'Végétalien / Vegan' ?></span>
                </label>
            </div>

            <div id="step-4" class="hidden flex flex-col gap-3">
                <h2 class="text-lg font-bold mb-2 text-center uppercase tracking-widest text-[10px]"><?= $t['q4_title'] ?? '4. Type de logement ?' ?></h2>
                
                <input type="radio" name="q4" id="q4_a1" value="3500" onclick="autoNext(4)">
                <label for="q4_a1" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-house-crack text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q4_a1'] ?? 'Maison ancienne / Chauffage au fioul ou gaz' ?></span>
                </label>

                <input type="radio" name="q4" id="q4_a2" value="2000" onclick="autoNext(4)">
                <label for="q4_a2" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-building text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q4_a2'] ?? 'Appartement classique ou Maison bien isolée' ?></span>
                </label>

                <input type="radio" name="q4" id="q4_a3" value="600" onclick="autoNext(4)">
                <label for="q4_a3" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-house-circle-check text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q4_a3'] ?? 'Logement très performant (récent ou pompe à chaleur)' ?></span>
                </label>
            </div>

            <div id="step-5" class="hidden flex flex-col gap-3">
                <h2 class="text-lg font-bold mb-2 text-center uppercase tracking-widest text-[10px]"><?= $t['q5_title'] ?? '5. Achats & Numérique ?' ?></h2>
                
                <input type="radio" name="q5" id="q5_a1" value="2500" onclick="showSubmit()">
                <label for="q5_a1" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-boxes-stacked text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q5_a1'] ?? 'Achats fréquents (fast-fashion, tech neuve)' ?></span>
                </label>

                <input type="radio" name="q5" id="q5_a2" value="1500" onclick="showSubmit()">
                <label for="q5_a2" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-cart-shopping text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q5_a2'] ?? 'Consommation moyenne et raisonnée' ?></span>
                </label>

                <input type="radio" name="q5" id="q5_a3" value="500" onclick="showSubmit()">
                <label for="q5_a3" class="bg-transparent border-2 border-brand-secondary/30 text-brand-secondary p-4 rounded-2xl cursor-pointer transition shadow-sm font-bold flex items-center gap-4 hover:border-brand-secondary">
                    <i class="fa-solid fa-recycle text-xl w-8 text-center"></i> 
                    <span class="text-sm leading-tight"><?= $t['q5_a3'] ?? 'Minimaliste, 100% seconde main ou reconditionné' ?></span>
                </label>
            </div>

            <div id="submit-container" class="hidden mt-8">
                <button type="submit" class="w-full bg-brand-secondary text-brand-primary font-bold py-4 rounded-xl hover:opacity-90 active:scale-95 transition shadow-lg text-lg fade-in flex justify-center items-center gap-2">
                    <?= $t['btn_calculate'] ?? 'Calculer mon impact' ?> <i class="fa-solid fa-calculator"></i>
                </button>
            </div>

        </form>
    </main>

    <script>
        function autoNext(currentStep) {
            setTimeout(() => {
                document.getElementById('step-' + currentStep).classList.add('hidden');
                const nextStep = currentStep + 1;
                const nextDiv = document.getElementById('step-' + nextStep);
                nextDiv.classList.remove('hidden');
                nextDiv.classList.add('fade-in');
                document.getElementById('dot-' + nextStep).classList.replace('bg-brand-secondary/30', 'bg-brand-secondary');
            }, 250);
        }

        function showSubmit() {
            setTimeout(() => {
                document.getElementById('submit-container').classList.remove('hidden');
            }, 250);
        }
    </script>
</body>
</html>