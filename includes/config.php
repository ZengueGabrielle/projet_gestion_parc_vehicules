<?php
// Database configuration
$host = 'localhost';
$dbname = 'gestion_parc_vehicules';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// API keys
$gemini_api_key = 'AIzaSyDaQr3Uhikdu0DYoO2utiJRR0kJXrF0y7c'; // Replace with your Google Gemini API key
$google_maps_api_key = 'AIzaSyB_vJxK3Erk4gLf3wu988kaJqH83Jszezk'; // Replace with your Google Maps API key
?>