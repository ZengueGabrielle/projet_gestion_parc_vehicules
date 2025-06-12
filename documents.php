<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'utilisateur') {
    header('Location: index.php');
    exit;
}

// Fetch documents for assigned vehicles
$stmt = $pdo->prepare("SELECT d.*, v.marque, v.modele, v.immatriculation 
                      FROM documents d 
                      JOIN vehicules v ON d.vehicule_id = v.id 
                      JOIN affectations a ON v.id = a.vehicule_id 
                      WHERE a.utilisateur_id = :user_id AND a.date_fin IS NULL 
                      ORDER BY d.created_at DESC");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents Liés</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { margin: 0; padding: 0; min-height: 100vh; font-family: Arial, sans-serif; color: #fff; display: flex; flex-direction: column; overflow-x: hidden; position: relative; }
        video.background-video { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
        .header { display: flex; align-items: center; padding: 15px 30px; background: rgba(52, 58, 64, 0.9); color: #fff; position: fixed; top: 0; left: 0; width: 100%; height: 60px; z-index: 1001; }
        .u-name { font-size: 24px; margin-left: 10px; }
        .u-name b { color: #dc3545; }
        .header .toggle-btn { background: none; border: none; color: #fff; font-size: 24px; cursor: pointer; }
        .header .toggle-btn:hover { color: #dc3545; }
        .main-content { display: flex; flex-direction: row; margin-top: 60px; min-height: calc(100vh - 60px); }
        .sidebar { width: 60px; background: rgba(52, 58, 64, 0.9); transition: width 0.3s ease; overflow-x: hidden; box-shadow: 2px 0 8px rgba(0, 0, 0, 0.3); position: relative; z-index: 1000; }
        .sidebar.expanded { width: 300px; }
        .sidebar .user-p { text-align: center; padding: 20px 0; }
        .sidebar .user-p img { width: 80px; height: 80px; border-radius: 50%; border: 3px solid #dc3545; }
        .sidebar .user-p h4 { color: #fff; margin-top: 10px; font-size: 18px; display: none; }
        .sidebar.expanded .user-p h4 { display: block; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar li { margin: 10px 0; }
        .sidebar a { display: flex; align-items: center; color: #fff; text-decoration: none; padding: 15px; font-size: 16px; white-space: nowrap; transition: background 0.3s, transform 0.2s; cursor: pointer; }
        .sidebar a:hover { background: #dc3545; transform: translateX(5px); }
        .sidebar a i { width: 30px; text-align: center; margin-right: 10px; font-size: 18px; }
        .sidebar a span { display: none; }
        .sidebar.expanded a span { display: inline; }
        .content { margin-left: 60px; padding: 20px; flex-grow: 1; transition: margin-left 0.3s ease; }
        .sidebar.expanded ~ .content { margin-left: 300px; }
        .documents-container { background: rgba(255, 255, 255, 0.9); padding: 30px; border-radius: 20px; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3); max-width: 800px; margin: 20px auto; border: 3px solid #dc3545; color: #333; }
        h2 { color: #dc3545; font-size: 28px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8d7da; color: #333; }
        tr:hover { background: #f1f1f1; }
        a.download { color: #dc3545; text-decoration: none; }
        a.download:hover { text-decoration: underline; }
        @media (max-width: 768px) {
            .sidebar { width: 50px; }
            .sidebar.expanded { width: 250px; }
            .content { margin-left: 50px; }
            .sidebar.expanded ~ .content { margin-left: 250px; }
            .documents-container { padding: 20px; margin: 10px; }
            h2 { font-size: 24px; }
            table { font-size: 14px; }
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
                <li><a href="dashboard_utilisateur.php" onclick="showSection('welcome')"><i class="fa fa-home"></i> <span>Tableau de bord</span></a></li>
                <li><a href="vehicle_catalog.php"><i class="fa fa-car"></i> <span>Catalogue des véhicules</span></a></li>
                <li><a href="assigned_vehicles.php"><i class="fa fa-car"></i> <span>Mes véhicules assignés</span></a></li>
                <li><a href="track_trips.php"><i class="fa fa-road"></i> <span>Suivi de mes trajets</span></a></li>
                <li><a href="report_anomaly.php"><i class="fa fa-exclamation-circle"></i> <span>Signaler une anomalie</span></a></li>
                <li><a href="maintenance_schedule.php"><i class="fa fa-wrench"></i> <span>Maintenance prévue</span></a></li>
                <li><a href="nearby_garages.php"><i class="fa fa-map-marker-alt"></i> <span>Garages à proximité</span></a></li>
                <li><a href="notifications.php"><i class="fa fa-bell"></i> <span>Notifications</span></a></li>
                <li><a href="personal_history.php"><i class="fa fa-history"></i> <span>Historique personnel</span></a></li>
                <li><a href="documents.php"><i class="fa fa-file"></i> <span>Documents liés</span></a></li>
                <li><a href="chatbot.php"><i class="fa fa-comment"></i> <span>Chatbot</span></a></li>
                <li><a href="index.php"><i class="fa fa-home"></i> <span>Retour à l'accueil</span></a></li>
                <li><a href="logout.php"><i class="fa fa-sign-out"></i> <span>Se déconnecter</span></a></li>
            </ul>
        </nav>
        <div class="content">
            <div class="documents-container">
                <h2>Documents Liés</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Véhicule</th>
                            <th>Type de Document</th>
                            <th>Description</th>
                            <th>Télécharger</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($document['marque'] . ' ' . $document['modele'] . ' (' . $document['immatriculation'] . ')'); ?></td>
                                <td><?php echo ucfirst($document['type_document']); ?></td>
                                <td><?php echo htmlspecialchars($document['description'] ?? 'N/A'); ?></td>
                                <td><a href="<?php echo htmlspecialchars($document['fichier']); ?>" class="download" download>Télécharger</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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