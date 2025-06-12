<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

define('GEMINI_API_KEY', 'AIzaSyDaQr3Uhikdu0DYoO2utiJRR0kJXrF0y7c');
define('MODEL', 'gemini-1.5-flash-latest'); // Remplacer par gemini-2.0-flash si disponible
define('BASEURL', 'https://generativelanguage.googleapis.com/v1beta/models/' . MODEL . ':generateContent?key=' . GEMINI_API_KEY);

// Questions autorisées
$allowedQuestions = [
    "Comment entretenir mes pneus ?",
    "Comment améliorer l'autonomie de mon véhicule ?",
    "Comment gérer mes trajets ?",
    "Comment signaler une anomalie sur un véhicule ?",
    "Où trouver un garage à proximité ?",
    "Quels sont mes véhicules assignés ?",
    "Comment vérifier la maintenance prévue ?",
    "Quels sont les conseils pour une conduite économique ?",
    "Comment vérifier la pression des pneus ?",
    "Quelles sont les règles de sécurité routière ?",
    "Comment planifier un itinéraire efficace ?",
    "Quels sont les types de carburant recommandés ?"
];

$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';

if (empty($message)) {
    echo json_encode(['error' => 'Message requis']);
    exit;
}

// Construire le prompt restrictif
$prompt = "Tu es un assistant IA spécialisé dans l'automobile, la gestion de parc automobile, l'entretien des véhicules, la route, et le transport routier. Répond uniquement aux questions liées à ces domaines, en français, de manière concise et pertinente. Si la question ne concerne pas l'automobile, la route, ou la gestion de parc, réponds : \"Je ne peux répondre qu'aux questions spécifiées.\"";
$prompt .= "\n\nExemples de questions autorisées :\n";
foreach ($allowedQuestions as $i => $q) {
    $prompt .= ($i + 1) . ". " . $q . "\n";
}
$prompt .= "\nQuestion : " . $message . "\nRéponse :";

$requestData = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ]
];

$ch = curl_init(BASEURL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

rewind($verbose);
$verboseLog = stream_get_contents($verbose);
fclose($verbose);

if ($response === false) {
    error_log("Erreur cURL : $curlError\n$verboseLog");
    echo json_encode(['error' => "Erreur cURL : $curlError"]);
    curl_close($ch);
    exit;
}

curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $responseText = $data['candidates'][0]['content']['parts'][0]['text'];
        echo json_encode(['response' => $responseText]);
    } else {
        error_log("Réponse API invalide : " . json_encode($data));
        echo json_encode(['error' => 'Réponse API invalide']);
    }
} else {
    error_log("Réponse API : $response\nCode HTTP : $httpCode\n$verboseLog");
    echo json_encode(['error' => "Erreur API Gemini (HTTP $httpCode) : $response"]);
}
?>