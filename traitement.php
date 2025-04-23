<?php
session_start();
header('Content-Type: text/plain');

$message = strtolower(trim($_POST['message'] ?? ''));
$_SESSION['history'][] = $message;

// Correction de fautes
function matchIntentWithTypo($input, $intents) {
    foreach ($intents as $intent => $keywords) {
        foreach ($keywords as $word) {
            if (levenshtein($input, $word) <= 2 || strpos($input, $word) !== false) {
                return $intent;
            }
        }
    }
    return null;
}

// Intentions
$intents = [
    'salutations' => ['bonjour', 'salut', 'hello'],
    'etat' => ['ca va', 'comment tu vas', 'comment vas tu'],
    'bye' => ['au revoir', 'bye'],
    'nom' => ['comment tu t appelles', 'quel est ton nom'],
    'reserver' => ['reserver', 'réserver', 'faire une reservation'],
    'quizz' => ['quizz', 'jouer', 'poser une question'],
    'meteo' => ['météo', 'temps', 'quel temps fait il']
];

// Réponses par défaut
$responses = [
    'salutations' => ['Salut ! 😊', 'Bonjour ! Comment puis-je t’aider ?'],
    'etat' => ['Je vais bien, merci ! Et toi ?'],
    'bye' => ['À bientôt ! 👋'],
    'nom' => ['Je suis PHPBot, ravi de discuter avec toi.'],
];

// --- SCÉNARIO : RÉSERVATION ---
if ($_SESSION['scenario'] === 'reserver') {
    if (!isset($_SESSION['reservation']['nom'])) {
        $_SESSION['reservation']['nom'] = $message;
        echo "Merci, $message. Quelle date souhaites-tu réserver ?";
        exit;
    } elseif (!isset($_SESSION['reservation']['date'])) {
        $_SESSION['reservation']['date'] = $message;
        $nom = $_SESSION['reservation']['nom'];
        $date = $_SESSION['reservation']['date'];
        $_SESSION['scenario'] = null;
        echo "Réservation confirmée pour $nom le $date ✅";
        exit;
    }
}

// --- SCÉNARIO : QUIZZ ---
if ($_SESSION['scenario'] === 'quizz') {
    if (!isset($_SESSION['quizz'])) {
        $_SESSION['quizz'] = [
            'questions' => [
                ['q' => 'Quelle est la capitale de la France ?', 'r' => 'paris'],
                ['q' => 'Combien font 3 + 4 ?', 'r' => '7'],
                ['q' => 'Quelle couleur a le ciel en journée ?', 'r' => 'bleu']
            ],
            'index' => 0,
            'score' => 0
        ];
        echo $_SESSION['quizz']['questions'][0]['q'];
        exit;
    } else {
        $current = $_SESSION['quizz']['questions'][$_SESSION['quizz']['index']];
        if ($message === strtolower($current['r'])) {
            $_SESSION['quizz']['score'] += 1;
            $response = "Bonne réponse ✅ ";
        } else {
            $response = "Mauvaise réponse ❌ La bonne réponse était : " . $current['r'] . ". ";
        }
        $_SESSION['quizz']['index']++;

        if ($_SESSION['quizz']['index'] >= count($_SESSION['quizz']['questions'])) {
            $score = $_SESSION['quizz']['score'];
            $_SESSION['scenario'] = null;
            unset($_SESSION['quizz']);
            echo $response . "Quizz terminé ! Ton score : $score / 3";
            exit;
        } else {
            $next = $_SESSION['quizz']['questions'][$_SESSION['quizz']['index']]['q'];
            echo $response . "Prochaine question : $next";
            exit;
        }
    }
}

// --- SCÉNARIO : MÉTÉO FACTICE ---
if ($_SESSION['scenario'] === 'meteo') {
    $_SESSION['scenario'] = null;
    $ville = ucfirst($message);
    $fauxTemps = ['ensoleillé', 'nuageux', 'pluvieux', 'orageux', 'venteux'];
    $temps = $fauxTemps[array_rand($fauxTemps)];
    echo "À $ville, il fera probablement $temps demain ☁️🌤️🌧️";
    exit;
}

// --- IDENTIFICATION DES INTENTIONS ---
$intent = matchIntentWithTypo($message, $intents);

if ($intent) {
    if ($intent === 'reserver') {
        $_SESSION['scenario'] = 'reserver';
        $_SESSION['reservation'] = [];
        echo "Commençons la réservation. Quel est ton prénom ?";
        exit;
    }

    if ($intent === 'quizz') {
        $_SESSION['scenario'] = 'quizz';
        echo "Ok, lançons un mini quizz 🎯 ! Première question :";
        $_SESSION['quizz'] = null; // Déclenché juste après
        exit;
    }

    if ($intent === 'meteo') {
        $_SESSION['scenario'] = 'meteo';
        echo "Pour quelle ville veux-tu connaître la météo ?";
        exit;
    }

    echo $responses[$intent][array_rand($responses[$intent])];
} else {
    echo "Je ne suis pas sûr de comprendre. Tu peux reformuler ou demander un quizz, une réservation ou la météo.";
}