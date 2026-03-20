<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Accès interdit</title>
    <link rel="stylesheet" href="/cours/Shift-up/css/style.css">
    <style>
        .error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: bold;
            margin: 20px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .error-message {
            font-size: 32px;
            margin: 20px 0;
        }
        
        .error-description {
            font-size: 16px;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        
        .btn-home {
            padding: 12px 30px;
            background-color: white;
            color: #f5576c;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">403</div>
        <div class="error-message">Accès interdit</div>
        <div class="error-description">
            Tu n'as pas la permission d'accéder à cette ressource.
        </div>
        <a href="../views/users/index.php" class="btn-home">Retour à l'accueil</a>
    </div>
</body>
</html>