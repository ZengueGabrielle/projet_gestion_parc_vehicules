<?php
$stmt = $pdo->prepare("SELECT * FROM garages");
$stmt->execute();
$garages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section" id="garages">
    <h2>Garages à proximité</h2>
    <div id="map" style="height: 400px; width: 100%; border: 2px solid #dc3545; border-radius: 5px;"></div>
</div>

<script>
    function initMap() {
        const map = new google.maps.Map(document.getElementById('map'), {
            center: { lat: 0, lng: 0 },
            zoom: 10
        });

        // Use Geolocation to center map on user's location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(position => {
                const userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                map.setCenter(userLocation);
                new google.maps.Marker({
                    position: userLocation,
                    map: map,
                    title: 'Votre position',
                    icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                });
            });
        }

        // Add garage markers
        const garages = <?php echo json_encode($garages); ?>;
        garages.forEach(garage => {
            if (garage.latitude && garage.longitude) {
                new google.maps.Marker({
                    position: { lat: parseFloat(garage.latitude), lng: parseFloat(garage.longitude) },
                    map: map,
                    title: garage.nom,
                    label: garage.performance_score ? `Score: ${garage.performance_score}` : ''
                });
            }
        });
    }
</script>
<script async src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap"></script>