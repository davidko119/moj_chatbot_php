<?php

function esc_text(string $hodnota): string
{
	return htmlspecialchars($hodnota, ENT_QUOTES, 'UTF-8');
}

function vytvor_chat_zaznam(?string $id = null): array
{
	$cas = time();

	if ($id === null || $id === '') {
		try {
			$id = 'chat_' . bin2hex(random_bytes(8));
		} catch (Throwable $e) {
			$id = 'chat_' . uniqid('', true);
		}
	}

	return [
		'id' => $id,
		'title' => 'Nový chat',
		'updated_at' => $cas,
		'messages' => [predvolena_sprava_asistenta()]
	];
}

function predvolena_sprava_asistenta(): array
{
	return [
		'role' => 'assistant',
		'text' => "Vitaj v mojom chate.\n\nSom pripravený pomôcť s návrhmi, textami alebo rýchlym vysvetlením.\n\nNapíš mi, čo potrebuješ vyriešiť ako prvé."
	];
}

function priprav_session_spravy(): void
{
	if (!isset($_SESSION['chats']) || !is_array($_SESSION['chats']) || $_SESSION['chats'] === []) {
		$legacyMessages = [];
		if (isset($_SESSION['messages']) && is_array($_SESSION['messages'])) {
			$legacyMessages = $_SESSION['messages'];
		}

		$prvyChat = vytvor_chat_zaznam();
		if ($legacyMessages !== []) {
			$prvyChat['messages'] = $legacyMessages;
		}

		$_SESSION['chats'] = [$prvyChat];
		$_SESSION['current_chat_id'] = $prvyChat['id'];
	}

	if (!isset($_SESSION['current_chat_id']) || !is_string($_SESSION['current_chat_id']) || $_SESSION['current_chat_id'] === '') {
		$_SESSION['current_chat_id'] = (string) ($_SESSION['chats'][0]['id'] ?? '');
	}

	$aktivnyExistuje = false;
	foreach ($_SESSION['chats'] as $chat) {
		if ((string) ($chat['id'] ?? '') === (string) $_SESSION['current_chat_id']) {
			$aktivnyExistuje = true;
			break;
		}
	}

	if (!$aktivnyExistuje) {
		$_SESSION['current_chat_id'] = (string) ($_SESSION['chats'][0]['id'] ?? '');
	}

	$index = 0;
	$aktivnyId = (string) ($_SESSION['current_chat_id'] ?? '');
	foreach ($_SESSION['chats'] as $i => $chat) {
		if ((string) ($chat['id'] ?? '') === $aktivnyId) {
			$index = (int) $i;
			break;
		}
	}

	if (!isset($_SESSION['chats'][$index])) {
		$_SESSION['chats'][0] = vytvor_chat_zaznam();
		$index = 0;
		$_SESSION['current_chat_id'] = (string) ($_SESSION['chats'][0]['id'] ?? '');
	}
	if (!isset($_SESSION['chats'][$index]['messages']) || !is_array($_SESSION['chats'][$index]['messages'])) {
		$_SESSION['chats'][$index]['messages'] = [predvolena_sprava_asistenta()];
	}

	if ($_SESSION['chats'][$index]['messages'] === []) {
		$_SESSION['chats'][$index]['messages'][] = predvolena_sprava_asistenta();
	}

	// Spatna kompatibilita pre casti kodu, ktore este citaju messages priamo.
	$_SESSION['messages'] = $_SESSION['chats'][$index]['messages'];
}

function skrat_spravu(string $text, int $maxDlzka = 1200): string
{
	return mb_substr(trim($text), 0, $maxDlzka);
}

function index_aktivneho_chatu(): int
{
	priprav_session_spravy();

	$aktivnyId = (string) ($_SESSION['current_chat_id'] ?? '');
	foreach ($_SESSION['chats'] as $index => $chat) {
		if ((string) ($chat['id'] ?? '') === $aktivnyId) {
			return (int) $index;
		}
	}

	$_SESSION['chats'][] = vytvor_chat_zaznam();
	$novyIndex = (int) array_key_last($_SESSION['chats']);
	$_SESSION['current_chat_id'] = (string) ($_SESSION['chats'][$novyIndex]['id'] ?? '');

	return $novyIndex;
}

function ziskaj_aktivny_chat_id(): string
{
	priprav_session_spravy();
	return (string) ($_SESSION['current_chat_id'] ?? '');
}

function ziskaj_aktivne_spravy(): array
{
	$index = index_aktivneho_chatu();
	return (array) ($_SESSION['chats'][$index]['messages'] ?? []);
}

function nastav_aktivne_spravy(array $spravy): void
{
	$index = index_aktivneho_chatu();
	$_SESSION['chats'][$index]['messages'] = $spravy;
	$_SESSION['chats'][$index]['updated_at'] = time();
	$_SESSION['messages'] = $spravy;
}

function pridaj_spravu_do_aktivneho_chatu(string $role, string $text): void
{
	$index = index_aktivneho_chatu();
	$_SESSION['chats'][$index]['messages'][] = [
		'role' => $role,
		'text' => $text
	];
	$_SESSION['chats'][$index]['updated_at'] = time();
	$_SESSION['messages'] = $_SESSION['chats'][$index]['messages'];
}

function aktualizuj_nazov_aktivneho_chatu_podla_spravy(string $sprava): void
{
	$index = index_aktivneho_chatu();
	$aktualnyNazov = (string) ($_SESSION['chats'][$index]['title'] ?? '');

	if ($aktualnyNazov !== '' && $aktualnyNazov !== 'Nový chat') {
		return;
	}

	$novyNazov = skrat_spravu($sprava, 38);
	if ($novyNazov === '') {
		return;
	}

	$_SESSION['chats'][$index]['title'] = $novyNazov;
}

function vytvor_novy_chat(): string
{
	priprav_session_spravy();
	$novyChat = vytvor_chat_zaznam();
	array_unshift($_SESSION['chats'], $novyChat);
	$_SESSION['current_chat_id'] = (string) $novyChat['id'];
	$_SESSION['messages'] = $novyChat['messages'];

	return (string) $novyChat['id'];
}

function prepni_aktivny_chat(string $chatId): bool
{
	priprav_session_spravy();

	foreach ($_SESSION['chats'] as $chat) {
		if ((string) ($chat['id'] ?? '') === $chatId) {
			$_SESSION['current_chat_id'] = $chatId;
			$_SESSION['messages'] = (array) ($chat['messages'] ?? []);
			return true;
		}
	}

	return false;
}

function zoznam_chatov(): array
{
	priprav_session_spravy();
	$chaty = (array) $_SESSION['chats'];

	usort($chaty, static function (array $a, array $b): int {
		return (int) ($b['updated_at'] ?? 0) <=> (int) ($a['updated_at'] ?? 0);
	});

	return $chaty;
}

function je_uvodny_stav_chatu(array $spravy): bool
{
	if (count($spravy) !== 1) {
		return false;
	}

	$prvaSprava = $spravy[0] ?? [];
	return ($prvaSprava['role'] ?? '') === 'assistant';
}

