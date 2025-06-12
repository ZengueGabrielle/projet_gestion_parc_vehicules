<?php
session_start();
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $nom_utilisateur = $_POST['nom_utilisateur'];
    $email = $_POST['email'];
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, nom_utilisateur, email, mot_de_passe, role_id) VALUES (?, ?, ?, ?, ?, (SELECT id FROM roles WHERE nom = 'gestionnaire'))");
        $stmt->execute([$nom, $prenom, $nom_utilisateur, $email, $mot_de_passe]);
        header('Location: login_gestionnaire.php');
        exit;
    } catch (PDOException $e) {
        $erreur = "Erreur lors de l'inscription : " . ($e->getCode() == 23000 ? "Nom d'utilisateur ou email déjà utilisé." : $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Gestionnaire</title>
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
        .register-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
        }
        .register-box {
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
            .register-box {
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
    <div class="register-container">
        <div class="register-box">
            <h2>Inscription Gestionnaire</h2>
            <?php if (isset($erreur)) : ?>
                <p class="erreur"><?php echo $erreur; ?></p>
            <?php endif; ?>
            <form method="POST">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" placeholder="Entrez votre nom" required>
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" placeholder="Entrez votre prénom" required>
                <label for="nom_utilisateur">Nom d'utilisateur</label>
                <input type="text" id="nom_utilisateur" name="nom_utilisateur" placeholder="Entrez votre nom d'utilisateur" required>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Entrez votre email" required>
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="Entrez votre mot de passe" required>
                <button type="submit">S'inscrire</button>
            </form>
            <div class="links">
                <a href="index.php">Retour à l'accueil</a>
            </div>
        </div>
    </div>
</body>
</html>