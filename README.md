# Moj Chatbot PHP

Jednoduchy chat projekt v PHP so session pamatou a modernym UI cez Tailwind CSS.

## O projekte

Projekt je navrhnuty ako lahky zaklad pre chatbot aplikaciu:
- frontend je v jednom PHP view subore
- odosielanie sprav spracovava samostatny handler
- spravy sa ukladaju do `$_SESSION`
- UI je responzivne a stylovane cez Tailwind CDN

## Struktura projektu

- `index.php`
  - hlavna stranka chatu
  - zobrazenie historie sprav zo session
  - formular pre odoslanie spravy
  - klientsky JS pre auto-resize textarea a odoslanie Enterom

- `chat.php`
  - backend handler pre chat akcie
  - prijima `POST` spravy
  - uklada user/assistant spravy do session
  - podporuje reset konverzacie cez `?action=reset`

- `config.php`
  - pripraveny subor pre buducu konfiguraciu (API kluce, nastavenia)

- `functions.php`
  - pripraveny subor pre pomocne funkcie projektu

- `style.css`
  - momentalne nepouzity (UI je riesene Tailwind triedami)

## Ako spustit lokalne

1. Projekt maj v `htdocs` (MAMP/XAMPP alebo iny PHP server)
2. Spusti lokalny server (napr. MAMP)
3. Otvor v prehliadaci:
   - `http://localhost/my_database/moj_chatbot_php/`

## Poznamky k dalsiemu rozvoju

- napojit realny AI backend (OpenAI/ine API)
- oddelit frontend a backend logiku
- doplnit logovanie a validacie
- pridat testy pre backend cast
