<?php
session_start();
require_once 'includes/config.php';

// Vérification du rôle (doit être gestionnaire ou admin selon ta logique)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'gestionnaire') {
    header('Location: dashboard_gestionnaire.php');
    exit;
}

// Ajouter un gestionnaire
if (isset($_POST['add_gestionnaire'])) {
    try {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $nom_utilisateur = trim($_POST['nom_utilisateur']);
        $email = trim($_POST['email']);
        $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);

        // Vérifier si l'email existe déjà
        $stmt_check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->rowCount() > 0) {
            $error = "Cet email est déjà utilisé.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, nom_utilisateur, email, mot_de_passe, role_id) VALUES (?, ?, ?, ?, ?, 2)");
            $stmt->execute([$nom, $prenom, $nom_utilisateur, $email, $mot_de_passe]);
            header('Location: manage_employees.php?success=Gestionnaire ajouté');
            exit;
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de l'ajout : " . $e->getMessage();
    }
}

// Modifier un gestionnaire
if (isset($_POST['edit_gestionnaire'])) {
    try {
        $id = $_POST['id'];
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $nom_utilisateur = trim($_POST['nom_utilisateur']);
        $email = trim($_POST['email']);
        $mot_de_passe = !empty($_POST['mot_de_passe']) ? password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT) : null;

        // Vérifier si l'email est unique (hors utilisateur courant)
        $stmt_check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        $stmt_check->execute([$email, $id]);
        if ($stmt_check->rowCount() > 0) {
            $error = "Cet email est déjà utilisé.";
        } else {
            if ($mot_de_passe) {
                $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, nom_utilisateur = ?, email = ?, mot_de_passe = ? WHERE id = ? AND role_id = 2");
                $stmt->execute([$nom, $prenom, $nom_utilisateur, $email, $mot_de_passe, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, nom_utilisateur = ?, email = ? WHERE id = ? AND role_id = 2");
                $stmt->execute([$nom, $prenom, $nom_utilisateur, $email, $id]);
            }
            header('Location: manage_employees.php?success=Gestionnaire modifié');
            exit;
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la modification : " . $e->getMessage();
    }
}

// Supprimer un gestionnaire
if (isset($_POST['delete_gestionnaire'])) {
    try {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ? AND role_id = 2");
        $stmt->execute([$id]);
        header('Location: manage_employees.php?success=Gestionnaire supprimé');
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Récupérer les gestionnaires
try {
    $stmt = $pdo->query("SELECT u.id, u.nom, u.prenom, u.nom_utilisateur, u.email, u.created_at, r.nom AS nom_role 
                         FROM utilisateurs u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.role_id = 2");
    $gestionnaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des gestionnaires : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Gestionnaires</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { margin: 0; padding: 20px; min-height: 100vh; font-family: Arial, sans-serif; color: #333; position: relative; }
        video.background-video { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
        .container { background: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 8px 16px rgba(0,0,0,0.1); width: 100%; max-width: 1200px; margin: 0 auto; border: 3px solid #dc3545; }
        h1, h2 { color: #dc3545; text-align: center; }
        form { margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 16px; margin-bottom: 5px; }
        input { width: 100%; padding: 10px; border: 1px solid #dc3545; border-radius: 5px; font-size: 16px; }
        .btn { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background 0.3s; }
        .btn-primary { background: #dc3545; color: #fff; }
        .btn-primary:hover { background: #b02a37; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-danger:hover { background: #b02a37; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 12px; border: 1px solid #dc3545; text-align: left; }
        th { background: #dc3545; color: #fff; }
        tr:nth-child(even) { background: #f8f9fa; }
        .back-link { display: block; width: 200px; margin: 20px auto; padding: 12px; background: #dc3545; color: #fff; text-align: center; text-decoration: none; border-radius: 10px; font-size: 18px; transition: background 0.3s, transform 0.2s; }
        .back-link:hover { background: #b02a37; transform: translateY(-2px); }
        .error, .success { padding: 10px; margin-bottom: 20px; border-radius: 5px; text-align: center; }
        .error { background: #f8d7da; color: #dc3545; }
        .success { background: #d4edda; color: #28a745; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: #fff; padding: 20px; border-radius: 10px; width: 90%; max-width: 500px; border: 2px solid #dc3545; }
        .close { float: right; font-size: 24px; cursor: pointer; color: #dc3545; }
        @media (max-width: 768px) {
            .container { padding: 20px; margin: 10px; }
            h1 { font-size: 28px; }
            table { font-size: 14px; }
        }
    </style>
</head>
<body>
    <video class="background-video" autoplay loop muted playsinline>
        <source src="assets/videos/background.mp4" type="video/mp4">
    </video>
    <div class="container">
        <h1>Gestion des Gestionnaires</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <h2>Ajouter un gestionnaire</h2>
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
            <button type="submit" name="add_gestionnaire" class="btn btn-primary">Ajouter</button>
        </form>
        <h2>Liste des gestionnaires</h2>
        <?php if (empty($gestionnaires)): ?>
            <p style="text-align: center;">Aucun gestionnaire enregistré.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Nom d'utilisateur</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Date de création</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($gestionnaires as $g): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($g['nom']); ?></td>
                        <td><?php echo htmlspecialchars($g['prenom']); ?></td>
                        <td><?php echo htmlspecialchars($g['nom_utilisateur']); ?></td>
                        <td><?php echo htmlspecialchars($g['email']); ?></td>
                        <td><?php echo htmlspecialchars($g['nom_role']); ?></td>
                        <td><?php echo htmlspecialchars($g['created_at']); ?></td>
                        <td>
                            <button class="btn btn-warning" onclick="openModal(
                                <?php echo $g['id']; ?>,
                                '<?php echo htmlspecialchars(addslashes($g['nom'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($g['prenom'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($g['nom_utilisateur'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($g['email'])); ?>'
                            )">Modifier</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                                <button type="submit" name="delete_gestionnaire" class="btn btn-danger" onclick="return confirm('Supprimer ce gestionnaire ?');">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <a href="dashboard_gestionnaire.php" class="back-link">Retour au tableau de bord</a>
    </div>

    <!-- Modal pour modification -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">×</span>
            <h2>Modifier le gestionnaire</h2>
            <form method="POST">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_nom">Nom</label>
                    <input type="text" id="edit_nom" name="nom" required>
                </div>
                <div class="form-group">
                    <label for="edit_prenom">Prénom</label>
                    <input type="text" id="edit_prenom" name="prenom" required>
                </div>
                <div class="form-group">
                    <label for="edit_nom_utilisateur">Nom d ''utilisateur</label>
                    <input type="text" id="edit_nom_utilisateur" name="nom_utilisateur" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_mot_de_passe">Nouveau mot de passe (facultatif)</label>
                    <input type="password" id="edit_mot_de_passe" name="mot_de_passe" placeholder="Laissez vide pour ne pas modifier">
                </div>
                <button type="submit" name="edit_gestionnaire" class="btn btn-primary">Enregistrer</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id, nom, prenom, nom_utilisateur, email) {
            document.getElementById('editModal').style.display = 'flex';
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nom').value = nom;
            document.getElementById('edit_prenom').value = prenom;
            document.getElementById('edit_nom_utilisateur').value = nom_utilisateur;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_mot_de_passe').value = '';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>