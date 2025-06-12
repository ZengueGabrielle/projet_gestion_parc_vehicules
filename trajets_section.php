<?php
$stmt = $pdo->prepare("SELECT sk.*, v.marque, v.modele 
                       FROM suivi_kilometrage sk 
                       JOIN vehicules v ON sk.vehicule_id = v.id 
                       WHERE v.id IN (SELECT vehicule_id FROM affectations WHERE utilisateur_id = ?)");
$stmt->execute([$_SESSION['user_id']]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section" id="trajets">
    <h2>Suivi de mes trajets</h2>
    <p>Nombre de trajets : <?php echo count($trips); ?></p>
    <?php if (empty($trips)): ?>
        <p>Aucun trajet enregistré.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background: #dc3545; color: #fff;">
                    <th style="padding: 10px; border: 1px solid #ccc;">Véhicule</th>
                    <th style="padding: 10px; border: 1px solid #ccc;">Kilométrage</th>
                    <th style="padding: 10px; border: 1px solid #ccc;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trips as $trip): ?>
                    <tr style="background: #fff; color: #333;">
                        <td style="padding: 10px; border: 1px solid #ccc;"><?php echo htmlspecialchars($trip['marque'] . ' ' . $trip['modele']); ?></td>
                        <td style="padding: 10px; border: 1px solid #ccc;"><?php echo htmlspecialchars($trip['kilometrage']); ?> km</td>
                        <td style="padding: 10px; border: 1px solid #ccc;"><?php echo date('d/m/Y H:i', strtotime($trip['date_enregistrement'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>