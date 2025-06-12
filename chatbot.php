<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'utilisateur') {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot IA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            color: #fff;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            position: relative;
        }
        video.background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        .header {
            display: flex;
            align-items: center;
            padding: 15px 30px;
            background: rgba(52, 58, 64, 0.9);
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            z-index: 1001;
        }
        .u-name {
            font-size: 24px;
            margin-left: 10px;
        }
        .u-name b {
            color: #dc3545;
        }
        .header .toggle-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
        }
        .header .toggle-btn:hover {
            color: #dc3545;
        }
        .main-content {
            display: flex;
            flex-direction: row;
            margin-top: 60px;
            min-height: calc(100vh - 60px);
        }
        .sidebar {
            width: 60px;
            background: rgba(52, 58, 64, 0.9);
            transition: width 0.3s ease;
            overflow-x: hidden;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1000;
        }
        .sidebar.expanded {
            width: 300px;
        }
        .sidebar .user-p {
            text-align: center;
            padding: 20px 0;
        }
        .sidebar .user-p img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #dc3545;
        }
        .sidebar .user-p h4 {
            color: #fff;
            margin-top: 10px;
            font-size: 18px;
            display: none;
        }
        .sidebar.expanded .user-p h4 {
            display: block;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar li {
            margin: 10px 0;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            color: #fff;
            text-decoration: none;
            padding: 15px;
            font-size: 16px;
            white-space: nowrap;
            transition: background 0.3s, transform 0.2s;
            cursor: pointer;
        }
        .sidebar a:hover {
            background: #dc3545;
            transform: translateX(5px);
        }
        .sidebar a i {
            width: 30px;
            text-align: center;
            margin-right: 10px;
            font-size: 18px;
        }
        .sidebar a span {
            display: none;
        }
        .sidebar.expanded a span {
            display: inline;
        }
        .content {
            margin-left: 60px;
            padding: 20px;
            flex-grow: 1;
            transition: margin-left 0.3s ease;
        }
        .sidebar.expanded ~ .content {
            margin-left: 300px;
        }
        .chat-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            margin: 20px auto;
            border: 3px solid #dc3545;
            color: #333;
            display: flex;
            flex-direction: column;
            height: 70vh;
        }
        h2 {
            color: #dc3545;
            font-size: 24px;
            margin-bottom: 15px;
            text-align: center;
        }
        .chat-history {
            flex-grow: 1;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
            margin-bottom: 10px;
        }
        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 10px;
            max-width: 80%;
            word-wrap: break-word;
        }
        .user-message {
            background: #dc3545;
            color: #fff;
            margin-left: auto;
            text-align: right;
        }
        .ai-message {
            background: #e9ecef;
            color: #333;
            margin-right: auto;
        }
        .input-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        #chat-input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        #send-btn, #voice-btn {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        #send-btn:hover, #voice-btn:hover {
            background: #c82333;
        }
        #voice-btn.recording {
            background: #28a745;
        }
        .error-message {
            color: #dc3545;
            font-size: 14px;
            text-align: center;
            margin-top: 10px;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 50px;
            }
            .sidebar.expanded {
                width: 250px;
            }
            .content {
                margin-left: 50px;
            }
            .sidebar.expanded ~ .content {
                margin-left: 250px;
            }
            .chat-container {
                margin: 10px;
                padding: 15px;
                height: 60vh;
            }
            #chat-input {
                font-size: 14px;
            }
            #send-btn, #voice-btn {
                padding: 8px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <video class="background-video" autoplay loop muted playsinline>
        <source src="assets/videos/background.mp4" type="video/mp4">
        Votre navigateur ne supporte pas la lecture de vidéos.
    </video>
    <header class="header">
        <button class="toggle-btn"><i class="fa-solid fa-bars"></i></button>
        <h2 class="u-name">GESTION <b>PARC</b></h2>
    </header>
    <div class="main-content">
        <nav class="sidebar">
            <div class="user-p">
                <img src="assets/images/user.png" alt="User Image">
                <h4>@<?php echo htmlspecialchars($_SESSION['nom_utilisateur']); ?></h4>
            </div>
            <ul>
                <li><a href="dashboard_utilisateur.php"><i class="fa fa-home"></i> <span>Tableau de bord</span></a></li>
                <li><a href="vehicle_catalog.php"><i class="fa fa-car"></i> <span>Catalogue des véhicules</span></a></li>
                <li><a href="vehicules_assignes.php"><i class="fa fa-car"></i> <span>Mes véhicules assignés</span></a></li>
                <li><a href="suivi_trajets.php"><i class="fa fa-road"></i> <span>Suivi de mes trajets</span></a></li>
                <li><a href="report_anomaly.php"><i class="fa fa-exclamation-circle"></i> <span>Signaler une anomalie</span></a></li>
                <li><a href="maintenance_prevue.php"><i class="fa fa-wrench"></i> <span>Maintenance prévue</span></a></li>
                <li><a href="garages_proximite.php"><i class="fa fa-map-marker-alt"></i> <span>Garages à proximité</span></a></li>
                <li><a href="notifications.php"><i class="fa fa-bell"></i> <span>Notifications</span></a></li>
                <li><a href="historique_personnel.php"><i class="fa fa-history"></i> <span>Historique personnel</span></a></li>
                <li><a href="documents_lies.php"><i class="fa fa-file"></i> <span>Documents liés</span></a></li>
                <li><a href="chatbot.php"><i class="fa fa-comment"></i> <span>Chatbot IA</span></a></li>
                <li><a href="index.php"><i class="fa fa-home"></i> <span>Retour à l'accueil</span></a></li>
                <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> <span>Se déconnecter</span></a></li>
            </ul>
        </nav>
        <div class="content">
            <div class="chat-container">
                <h2>Chatbot IA</h2>
                <div class="chat-history" id="chat-history"></div>
                <div class="input-container">
                    <input type="text" id="chat-input" placeholder="Tapez votre message..." autocomplete="off">
                    <button id="send-btn"><i class="fa fa-paper-plane"></i></button>
                    <button id="voice-btn" disabled title="Reconnaissance vocale indisponible en HTTP"><i class="fa fa-microphone"></i></button>
                </div>
                <p id="error-message" class="error-message" style="display: none;"></p>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.toggle-btn');
            const chatHistory = document.getElementById('chat-history');
            const chatInput = document.getElementById('chat-input');
            const sendBtn = document.getElementById('send-btn');
            const voiceBtn = document.getElementById('voice-btn');
            const errorMessage = document.getElementById('error-message');

            // Toggle sidebar
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('expanded');
            });

            // Initialiser la synthèse vocale
            function initSpeechSynthesis() {
                if ('speechSynthesis' in window) {
                    let voicesLoaded = false;
                    const loadVoices = () => {
                        const voices = speechSynthesis.getVoices();
                        if (voices.length > 0 && !voicesLoaded) {
                            voicesLoaded = true;
                            console.log('Voix chargées:', voices.map(v => v.lang));
                        }
                    };
                    loadVoices();
                    if (speechSynthesis.onvoiceschanged !== undefined) {
                        speechSynthesis.onvoiceschanged = loadVoices;
                    }
                } else {
                    console.warn('Synthèse vocale non prise en charge.');
                }
            }

            // Lire le texte à haute voix
            function speakText(text) {
                if (!window.speechSynthesis) {
                    console.warn('Synthèse vocale non prise en charge.');
                    return;
                }
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'fr-FR';
                utterance.volume = 1;
                utterance.rate = 0.9;
                utterance.pitch = 1;

                const voices = speechSynthesis.getVoices();
                const frenchVoice = voices.find(voice => voice.lang.startsWith('fr')) || voices[0];
                if (frenchVoice) {
                    utterance.voice = frenchVoice;
                    console.log('Voix sélectionnée:', frenchVoice.lang);
                }

                speechSynthesis.speak(utterance);
            }

            // Ajouter un message à l'historique
            function addMessage(content, isUser) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${isUser ? 'user-message' : 'ai-message'}`;
                messageDiv.textContent = content;
                chatHistory.appendChild(messageDiv);
                chatHistory.scrollTop = chatHistory.scrollHeight;
            }

            // Envoyer un message à Gemini
            async function sendMessageToGemini(message) {
                console.log('Envoi du message:', message);
                try {
                    const response = await fetch('chatbot_proxy.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message })
                    });
                    console.log('Statut de la réponse:', response.status);
                    const data = await response.json();
                    console.log('Données reçues:', data);
                    if (data.response) {
                        addMessage(data.response, false);
                        speakText(data.response);
                        errorMessage.style.display = 'none';
                    } else {
                        errorMessage.textContent = data.error || 'Erreur lors de la réponse de l\'IA.';
                        errorMessage.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Erreur fetch:', error);
                    errorMessage.textContent = 'Erreur réseau. Vérifiez votre connexion ou la configuration du serveur.';
                    errorMessage.style.display = 'block';
                }
            }

            // Gérer l'envoi de texte
            sendBtn.addEventListener('click', sendMessage);
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') sendMessage();
            });

            function sendMessage() {
                const message = chatInput.value.trim();
                if (message) {
                    addMessage(message, true);
                    sendMessageToGemini(message);
                    chatInput.value = '';
                    errorMessage.style.display = 'none';
                }
            }

            // Message d'accueil
            const welcomeMessage = 'Bonjour ! Je suis votre assistant IA pour l\'automobile et la gestion de parc. Posez-moi une question sur les véhicules, l\'entretien, ou la route.';
            addMessage(welcomeMessage, false);
            speakText(welcomeMessage);

            // Initialiser
            initSpeechSynthesis();
        });
    </script>
</body>
</html>