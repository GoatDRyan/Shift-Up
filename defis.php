<?php
session_start();
require_once 'db_connect.php';

if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
} elseif (file_exists('functions.php')) {
    require_once 'functions.php';
} 

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 4;
} 

$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'fr';
if (!in_array($lang, ['fr', 'en'])) {
    $lang = 'fr';
}
$textes = require_once "lang/$lang.php";

$sql = "SELECT * FROM challenges";
$stmt = $pdo->query($sql);
$challenges = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title>Les Défis Shift'Up</title>
<link rel="stylesheet" href="css/defis.css" />
<script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {                   
                        brand: {
                            primary: '#ffffff',    
                            secondary: '#d1d5db',  
                            tertiary: '#4b5563',   
                            dark: '#000000',       
                            card: '#111827',       
                            border: '#374151',     
                        }
                    },
                    fontFamily: {
                        display: ['ShiftTitle', 'sans-serif'],
                        body: ['ShiftBody', 'sans-serif'],
                        sans: ['ShiftBody', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body>

    <h1><?= $textes['liste_defis'] ?? 'Liste des Défis' ?></h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div>
            <?= $_SESSION['flash_message']; ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="liste-defis">
        
        <?php foreach($challenges as $defi): ?>

            <?php
            $sql_today = "SELECT COUNT(*) FROM user_actions WHERE user_id = :uid AND challenge_id = :cid AND DATE(date_action) = CURDATE()";
            $stmt_td = $pdo->prepare($sql_today);
            $stmt_td->execute(['uid' => $_SESSION['user_id'], 'cid' => $defi['id']]);
            $today_count = $stmt_td->fetchColumn();

            $progression_totale = 0;
            $percent = 0;
            if ($defi['duration_days'] > 1) {
                $sql_total = "SELECT COUNT(*) FROM user_actions WHERE user_id = :uid AND challenge_id = :cid";
                $stmt_tot = $pdo->prepare($sql_total);
                $stmt_tot->execute(['uid' => $_SESSION['user_id'], 'cid' => $defi['id']]);
                $progression_totale = $stmt_tot->fetchColumn();
                
                $percent = min(100, ($progression_totale / $defi['duration_days']) * 100);
            }
            ?>

            <div class="carte-defi">
                <div>
                    <h3><?= get_trad_bdd($defi, 'titre', $lang) ?></h3>
                    
                    <?php 
                    $colors = ['facile' => '#2ecc71', 'moyen' => '#f1c40f', 'difficile' => '#e74c3c'];
                    $c = $colors[$defi['difficulty']] ?? '#ccc'; 
                    ?>
                    <span class="badge-diff"><?= ucfirst($defi['difficulty']) ?></span>
                </div>

                <p><?= get_trad_bdd($defi, 'descr', $lang) ?></p>

                <?php if ($defi['duration_days'] > 1): ?>
                    <div class="progress-bg">
                        <div class="progress-bar"></div>
                    </div>
                    <small>Objectif : <?= $progression_totale ?> / <?= $defi['duration_days'] ?> étapes</small>
                <?php else: ?>
                    <small>Gain immédiat : +<?= $defi['xp_gain'] ?> XP à chaque validation</small>
                <?php endif; ?>


                <div>
                    <?php
                    $is_finished_long_term = ($defi['duration_days'] > 1 && $progression_totale >= $defi['duration_days']);
                    
                    $is_limit_today_reached = ($today_count >= $defi['max_actions_day']);
                    ?>

                    <?php if ($is_finished_long_term): ?>
                        <button disabled>Défi Terminé</button>
                    
                    <?php elseif ($is_limit_today_reached): ?>
                        <button disabled>Reviens demain (Max atteint)</button>
                    
                    <?php else: ?>
                        <form action="validate_mission.php" method="POST">
                            <input type="hidden" name="challenge_id" value="<?= $defi['id'] ?>">
                            <button type="submit">
                                <?php if ($defi['duration_days'] > 1): ?>
                                    Avancer (+1)
                                <?php else: ?>
                                    Valider (+<?= $defi['xp_gain'] ?> XP)
                                <?php endif; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div> 

        <?php endforeach; ?>
    </div>

</body>
</html>