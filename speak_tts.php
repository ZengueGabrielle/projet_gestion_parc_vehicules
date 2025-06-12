<?php
header('Content-Type: application/json');

define('GOOGLE_TTS_API_KEY', 'AIzaSyDaQr3Uhikdu0DYoO2utiJRR0kJXrF0y7c');

$input = json_decode(file_get_contents('php://input'), true);
$text = $input['text'] ?? '';

if (empty($text)) {
    echo json_encode(['error' => 'Texte manquant']);
    exit;
}

$requestData = [
    'input' => ['text' => $text],
    'voice' => ['languageCode' => 'fr-FR', 'name' => 'fr-FR-Wavenet-A'],
    'audioConfig' => ['audioEncoding' => 'MP3']
];

$ch = curl_init('https://texttospeech.googleapis.com/v1/text:synthesize?key=' . GOOGLE_TTS_API_KEY);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo $response;
} else {
    echo json_encode(['error' => 'Erreur API TTS: ' . $response]);
}
?>