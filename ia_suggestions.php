<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gestionnaire') {
    header('Location: index.php');
    exit;
}

// Génération d'une suggestion IA via Gemini
if (isset($_POST['ask_ia'])) {
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

        // Appel à l'API Gemini
        $api_key = 'AIzaSyDaQr3Uhikdu0DYoO2utiJRR0kJXrF0y7c';
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
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result !== false) {
            $response = json_decode($result, true);
            $suggestion = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'Aucune suggestion générée.';
        } else {
            $suggestion = "Erreur lors de la communication avec l'API Gemini.";
        }
    } else {
        $suggestion = "Véhicule non trouvé.";
    }
}

// Récupérer les prédictions existantes
$stmt = $pdo->query("SELECT p.*, v.immatriculation FROM predictions_maintenance p JOIN vehicules v ON p.vehicule_id = v.id");
$predictions = $stmt->fetchAll();

// Récupérer les véhicules pour le formulaire
$vehicules = $pdo->query("SELECT id, immatriculation FROM vehicules")->fetchAll();
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
        }
    </style>
</head>
<body>
    <video class="background-video" autoplay loop muted playsinline>
        <source src="assets/videos/background.mp4" type="video/mp4">
    </video>
    <div class="container">
        <h1>Suggestions IA</h1>
        <!-- Formulaire pour générer une suggestion IA -->
        <form method="POST" style="margin-bottom:30px;">
            <label for="vehicule_id"><strong>Générer une suggestion IA pour le véhicule :</strong></label>
            <select name="vehicule_id" id="vehicule_id" required>
                <?php foreach ($vehicules as $v): ?>
                    <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['immatriculation']); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="ask_ia" class="btn btn-primary">Obtenir une suggestion IA</button>
        </form>

        <!-- Affichage de la suggestion IA générée -->
        <?php if (isset($suggestion)): ?>
            <div class="prediction">
                <strong>Suggestion IA :</strong>
                <p><?php echo nl2br(htmlspecialchars($suggestion)); ?></p>
            </div>
        <?php endif; ?>

        <!-- Affichage des prédictions existantes -->
        <?php if (empty($predictions)): ?>
            <p style="text-align: center;">Aucune suggestion disponible.</p>
        <?php else: ?>
            <?php foreach ($predictions as $prediction): ?>
                <div class="prediction">
                    <p><strong>Véhicule :</strong> <?php echo htmlspecialchars($prediction['immatriculation']); ?></p>
                    <p><strong>Type :</strong> <?php echo htmlspecialchars($prediction['type_maintenance']); ?></p>
                    <p><strong>Date Prévue :</strong> <?php echo htmlspecialchars($prediction['date_prevue']); ?></p>
                    <p><strong>Probabilité :</strong> <?php echo htmlspecialchars($prediction['probabilite']); ?></p>
                    <p><strong>Description :</strong>
                        <?php
                        $desc = (isset($prediction['description']) && $prediction['description'] !== null) ? $prediction['description'] : 'Non renseignée';
                        echo htmlspecialchars($desc);
                        ?>
                    </p>
                    <button class="btn btn-primary" onclick="readPrediction('<?php echo addslashes($desc); ?>')">Lire à haute voix</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <a href="dashboard_gestionnaire.php" class="back-link">Retour au tableau de bord</a>
    </div>
    <script>
        function readPrediction(text) {
            const speech = new SpeechSynthesisUtterance(text);
            speech.lang = 'fr-FR';
            window.speechSynthesis.speak(speech);
        }
    </script>
</body>
</html>