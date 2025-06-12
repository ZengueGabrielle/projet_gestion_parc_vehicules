<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gestionnaire') {
    header('Location: index.php');
    exit;
}

// Ajouter un utilisateur (rôle utilisateur uniquement)
if (isset($_POST['add_user'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $nom_utilisateur = trim($_POST['nom_utilisateur']);
    $email = trim($_POST['email']);
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);
    $role_id = 3; // Toujours utilisateur

    // Vérifier si l'email existe déjà (hors modification)
    $stmt_check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt_check->execute([$email]);
    if ($stmt_check->rowCount() > 0) {
        $error = "Cet email est déjà utilisé.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, nom_utilisateur, email, mot_de_passe, role_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$nom, $prenom, $nom_utilisateur, $email, $mot_de_passe, $role_id]);
        header('Location: manage_users.php?success=Utilisateur ajouté');
        exit;
    }
}

// Supprimer un utilisateur
if (isset($_POST['delete_user'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: manage_users.php?success=Utilisateur supprimé');
    exit;
}

// Préparer la modification
$edit_user = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ? AND role_id = 3");
    $stmt->execute([$_GET['edit']]);
    $edit_user = $stmt->fetch();
}

// Modifier un utilisateur
if (isset($_POST['update_user'])) {
    $id = intval($_POST['id']);
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $nom_utilisateur = trim($_POST['nom_utilisateur']);
    $email = trim($_POST['email']);

    // Vérifier si l'email existe déjà pour un autre utilisateur
    $stmt_check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
    $stmt_check->execute([$email, $id]);
    if ($stmt_check->rowCount() > 0) {
        $error = "Cet email est déjà utilisé par un autre utilisateur.";
    } else {
        // Si le mot de passe est renseigné, on le met à jour
        if (!empty($_POST['mot_de_passe'])) {
            $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, nom_utilisateur = ?, email = ?, mot_de_passe = ? WHERE id = ? AND role_id = 3");
            $stmt->execute([$nom, $prenom, $nom_utilisateur, $email, $mot_de_passe, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, nom_utilisateur = ?, email = ? WHERE id = ? AND role_id = 3");
            $stmt->execute([$nom, $prenom, $nom_utilisateur, $email, $id]);
        }
        header('Location: manage_users.php?success=Utilisateur modifié');
        exit;
    }
}

// Récupérer uniquement les utilisateurs (role_id = 3)
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE role_id = 3 ORDER BY created_at DESC");
$stmt->execute();
$utilisateurs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            color: #333;
            position: relative;
        }
        video.background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            border: 3px solid #dc3545;
        }
        h1, h2 {
            color: #dc3545;
            text-align: center;
        }
        form {
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-size: 16px;
            margin-bottom: 5px;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #dc3545;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        .btn-primary {
            background: #dc3545;
            color: #fff;
        }
        .btn-primary:hover {
            background: #b02a37;
        }
        .btn-danger {
            background: #dc3545;
            color: #fff;
        }
        .btn-danger:hover {
            background: #b02a37;
        }
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        .btn-edit:hover {
            background: #e0a800;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #dc3545;
            text-align: left;
        }
        th {
            background: #dc3545;
            color: #fff;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        .back-link {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 12px;
            background: #dc3545;
            color: #fff;
            text-align: center;
            text-decoration: none;
            border-radius: 10px;
            font-size: 18px;
            transition: background 0.3s, transform 0.2s;
        }
        .back-link:hover {
            background: #b02a37;
            transform: translateY(-2px);
        }
        .error {
            color: #b02a37;
            text-align: center;
            margin-bottom: 10px;
        }
        .success {
            color: #28a745;
            text-align: center;
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
            h1 {
                font-size: 28px;
            }
            table {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <video class="background-video" autoplay loop muted playsinline>
        <source src="assets/videos/background.mp4" type="video/mp4">
    </video>
    <div class="container">
        <h1>Gestion des Utilisateurs</h1>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['success'])): ?>
            <div class="success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <?php if ($edit_user): ?>
            <h2>Modifier l'utilisateur</h2>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $edit_user['id']; ?>">
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($edit_user['nom']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($edit_user['prenom']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="nom_utilisateur">Nom d'utilisateur</label>
                    <input type="text" id="nom_utilisateur" name="nom_utilisateur" value="<?php echo htmlspecialchars($edit_user['nom_utilisateur']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="mot_de_passe">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe">
                </div>
                <button type="submit" name="update_user" class="btn btn-primary">Enregistrer</button>
                <a href="manage_users.php" class="btn btn-danger" style="margin-left:10px;">Annuler</a>
            </form>
        <?php else: ?>
            <h2>Ajouter un utilisateur</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" required>
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" required>
                </div>
                <div class="form-group">
                    <label for="nom_utilisateur">Nom d'utilisateur</label>
                    <input type="text" id="nom_utilisateur" name="nom_utilisateur" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                </div>
                <button type="submit" name="add_user" class="btn btn-primary">Ajouter</button>
            </form>
        <?php endif; ?>

        <h2>Liste des utilisateurs</h2>
        <table>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Nom d'utilisateur</th>
                <th>Email</th>
                <th>Date de création</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($utilisateurs as $utilisateur): ?>
                <tr>
                    <td><?php echo htmlspecialchars($utilisateur['nom']); ?></td>
                    <td><?php echo htmlspecialchars($utilisateur['prenom']); ?></td>
                    <td><?php echo htmlspecialchars($utilisateur['nom_utilisateur']); ?></td>
                    <td><?php echo htmlspecialchars($utilisateur['email']); ?></td>
                    <td><?php echo htmlspecialchars($utilisateur['created_at']); ?></td>
                    <td>
                        <a href="manage_users.php?edit=<?php echo $utilisateur['id']; ?>" class="btn btn-edit">Modifier</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $utilisateur['id']; ?>">
                            <button type="submit" name="delete_user" class="btn btn-danger" onclick="return confirm('Supprimer cet utilisateur ?');">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <a href="dashboard_gestionnaire.php" class="back-link">Retour au tableau de bord</a>
    </div>
</body>
</html>