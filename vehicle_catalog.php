<?php
session_start();
require_once 'includes/config.php';

// Vérifier si l'utilisateur est connecté et a le rôle 'utilisateur'
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 3) {
    $_SESSION['error'] = "Accès non autorisé. Veuillez vous connecter en tant qu'utilisateur.";
    header('Location: index.php');
    exit;
}

// Initialiser le message d'erreur
$error_message = '';

try {
    // Requête pour récupérer les véhicules avec image principale et dernier kilométrage
    $stmt = $pdo->query("
        SELECT v.id, v.marque, v.modele, v.immatriculation, v.annee, v.statut, v.kilometrage_actuel,
               CASE WHEN a.id IS NOT NULL AND a.date_fin IS NULL THEN 'Assigné' ELSE 'Disponible' END AS disponibilite,
               i.chemin_image,
               COALESCE(
                   (SELECT kilometrage FROM suivi_kilometrage sk 
                    WHERE sk.vehicule_id = v.id 
                    ORDER BY sk.date_enregistrement DESC LIMIT 1),
                   v.kilometrage_actuel
               ) AS dernier_kilometrage
        FROM vehicules v
        LEFT JOIN affectations a ON v.id = a.vehicule_id AND a.date_fin IS NULL
        LEFT JOIN images_vehicules i ON v.id = i.vehicule_id AND i.est_principale = 1
        ORDER BY v.marque, v.modele
    ");
    $vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des véhicules : " . $e->getMessage();
    error_log($error_message, 3, 'logs/errors.log');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue des Véhicules</title>
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
            max-width: 1200px;
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
        .statut-disponible {
            color: #28a745;
            font-weight: bold;
        }
        .statut-affecte {
            color: #dc3545;
            font-weight: bold;
        }
        .statut-en_maintenance {
            color: #ffc107;
            font-weight: bold;
        }
        .statut-hors_service {
            color: #6c757d;
            font-weight: bold;
        }
        .vehicle-image {
            width: 100px;
            height: auto;
            object-fit: cover;
            border-radius: 5px;
        }
        .no-image {
            color: #999;
            font-style: italic;
        }
        .error {
            background: #f8d7da;
            color: #dc3545;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message-success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .request-btn {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .request-btn:hover {
            background: #218838;
        }
        .request-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        @media (max-width: 768px) {
            .sidebar { width: 50px; }
            .sidebar.expanded { width: 250px; }
            .content { margin-left: 50px; }
            .sidebar.expanded ~ .content { margin-left: 250px; }
            .dashboard-container { padding: 20px; margin: 10px; }
            h2 { font-size: 24px; }
            table { font-size: 14px; }
            th, td { padding: 8px; }
            .vehicle-image { width: 80px; }
            .request-btn { padding: 6px 10px; font-size: 12px; }
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
                <h4>@<?php echo htmlspecialchars($_SESSION['nom_utilisateur'] ?? 'Utilisateur'); ?></h4>
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
                <li><a href="logout.php"><i class="fa fa-sign-out"></i> <span>Se déconnecter</span></a></li>
            </ul>
        </nav>
        <div class="content">
            <div class="dashboard-container">
                <h2>Catalogue des Véhicules</h2>
                <?php if (!empty($_SESSION['success'])): ?>
                    <p class="message-success"><?php echo htmlspecialchars($_SESSION['success']); ?></p>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error'])): ?>
                    <p class="error"><?php echo htmlspecialchars($_SESSION['error']); ?></p>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                <?php if (!empty($error_message)): ?>
                    <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>
                <?php if (!empty($vehicules)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Marque</th>
                                <th>Modèle</th>
                                <th>Immatriculation</th>
                                <th>Année</th>
                                <th>Kilométrage</th>
                                <th>Statut</th>
                                <th>Disponibilité</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicules as $vehicule): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $image_path = !empty($vehicule['chemin_image']) ? $vehicule['chemin_image'] : '';
                                        $full_path = !empty($image_path) ? $_SERVER['DOCUMENT_ROOT'] . '/projet_gestion_parc/' . $image_path : '';
                                        if (!empty($image_path) && file_exists($full_path)): ?>
                                            <img src="/projet_gestion_parc/<?php echo htmlspecialchars($image_path); ?>" alt="Véhicule" class="vehicle-image">
                                        <?php else:
                                            error_log("Image manquante pour véhicule ID {$vehicule['id']}: $full_path", 3, 'logs/errors.log'); ?>
                                            <span class="no-image">Aucune image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($vehicule['marque']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicule['modele']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicule['immatriculation']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicule['annee'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($vehicule['dernier_kilometrage'], 0, ',', ' ')) . ' km'; ?></td>
                                    <td class="statut-<?php echo strtolower(str_replace(' ', '_', $vehicule['statut'])); ?>">
                                        <?php echo htmlspecialchars($vehicule['statut']); ?>
                                    </td>
                                    <td class="statut-<?php echo strtolower($vehicule['disponibilite']); ?>">
                                        <?php echo htmlspecialchars($vehicule['disponibilite']); ?>
                                    </td>
                                    <td>
                                        <?php if ($vehicule['statut'] === 'disponible' && $vehicule['disponibilite'] === 'Disponible'): ?>
                                            <form action="request_vehicle.php" method="POST">
                                                <input type="hidden" name="vehicule_id" value="<?php echo $vehicule['id']; ?>">
                                                <input type="hidden" name="utilisateur_id" value="<?php echo $_SESSION['user_id']; ?>">
                                                <button type="submit" class="request-btn">Demander l'assignation</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="request-btn" disabled>Non disponible</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucun véhicule disponible dans le catalogue.</p>
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