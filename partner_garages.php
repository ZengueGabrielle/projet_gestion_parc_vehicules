<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gestionnaire') {
    header('Location: index.php');
    exit;
}

// Récupérer les garages
$stmt = $pdo->query("SELECT * FROM garages");
$garages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garages Partenaires</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
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
            top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover; z-index: -1;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            width: 100%; max-width: 1200px; margin: 0 auto;
            border: 3px solid #dc3545;
        }
        h1 {
            color: #dc3545;
            font-size: 32px;
            text-align: center;
            margin-bottom: 20px;
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
        @media (max-width: 768px) {
            .container { padding: 20px; margin: 10px; }
            h1 { font-size: 28px; }
            #map { height: 300px; }
            table { font-size: 14px; }
        }
    </style>
</head>
<body>
    <video class="background-video" autoplay loop muted playsinline>
        <source src="assets/videos/background.mp4" type="video/mp4">
    </video>
    <div class="container">
        <h1>Garages Partenaires</h1>
        <!-- Barre de recherche personnalisée -->
        <div class="custom-search-bar">
            <input type="text" id="custom-search-input" placeholder="Rechercher un lieu, quartier, ville, pays...">
            <button onclick="triggerCustomSearch()" style="padding:8px 12px;border-radius:5px;border:none;background:#dc3545;color:#fff;cursor:pointer;">🔍</button>
            <div id="search-results" style="display:none;"></div>
        </div>
        <div id="map"></div>
        <table>
            <tr>
                <th>Nom</th>
                <th>Adresse</th>
                <th>Téléphone</th>
                <th>Email</th>
            </tr>
            <?php foreach ($garages as $garage): ?>
                <tr>
                    <td><?php echo htmlspecialchars($garage['nom']); ?></td>
                    <td><?php echo htmlspecialchars($garage['adresse']); ?></td>
                    <td><?php echo htmlspecialchars($garage['telephone']); ?></td>
                    <td><?php echo htmlspecialchars($garage['email']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <a href="dashboard_gestionnaire.php" class="back-link">Retour au tableau de bord</a>
    </div>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        // Initialisation de la carte (centrée sur Paris par défaut)
        var map = L.map('map').setView([48.8566, 2.3522], 8);

        // Fond de carte OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Ajout des garages depuis PHP
        var garages = <?php echo json_encode($garages); ?>;
        garages.forEach(function(garage) {
            if (garage.latitude && garage.longitude) {
                L.marker([garage.latitude, garage.longitude])
                    .addTo(map)
                    .bindPopup('<b>' + garage.nom + '</b><br>' + garage.adresse);
            }
        });

        // Géolocalisation automatique si disponible
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                map.setView([lat, lng], 12);
            });
        }

        // Barre de recherche personnalisée avec suggestions
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
            searchTimeout = setTimeout(function() {
                // Recherche mondiale (quartiers, villes, pays, etc.)
                fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query) + '&addressdetails=1&limit=20')
                    .then(response => response.json())
                    .then(data => {
                        resultsDiv.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(function(place) {
                                // Construction du label détaillé
                                const address = place.address;
                                let label = '';
                                if (address.neighbourhood) label += address.neighbourhood + ', ';
                                if (address.suburb && address.suburb !== address.neighbourhood) label += address.suburb + ', ';
                                if (address.city_district && address.city_district !== address.suburb) label += address.city_district + ', ';
                                if (address.city) label += address.city + ', ';
                                if (address.town && address.town !== address.city) label += address.town + ', ';
                                if (address.village && address.village !== address.city && address.village !== address.town) label += address.village + ', ';
                                if (address.state) label += address.state + ', ';
                                if (address.country) label += address.country;
                                if (!label) label = place.display_name;

                                // Affichage du nom principal + détails
                                const div = document.createElement('div');
                                div.innerHTML = '<strong>' + (place.display_name.split(',')[0]) + '</strong><br><span style="font-size:12px;color:#666;">' + label + '</span>';
                                div.style.cursor = 'pointer';
                                div.onclick = function() {
                                    // Garde le zoom courant ou fixe à 12
                                    map.setView([place.lat, place.lon], map.getZoom() < 12 ? 12 : map.getZoom());
                                    // Supprime les anciens marqueurs de recherche
                                    markers.forEach(m => map.removeLayer(m));
                                    markers = [];
                                    const marker = L.marker([place.lat, place.lon]).addTo(map)
                                        .bindPopup(place.display_name).openPopup();
                                    markers.push(marker);
                                    resultsDiv.style.display = 'none';
                                    resultsDiv.innerHTML = '';
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
            }, 400);
        });

        // Recherche au clic sur le bouton
        function triggerCustomSearch() {
            input.dispatchEvent(new Event('input'));
        }

        // Recherche avec la touche Entrée
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                triggerCustomSearch();
            }
        });

        // Fermer la liste si on clique ailleurs
        document.addEventListener('click', function(e) {
            if (!resultsDiv.contains(e.target) && e.target !== input) {
                resultsDiv.style.display = 'none';
            }
        });
    </script>
</body>
</html>