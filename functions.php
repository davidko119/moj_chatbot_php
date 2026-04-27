<?php

function esc_text(string $hodnota): string
{
	return htmlspecialchars($hodnota, ENT_QUOTES, 'UTF-8');
}

function db_je_povolena(): bool
{
	return DB_NAME !== '' && DB_USER !== '';
}

function db_pripojenie(): ?PDO
{
	static $pdo = null;
	static $initialized = false;

	if (!db_je_povolena()) {
		return null;
	}

	if ($pdo instanceof PDO) {
		return $pdo;
	}

	$host = DB_HOST;
	$port = DB_PORT;
	$dbName = DB_NAME;
	$charset = 'utf8mb4';
	$pdoOptions = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
	];

	try {
		$dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
		$pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
	} catch (Throwable $e) {
		try {
			$dsn = "mysql:host={$host};port={$port};charset={$charset}";
			$tmpPdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
			$tmpPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		} catch (Throwable $e2) {
			return null;
		}

		try {
			$dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
			$pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
		} catch (Throwable $e3) {
			return null;
		}
	}

	if (!$initialized && $pdo instanceof PDO) {
		db_inicializuj($pdo);
		$initialized = true;
	}

	return $pdo;
}

function db_inicializuj(PDO $pdo): void
{
	$pdo->exec(
		"CREATE TABLE IF NOT EXISTS chats (" .
		"id VARCHAR(64) PRIMARY KEY," .
		"title VARCHAR(200) NOT NULL," .
		"updated_at INT UNSIGNED NOT NULL," .
		"is_favorite TINYINT(1) NOT NULL DEFAULT 0" .
		") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
	);

	$pdo->exec(
		"CREATE TABLE IF NOT EXISTS messages (" .
		"id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY," .
		"chat_id VARCHAR(64) NOT NULL," .
		"role VARCHAR(16) NOT NULL," .
		"text TEXT NOT NULL," .
		"created_at INT UNSIGNED NOT NULL," .
		"INDEX idx_messages_chat_id (chat_id)," .
		"CONSTRAINT fk_messages_chat FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE" .
		") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
	);
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

function db_chat_existuje(string $chatId): bool
{
	$pdo = db_pripojenie();
	if (!$pdo) {
		return false;
	}

	$statement = $pdo->prepare('SELECT 1 FROM chats WHERE id = :id LIMIT 1');
	$statement->execute([':id' => $chatId]);

	return (bool) $statement->fetchColumn();
}

function db_nacitaj_chaty(): array
{
	$pdo = db_pripojenie();
	if (!$pdo) {
		return [];
	}

	$statement = $pdo->query('SELECT id, title, updated_at, is_favorite FROM chats ORDER BY updated_at DESC');
	return (array) $statement->fetchAll();
}

function db_nacitaj_spravy(string $chatId): array
{
	$pdo = db_pripojenie();
	if (!$pdo) {
		return [];
	}

	$statement = $pdo->prepare('SELECT role, text FROM messages WHERE chat_id = :chat_id ORDER BY id ASC');
	$statement->execute([':chat_id' => $chatId]);

	return (array) $statement->fetchAll();
}

function db_vytvor_chat_zaznam(?string $id = null): array
{
	$pdo = db_pripojenie();
	if (!$pdo) {
		return vytvor_chat_zaznam($id);
	}

	$cas = time();
	if ($id === null || $id === '') {
		try {
			$id = 'chat_' . bin2hex(random_bytes(8));
		} catch (Throwable $e) {
			$id = 'chat_' . uniqid('', true);
		}
	}

	$pdo->prepare('INSERT INTO chats (id, title, updated_at, is_favorite) VALUES (:id, :title, :updated_at, 0)')
		->execute([
			':id' => $id,
			':title' => 'Nový chat',
			':updated_at' => $cas
		]);

	$uvod = predvolena_sprava_asistenta();
	$pdo->prepare('INSERT INTO messages (chat_id, role, text, created_at) VALUES (:chat_id, :role, :text, :created_at)')
		->execute([
			':chat_id' => $id,
			':role' => (string) $uvod['role'],
			':text' => (string) $uvod['text'],
			':created_at' => $cas
		]);

	return [
		'id' => $id,
		'title' => 'Nový chat',
		'updated_at' => $cas,
		'is_favorite' => 0
	];
}

function db_nastav_spravy(string $chatId, array $spravy): void
{
	$pdo = db_pripojenie();
	if (!$pdo) {
		return;
	}

	$pdo->prepare('DELETE FROM messages WHERE chat_id = :chat_id')
		->execute([':chat_id' => $chatId]);

	$cas = time();
	$statement = $pdo->prepare('INSERT INTO messages (chat_id, role, text, created_at) VALUES (:chat_id, :role, :text, :created_at)');
	foreach ($spravy as $sprava) {
		$role = (string) ($sprava['role'] ?? '');
		$text = (string) ($sprava['text'] ?? '');
		if ($role === '' || $text === '') {
			continue;
		}
		$statement->execute([
			':chat_id' => $chatId,
			':role' => $role,
			':text' => $text,
			':created_at' => $cas
		]);
	}

	$pdo->prepare('UPDATE chats SET updated_at = :updated_at WHERE id = :id')
		->execute([':updated_at' => $cas, ':id' => $chatId]);
}

function db_pridaj_spravu(string $chatId, string $role, string $text): void
{
	$pdo = db_pripojenie();
	if (!$pdo) {
		return;
	}

	$cas = time();
	$pdo->prepare('INSERT INTO messages (chat_id, role, text, created_at) VALUES (:chat_id, :role, :text, :created_at)')
		->execute([
			':chat_id' => $chatId,
			':role' => $role,
			':text' => $text,
			':created_at' => $cas
		]);

	$pdo->prepare('UPDATE chats SET updated_at = :updated_at WHERE id = :id')
		->execute([':updated_at' => $cas, ':id' => $chatId]);
}

function db_premenuj_chat(string $chatId, string $novyNazov): bool
{
	$pdo = db_pripojenie();
	if (!$pdo) {
		return false;
	}

	$pdo->prepare('UPDATE chats SET title = :title, updated_at = :updated_at WHERE id = :id')
		->execute([
			':title' => $novyNazov,
			':updated_at' => time(),
			':id' => $chatId
		]);

	return true;
}

function db_nastav_favorite(string $chatId, bool $favorite): bool
{
	$pdo = db_pripojenie();
	if (!$pdo) {
		return false;
	}

	$pdo->prepare('UPDATE chats SET is_favorite = :favorite, updated_at = :updated_at WHERE id = :id')
		->execute([
			':favorite' => $favorite ? 1 : 0,
			':updated_at' => time(),
			':id' => $chatId
		]);

	return true;
}

function db_je_oblubeny(string $chatId): bool
{
	$pdo = db_pripojenie();
	if (!$pdo) {
		return false;
	}

	$statement = $pdo->prepare('SELECT is_favorite FROM chats WHERE id = :id LIMIT 1');
	$statement->execute([':id' => $chatId]);
	$value = $statement->fetchColumn();

	return (bool) $value;
}

function db_zmaz_chat(string $chatId): bool
{
	$pdo = db_pripojenie();
	if (!$pdo) {
		return false;
	}

	$pdo->prepare('DELETE FROM chats WHERE id = :id')
		->execute([':id' => $chatId]);

	return true;
}

function priprav_session_spravy(): void
{
	$pdo = db_pripojenie();
	if ($pdo instanceof PDO) {
		$chaty = db_nacitaj_chaty();
		if ($chaty === []) {
			$novyChat = db_vytvor_chat_zaznam();
			$chaty = [$novyChat];
		}

		$aktualnyId = (string) ($_SESSION['current_chat_id'] ?? '');
		if ($aktualnyId === '' || !db_chat_existuje($aktualnyId)) {
			$_SESSION['current_chat_id'] = (string) ($chaty[0]['id'] ?? '');
			$aktualnyId = (string) ($_SESSION['current_chat_id'] ?? '');
		}

		$spravy = $aktualnyId !== '' ? db_nacitaj_spravy($aktualnyId) : [];
		if ($spravy === []) {
			$uvod = predvolena_sprava_asistenta();
			if ($aktualnyId !== '') {
				db_nastav_spravy($aktualnyId, [$uvod]);
			}
			$spravy = [$uvod];
		}

		// Spatna kompatibilita pre casti kodu, ktore este citaju messages priamo.
		$_SESSION['messages'] = $spravy;
		return;
	}

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
	$pdo = db_pripojenie();
	if ($pdo instanceof PDO) {
		$chatId = ziskaj_aktivny_chat_id();
		return $chatId !== '' ? db_nacitaj_spravy($chatId) : [];
	}

	$index = index_aktivneho_chatu();
	return (array) ($_SESSION['chats'][$index]['messages'] ?? []);
}

function nastav_aktivne_spravy(array $spravy): void
{
	$pdo = db_pripojenie();
	if ($pdo instanceof PDO) {
		$chatId = ziskaj_aktivny_chat_id();
		if ($chatId !== '') {
			db_nastav_spravy($chatId, $spravy);
			$_SESSION['messages'] = $spravy;
		}
		return;
	}

	$index = index_aktivneho_chatu();
	$_SESSION['chats'][$index]['messages'] = $spravy;
	$_SESSION['chats'][$index]['updated_at'] = time();
	$_SESSION['messages'] = $spravy;
}

function pridaj_spravu_do_aktivneho_chatu(string $role, string $text): void
{
	$pdo = db_pripojenie();
	if ($pdo instanceof PDO) {
		$chatId = ziskaj_aktivny_chat_id();
		if ($chatId !== '') {
			db_pridaj_spravu($chatId, $role, $text);
			$_SESSION['messages'] = db_nacitaj_spravy($chatId);
		}
		return;
	}

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
	$pdo = db_pripojenie();
	if ($pdo instanceof PDO) {
		$chatId = ziskaj_aktivny_chat_id();
		$chaty = db_nacitaj_chaty();
		$aktualnyNazov = '';
		foreach ($chaty as $chat) {
			if ((string) ($chat['id'] ?? '') === $chatId) {
				$aktualnyNazov = (string) ($chat['title'] ?? '');
				break;
			}
		}
		if ($aktualnyNazov !== '' && $aktualnyNazov !== 'Nový chat') {
			return;
		}
		$novyNazov = skrat_spravu($sprava, 38);
		if ($novyNazov === '') {
			return;
		}
		db_premenuj_chat($chatId, $novyNazov);
		return;
	}

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
	$pdo = db_pripojenie();
	if ($pdo instanceof PDO) {
		$novyChat = db_vytvor_chat_zaznam();
		$_SESSION['current_chat_id'] = (string) ($novyChat['id'] ?? '');
		$_SESSION['messages'] = db_nacitaj_spravy((string) $novyChat['id']);
		return (string) ($novyChat['id'] ?? '');
	}

	priprav_session_spravy();
	$novyChat = vytvor_chat_zaznam();
	array_unshift($_SESSION['chats'], $novyChat);
	$_SESSION['current_chat_id'] = (string) $novyChat['id'];
	$_SESSION['messages'] = $novyChat['messages'];

	return (string) $novyChat['id'];
}

function prepni_aktivny_chat(string $chatId): bool
{
	$pdo = db_pripojenie();
	if ($pdo instanceof PDO) {
		if (!db_chat_existuje($chatId)) {
			return false;
		}
		$_SESSION['current_chat_id'] = $chatId;
		$_SESSION['messages'] = db_nacitaj_spravy($chatId);
		return true;
	}

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
	$pdo = db_pripojenie();
	if ($pdo instanceof PDO) {
		return db_nacitaj_chaty();
	}

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

function zmaz_chat(string $chatId): bool
{
	$pdo = db_pripojenie();
	if ($pdo instanceof PDO) {
		if (!db_chat_existuje($chatId)) {
			return false;
		}

		$aktualnyId = (string) ($_SESSION['current_chat_id'] ?? '');
		db_zmaz_chat($chatId);

		if ($aktualnyId === $chatId) {
			$chaty = db_nacitaj_chaty();
			if ($chaty === []) {
				$novyChat = db_vytvor_chat_zaznam();
				$_SESSION['current_chat_id'] = (string) ($novyChat['id'] ?? '');
				$_SESSION['messages'] = db_nacitaj_spravy((string) $novyChat['id']);
			} else {
				$_SESSION['current_chat_id'] = (string) ($chaty[0]['id'] ?? '');
				$_SESSION['messages'] = db_nacitaj_spravy((string) ($chaty[0]['id'] ?? ''));
			}
		}

		return true;
	}

	priprav_session_spravy();

	$index = -1;
	foreach ($_SESSION['chats'] as $i => $chat) {
		if ((string) ($chat['id'] ?? '') === $chatId) {
			$index = $i;
			break;
		}
	}

	if ($index === -1) {
		return false;
	}

	// Ak mazeme aktivny chat, prepni na prvý dostupný
	$aktualnyId = (string) ($_SESSION['current_chat_id'] ?? '');
	if ($aktualnyId === $chatId) {
		unset($_SESSION['chats'][$index]);
		$_SESSION['chats'] = array_values($_SESSION['chats']);

		if (count($_SESSION['chats']) > 0) {
			$_SESSION['current_chat_id'] = (string) ($_SESSION['chats'][0]['id'] ?? '');
			$_SESSION['messages'] = (array) ($_SESSION['chats'][0]['messages'] ?? []);
		} else {
			$novyChat = vytvor_chat_zaznam();
			$_SESSION['chats'] = [$novyChat];
			$_SESSION['current_chat_id'] = (string) $novyChat['id'];
			$_SESSION['messages'] = $novyChat['messages'];
		}
	} else {
		unset($_SESSION['chats'][$index]);
		$_SESSION['chats'] = array_values($_SESSION['chats']);
	}

	return true;
}

function premenuj_chat(string $chatId, string $novyNazov): bool
{
	$pdo = db_pripojenie();
	if ($pdo instanceof PDO) {
		$novyNazov = skrat_spravu($novyNazov, 100);
		if ($novyNazov === '') {
			return false;
		}
		return db_premenuj_chat($chatId, $novyNazov);
	}

	priprav_session_spravy();

	$novyNazov = skrat_spravu($novyNazov, 100);
	if ($novyNazov === '') {
		return false;
	}

	foreach ($_SESSION['chats'] as &$chat) {
		if ((string) ($chat['id'] ?? '') === $chatId) {
			$chat['title'] = $novyNazov;
			$chat['updated_at'] = time();
			return true;
		}
	}

	return false;
}

function pridas_do_oblubeneho(string $chatId): bool
{
	$pdo = db_pripojenie();
	if ($pdo instanceof PDO) {
		return db_nastav_favorite($chatId, true);
	}

	priprav_session_spravy();

	foreach ($_SESSION['chats'] as &$chat) {
		if ((string) ($chat['id'] ?? '') === $chatId) {
			$chat['is_favorite'] = true;
			$chat['updated_at'] = time();
			return true;
		}
	}

	return false;
}

function odeber_z_oblubeneho(string $chatId): bool
{
	$pdo = db_pripojenie();
	if ($pdo instanceof PDO) {
		return db_nastav_favorite($chatId, false);
	}

	priprav_session_spravy();

	foreach ($_SESSION['chats'] as &$chat) {
		if ((string) ($chat['id'] ?? '') === $chatId) {
			$chat['is_favorite'] = false;
			$chat['updated_at'] = time();
			return true;
		}
	}

	return false;
}

function je_oblubeny_chat(string $chatId): bool
{
	$pdo = db_pripojenie();
	if ($pdo instanceof PDO) {
		return db_je_oblubeny($chatId);
	}

	foreach ($_SESSION['chats'] as $chat) {
		if ((string) ($chat['id'] ?? '') === $chatId) {
			return (bool) ($chat['is_favorite'] ?? false);
		}
	}

	return false;
}

