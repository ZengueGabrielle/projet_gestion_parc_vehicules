<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Récupérer les garages
try {
    $stmt = $pdo->query("SELECT * FROM garages");
    $garages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Erreur lors de la récupération des garages : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garages Partenaires</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
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
        h2 {
            color: #dc3545;
            font-size: 28px;
            margin-bottom: 20px;
            text-align: center;
        }
        .custom-search-bar {
            width: 100%;
            max-width: 400px;
            margin: 0 auto 10px auto;
            z-index: 1000;
            position: relative;
        }
        #custom-search-input {
            width: 80%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #dc3545;
        }
        #search-results {
            background: #fff;
            border: 1px solid #dc3545;
            border-top: none;
            max-height: 250px;
            overflow-y: auto;
            position: absolute;
            width: 80%;
            left: 0;
            right: 0;
            margin: 0 auto;
            z-index: 2000;
        }
        #search-results div {
            padding: 8px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        #search-results div:last-child {
            border-bottom: none;
        }
        #search-results div:hover {
            background: #f8f9fa;
        }
        #map {
            height: 400px;
            width: 100%;
            border-radius: 10px;
            margin-bottom: 20px;
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
            #map {
                height: 300px;
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
                <img src="https://images.unsplash.com/photo-1721332153282-2bd0aa3b99ed?q=80&w=1887&auto=format&fit=crop" alt="User Image">
                <h4>@<?php echo htmlspecialchars($_SESSION['nom_utilisateur'] ?? 'admin'); ?></h4>
            </div>
            <ul>
                <li><a href="admin_overview.php"><i class="fas fa-chart-bar"></i> <span>Vue globale du parc</span></a></li>
                <li><a href="admin_manage_users.php"><i class="fas fa-users"></i> <span>Gestion des utilisateurs</span></a></li>
                <li><a href="admin_manage_employees.php"><i class="fas fa-user-friends"></i> <span>Gestion des employés</span></a></li>
                <li><a href="admin_manage_vehicles.php"><i class="fas fa-car"></i> <span>Gestion des véhicules</span></a></li>
                <li><a href="admin_maintenance_alerts.php"><i class="fas fa-exclamation-triangle"></i> <span>Alertes maintenance</span></a></li>
                <li><a href="admin_ia_suggestions.php"><i class="fas fa-brain"></i> <span>Suggestions IA</span></a></li>
                <li><a href="admin_intervention_calendar.php"><i class="fas fa-calendar"></i> <span>Calendrier des interventions</span></li>
                <li><a href="admin_vehicle_diagnostic.php"><i class="fas fa-stethoscope"></i> <span>Diagnostic de véhicule</span></a></li>
                <li><a href="admin_manage_parts.php"><i class="fas fa-box"></i> <span>Gestion des pièces</span></a></li>
                <li><a href="admin_partner_garages.php"><i class="fas fa-tools"></i> <span>Garages partenaires</span></a></li>
                <li><a href="admin_cost_tracking.php"><i class="fas fa-dollar-sign"></i> <span>Suivi des coûts</span></a></li>
                <li><a href="admin_intervention_reports.php"><i class="fas fa-file-alt"></i> <span>Rapport d’intervention</span></a></li>
                <li><a href="admin_incident_diagnostic.php"><i> class="fas fa-shield-alt"></i> <span>Suivi des incidents</span></a></li>
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
                <h2>Garages Partenaires</h2>
                <?php if (isset($errorMessage)): ?>
                    <div class="error"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>
                <div class="custom-search-bar">
                    <input type="text" id="custom-search-input" placeholder="Rechercher un lieu, quartier, ville...">
                    <button onclick="triggerCustomSearch()" class="btn btn-primary">🔍</button>
                    <div id="search-results" style="display:none;"></div>
                </div>
                <div id="map"></div>
                <?php if (empty($garages)): ?>
                    <p style="text-align: center;">Aucun garage partenaire enregistré.</p>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Nom</th>
                            <th>Adresse</th>
                            <th>Téléphone</th>
                            <th>Email</th>
                        </tr>
                        <?php foreach ($garages as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nom']); ?></td>
                                <td><?php echo htmlspecialchars($row['adresse']); ?></td>
                                <td><?php echo htmlspecialchars($row['telephone']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.toggle-btn');
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('expanded');
            });

            // Initialisation de la carte
            var map = L.map('map').setView([48.8566, 2.3522], 8);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Ajout des garages
            var garages = <?php echo json_encode($garages); ?>;
            garages.forEach(function(garage) {
                if (garage.latitude && garagelightitude) {
                    L.marker([garage.latitude, garagelightitude])
                        .addTo(map)
                        .bindPopup('<b>' + garage.nom + '</b><br>' + garage.adresse);
                }
            });

            // Géolocalisation
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;
                    map.setView([lat, lng], 12);
                });
            }

            // Recherche
            const input = document.getElementById('custom-search-input');
            const resultsDiv = document.getElementById('search-results');
            let searchTimeout = null;
            let markers = [];

            input.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = input.value.trim();
                if (query.length < 2) {
                    resultsDiv.style.display = 'none';
                    resultsDiv.innerHTML = '';
                    return;
                }
                searchTimeout = setTimeout(() => {
                    fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query) + '&addressdetails=1&limit=20')
                        .then(response => response.json())
                        .then(data => {
                            resultsDiv.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(function(place) {
                                    const address = place.address;
                                    let label = '';
                                    if (address.neighbourhood) label += address.neighbourhood + ', ';
                                    if (address.suburb && address.suburb !== address.neighbourhood) label += address.suburb + ', ';
                                    if (address.city_district) label += address.city_district + ', ';
                                    if (address.city) label += address.city + ', ';
                                    if (address.town && address.town !== address.city) label += address.town + ', ';
                                    if (address.village) label += address.village + ', ';
                                    if (address.state) label += address.state + ', ';
                                    if (address.country) label += address.country;
                                    if (!label) label = place.display_name;

                                    const div = document.createElement('div');
                                    div.innerHTML = '<b>' + (place.display_name.split(',')[0]) + '</b><br><span style="font-size:12px;color:#666;">' + label + '</span>';
                                    div.onclick = function() {
                                        map.setView([place.lat, place.lon], map.getZoom() < 12 ? 12 : map.getZoom());
                                        markers.forEach(m => map.removeLayer(m));
                                        markers = [];
                                        const marker = L.marker([place.lat, place.lon]).addTo(map)
                                            .bindPopup(place.display_name).openPopup();
                                        markers.push(marker);
                                        resultsDiv.style.display = 'none';
                                        input.value = place.display_name.split(',')[0];
                                    };
                                    resultsDiv.appendChild(div);
                                });
                                resultsDiv.style.display = 'block';
                            } else {
                                resultsDiv.innerHTML = '<div>Aucun résultat</div>';
                                resultsDiv.style.display = 'block';
                            }
                        });
                }, 500);
            });

            function triggerCustomSearch() {
                input.dispatchEvent(new Event('input'));
            }

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    triggerCustomSearch();
                }
            });

            document.addEventListener('click', function(e) {
                if (!resultsDiv.contains(e.target) && e.target !== input) {
                    resultsDiv.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>