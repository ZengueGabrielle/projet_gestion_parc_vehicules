<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gestionnaire') {
    header('Location: index.php');
    exit;
}

// Récupérer les coûts par mois
$stmt = $pdo->query("SELECT DATE_FORMAT(date_maintenance, '%Y-%m') as mois, SUM(cout) as total FROM maintenances GROUP BY mois");
$coutes = $stmt->fetchAll();
$labels = [];
$values = [];
foreach ($coutes as $cout) {
    $labels[] = $cout['mois'];
    $values[] = $cout['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi des Coups</title>
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
        canvas {
            max-width: 800px;
            margin: 0 auto;
            display: block;
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
            canvas {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <video class="background-video" autoplay loop muted playsinline>
        <source src="assets/videos/background.mp4" type="video/mp4">
    </video>
    <div class="container">
        <h1>Suivi des Coûts</h1>
        <canvas id="costChart"></canvas>
        <a href="dashboard_gestionnaire.php" class="back-link">Retour au tableau de bord</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        const ctx = document.getElementById('costChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Coûts (€)',
                    data: <?php echo json_encode($values); ?>,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.2)',
                    fill: true,
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>