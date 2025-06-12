<?php
require_once 'config.php';

function updateVehicleStatus($pdo) {
    try {
        // Récupérer tous les véhicules
        $stmt = $pdo->query("SELECT * FROM vehicules");
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($vehicles as $vehicle) {
            $vehicle_id = $vehicle['id'];
            $kilometrage = $vehicle['kilometrage_actuel'];
            $current_status = $vehicle['statut'];

            // Compter les anomalies signalées au cours des 6 derniers mois
            $stmt_anomalies = $pdo->prepare("SELECT COUNT(*) FROM anomalies WHERE vehicule_id = ? AND date_signalement > DATE_SUB(NOW(), INTERVAL 6 MONTH)");
            $stmt_anomalies->execute([$vehicle_id]);
            $anomaly_count = $stmt_anomalies->fetchColumn();

            // Logique simulée pour les prédictions de maintenance
            $maintenance_needed = false;
            $maintenance_type = '';
            $probability = 50;
            $comment = '';

            // Exemple : Vidange tous les 10000 km
            if ($kilometrage >= 10000 && ($kilometrage % 10000) < 500) {
                $maintenance_needed = true;
                $maintenance_type = 'Vidange';
                $probability = 90;
                $comment = "Vidange prévue à {$kilometrage} km";
            }

            // Exemple : Inspection si anomalies fréquentes
            if ($anomaly_count >= 3) {
                $maintenance_needed = true;
                $maintenance_type = 'Inspection';
                $probability = 85;
                $comment = "Inspection recommandée en raison de {$anomaly_count} anomalies signalées";
            }

            // Si une maintenance est nécessaire, insérer une prédiction
            if ($maintenance_needed) {
                $date_prevue = date('Y-m-d', strtotime('+30 days'));
                $stmt_insert = $pdo->prepare("INSERT INTO predictions_maintenance (vehicule_id, type_maintenance, date_prevue, probabilite, commentaire) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE date_prevue = ?, probabilite = ?, commentaire = ?");
                $stmt_insert->execute([$vehicle_id, $maintenance_type, $date_prevue, $probability, $comment, $date_prevue, $probability, $comment]);
            }

            // Mettre à jour le statut du véhicule
            $new_status = $current_status;
            if ($maintenance_needed && $probability > 80) {
                $new_status = 'Maintenance prévue';
            } elseif ($anomaly_count > 0) {
                $new_status = 'Anomalie signalée';
            } elseif ($kilometrage > 200000) {
                $new_status = 'Fin de vie';
            } else {
                $new_status = 'En service';
            }

            if ($new_status !== $current_status) {
                $stmt_update = $pdo->prepare("UPDATE vehicules SET statut = ? WHERE id = $1");
                $stmt_update->execute([$new_status, $vehicle_id]);
            }

             //Intégration avec l'API Gemini (décommenter si vous avez une clé API)
            $api_key = 'AIzaSyDaQr3Uhikdu0DYoO2utiJRR0kJXrF0y7c';
            $api_url = 'https://api.gemini.google.com/v1/predict'; // Remplacer par l'URL réelle
            $data = [
                'kilometrage' => $kilometrage,
                'anomalies' => $anomaly_count,
                'marque' => $vehicle['marque'],
                'modele' => $vehicle['modele']
            ];

            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json'
            ]);
            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                error_log("Erreur cURL avec Gemini API : " . $curl_error);
                continue;
            }

            $result = json_decode($response, true);
            if (isset($result['maintenance_needed'])) {
                $type_maintenance = $result['maintenance_type'] ?? 'Maintenance générale';
                $probabilite = $result['probability'] ?? 50;
                $date_prevue = date('Y-m-d', strtotime('+30 days'));
                $commentaire = $result['comment'] ?? 'Prédiction par Gemini';

                // Insérer la prédiction
                $stmt_insert = $pdo->prepare("INSERT INTO predictions_maintenance (vehicule_id, type_maintenance, date_prevue, probabilite, commentaire) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE date_prevue = ?, probabilite = ?, commentaire = ?");
                $stmt_insert->execute([$vehicle_id, $type_maintenance, $date_prevue, $probabilite, $commentaire, $date_prevue, $probabilite, $commentaire]);

                // Mettre à jour le statut si nécessaire
                $new_status = $probabilite > 80 ? 'Maintenance prévue' : $vehicle['statut'];
                if ($new_status !== $current_status) {
                    $stmt_update = $pdo->prepare("UPDATE vehicules SET statut = ? WHERE id = ?");
                    $stmt_update->execute([$new_status, $vehicle_id]);
                }
            }
        
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour des statuts : " . $e->getMessage());
    }
}

// Exécuter la fonction
updateVehicleStatus($pdo);
?>