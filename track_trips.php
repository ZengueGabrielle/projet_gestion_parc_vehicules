<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/update_vehicle_status.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] !== 3) {
    header('Location: index.php');
    exit;
}

if (isset($_POST['add_trip'])) {
    try {
        $vehicule_id = $_POST['vehicule_id'];
        $kilometrage = $_POST['kilometrage'];
        $commentaire = trim($_POST['commentaire']) ?: null;

        // Vérifier que le kilométrage est valide
        $stmt_last_km = $pdo->prepare("SELECT kilometrage_actuel FROM vehicules WHERE id = ?");
        $stmt_last_km->execute([$vehicule_id]);
        $last_km = $stmt_last_km->fetchColumn();
        if ($kilometrage < $last_km) {
            $error = "Le kilométrage doit être supérieur au dernier enregistré ($last_km km).";
        } else {
            // Mettre à jour le kilométrage du véhicule
            $stmt_update_km = $pdo->prepare("UPDATE vehicules SET kilometrage_actuel = ? WHERE id = ?");
            $stmt_update_km->execute([$kilometrage, $vehicule_id]);

            // Enregistrer le trajet
            $stmt = $pdo->prepare("INSERT INTO suivi_kilometrage (vehicule_id, kilometrage, date_enregistrement) VALUES (?, ?, NOW())");
            $stmt->execute([$vehicule_id, $kilometrage]);
            $success = "Trajet enregistré avec succès.";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de l'enregistrement : " . $e->getMessage();
    }
}

try {
    $stmt_trips = $pdo->prepare("SELECT sk.*, v.marque, v.modele FROM suivi_kilometrage sk JOIN vehicules v ON sk.vehicule_id = v.id WHERE sk.vehicule_id IN (SELECT vehicule_id FROM affectations WHERE utilisateur_id = ?) ORDER BY sk.date_enregistrement DESC");
    $stmt_trips->execute([$_SESSION['user_id']]);
    $trips = $stmt_trips->fetchAll(PDO::FETCH_ASSOC);

    $stmt_vehicles = $pdo->prepare("SELECT v.id, v.marque, v.modele FROM affectations a JOIN vehicules v ON a.vehicule_id = v.id WHERE a.utilisateur_id = ? AND a.date_fin IS NULL");
    $stmt_vehicles->execute([$_SESSION['user_id']]);
    $vehicles = $stmt_vehicles->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des données : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de Mes Trajets</title>
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
        .container {
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
            font-size: 28px;
            margin-bottom: 20px;
            text-align: center;
        }
        .error, .success {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .error {
            background: #f8d7da;
            color: #dc3545;
        }
        .success {
            background: #d4edda;
            color: #28a745;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-size: 16px;
            margin-bottom: 5px;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #dc3545;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn-primary {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            background: #dc3545;
            color: #fff;
            transition: background 0.3s;
        }
        .btn-primary:hover {
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
            .container {
                padding: 20px;
                margin: 10px;
            }
            h2, h3 {
                font-size: 24px;
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
    </video>
    <header class="header">
        <button class="toggle-btn"><i class="fa-solid fa-bars"></i></button>
        <h2 class="u-name">GESTION <b>PARC</b></h2>
    </header>
    <div class="main-content">
        <?php include 'includes/sidebar.php'; ?>
        <div class="content">
            <div class="container">
                <h2>Suivi de Mes Trajets</h2>
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <h3>Enregistrer un trajet</h3>
                <?php if (empty($vehicles)): ?>
                    <p style="text-align: center;">Aucun véhicule assigné pour enregistrer un trajet.</p>
                <?php else: ?>
                    <form method="POST">
                        <div class="form-group">
                            <label for="vehicule_id">Véhicule</label>
                            <select id="vehicule_id" name="vehicule_id" required>
                                <option value="">Sélectionner un véhicule</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?php echo htmlspecialchars($vehicle['id']); ?>"><?php echo htmlspecialchars($vehicle['marque'] . ' ' . $vehicle['modele']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="kilometrage">Kilométrage</label>
                            <input type="number" id="kilometrage" name="kilometrage" required min="0">
                        </div>
                        <div class="form-group">
                            <label for="commentaire">Commentaire (facultatif)</label>
                            <textarea id="commentaire" name="commentaire" rows="4"></textarea>
                        </div>
                        <button type="submit" name="add_trip" class="btn-primary">Enregistrer</button>
                    </form>
                <?php endif; ?>
                <h3>Mes trajets</h3>
                <?php if (empty($trips)): ?>
                    <p style="text-align: center;">Aucun trajet enregistré.</p>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Véhicule</th>
                            <th>Date</th>
                            <th>Kilométrage</th>
                            <th>Commentaire</th>
                        </tr>
                        <?php foreach ($trips as $trip): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($trip['marque'] . ' ' . $trip['modele']); ?></td>
                                <td><?php echo htmlspecialchars($trip['date_enregistrement']); ?></td>
                                <td><?php echo htmlspecialchars($trip['kilometrage']); ?> km</td>
                                <td><?php echo htmlspecialchars($trip['commentaire'] ?: '-'); ?></td>
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