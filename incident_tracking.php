<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gestionnaire') {
    header('Location: index.php');
    exit;
}

// Mettre à jour le statut d'un incident
if (isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $statut = $_POST['statut'];
    $stmt = $pdo->prepare("UPDATE anomalies SET statut = ? WHERE id = ?");
    $stmt->execute([$statut, $id]);
    header('Location: incident_tracking.php');
    exit;
}

// Récupérer les incidents
$stmt = $pdo->query("SELECT a.*, v.immatriculation, u.nom_utilisateur FROM anomalies a JOIN vehicules v ON a.vehicule_id = v.id JOIN utilisateurs u ON a.utilisateur_id = u.id");
$incidents = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi des Incidents</title>
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
        .btn-primary {
            background: #dc3545;
            color: #fff;
        }
        .btn-primary:hover {
            background: #b02a37;
        }
        select {
            padding: 10px;
            border: 1px solid #dc3545;
            border-radius: 5px;
            font-size: 16px;
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
        <h1>Suivi des Incidents</h1>
        <?php if (empty($incidents)): ?>
            <p style="text-align: center;">Aucun incident signalé.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Véhicule</th>
                    <th>Signalé par</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($incidents as $incident): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($incident['immatriculation']); ?></td>
                        <td><?php echo htmlspecialchars($incident['nom_utilisateur']); ?></td>
                        <td><?php echo htmlspecialchars($incident['date_signalement']); ?></td>
                        <td><?php echo htmlspecialchars($incident['description']); ?></td>
                        <td><?php echo htmlspecialchars($incident['statut']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $incident['id']; ?>">
                                <select name="statut" required>
                                    <option value="en_attente" <?php echo $incident['statut'] === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="en_cours" <?php echo $incident['statut'] === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                    <option value="resolue" <?php echo $incident['statut'] === 'resolue' ? 'selected' : ''; ?>>Résolue</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-primary">Mettre à jour</button>
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