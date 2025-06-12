<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Date range filter (default: last 12 months)
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01', strtotime('-12 months'));
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-t');

// Validate date range
if (strtotime($endDate) < strtotime($startDate)) {
    $errorMessage = "La date de fin doit être postérieure à la date de début.";
    $startDate = date('Y-m-01', strtotime('-12 months'));
    $endDate = date('Y-m-t');
}

// Fetch vehicle summary from affectations
try {
    // Total active vehicles (affectations with no end date or future end date)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT vehicule_id) as total_vehicles FROM affectations WHERE date_fin IS NULL OR date_fin > NOW()");
    $stmt->execute();
    $totalVehicles = $stmt->fetch(PDO::FETCH_ASSOC)['total_vehicles'];

    // Vehicle status (inferred from affectations, maintenances, incidents)
    $vehicleStatus = [];
    // Actif: Vehicles with active affectations
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT vehicule_id) as count FROM affectations WHERE date_fin IS NULL OR date_fin > NOW()");
    $stmt->execute();
    $vehicleStatus[] = ['statut' => 'Actif', 'count' => $stmt->fetch(PDO::FETCH_ASSOC)['count']];

    // En maintenance: Vehicles with recent maintenance (within date range)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT vehicule_id) as count FROM maintenances WHERE date_maintenance BETWEEN ? AND ?");
    $stmt->execute([$startDate, $endDate]);
    $vehicleStatus[] = ['statut' => 'En maintenance', 'count' => $stmt->fetch(PDO::FETCH_ASSOC)['count']];

    // Avec incident: Vehicles with unresolved incidents
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT vehicule_id) as count FROM incidents WHERE date_incident BETWEEN ? AND ? AND statut IN ('en_attente', 'en_cours')");
    $stmt->execute([$startDate, $endDate]);
    $vehicleStatus[] = ['statut' => 'Avec incident', 'count' => $stmt->fetch(PDO::FETCH_ASSOC)['count']];
} catch (PDOException $e) {
    $errorMessage = "Erreur lors de la récupération des données des véhicules : " . $e->getMessage();
}

// Fetch maintenance costs by month
try {
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(date_maintenance, '%Y-%m') as month, SUM(cout) as total_cost
        FROM maintenances
        WHERE date_maintenance BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(date_maintenance, '%Y-%m')
        ORDER BY month
    ");
    $stmt->execute([$startDate, $endDate]);
    $maintenanceCosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare chart data
    $chartLabels = [];
    $chartData = [];
    foreach ($maintenanceCosts as $cost) {
        $chartLabels[] = $cost['month'];
        $chartData[] = (float)$cost['total_cost'];
    }
} catch (PDOException $e) {
    $errorMessage = "Erreur lors de la récupération des coûts de maintenance : " . $e->getMessage();
}

