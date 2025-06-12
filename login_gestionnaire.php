<?php
session_start();
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];

    $stmt = $pdo->prepare("SELECT u.*, r.nom AS role_nom FROM utilisateurs u JOIN roles r ON u.role_id = r.id WHERE u.email = ? AND r.nom = 'gestionnaire'");
    $stmt->execute([$email]);
    $utilisateur = $stmt->fetch();

    if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
        $_SESSION['user_id'] = $utilisateur['id'];
        $_SESSION['role'] = $utilisateur['role_nom'];
        $_SESSION['nom_utilisateur'] = $utilisateur['nom_utilisateur'];
        header('Location: dashboard_gestionnaire.php');
        exit;
    } else {
        $erreur = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Gestionnaire</title>
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
        .login-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
        }
        .login-box {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            color: #333;
        }
        h2 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        label {
            display: block;
            text-align: left;
            margin: 10px 0 5px;
            color: #333;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #dc3545;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        button:hover {
            background-color: #b02a37;
        }
        .erreur {
            color: #dc3545;
            margin-bottom: 10px;
        }
        .links {
            margin-top: 20px;
        }
        .links a {
            color: #dc3545;
            text-decoration: none;
            margin: 0 10px;
        }
        .links a:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .login-box {
                width: 90%;
            }
            h2 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <video autoplay muted loop>
        <source src="assets/videos/background.mp4" type="video/mp4">
        Votre navigateur ne supporte pas les vidéos.
    </video>
    <div class="login-container">
        <div class="login-box">
            <h2>Connexion Gestionnaire</h2>
            <?php if (isset($erreur)) : ?>
                <p class="erreur"><?php echo $erreur; ?></p>
            <?php endif; ?>
            <form method="POST">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Entrez votre email" required>
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="Entrez votre mot de passe" required>
                <button type="submit">Se connecter</button>
            </form>
            <div class="links">
                <a href="register_gestionnaire.php">S'inscrire</a> |
                <a href="index.php">Retour à l'accueil</a>
            </div>
        </div>
    </div>
</body>
</html>