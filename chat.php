<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

priprav_session_spravy();

if (isset($_GET['action']) && $_GET['action'] === 'reset') {
    $_SESSION['messages'] = [
        predvolena_sprava_asistenta()
    ];

    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$sprava = trim((string) ($_POST['message'] ?? ''));

if ($sprava === '') {
    header('Location: index.php');
    exit;
}

$_SESSION['messages'][] = [
    'role' => 'user',
    'text' => skrat_spravu($sprava, 1200)
];

if (OPENAI_API_KEY === '') {
    $odpoved = 'Spravu som prijal. Toto je lahka demo verzia bez realneho AI backendu.';
} else {
    $odpoved = 'Spravu som prijal. OpenAI kluc je pripraveny, ale volanie API tu este nie je zapnute.';
}

$_SESSION['messages'][] = [
    'role' => 'assistant',
    'text' => $odpoved
];

if (count($_SESSION['messages']) > 40) {
    $_SESSION['messages'] = array_slice($_SESSION['messages'], -40);
}

header('Location: index.php');
exit;
