<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gestionnaire') {
    header('Location: index.php');
    exit;
}

// Ajouter une pièce
if (isset($_POST['add_part'])) {
    $nom = $_POST['nom'];
    $reference = $_POST['reference'];
    $quantite = $_POST['quantite'];
    $prix = $_POST['prix'];
    $description = $_POST['description'];
    $stmt = $pdo->prepare("INSERT INTO pieces (nom, reference, quantite_en_stock, prix_unitaire, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nom, $reference, $quantite, $prix, $description]);
    header('Location: manage_parts.php');
    exit;
}

// Supprimer une pièce
if (isset($_POST['delete_part'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM pieces WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: manage_parts.php');
    exit;
}

// Récupérer les pièces
$stmt = $pdo->query("SELECT * FROM pieces");
$pieces = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Pièces</title>
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
        input, textarea {
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
        <h1>Gestion des Pièces</h1>
        <h2>Ajouter une pièce</h2>
        <form method="POST">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom" required>
            </div>
            <div class="form-group">
                <label>Référence</label>
                <input type="text" name="reference" required>
            </div>
            <div class="form-group">
                <label>Quantité en stock</label>
                <input type="number" name="quantite" required min="0">
            </div>
            <div class="form-group">
                <label>Prix unitaire (€)</label>
                <input type="number" name="prix" step="0.01" required min="0">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description"></textarea>
            </div>
            <button type="submit" name="add_part" class="btn btn-primary">Ajouter</button>
        </form>
        <h2>Liste des pièces</h2>
        <table>
            <tr>
                <th>Nom</th>
                <th>Référence</th>
                <th>Quantité</th>
                <th>Prix (€)</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
            <?php foreach ($pieces as $piece): ?>
                <tr>
                    <td><?php echo htmlspecialchars($piece['nom']); ?></td>
                    <td><?php echo htmlspecialchars($piece['reference']); ?></td>
                    <td><?php echo htmlspecialchars($piece['quantite_en_stock']); ?></td>
                    <td><?php echo htmlspecialchars($piece['prix_unitaire']); ?></td>
                    <td><?php echo htmlspecialchars($piece['description']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $piece['id']; ?>">
                            <button type="submit" name="delete_part" class="btn btn-danger">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <a href="dashboard_gestionnaire.php" class="back-link">Retour au tableau de bord</a>
    </div>
</body>
</html>