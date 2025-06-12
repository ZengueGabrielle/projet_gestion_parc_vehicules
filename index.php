<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Gestion du Parc Automobile</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #fff;
            overflow: hidden;
        }
        video {
            position: fixed;
            top: 0;
            left: 0;
            min-width: 100%;
            min-height: 100%;
            z-index: -1;
            object-fit: cover;
        }
        .container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
        }
        h1 {
            font-size: 2.5em;
            margin-bottom: 40px;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }
        .role-buttons {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .role-button {
            background-color: #dc3545; /* Rouge */
            color: #fff;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1.2em;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
        }
        .role-button:hover {
            background-color: #b02a37;
        }
        @media (max-width: 768px) {
            h1 {
                font-size: 2em;
            }
            .role-button {
                padding: 10px 20px;
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <video autoplay muted loop>
        <source src="assets/videos/background.mp4" type="video/mp4">
        Votre navigateur ne supporte pas les vidéos.
    </video>
    <div class="container">
        <h1>Gestion du Parc Automobile</h1>
        <div class="role-buttons">
            <a href="login_admin.php" class="role-button">Administrateur</a>
            <a href="login_gestionnaire.php" class="role-button">Gestionnaire</a>
            <a href="login_utilisateur.php" class="role-button">Utilisateur</a>
        </div>
    </div>
</body>
</html>