// Fetch incident statistics
try {
    $stmt = $pdo->prepare("
        SELECT type_incident, COUNT(*) as count
        FROM incidents
        WHERE date_incident BETWEEN ? AND ?
        GROUP BY type_incident
    ");
    $stmt->execute([$startDate, $endDate]);
    $incidentStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Erreur lors de la récupération des statistiques des incidents : " . $e->getMessage();
}

// Fetch low stock parts
try {
    $stmt = $pdo->query("SELECT nom_piece, quantite_en_stock FROM pieces_detachees WHERE quantite_en_stock < 5");
    $lowStockParts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Erreur lors de la récupération des pièces en stock faible : " . $e->getMessage();
}

// Fetch active assignments
try {
    $stmt = $pdo->prepare("
        SELECT a.vehicule_id, u.nom, u.prenom, a.date_debut
        FROM affectations a
        JOIN utilisateurs u ON a.utilisateur_id = u.id
        WHERE a.date_fin IS NULL OR a.date_fin > NOW()
    ");
    $stmt->execute();
    $activeAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Erreur lors de la récupération des affectations actives : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports Globaux</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            padding: 15px 20px;
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
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        label {
            font-size: 16px;
            margin-right: 5px;
        }
        input[type="date"] {
            padding: 8px;
            border: 1px solid #dc3545;
            border-radius: 5px;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            background: #dc3545;
            color: #fff;
        }
        .btn:hover {
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
        .error {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            background: #f8d7da;
            color: #dc3545;
            text-align: center;
        }
        canvas {
            max-width: 100%;
            margin: 20px 0;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 50px;
            }
            .content {
                margin-left: 50px;
            }
            .sidebar.expanded {
                width: 250px;
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
            .form-group {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <video class="background-video" autoplay loop muted playsinline>
        <source src="assets/videos/background.mp4" type="video/mp4">
        Votre appareil ne supporte pas la lecture de vidéos.
    </video>
    <header class="header">
        <button class="toggle-btn"><i class="fa-solid fa-bars"></i></button>
        <h2 class="u-name">GESTION <b>Pôle</b></h2>
    </header>
    <div class="main-content">
        <nav class="sidebar">
            <div class="user-p">
                <img src="https://images.unsplash.com/photo-1721332153282-2bd0aa-1b3a99b02ed?format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufqDB%3D%3D" alt="User Image">
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
                <h2>Rapports Globaux</h2>
                <?php if (isset($errorMessage)): ?>
                    <div class="error"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>
                <form method="POST" class="form-group">
                    <div>
                        <label>Date de début :</label>
                        <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" required>
                    </div>
                    <div>
                        <label>Date de fin :</label>
                        <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" required>
                    </div>
                    <button type="submit" class="btn">Filtrer</button>
                </form>

                <h3>Résumé des Véhicules</h3>
                <?php if ($totalVehicles == 0): ?>
                    <p style="text-align: center;">Aucun véhicule actif trouvé.</p>
                <?php else: ?>
                    <p>Total des véhicules actifs : <?php echo htmlspecialchars($totalVehicles); ?></p>
                    <table>
                        <tr><th>Statut</th><th>Nombre</th></tr>
                        <?php foreach ($vehicleStatus as $status): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($status['statut']); ?></td>
                                <td><?php echo htmlspecialchars($status['count']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>

                <h3>Coûts de Maintenance</h3>
                <?php if (empty($maintenanceCosts)): ?>
                    <p style="text-align: center;">Aucun coût de maintenance pour la période sélectionnée.</p>
                <?php else: ?>
                    <canvas id="maintenanceChart"></canvas>
                    <table>
                        <tr><th>Mois</th><th>Coût Total (€)</th></tr>
                        <?php foreach ($maintenanceCosts as $cost): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cost['month']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($cost['total_cost'], 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>

                <h3>Statistiques des Incidents</h3>
                <?php if (empty($incidentStats)): ?>
                    <p style="text-align: center;">Aucun incident pour la période sélectionnée.</p>
                <?php else: ?>
                    <table>
                        <tr><th>Type d'Incident</th><th>Nombre</th></tr>
                        <?php foreach ($incidentStats as $stat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stat['type_incident']); ?></td>
                                <td><?php echo htmlspecialchars($stat['count']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>

                <h3>Pièces en Stock Faible</h3>
                <?php if (empty($lowStockParts)): ?>
                    <p style="text-align: center;">Aucune pièce en stock faible.</p>
                <?php else: ?>
                    <table>
                        <tr><th>Nom de la Pièce</th><th>Quantité en Stock</th></tr>
                        <?php foreach ($lowStockParts as $part): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($part['nom_piece']); ?></td>
                                <td><?php echo htmlspecialchars($part['quantite_en_stock']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>

                <h3>Affectations Actives</h3>
                <?php if (empty($activeAssignments)): ?>
                    <p style="text-align: center;">Aucune affectation active.</p>
                <?php else: ?>
                    <table>
                        <tr><th>Véhicule ID</th><th>Utilisateur</th><th>Date de Début</th></tr>
                        <?php foreach ($activeAssignments as $assignment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($assignment['vehicule_id']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['prenom'] . ' ' . $assignment['nom']); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($assignment['date_debut']))); ?></td>
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

            // Maintenance Costs Chart
            const ctx = document.getElementById('maintenanceChart')?.getContext('2d');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($chartLabels); ?>,
                        datasets: [{
                            label: 'Coût de Maintenance (€)',
                            data: <?php echo json_encode($chartData); ?>,
                            backgroundColor: '#dc3545',
                            borderColor: '#b02a37',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>