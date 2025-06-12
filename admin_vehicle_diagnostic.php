<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Récupérer les véhicules avec diagnostics
try {
    $stmt = $pdo->query("SELECT v.*, COUNT(p.id) as alert_count 
                         FROM vehicules v 
                         LEFT JOIN predictions_maintenance p ON v.id = p.vehicule_id 
                         GROUP BY v.id");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des véhicules : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic des diagnostics</title>
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
        h2 {
            color: #dc3545;
            font-size: 28px;
            margin-bottom: 20px;
            text-align: center;
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
        .error {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            background: #f8d7da;
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
            h2 {
                font-size: 24px;
            }
            table {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <video class="background-video" autoplay loop playsinline>
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
                <li><a href="admin_vehicle_diagnostic.php"><i class="fas fa-stethoscope"></i> <span>Diagnostic de véhicule</span></a></li>
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
                <h2>Diagnostic des diagnostics</h2>
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (empty($vehicles)): ?>
                    <p style="text-align: center;">Aucun véhicule enregistré.</p>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Immatriculation</th>
                            <th>Marque</th>
                            <th>Modèle</th>
                            <th>Kilométrage</th>
                            <th>Statut</th>
                            <th>Alertes</th>
                            <th>Recommandation</th>
                        </tr>
                        <?php foreach ($vehicles as $vehicule): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vehicule['immatriculation']); ?></td>
                                <td><?php echo htmlspecialchars($vehicule['marque']); ?></td>
                                <td><?php echo htmlspecialchars($vehicule['modele']); ?></td>
                                <td><?php echo htmlspecialchars($vehicule['kilométrage_actuel']); ?> km</td>
                                <td><?php echo htmlspecialchars($vehicule['statut']); ?></td>
                                <td><?php echo $vehicule['alert_count']; ?></td>
                                <td><?php echo $vehicule['alert_count'] > 0 ? 'Planifier maintenance' : 'Aucune action'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
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
    </script>
</body>
</html>