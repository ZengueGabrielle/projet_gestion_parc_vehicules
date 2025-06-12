<?php
$stmt = $pdo->prepare("SELECT d.*, v.marque, v.modele 
                       FROM documents d 
                       JOIN vehicules v ON d.vehicule_id = v.id 
                       WHERE v.id IN (SELECT vehicule_id FROM affectations WHERE utilisateur_id = ?)");
$stmt->execute([$_SESSION['user_id']]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section" id="documents">
    <h2>Documents liés</h2>
    <?php if (empty($documents)): ?>
        <p>Aucun document disponible.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background: #dc3545; color: #fff;">
                    <th style="padding: 10px; border: 1px solid #ccc;">Véhicule</th>
                    <th style="padding: 10px; border: 1px solid #ccc;">Type</th>
                    <th style="padding: 10px; border: 1px solid #ccc;">Description</th>
                    <th style="padding: 10px; border: 1px solid #ccc;">Télécharger</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $document): ?>
                    <tr style="background: #fff; color: #333;">
                        <td style="padding: 10px; border: 1px solid #ccc;"><?php echo htmlspecialchars($document['marque'] . ' ' . $document['modele']); ?></td>
                        <td style="padding: 10px; border: 1px solid #ccc;"><?php echo htmlspecialchars($document['type_document']); ?></td>
                        <td style="padding: 10px; border: 1px solid #ccc;"><?php echo htmlspecialchars($document['description'] ?? 'N/A'); ?></td>
                        <td style="padding: 10px; border: 1px solid #ccc;">
                            <a href="<?php echo htmlspecialchars($document['fichier']); ?>" download style="color: #dc3545;">Télécharger</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>