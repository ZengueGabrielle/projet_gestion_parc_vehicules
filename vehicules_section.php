<?php
// Fetch assigned vehicles
$stmt = $pdo->prepare("SELECT v.*, a.date_debut, i.chemin_image 
                       FROM affectations a 
                       JOIN vehicules v ON a.vehicule_id = v.id 
                       LEFT JOIN images_vehicules i ON v.id = i.vehicule_id AND i.est_principale = 1 
                       WHERE a.utilisateur_id = ? AND a.date_fin IS NULL");
$stmt->execute([$_SESSION['user_id']]);
$assigned_vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section" id="vehicules">
    <h2>Mes véhicules assignés</h2>
    <p>Véhicules assignés : <?php echo count($assigned_vehicles); ?></p>
    <?php if (empty($assigned_vehicles)): ?>
        <p>Aucun véhicule assigné pour le moment.</p>
    <?php else: ?>
        <div class="vehicle-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
            <?php foreach ($assigned_vehicles as $vehicle): ?>
                <div class="vehicle-card" style="background: #fff; border: 2px solid #dc3545; border-radius: 10px; padding: 15px; text-align: left; color: #333;">
                    <?php if ($vehicle['chemin_image']): ?>
                        <img src="<?php echo htmlspecialchars($vehicle['chemin_image']); ?>" alt="Véhicule" style="width: 100%; height: 150px; object-fit: cover; border-radius: 5px;">
                    <?php else: ?>
                        <img src="assets/images/default_vehicle.jpg" alt="Véhicule par défaut" style="width: 100%; height: 150px; object-fit: cover; border-radius: 5px;">
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($vehicle['marque'] . ' ' . $vehicle['modele']); ?></h3>
                    <p>Immatriculation: <?php echo htmlspecialchars($vehicle['immatriculation']); ?></p>
                    <p>Année: <?php echo htmlspecialchars($vehicle['annee']); ?></p>
                    <p>Kilométrage: <?php echo htmlspecialchars($vehicle['kilometrage_actuel']); ?> km</p>
                    <p>Statut: 
                        <?php
                        $km = $vehicle['kilometrage_actuel'];
                        if ($km < 100000) {
                            echo "Bonne condition";
                        } elseif ($km <= 200000) {
                            echo "À surveiller";
                        } else {
                            echo "Maintenance requise";
                        }
                        ?>
                    </p>
                    <p>Date d'assignation: <?php echo date('d/m/Y', strtotime($vehicle['date_debut'])); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>