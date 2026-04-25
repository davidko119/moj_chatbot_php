<?php
session_start();

if (isset($_GET['action']) && $_GET['action'] === 'reset') {
    $_SESSION['messages'] = [
        [
            'role' => 'assistant',
            'text' => 'Ahoj, som tvoj AI asistent. Napis mi, s cim ti mam pomoct.'
        ]
    ];

    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$message = trim((string) ($_POST['message'] ?? ''));

if ($message === '') {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['messages']) || !is_array($_SESSION['messages'])) {
    $_SESSION['messages'] = [];
}

$_SESSION['messages'][] = [
    'role' => 'user',
    'text' => mb_substr($message, 0, 1200)
];

$assistantReply = "Rozumiem. Toto je pripravene UI + session workflow. Dalsi krok je napojit realny AI backend.\n\nTvoja posledna sprava: \"" . mb_substr($message, 0, 240) . "\"";

$_SESSION['messages'][] = [
    'role' => 'assistant',
    'text' => $assistantReply
];

if (count($_SESSION['messages']) > 40) {
    $_SESSION['messages'] = array_slice($_SESSION['messages'], -40);
}

header('Location: index.php');
exit;
