<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Génération d'une suggestion IA
if (isset($_POST['ask_ia'])) {
    try {
        $vehicule_id = intval($_POST['vehicule_id']);
        $vehicule = $pdo->prepare("SELECT * FROM vehicules WHERE id = ?");
        $vehicule->execute([$vehicule_id]);
        $vehicule = $vehicule->fetch();

        if ($vehicule) {
            $prompt = "Donne une suggestion de maintenance préventive pour ce véhicule : ".
                "Immatriculation : {$vehicule['immatriculation']}, ".
                "Marque : {$vehicule['marque']}, ".
                "Modèle : {$vehicule['modele']}, ".
                "Année : {$vehicule['annee']}, ".
                "Kilométrage actuel : {$vehicule['kilometrage_actuel']} km.";

            // Appel à l'API Gemini (simulé si pas de clé)
            $api_key = 'VOTRE_CLE_API_GEMINI';
            if ($api_key === 'VOTRE_CLE_API_GEMINI') {
                // Simulation de réponse
                $suggestion = "Pour le véhicule {$vehicule['immatriculation']}, envisagez une vérification des freins et un changement d'huile moteur, compte tenu du kilométrage actuel de {$vehicule['kilometrage_actuel']} km.";
            } else {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=$api_key";
                $data = [
                    "contents" => [
                        [
                            "parts" => [
                                ["text" => $prompt]
                            ]
                        ]
                    ]
                ];
                $options = [
                    'http' => [
                        'header'  => "Content-type: application/json",
                        'method'  => 'POST',
                        'content' => json_encode($data),
                        'timeout' => 30
                    ]
                ];
                $context = stream_context_create($options);
                $result = @file_get_contents($url, false, $context);

                if ($result !== false) {
                    $response = json_decode($result, true);
                    $suggestion = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'Aucune suggestion générée.';
                } else {
                    $suggestion = "Erreur lors de la communication avec l'API.";
                }
            }
        } else {
            $suggestion = "Véhicule non trouvé.";
        }
    } catch (Exception $e) {
        $suggestion = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les prédictions existantes
try {
    $stmt = $pdo->query("SELECT p.*, v.immatriculation FROM predictions_maintenance p JOIN vehicules v ON p.vehicule_id = v.id");
    $predictions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des prédictions : " . $e->getMessage();
}

// Récupérer les véhicules pour le formulaire
try {
    $vehicules = $pdo->query("SELECT id, immatriculation FROM vehicules")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des véhicules : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suggestions IA</title>
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
            transition: background 0.3s, transform 0.3s;
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
            color: #333;
        }
        h2 {
            color: #dc3545;
            font-size: 28px;
            margin-bottom: 20px;
            text-align: center;
        }
        .prediction {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 10px;
            border: 1px solid #dc3545;
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
            margin-right: 10px;
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
                <img src="https://images.unsplash.com/photo-1721332153282-2bd0aa3b99ed?q=80&w=1887&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="User Image">
                <h4>@<?php echo htmlspecialchars($_SESSION['nom_utilisateur'] ?? 'admin'); ?></h4>
            </div>
            <ul>
                <li><a href="admin_overview.php"><i class="fas fa-chart-bar"></i> <span>Vue globale du parc</span></a></li>
                <li><a href="admin_manage_users.php"><i class="fas fa-users"></i> <span>Gestion des utilisateurs</span></a></li>
                <li><a href="admin_manage_employees.php"><i class="fas fa-user-friends"></i> <span>Gestion des employés</span></a></li>
                <li><a href="admin_manage_vehicles.php"><i class="fas fa-car"></i> <span>Gestion des véhicules</span></a></li>
                <li><a href="admin_maintenance_alerts.php"><i class="fas fa-exclamation-triangle"></i> <span>Alertes maintenance</span></a></li>
                <li><a href="admin_ia_suggestions.php"><i class="fas fa-brain"></i> <span>Suggestions IA</span></a></li>
                <li><a href="admin_intervention_calendar.php"><i class="fas fa-calendar"></i> <span>Calendrier des interventions</span></a></li>
                <li><a href="admin_vehicle_diagnostics.php"><i class="fas fa-stethoscope"></i> <span>Diagnostic de véhicule</span></a></li>
                <li><a href="admin_manage_parts.php"><i class="fas fa-box"></i> <span>Gestion des pièces</span></a></li>
                <li><a href="admin_partner_garages.php"><i class="fas fa-tools"></i> <span>Garages partenaires</span></a></li>
                <li><a href="admin_cost_tracking.php"><i class="fas fa-dollar-sign"></i> <span>Suivi des coûts</span></a></li>
                <li><a href="admin_intervention_reports.php"><i class="fas fa-file-alt"></i> <span>Rapport d’intervention</span></a></li>
                <li><a href="admin_incident_tracking.php"><i class="fas fa-shield-alt"></i> <span>Suivi des incidents</span></a></li>
                <li><a href="admin_maintenance_history.php"><i class="fas fa-wrench"></i> <span>Historique des maintenances</span></a></li>
                <li><a href="admin_manage_assignments.php"><i class="fas fa-calendar-check"></i> <span>Gestion des affectations</span></a></li>
                <li><a href="admin_manage_roles.php"><i class="fas fa-lock"></i> <span>Gestion des rôles & accès</span></a></li>
                <li><a href="admin_integrations.php"><i class="fas fa-plug"></i> <span>Intégrations externes</span></a></li>
                <li><a href="admin_manage_backups.php"><i class="fas fa-database"></i> <span>Gestion des sauvegardes</span></a></li>
                <li><a href="admin_reports.php"><i class="fas fa-file-excel"></i> <span>Rapports globaux</span></a></li>
                <li><a href="dashboard_admin.php"><i class="fa fa-home"></i> <span>Retour au tableau de bord</span></a></li>
                <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> <span>Déconnexion</span></a></li>
            </ul>
        </nav>
        <div class="content">
            <div class="dashboard-container">
                <h2>Suggestions IA</h2>
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (isset($suggestion)): ?>
                    <div class="success"><?php echo nl2br(htmlspecialchars($suggestion)); ?></div>
                <?php endif; ?>
                <form method="POST" style="margin-bottom:30px;">
                    <label for="vehicule_id"><strong>Générer une suggestion IA pour le véhicule :</strong></label>
                    <select name="vehicule_id" id="vehicule_id" required>
                        <?php foreach ($vehicules as $v): ?>
                            <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['immatriculation']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="ask_ia" class="btn btn-primary">Obtenir une suggestion IA</button>
                </form>
                <h3>Prédictions existantes</h3>
                <?php if (empty($predictions)): ?>
                    <p style="text-align: center;">Aucune suggestion disponible.</p>
                <?php else: ?>
                    <?php foreach ($predictions as $prediction): ?>
                        <div class="prediction">
                            <p><strong>Véhicule :</strong> <?php echo htmlspecialchars($prediction['immatriculation']); ?></p>
                            <p><strong>Type :</strong> <?php echo htmlspecialchars($prediction['type_maintenance']); ?></p>
                            <p><strong>Date Prévue :</strong> <?php echo htmlspecialchars($prediction['date_prevue']); ?></p>
                            <p><strong>Probabilité :</strong> <?php echo htmlspecialchars($prediction['probabilite']); ?></p>
                            <p><strong>Description :</strong> <?php echo htmlspecialchars($prediction['description']); ?></p>
                            <button class="btn btn-primary" onclick="readPrediction('<?php echo addslashes($prediction['description']); ?>')">Lire à haute voix</button>
                        </div>
                    <?php endforeach; ?>
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

        function readPrediction(text) {
            const speech = new SpeechSynthesisUtterance(text);
            speech.lang = 'fr-FR';
            window.speechSynthesis.speak(speech);
        }
    </script>
</body>
</html>