<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

function posli_json(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function zavolaj_openai_chat(array $spravy): array
{
    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'text' => 'Na serveri nie je dostupné rozšírenie cURL.'
        ];
    }

    $apiMessages = [
        [
            'role' => 'system',
            'content' => 'Si nápomocný asistent. Odpovedaj po slovensky, používaj prirodzenú slovenčinu s diakritikou a buď vecný.'
        ]
    ];

    foreach ($spravy as $sprava) {
        $role = (string) ($sprava['role'] ?? '');
        $text = trim((string) ($sprava['text'] ?? ''));

        if ($text === '' || ($role !== 'user' && $role !== 'assistant')) {
            continue;
        }

        $apiMessages[] = [
            'role' => $role,
            'content' => $text
        ];
    }

    $payload = [
        'model' => OPENAI_MODEL,
        'messages' => $apiMessages,
        'temperature' => 0.7
    ];

    $vykonajRequest = static function (array $payloadData, bool $insecureSsl = false): array {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        if ($ch === false) {
            return [
                'raw' => false,
                'errno' => 0,
                'error' => 'Nepodarilo sa inicializovať požiadavku na OpenAI API.',
                'httpCode' => 0
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . OPENAI_API_KEY
            ],
            CURLOPT_POSTFIELDS => json_encode($payloadData, JSON_UNESCAPED_UNICODE),
            CURLOPT_SSL_VERIFYPEER => !$insecureSsl,
            CURLOPT_SSL_VERIFYHOST => $insecureSsl ? 0 : 2
        ]);

        $raw = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'raw' => $raw,
            'errno' => $curlErrNo,
            'error' => $curlError,
            'httpCode' => $httpCode
        ];
    };

    $result = $vykonajRequest($payload, false);
    $raw = $result['raw'];
    $curlErrNo = (int) $result['errno'];
    $curlError = (string) $result['error'];
    $httpCode = (int) $result['httpCode'];

    // Pri lokálnom SSL probléme automaticky skúsi fallback bez overenia certifikátu.
    $sslIssuerError = stripos($curlError, 'unable to get local issuer certificate') !== false;
    if (($raw === false || $curlErrNo !== 0) && ($curlErrNo === 60 || $sslIssuerError)) {
        $result = $vykonajRequest($payload, true);
        $raw = $result['raw'];
        $curlErrNo = (int) $result['errno'];
        $curlError = (string) $result['error'];
        $httpCode = (int) $result['httpCode'];
    }

    if ($raw === false || $curlErrNo !== 0) {
        $chybaText = trim((string) $curlError);
        if (stripos($chybaText, 'unable to get local issuer certificate') !== false) {
            $chybaText = 'SSL certifikát sa nepodarilo overiť (chýba lokálny vydavateľ certifikátu).';
        } elseif ($chybaText === '') {
            $chybaText = 'Neznáma chyba spojenia.';
        }

        return [
            'ok' => false,
            'text' => 'Nepodarilo sa spojiť s OpenAI API: ' . $chybaText
        ];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [
            'ok' => false,
            'text' => 'OpenAI API vrátilo neplatnú odpoveď.'
        ];
    }

    if ($httpCode >= 400) {
        $apiError = (string) ($data['error']['message'] ?? 'Neznáma chyba API.');
        return [
            'ok' => false,
            'text' => 'OpenAI API chyba: ' . $apiError
        ];
    }

    $text = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
    if ($text === '') {
        return [
            'ok' => false,
            'text' => 'Model nevrátil žiadny text.'
        ];
    }

    return [
        'ok' => true,
        'text' => skrat_spravu($text, 4000)
    ];
}

priprav_session_spravy();

if (isset($_GET['action']) && $_GET['action'] === 'reset') {
    vytvor_novy_chat();

    header('Location: index.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'switch') {
    $chatId = trim((string) ($_GET['id'] ?? ''));
    if ($chatId !== '') {
        prepni_aktivny_chat($chatId);
    }

    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$isAjax = (($_POST['ajax'] ?? '') === '1') || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch');

$sprava = trim((string) ($_POST['message'] ?? ''));

if ($sprava === '') {
    if ($isAjax) {
        posli_json([
            'ok' => false,
            'error' => 'Prázdna správa sa nedá odoslať.'
        ], 400);
    }

    header('Location: index.php');
    exit;
}

$aktivneSpravy = ziskaj_aktivne_spravy();

if (je_uvodny_stav_chatu($aktivneSpravy)) {
	nastav_aktivne_spravy([]);
}

$userSprava = skrat_spravu($sprava, 1200);
pridaj_spravu_do_aktivneho_chatu('user', $userSprava);
aktualizuj_nazov_aktivneho_chatu_podla_spravy($userSprava);

if (OPENAI_API_KEY === '') {
    $odpoved = 'Chýba OPENAI_API_KEY. Doplň ho do súboru .env alebo do systémovej premennej.';
} else {
    $vysledok = zavolaj_openai_chat(ziskaj_aktivne_spravy());
    if ($vysledok['ok'] === true) {
        $odpoved = (string) $vysledok['text'];
    } else {
        $odpoved = 'Prepáč, nepodarilo sa získať odpoveď z AI. ' . (string) $vysledok['text'];
    }
}

pridaj_spravu_do_aktivneho_chatu('assistant', $odpoved);

$spravyPoOdpovedi = ziskaj_aktivne_spravy();
if (count($spravyPoOdpovedi) > 40) {
    nastav_aktivne_spravy(array_slice($spravyPoOdpovedi, -40));
}

if ($isAjax) {
    posli_json([
        'ok' => true,
        'assistant' => $odpoved
    ]);
}

header('Location: index.php');
exit;
