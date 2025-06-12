<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gestionnaire') {
    header('Location: index.php');
    exit;
}

// Marquer une alerte comme traitée
if (isset($_POST['mark_as_handled'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM predictions_maintenance WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: maintenance_alerts.php');
    exit;
}

// Récupérer les alertes
$stmt = $pdo->query("SELECT p.*, v.immatriculation FROM predictions_maintenance p JOIN vehicules v ON p.vehicule_id = v.id WHERE p.date_prevue <= NOW()");
$alertes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertes de Maintenance</title>
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
        .container {
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
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        .btn-success {
            background: #28a745;
            color: #fff;
        }
        .btn-success:hover {
            background: #218838;
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
            .container {
                padding: 20px;
                margin: 10px;
            }
            h1 {
                font-size: 28px;
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
    <div class="container">
        <h1>Alertes de Maintenance</h1>
        <?php if (empty($alertes)): ?>
            <p style="text-align: center;">Aucune alerte en attente.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Véhicule</th>
                    <th>Type</th>
                    <th>Date Prévue</th>
                    <th>Probabilité</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($alertes as $alerte): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($alerte['immatriculation']); ?></td>
                        <td><?php echo htmlspecialchars($alerte['type_maintenance']); ?></td>
                        <td><?php echo htmlspecialchars($alerte['date_prevue']); ?></td>
                        <td><?php echo htmlspecialchars($alerte['probabilite']); ?></td>
                        <td><?php echo htmlspecialchars($alerte['description']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $alerte['id']; ?>">
                                <button type="submit" name="mark_as_handled" class="btn btn-success">Marquer comme traité</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <a href="dashboard_gestionnaire.php" class="back-link">Retour au tableau de bord</a>
    </div>
</body>
</html>