# Môj Chatbot PHP

Jednoduchy PHP chat so session pamatou a vlastnym minimalistickym dizajnom.

## GitHub popis projektu

Lahky PHP chatbot starter s ukladanim konverzacie do `$_SESSION`.
Podporuje lokalne nacitanie OpenAI kluca zo suboru `.env` (bezpecne mimo gitu)
alebo zo systemovej premennej `OPENAI_API_KEY`.

## O projekte

Projekt je spraveny ako lahky zaklad pre chatbot aplikaciu:
- frontend je v jednom PHP view subore
- odosielanie sprav riesi samostatny handler
- spravy sa ukladaju do `$_SESSION`
- UI je bez Tailwindu, iba vlastny `style.css`

## Struktura projektu

- `index.php`
  - hlavna stranka chatu
  - zobrazenie historie sprav zo session
  - formular pre odoslanie spravy
  - male JS na auto-resize textarea a odoslanie Enterom

- `chat.php`
  - backend handler pre chat akcie
  - prijima `POST` spravy
  - uklada user/assistant spravy do session
  - podporuje reset konverzacie cez `?action=reset`

- `config.php`
  - nacitanie API kluca zo systemovej premennej alebo zo suboru `.env`

- `.env`
  - lokalny subor pre vlastny `OPENAI_API_KEY` (je ignorovany v gite)

- `.env.example`
  - ukazkovy format env pre GitHub

- `functions.php`
  - pomocne funkcie projektu

- `style.css`
  - vlastne styly pre jednoduchy a cisty layout

## Ako spustit lokalne

1. Projekt nechaj v `htdocs` (MAMP/XAMPP alebo iny PHP server)
2. Spusti lokalny server
3. Otvor v prehliadaci:
   - `http://localhost/my_database/moj_chatbot_php/`

## Nastavenie OpenAI API kluca

Mas dve moznosti, projekt pouzije najprv systemovu premennu a potom `.env`.

### Moznost 1: .env subor (odporucane)

Do `.env` daj:

```env
OPENAI_API_KEY=sk-...tvoj_realny_kluc...
```

Subor `.env` je v `.gitignore`, takze sa neposle na GitHub.

### Moznost 2: systemova premenna vo Windows

```powershell
setx OPENAI_API_KEY "sk-...tvoj_realny_kluc..."
```

Potom zavri a znova otvor terminal alebo server, aby sa nova premenna nacitala.

## Poznamky k dalsiemu rozvoju

- napojit realny AI backend (OpenAI alebo ine API)
- doplnit validacie a logovanie
- pridat testy pre backend cast
