<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'utilisateur') {
    header('Location: index.php');
    exit;
}

// Récupérer les notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY date_envoi DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Marquer les notifications comme lues
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $pdo->prepare("UPDATE notifications SET lu = 1 WHERE utilisateur_id = ?")->execute([$_SESSION['user_id']]);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
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
        .unread {
            background: #ffe6e6;
            font-weight: bold;
        }
        button {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }
        button:hover {
            background: #c82333;
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
                <h2>Notifications</h2>
                <?php if (count($notifications) > 0): ?>
                    <form method="POST">
                        <button type="submit" name="mark_read">Marquer tout comme lu</button>
                    </form>
                    <table>
                        <thead>
                            <tr>
                                <th>Message</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $notification): ?>
                                <tr <?php echo $notification['lu'] == 0 ? 'class="unread"' : ''; ?>>
                                    <td><?php echo htmlspecialchars($notification['message']); ?></td>
                                    <td><?php echo htmlspecialchars($notification['type_notification']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($notification['date_envoi'])); ?></td>
                                    <td><?php echo $notification['lu'] == 0 ? 'Non lu' : 'Lu'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucune notification pour le moment.</p>
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