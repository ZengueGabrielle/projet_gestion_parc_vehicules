<?php
// Placeholder for Gemini API integration to generate maintenance predictions
// Replace 'YOUR_GEMINI_API_KEY' with your actual Gemini API key in your configuration
// Example: Call Gemini API to predict maintenance based on vehicle mileage and history
function generateMaintenancePredictions($pdo, $vehicule_id) {
    // Mock API call (replace with actual Gemini API integration)
    // $response = callGeminiAPI(['vehicule_id' => $vehicule_id, 'api_key' => 'YOUR_GEMINI_API_KEY']);
    // For demo, insert a sample prediction
    $stmt = $pdo->prepare("INSERT INTO predictions_maintenance (vehicule_id, type_maintenance, date_prevue, probabilite, commentaire) 
                           SELECT ?, 'Vidange', DATE_ADD(NOW(), INTERVAL 30 DAY), 0.85, 'Prédiction basée sur le kilométrage' 
                           WHERE NOT EXISTS (SELECT 1 FROM predictions_maintenance WHERE vehicule_id = ?)");
    $stmt->execute([$vehicule_id, $vehicule_id]);
}

// Fetch assigned vehicles and generate predictions
$stmt = $pdo->prepare("SELECT vehicule_id FROM affectations WHERE utilisateur_id = ? AND date_fin IS NULL");
$stmt->execute([$_SESSION['user_id']]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($vehicles as $vehicle) {
    generateMaintenancePredictions($pdo, $vehicle['vehicule_id']);
}

// Fetch maintenance predictions
$stmt = $pdo->prepare("SELECT pm.*, v.marque, v.modele 
                       FROM predictions_maintenance pm 
                       JOIN vehicules v ON pm.vehicule_id = v.id 
                       WHERE pm.vehicule_id IN (SELECT vehicule_id FROM affectations WHERE utilisateur_id = ?)");
$stmt->execute([$_SESSION['user_id']]);
$predictions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section" id="maintenance">
    <h2>Maintenance prévue</h2>
    <?php if (empty($predictions)): ?>
        <p>Aucune maintenance prévue.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background: #dc3545; color: #fff;">
                    <th style="padding: 10px; border: 1px solid #ccc;">Véhicule</th>
                    <th style="padding: 10px; border: 1px solid #ccc;">Type</th>
                    <th style="padding: 10px; border: 1px solid #ccc;">Date prévue</th>
                    <th style="padding: 10px; border: 1px solid #ccc;">Probabilité</th>
                    <th style="padding: 10px; border: 1px solid #ccc;">Commentaire</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($predictions as $prediction): ?>
                    <tr style="background: #fff; color: #333;">
                        <td style="padding: 10px; border: 1px solid #ccc;"><?php echo htmlspecialchars($prediction['marque'] . ' ' . $prediction['modele']); ?></td>
                        <td style="padding: 10px; border: 1px solid #ccc;"><?php echo htmlspecialchars($prediction['type_maintenance']); ?></td>
                        <td style="padding: 10px; border: 1px solid #ccc;"><?php echo date('d/m/Y', strtotime($prediction['date_prevue'])); ?></td>
                        <td style="padding: 10px; border: 1px solid #ccc;"><?php echo htmlspecialchars($prediction['probabilite'] * 100); ?>%</td>
                        <td style="padding: 10px; border: 1px solid #ccc;"><?php echo htmlspecialchars($prediction['commentaire']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>