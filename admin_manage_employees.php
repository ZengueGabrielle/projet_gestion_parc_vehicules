<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Ajouter un employé (gestionnaire)
if (isset($_POST['add_employee'])) {
    try {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $nom_utilisateur = trim($_POST['nom_utilisateur']);
        $email = trim($_POST['email']);
        $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);

        $stmt_check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->rowCount() > 0) {
            $error = "Cet email est déjà utilisé.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, nom_utilisateur, email, mot_de_passe, role_id, created_at) VALUES (?, ?, ?, ?, ?, 2, NOW())");
            $stmt->execute([$nom, $prenom, $nom_utilisateur, $email, $mot_de_passe]);
            header('Location: admin_manage_employees.php?success=Employé ajouté');
            exit;
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de l'ajout : " . $e->getMessage();
    }
}

// Modifier un employé
if (isset($_POST['edit_employee'])) {
    try {
        $id = $_POST['id'];
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $nom_utilisateur = trim($_POST['nom_utilisateur']);
        $email = trim($_POST['email']);
        $mot_de_passe = !empty($_POST['mot_de_passe']) ? password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT) : null;

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
            header('Location: admin_manage_employees.php?success=Employé modifié');
            exit;
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la modification : " . $e->getMessage();
    }
}

// Supprimer un employé
if (isset($_POST['delete_employee'])) {
    try {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ? AND role_id = 2");
        $stmt->execute([$id]);
        header('Location: admin_manage_employees.php?success=Employé supprimé');
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Récupérer les employés (gestionnaires)
try {
    $stmt = $pdo->query("SELECT u.id, u.nom, u.prenom, u.nom_utilisateur, u.email, u.created_at, r.nom AS nom_role 
                         FROM utilisateurs u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.role_id = 2");
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des employés : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Employés</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            color: #fff;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
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
        .header {
            display: flex;
            align-items: center;
            padding: 15px 30px;
            background: rgba(52, 58, 64, 0.9);
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            z-index: 1001;
        }
        .u-name {
            font-size: 24px;
            margin-left: 10px;
        }
        .u-name b {
            color: #dc3545;
        }
        .header .toggle-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
        }
        .header .toggle-btn:hover {
            color: #dc3545;
        }
        .main-content {
            display: flex;
            flex-direction: row;
            margin-top: 60px;
            min-height: calc(100vh - 60px);
        }
        .sidebar {
            width: 60px;
            background: rgba(52, 58, 64, 0.9);
            transition: width 0.3s ease;
            overflow-x: hidden;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.3);
            position: fixed;
            top: 60px;
            bottom: 0;
            z-index: 1000;
        }
        .sidebar.expanded {
            width: 300px;
        }
        .sidebar .user-p {
            text-align: center;
            padding: 20px 0;
        }
        .sidebar .user-p img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #dc3545;
        }
        .sidebar .user-p h4 {
            color: #fff;
            margin-top: 10px;
            font-size: 18px;
            display: none;
        }
        .sidebar.expanded .user-p h4 {
            display: block;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar li {
            margin: 10px 0;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            color: #fff;
            text-decoration: none;
            padding: 15px;
            font-size: 16px;
            white-space: nowrap;
            transition: background 0.3s, transform 0.3s;
            cursor: pointer;
        }
        .sidebar a:hover {
            background: #dc3545;
            transform: translateX(5px);
        }
        .sidebar a i {
            width: 30px;
            text-align: center;
            margin-right: 10px;
            font-size: 18px;
        }
        .sidebar a span {
            display: none;
        }
        .sidebar.expanded a span {
            display: inline;
        }
        .content {
            margin-left: 60px;
            padding: 20px;
            flex-grow: 1;
            transition: margin-left 0.3s ease;
        }
        .sidebar.expanded ~ .content {
            margin-left: 300px;
        }
        .dashboard-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            max-width: 1200px;
            margin: 20px auto;
            border: 3px solid #dc3545;
            color: #333;
        }
        h2, h3 {
            color: #dc3545;
            font-size: 28px;
            margin-bottom: 20px;
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
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        .btn-danger {
            background: #dc3545;
            color: #fff;
        }
        .btn-danger:hover {
            background: #b02a37;
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
        .error, .success {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .error {
            background: #f8d7da;
            color: #dc3545;
        }
        .success {
            background: #d4edda;
            color: #28a745;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            border: 2px solid #dc3545;
        }
        .close {
            float: right;
            font-size: 24px;
            cursor: pointer;
            color: #dc3545;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 50px;
            }
            .sidebar.expanded {
                width: 250px;
            }
            .content {
                margin-left: 50px;
            }
            .sidebar.expanded ~ .content {
                margin-left: 250px;
            }
            .dashboard-container {
                padding: 20px;
                margin: 10px;
            }
            h2, h3 {
                font-size: 24px;
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
        Votre navigateur ne supporte pas la lecture de vidéos.
    </video>
    <header class="header">
        <button class="toggle-btn"><i class="fa-solid fa-bars"></i></button>
        <h2 class="u-name">GESTION <b>PARC</b></h2>
    </header>
    <div class="main-content">
        <nav class="sidebar">
            <div class="user-p">
                <img src="https://images.unsplash.com/photo-1721332153282-2bd0aa3b99ed?q=80&w=1887&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="User Image">
                <h4>@<?php echo htmlspecialchars($_SESSION['nom_utilisateur'] ?? 'admin'); ?></h4>
            </div>
            <ul>
                <li><a href="admin_overview.php"><i class="fas fa-chart-bar"></i> <span>Vue globale du parc</span></a></li>
                <li><a href="admin_manage_users.php"><i class="fas fa-users"></i> <span>Gestion des utilisateurs</span></a></li>
                <li><a href="admin_manage_employees.php"><i class="fas fa-user-friends"></i> <span>Gestion des employés</span></a></li>
                <li><a href="admin_manage_vehicles.php"><i class="fas fa-car"></i> <span>Gestion des véhicules</span></a></li>
                <li><a href="admin_maintenance_alerts.php"><i class="fas fa-exclamation-triangle"></i> <span>Alertes maintenance</span></a></li>
                <li><a href="admin_ia_suggestions.php"><i class="fas fa-brain"></i> <span>Suggestions IA</span></a></li>
                <li><a href="admin_intervention_calendar.php"><i class="fas fa-calendar"></i> <span>Calendrier des interventions</span></a></li>
                <li><a href="admin_vehicle_diagnostics.php"><i class="fas fa-stethoscope"></i> <span>Diagnostic de véhicule</span></a></li>
                <li><a href="admin_manage_parts.php"><i class="fas fa-box"></i> <span>Gestion des pièces</span></a></li>
                <li><a href="admin_partner_garages.php"><i class="fas fa-tools"></i> <span>Garages partenaires</span></a></li>
                <li><a href="admin_cost_tracking.php"><i class="fas fa-dollar-sign"></i> <span>Suivi des coûts</span></a></li>
                <li><a href="admin_intervention_reports.php"><i class="fas fa-file-alt"></i> <span>Rapport d’intervention</span></a></li>
                <li><a href="admin_incident_tracking.php"><i class="fas fa-shield-alt"></i> <span>Suivi des incidents</span></a></li>
                <li><a href="admin_maintenance_history.php"><i class="fas fa-wrench"></i> <span>Historique des maintenances</span></a></li>
                <li><a href="admin_manage_assignments.php"><i class="fas fa-calendar-check"></i> <span>Gestion des affectations</span></a></li>
                <li><a href="admin_manage_roles.php"><i class="fas fa-lock"></i> <span>Gestion des rôles & accès</span></a></li>
                <li><a href="admin_integrations.php"><i class="fas fa-plug"></i> <span>Intégrations externes</span></a></li>
                <li><a href="admin_manage_backups.php"><i class="fas fa-database"></i> <span>Gestion des sauvegardes</span></a></li>
                <li><a href="admin_reports.php"><i class="fas fa-file-excel"></i> <span>Rapports globaux</span></a></li>
                <li><a href="dashboard_admin.php"><i class="fa fa-home"></i> <span>Retour au tableau de bord</span></a></li>
                <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> <span>Déconnexion</span></a></li>
            </ul>
        </nav>
        <div class="content">
            <div class="dashboard-container">
                <h2>Gestion des Employés</h2>
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['success'])): ?>
                    <div class="success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>
                <h3>Ajouter un employé</h3>
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
                    <button type="submit" name="add_employee" class="btn btn-primary">Ajouter</button>
                </form>
                <h3>Liste des employés</h3>
                <?php if (empty($employes)): ?>
                    <p style="text-align: center;">Aucun employé enregistré.</p>
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
                        <?php foreach ($employes as $e): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($e['nom']); ?></td>
                                <td><?php echo htmlspecialchars($e['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($e['nom_utilisateur']); ?></td>
                                <td><?php echo htmlspecialchars($e['email']); ?></td>
                                <td><?php echo htmlspecialchars($e['nom_role']); ?></td>
                                <td><?php echo htmlspecialchars($e['created_at']); ?></td>
                                <td>
                                    <button class="btn btn-warning" onclick="openModal(
                                        <?php echo $e['id']; ?>,
                                        '<?php echo htmlspecialchars(addslashes($e['nom'])); ?>',
                                        '<?php echo htmlspecialchars(addslashes($e['prenom'])); ?>',
                                        '<?php echo htmlspecialchars(addslashes($e['nom_utilisateur'])); ?>',
                                        '<?php echo htmlspecialchars(addslashes($e['email'])); ?>'
                                    )">Modifier</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                                        <button type="submit" name="delete_employee" class="btn btn-danger" onclick="return confirm('Supprimer cet employé ?');">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">×</span>
            <h2>Modifier l'employé</h2>
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
                    <label for="edit_nom_utilisateur">Nom d'utilisateur</label>
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
                <button type="submit" name="edit_employee" class="btn btn-primary">Enregistrer</button>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.toggle-btn');
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('expanded');
            });
        });

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