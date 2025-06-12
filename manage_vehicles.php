<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gestionnaire') {
    header('Location: index.php');
    exit;
}

// Récupérer les véhicules
$stmt = $pdo->query("SELECT * FROM vehicules");
$vehicules = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des véhicules</title>
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
            table { font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestion des véhicules</h1>
        <table>
            <tr>
                <th>ID</th>
                <th>Marque</th>
                <th>Modèle</th>
                <th>Immatriculation</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($vehicules as $vehicule): ?>
                <tr>
                    <td><?php echo htmlspecialchars($vehicule['id']); ?></td>
                    <td><?php echo htmlspecialchars($vehicule['marque']); ?></td>
                    <td><?php echo htmlspecialchars($vehicule['modele']); ?></td>
                    <td><?php echo htmlspecialchars($vehicule['immatriculation']); ?></td>
                    <td><?php echo htmlspecialchars($vehicule['statut']); ?></td>
                    <td>
                        <!-- Actions à compléter selon besoins -->
                        <a href="edit_vehicle.php?id=<?php echo $vehicule['id']; ?>">Modifier</a> |
                        <a href="delete_vehicle.php?id=<?php echo $vehicule['id']; ?>" onclick="return confirm('Supprimer ce véhicule ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <a href="dashboard_gestionnaire.php" class="back-link">Retour au tableau de bord</a>
    </div>
</body>
</html>