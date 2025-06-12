<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'utilisateur') {
    header('Location: index.php');
    exit;
}

// Récupérer les garages
$stmt = $pdo->query("SELECT id, nom, adresse, telephone, latitude, longitude, performance_score FROM garages");
$garages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Définir la clé API Google Cloud TTS (remplacez par votre clé)
define('GOOGLE_TTS_API_KEY', 'AIzaSyCX80SITuKdVPhB-WyTGeQkfrnfdzfUTNo');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garages et Stations-Service à Proximité</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #dc3545;
            color: #fff;
        }
        tr:nth-child(even) {
            background: #f2f2f2;
        }
        tr.closest {
            background: #dc354533;
            font-weight: bold;
        }
        #map {
            height: 400px;
            margin-top: 20px;
            border-radius: 5px;
            width: 100%;
        }
        .error-message {
            color: #dc3545;
            font-size: 16px;
            margin-top: 20px;
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
            th, td {
                padding: 8px;
            }
            #map {
                height: 300px;
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
                <li><a href="dashboard_utilisateur.php"><i class="fa fa-home"></i> <span>Tableau de bord</span></a></li>
                <li><a href="vehicle_catalog.php"><i class="fa fa-car"></i> <span>Catalogue des véhicules</span></a></li>
                <li><a href="vehicules_assignes.php"><i class="fa fa-car"></i> <span>Mes véhicules assignés</span></a></li>
                <li><a href="suivi_trajets.php"><i class="fa fa-road"></i> <span>Suivi de mes trajets</span></a></li>
                <li><a href="report_anomaly.php"><i class="fa fa-exclamation-circle"></i> <span>Signaler une anomalie</span></a></li>
                <li><a href="maintenance_prevue.php"><i class="fa fa-wrench"></i> <span>Maintenance prévue</span></a></li>
                <li><a href="garages_proximite.php"><i class="fa fa-map-marker-alt"></i> <span>Garages à proximité</span></a></li>
                <li><a href="notifications.php"><i class="fa fa-bell"></i> <span>Notifications</span></a></li>
                <li><a href="historique_personnel.php"><i class="fa fa-history"></i> <span>Historique personnel</span></a></li>
                <li><a href="documents_lies.php"><i class="fa fa-file"></i> <span>Documents liés</span></a></li>
                <li><a href="index.php"><i class="fa fa-home"></i> <span>Retour à l'accueil</span></a></li>
                <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> <span>Se déconnecter</span></a></li>
            </ul>
        </nav>
        <div class="content">
            <div class="dashboard-container">
                <h2>Garages et Stations-Service à Proximité</h2>
                <div id="map"></div>
                <div id="garage-table">
                    <?php if (count($garages) > 0 || true): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Nom</th>
                                    <th>Adresse</th>
                                    <th>Téléphone</th>
                                    <th>Score</th>
                                    <th>Distance (km)</th>
                                </tr>
                            </thead>
                            <tbody id="garage-table-body">
                                <!-- Rempli dynamiquement via JavaScript -->
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Aucun garage ou station-service trouvé.</p>
                    <?php endif; ?>
                </div>
                <p id="error-message" class="error-message" style="display: none;"></p>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.toggle-btn');
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('expanded');
            });

            // Initialiser la carte Leaflet
            const map = L.map('map', {
                dragging: true,
                touchZoom: true,
                scrollWheelZoom: true,
                doubleClickZoom: true,
                boxZoom: true
            }).setView([48.8566, 2.3522], 12);

            // Utiliser MapTiler Basic
            L.tileLayer('https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=fvVgpkUJp4thTqeof492', {
                attribution: '© <a href="https://www.maptiler.com/copyright/">MapTiler</a> © <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                tileSize: 512,
                zoomOffset: -1
            }).addTo(map);

            const dbGarages = <?php echo json_encode($garages); ?>;
            const errorMessage = document.getElementById('error-message');
            const garageTableBody = document.getElementById('garage-table-body');

            // Fonction pour calculer la distance (Haversine)
            function calculateDistance(lat1, lon1, lat2, lon2) {
                const R = 6371;
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                          Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                          Math.sin(dLon / 2) * Math.sin(dLon / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c;
            }

            // Fonction pour lire le POI le plus proche
            function speakClosestPOI(type, nom, adresse, distance) {
                if (!window.speechSynthesis) {
                    console.warn('Synthèse vocale non prise en charge.');
                    return;
                }
                const message = `Le ${type.toLowerCase()} le plus proche est ${nom} situé à ${adresse}, à ${distance} kilomètres.`;
                const utterance = new SpeechSynthesisUtterance(message);
                utterance.lang = 'fr-FR';
                utterance.volume = 1;
                utterance.rate = 0.9; // Légèrement plus lent pour plus de clarté
                utterance.pitch = 1;

                // Sélectionner une voix
                const voices = speechSynthesis.getVoices();
                console.log('Voix disponibles:', voices.map(v => v.lang));
                const frenchVoice = voices.find(voice => voice.lang.startsWith('fr')) || voices[0];
                if (frenchVoice) {
                    utterance.voice = frenchVoice;
                    console.log('Voix sélectionnée:', frenchVoice.lang);
                }

                // Lire après un délai pour éviter les chevauchements
                setTimeout(() => {
                    speechSynthesis.speak(utterance);
                }, 1000);
            }

            // Fonction pour initialiser la synthèse vocale
            function initSpeechSynthesis() {
                if ('speechSynthesis' in window) {
                    let voicesLoaded = false;
                    const loadVoices = () => {
                        const voices = speechSynthesis.getVoices();
                        if (voices.length > 0 && !voicesLoaded) {
                            voicesLoaded = true;
                            console.log('Voix chargées:', voices.map(v => v.lang));
                        }
                    };
                    loadVoices();
                    if (speechSynthesis.onvoiceschanged !== undefined) {
                        speechSynthesis.onvoiceschanged = loadVoices;
                    }
                } else {
                    console.warn('Synthèse vocale non prise en charge.');
                }
            }

            // Fonction pour Google Cloud TTS (commentée)
            /*
            async function speakWithGoogleTTS(type, nom, adresse, distance) {
                const message = `Le ${type.toLowerCase()} le plus proche est ${nom} situé à ${adresse}, à ${distance} kilomètres.`;
                try {
                    const response = await fetch('/speak_tts.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ text: message })
                    });
                    const data = await response.json();
                    if (data.audioContent) {
                        const audio = new Audio(`data:audio/mp3;base64,${data.audioContent}`);
                        audio.play();
                    } else {
                        console.error('Erreur Google TTS:', data.error);
                        speakClosestPOI(type, nom, adresse, distance);
                    }
                } catch (error) {
                    console.error('Erreur fetch Google TTS:', error);
                    speakClosestPOI(type, nom, adresse, distance);
                }
            }
            */

            // Fonction pour récupérer les POI OSM
            async function fetchOSMPOIs(userPos, radius = 50000) {
                const query = `
                    [out:json][timeout:25];
                    (
                        node["shop"="car_repair"](around:${radius},${userPos.lat},${userPos.lng});
                        node["amenity"="fuel"](around:${radius},${userPos.lat},${userPos.lng});
                    );
                    out body;
                `;
                try {
                    const response = await fetch('https://overpass-api.de/api/interpreter', {
                        method: 'POST',
                        body: query
                    });
                    const data = await response.json();
                    return data.elements.map(poi => ({
                        id: poi.id,
                        type: poi.tags.shop === 'car_repair' ? 'Garage' : 'Station-service',
                        nom: poi.tags.name || (poi.tags.shop === 'car_repair' ? 'Garage' : 'Station-service'),
                        adresse: poi.tags['addr:street'] || 'Adresse inconnue',
                        telephone: poi.tags.phone || 'N/A',
                        latitude: poi.lat,
                        longitude: poi.lon,
                        performance_score: 'N/A'
                    }));
                } catch (error) {
                    console.error('Erreur Overpass API:', error);
                    return [];
                }
            }

            // Initialiser la synthèse vocale
            initSpeechSynthesis();

            // Obtenir la position de l'utilisateur
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    async (position) => {
                        const userPos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        // Centrer la carte
                        map.setView([userPos.lat, userPos.lng], 12);

                        // Marqueur utilisateur
                        L.marker([userPos.lat, userPos.lng], {
                            icon: L.icon({
                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                                iconSize: [25, 41],
                                iconAnchor: [12, 41],
                                popupAnchor: [1, -34]
                            })
                        }).addTo(map).bindPopup('Votre position').openPopup();

                        // Préparer les garages
                        let allPOIs = dbGarages.map(garage => ({
                            ...garage,
                            type: 'Garage',
                            latitude: parseFloat(garage.latitude),
                            longitude: parseFloat(garage.longitude)
                        }));

                        // Récupérer POI OSM
                        let osmPOIs = await fetchOSMPOIs(userPos, 50000);
                        if (osmPOIs.length === 0) {
                            osmPOIs = await fetchOSMPOIs(userPos, 100000);
                        }
                        allPOIs = allPOIs.concat(osmPOIs);

                        // Calculer distances et trier
                        const poisWithDistance = allPOIs
                            .filter(poi => poi.latitude && poi.longitude)
                            .map(poi => ({
                                ...poi,
                                distance: calculateDistance(userPos.lat, userPos.lng, poi.latitude, poi.longitude)
                            }))
                            .sort((a, b) => a.distance - b.distance);

                        // Ajouter marqueurs
                        poisWithDistance.forEach((poi, index) => {
                            const isClosest = index === 0;
                            L.marker([poi.latitude, poi.longitude], {
                                icon: L.icon({
                                    iconUrl: isClosest
                                        ? 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png'
                                        : 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                                    iconSize: [25, 41],
                                    iconAnchor: [12, 41],
                                    popupAnchor: [1, -34]
                                })
                            }).addTo(map)
                            .bindPopup(`<strong>${poi.nom}</strong><br>${poi.adresse}<br>${poi.type}${isClosest ? '<br><b>Plus proche</b>' : ''}`);
                        });

                        // Mettre à jour le tableau
                        garageTableBody.innerHTML = poisWithDistance.map((poi, index) => `
                            <tr${index === 0 ? ' class="closest"' : ''}>
                                <td>${poi.type}</td>
                                <td>${poi.nom}</td>
                                <td>${poi.adresse}</td>
                                <td>${poi.telephone}</td>
                                <td>${poi.performance_score}</td>
                                <td>${poi.distance.toFixed(2)}</td>
                            </tr>
                        `).join('');

                        if (poisWithDistance.length === 0) {
                            garageTableBody.innerHTML = '<tr><td colspan="6">Aucun garage ou station-service trouvé.</td></tr>';
                        } else {
                            // Analyser le tableau pour le POI le plus proche
                            const closestRow = document.querySelector('#garage-table-body tr.closest');
                            if (closestRow) {
                                const cells = closestRow.querySelectorAll('td');
                                const type = cells[0].textContent;
                                const nom = cells[1].textContent;
                                const adresse = cells[2].textContent;
                                const distance = cells[5].textContent;
                                console.log('POI le plus proche:', { type, nom, adresse, distance });
                                speakClosestPOI(type, nom, adresse, distance);
                                // Décommentez pour Google TTS
                                // speakWithGoogleTTS(type, nom, adresse, distance);
                            }
                        }
                    },
                    (error) => {
                        errorMessage.textContent = 'Impossible d\'obtenir votre position. Veuillez activer la géolocalisation.';
                        errorMessage.style.display = 'block';

                        // Afficher garages DB
                        dbGarages.forEach(garage => {
                            if (garage.latitude && garage.longitude) {
                                L.marker([parseFloat(garage.latitude), parseFloat(garage.longitude)]).addTo(map)
                                    .bindPopup(`<strong>${garage.nom}</strong><br>${garage.adresse}<br>Garage`);
                            }
                        });

                        // Remplir tableau sans distance
                        garageTableBody.innerHTML = dbGarages.map(garage => `
                            <tr>
                                <td>Garage</td>
                                <td>${garage.nom}</td>
                                <td>${garage.adresse}</td>
                                <td>${garage.telephone || 'N/A'}</td>
                                <td>${garage.performance_score || 'N/A'}</td>
                                <td>N/A</td>
                            </tr>
                        `).join('');
                    }
                );
            } else {
                errorMessage.textContent = 'Géolocalisation non prise en charge.';
                errorMessage.style.display = 'block';

                // Afficher garages DB
                dbGarages.forEach(garage => {
                    if (garage.latitude && garage.longitude) {
                        L.marker([parseFloat(garage.latitude), parseFloat(garage.longitude)]).addTo(map)
                            .bindPopup(`<strong>${garage.nom}</strong><br>${garage.adresse}<br>Garage`);
                    }
                });

                // Remplir tableau sans distance
                garageTableBody.innerHTML = dbGarages.map(garage => `
                    <tr>
                        <td>Garage</td>
                        <td>${garage.nom}</td>
                        <td>${garage.adresse}</td>
                        <td>${garage.telephone || 'N/A'}</td>
                        <td>${garage.performance_score || 'N/A'}</td>
                        <td>N/A</td>
                    </tr>
                `).join('');
            }
        });
    </script>
</body>
</html>