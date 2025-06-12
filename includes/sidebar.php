<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar">
    <div class="user-p">
        <img src="assets/images/user.png" alt="User Image">
        <h4>@<?php echo htmlspecialchars($_SESSION['nom_utilisateur']); ?></h4>
    </div>
    <ul>
        <li><a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>"><i class="fa fa-home"></i> <span>Tableau de bord</span></a></li>
        <li><a href="vehicle_catalog.php" class="<?php echo $current_page === 'vehicle_catalog.php' ? 'active' : ''; ?>"><i class="fa fa-car"></i> <span>Catalogue des voitures</span></a></li>
        <li><a href="my_vehicles.php" class="<?php echo $current_page === 'my_vehicles.php' ? 'active' : ''; ?>"><i class="fa fa-car"></i> <span>Mes voitures assignées</span></a></li>
        <li><a href="track_trips.php" class="<?php echo $current_page === 'track_trips.php' ? 'active' : ''; ?>"><i class="fa fa-road"></i> <span>Suivi de mes trajets</span></a></li>
        <li><a href="report_anomaly.php" class="<?php echo $current_page === 'report_anomaly.php' ? 'active' : ''; ?>"><i class="fa fa-exclamation-circle"></i> <span>Signaler une anomalie</span></a></li>
        <li><a href="planned_maintenance.php" class="<?php echo $current_page === 'planned_maintenance.php' ? 'active' : ''; ?>"><i class="fa fa-wrench"></i> <span>Maintenance prévue</span></a></li>
        <li><a href="nearby_garages.php" class="<?php echo $current_page === 'nearby_garages.php' ? 'active' : ''; ?>"><i class="fa fa-map-marker-alt"></i> <span>Garages à proximité</span></a></li>
        <li><a href="notifications.php" class="<?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>"><i class="fa fa-bell"></i> <span>Notifications</span></a></li>
        <li><a href="personal_history.php" class="<?php echo $current_page === 'personal_history.php' ? 'active' : ''; ?>"><i class="fa fa-history"></i> <span>Historique personnel</span></a></li>
        <li><a href="linked_documents.php" class="<?php echo $current_page === 'linked_documents.php' ? 'active' : ''; ?>"><i class="fa fa-file"></i> <span>Documents liés</span></a></li>
        <li><a href="index.php"><i class="fa fa-home"></i> <span>Retour à l'accueil</span></a></li>
        <li><a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> <span>Se déconnecter</span></a></li>
    </ul>
</nav>
<style>
.sidebar a.active {
    background-color: #dc3545;
}
</style>