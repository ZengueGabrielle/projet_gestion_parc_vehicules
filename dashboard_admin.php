<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Placeholder pour les données
$total_vehicules = $pdo->query("SELECT COUNT(*) FROM vehicules")->fetchColumn();
$vehicules_actifs = $pdo->query("SELECT COUNT(*) FROM vehicules WHERE statut = 'disponible' OR statut = 'affecte'")->fetchColumn();
$utilisateurs = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
// Correction ici : employés = gestionnaires (role_id = 2)
$employes = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role_id = 2")->fetchColumn();
$alertes_maintenance = $pdo->query("SELECT COUNT(*) FROM predictions_maintenance WHERE date_prevue <= NOW()")->fetchColumn();
$couts_total = $pdo->query("SELECT SUM(cout) FROM maintenances")->fetchColumn();
$couts_total = $couts_total !== null ? $couts_total : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Administrateur</title>
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
        .dashboard-container {
            background: #fff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            border: 3px solid #dc3545;
        }
        h1 {
            color: #dc3545;
            font-size: 32px;
            text-align: center;
            margin-bottom: 20px;
        }
        .welcome {
            font-size: 20px;
            color: #343a40;
            text-align: center;
            margin-bottom: 30px;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .menu-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            border: 2px solid #dc3545;
            text-align: center;
            transition: transform 0.3s, border-color 0.3s;
            cursor: pointer;
        }
        .menu-item:hover {
            transform: translateY(-5px);
            border-color: #b02a37;
        }
        .menu-item a {
            color: #343a40;
            text-decoration: none;
            display: block;
            font-size: 18px;
            margin-top: 10px;
        }
        .menu-item i {
            font-size: 40px;
            color: #dc3545;
            margin-bottom: 10px;
        }
        .back-link, .logout-btn {
            display: block;
            width: 200px;
            margin: 20px auto 0;
            padding: 12px;
            background: #dc3545;
            color: #fff;
            text-align: center;
            text-decoration: none;
            border-radius: 10px;
            font-size: 18px;
            transition: background 0.3s, transform 0.2s;
        }
        .back-link:hover, .logout-btn:hover {
            background: #b02a37;
            transform: translateY(-2px);
        }
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 20px;
                margin: 10px;
            }
            h1 {
                font-size: 28px;
            }
            .menu-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <video class="background-video" autoplay loop muted playsinline>
        <source src="assets/videos/background.mp4" type="video/mp4">
        Votre navigateur ne supporte pas la lecture de vidéos.
    </video>
    <div class="dashboard-container">
        <h1>Tableau de Bord Administrateur</h1>
        <p class="welcome">Bienvenue, <strong><?php echo htmlspecialchars($_SESSION['nom_utilisateur']); ?></strong> ! Rôle : <strong>Administrateur</strong></p>
        <div class="menu-grid">
            <div class="menu-item" onclick="window.location.href='admin_overview.php'">
                <i class="fas fa-chart-bar"></i>
                <a>Vue globale du parc</a>
                <div>Total véhicules : <?php echo $total_vehicules; ?></div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_manage_users.php'">
                <i class="fas fa-users"></i>
                <a>Gestion des utilisateurs</a>
                <div>Nombre d'utilisateurs : <?php echo $utilisateurs; ?></div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_manage_employees.php'">
                <i class="fas fa-user-friends"></i>
                <a>Gestion des employés</a>
                <div>Nombre d'employés : <?php echo $employes; ?></div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_manage_vehicles.php'">
                <i class="fas fa-car"></i>
                <a>Gestion des véhicules</a>
                <div>Véhicules actifs : <?php echo $vehicules_actifs; ?></div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_maintenance_alerts.php'">
                <i class="fas fa-exclamation-triangle"></i>
                <a>Alertes maintenance</a>
                <div>Alertes en attente : <?php echo $alertes_maintenance; ?></div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_ia_suggestions.php'">
                <i class="fas fa-brain"></i>
                <a>Suggestions IA</a>
                <div>Placeholder : Stratégies IA</div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_intervention_calendar.php'">
                <i class="fas fa-calendar"></i>
                <a>Calendrier des interventions</a>
                <div>Placeholder : Planning maintenances</div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_vehicle_diagnostics.php'">
                <i class="fas fa-stethoscope"></i>
                <a>Diagnostic de véhicule</a>
                <div>Placeholder : État des véhicules</div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_manage_parts.php'">
                <i class="fas fa-box"></i>
                <a>Gestion des pièces</a>
                <div>Placeholder : Niveau des pièces</div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_partner_garages.php'">
                <i class="fas fa-tools"></i>
                <a>Garages partenaires</a>
                <div>Placeholder : Liste des garages</div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_cost_tracking.php'">
                <i class="fas fa-dollar-sign"></i>
                <a>Suivi des coûts</a>
                <div>Coût total : <?php echo number_format($couts_total, 2); ?> €</div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_intervention_reports.php'">
                <i class="fas fa-file-alt"></i>
                <a>Rapport d’intervention</a>
                <div>Placeholder : Modifier rapports</div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_incident_tracking.php'">
                <i class="fas fa-shield-alt"></i>
                <a>Suivi des incidents</a>
                <div>Placeholder : Liste des incidents</div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_maintenance_history.php'">
                <i class="fas fa-wrench"></i>
                <a>Historique des maintenances</a>
                <div>Placeholder : Liste des maintenances</div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_manage_assignments.php'">
                <i class="fas fa-calendar-check"></i>
                <a>Gestion des affectations</a>
                <div>Placeholder : Liste des affectations</div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_manage_roles.php'">
                <i class="fas fa-lock"></i>
                <a>Gestion des rôles & accès</a>
                <div>Placeholder : Modifier permissions</div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_integrations.php'">
                <i class="fas fa-plug"></i>
                <a>Intégrations externes</a>
                <div>Placeholder : Statut intégrations</div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_manage_backups.php'">
                <i class="fas fa-database"></i>
                <a>Gestion des sauvegardes</a>
                <div>Placeholder : Créer/Restaurer backup</div>
            </div>
            <div class="menu-item" onclick="window.location.href='admin_reports.php'">
                <i class="fas fa-file-excel"></i>
                <a>Rapports globaux</a>
                <div>Placeholder : Télécharger rapports</div>
            </div>
        </div>
        <a href="index.php" class="back-link">Retour à l'accueil</a>
        <a href="logout.php" class="logout-btn">Déconnexion</a>
    </div>
</body>
</html>