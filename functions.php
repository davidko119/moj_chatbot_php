<?php

function esc_text(string $hodnota): string
{
	return htmlspecialchars($hodnota, ENT_QUOTES, 'UTF-8');
}

function predvolena_sprava_asistenta(): array
{
	return [
		'role' => 'assistant',
		'text' => 'Ahoj, som tvoj jednoduchy chat asistent. Napis mi spravu.'
	];
}

function priprav_session_spravy(): void
{
	if (!isset($_SESSION['messages']) || !is_array($_SESSION['messages'])) {
		$_SESSION['messages'] = [predvolena_sprava_asistenta()];
		return;
	}

	if ($_SESSION['messages'] === []) {
		$_SESSION['messages'][] = predvolena_sprava_asistenta();
	}
}

function skrat_spravu(string $text, int $maxDlzka = 1200): string
{
	return mb_substr(trim($text), 0, $maxDlzka);
}

