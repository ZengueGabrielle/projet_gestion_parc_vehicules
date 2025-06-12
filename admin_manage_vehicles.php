<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Véhicules</title>
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
            position: relative;
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
            transition: background 0.3s, transform 0.2s;
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
            max-width: 800px;
            margin: 20px auto;
            border: 3px solid #dc3545;
            text-align: center;
            color: #333;
        }
        h2 {
            color: #dc3545;
            font-size: 28px;
            margin-bottom: 20px;
        }
        p {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
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
                <img src="assets/images/user.png" alt="User Image">
                <h4>@<?php echo htmlspecialchars($_SESSION['nom_utilisateur']); ?></h4>
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
                <h2>Gestion des Véhicules</h2>
                <p>Ajoutez, modifiez ou supprimez des véhicules du parc automobile.</p>
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