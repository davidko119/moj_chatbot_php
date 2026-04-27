<?php

/**
 * Nacita hodnotu kluca zo systemovej premennej alebo lokalneho .env suboru.
 */
function nacitaj_env_hodnotu(string $key): string
{
	$zoSystemu = trim((string) (getenv($key) ?: ''));
	if ($zoSystemu !== '') {
		return $zoSystemu;
	}

	$envPath = __DIR__ . '/.env';
	if (!is_readable($envPath)) {
		return '';
	}

	$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if ($lines === false) {
		return '';
	}

	foreach ($lines as $line) {
		$line = trim($line);

		if ($line === '' || strpos($line, '#') === 0) {
			continue;
		}

		$parts = explode('=', $line, 2);
		if (count($parts) !== 2) {
			continue;
		}

		$currentKey = trim($parts[0]);
		if ($currentKey !== $key) {
			continue;
		}

		$value = trim($parts[1]);
		$isDoubleQuoted = strlen($value) >= 2 && $value[0] === '"' && substr($value, -1) === '"';
		$isSingleQuoted = strlen($value) >= 2 && $value[0] === "'" && substr($value, -1) === "'";
		if ($isDoubleQuoted || $isSingleQuoted) {
			$value = substr($value, 1, -1);
		}

		return trim($value);
	}

	return '';
}

// API kluc sa cita najprv zo systemovej premennej a potom z .env suboru.
define('OPENAI_API_KEY', nacitaj_env_hodnotu('OPENAI_API_KEY'));

// Predvolený model pre OpenAI volanie.
define('OPENAI_MODEL', 'gpt-4o-mini');

// Databazove nastavenia (MySQL/MariaDB) nacitane z env.
define('DB_HOST', nacitaj_env_hodnotu('DB_HOST') !== '' ? nacitaj_env_hodnotu('DB_HOST') : '127.0.0.1');
define('DB_PORT', nacitaj_env_hodnotu('DB_PORT') !== '' ? nacitaj_env_hodnotu('DB_PORT') : '3306');
define('DB_NAME', nacitaj_env_hodnotu('DB_NAME'));
define('DB_USER', nacitaj_env_hodnotu('DB_USER'));
define('DB_PASS', nacitaj_env_hodnotu('DB_PASS'));

