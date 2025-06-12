<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gestionnaire') {
    header('Location: index.php');
    exit;
}

// Ajouter un rapport
if (isset($_POST['add_report'])) {
    $vehicule_id = $_POST['vehicule_id'];
    $date = $_POST['date'];
    $description = $_POST['description'];
    $cout = $_POST['cout'];
    $stmt = $pdo->prepare("INSERT INTO maintenances (vehicule_id, date_maintenance, type_maintenance, cout, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$vehicule_id, $date, 'Intervention', $cout, $description]);
    header('Location: intervention_reports.php');
    exit;
}

// Supprimer un rapport
if (isset($_POST['delete_report'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM maintenances WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: intervention_reports.php');
    exit;
}

// Récupérer les rapports
$stmt = $pdo->query("SELECT m.*, v.immatriculation FROM maintenances m JOIN vehicules v ON m.vehicule_id = v.id");
$rapports = $stmt->fetchAll();

// Récupérer les véhicules
$stmt_veh = $pdo->query("SELECT id, immatriculation FROM vehicules");
$vehicules = $stmt_veh->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports d’Intervention</title>
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
        h1, h2 {
            color: #dc3545;
            text-align: center;
        }
        form {
            margin-bottom: 30px;
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
        .btn-danger {
            background: #dc3545;
            color: #fff;
        }
        .btn-danger:hover {
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
        <h1>Rapports d’Intervention</h1>
        <h2>Créer un rapport</h2>
        <form method="POST">
            <div class="form-group">
                <label for="vehicule_id">Véhicule</label>
                <select id="vehicule_id" name="vehicule_id" required>
                    <?php foreach ($vehicules as $vehicule): ?>
                        <option value="<?php echo $vehicule['id']; ?>"><?php echo htmlspecialchars($vehicule['immatriculation']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="date">Date</label>
                <input type="datetime-local" id="date" name="date" required>
            </div>
            <div class="form-group">
                <label for="cout">Coût (€)</label>
                <input type="number" id="cout" name="cout" step="0.01" required min="0">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            <button type="submit" name="add_report" class="btn btn-primary">Ajouter</button>
        </form>
        <h2>Liste des rapports</h2>
        <table>
            <tr>
                <th>Véhicule</th>
                <th>Date</th>
                <th>Type</th>
                <th>Coût (€)</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
            <?php foreach ($rapports as $rapport): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rapport['immatriculation']); ?></td>
                    <td><?php echo htmlspecialchars($rapport['date_maintenance']); ?></td>
                    <td><?php echo htmlspecialchars($rapport['type_maintenance']); ?></td>
                    <td><?php echo htmlspecialchars($rapport['cout']); ?></td>
                    <td><?php echo htmlspecialchars($rapport['description']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $rapport['id']; ?>">
                            <button type="submit" name="delete_report" class="btn btn-danger">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <a href="dashboard_gestionnaire.php" class="back-link">Retour au tableau de bord</a>
    </div>
</body>
</html>