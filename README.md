# Môj Chatbot PHP

Jednoduchý PHP chat so session pamäťou a vlastným minimalistickým dizajnom.

## GitHub popis projektu

Ľahký PHP chatbot starter s ukladaním konverzácie do `$_SESSION`.
Podporuje lokálne načítanie OpenAI kľúča zo súboru `.env` (bezpečne mimo gitu)
alebo zo systémovej premennej `OPENAI_API_KEY`.

## O projekte

Projekt je spravený ako ľahký základ pre chatbot aplikáciu:
- frontend je v jednom PHP view súbore
- odosielanie správ rieši samostatný handler
- správy sa ukladajú do `$_SESSION`
- UI je bez Tailwindu, iba vlastný `style.css`

## Štruktúra projektu

- `index.php`
  - hlavná stránka chatu
  - zobrazenie histórie správ zo session
  - formulár pre odoslanie správy
  - malé JS na auto-resize textarea a odoslanie Enterom

- `chat.php`
  - backend handler pre chat akcie
  - prijíma `POST` správy
  - ukladá user/assistant správy do session
  - podporuje reset konverzácie cez `?action=reset`

- `config.php`
  - načítanie API kľúča zo systémovej premennej alebo zo súboru `.env`

- `.env`
  - lokálny súbor pre vlastný `OPENAI_API_KEY` (je ignorovaný v gite)

- `.env.example`
  - ukážkový formát env pre GitHub

- `functions.php`
  - pomocné funkcie projektu

- `style.css`
  - vlastné štýly pre jednoduchý a čistý layout

## Ako spustiť lokálne

1. Projekt nechaj v `htdocs` (MAMP/XAMPP alebo iný PHP server)
2. Spusti lokálny server
3. Otvor v prehliadači:
   - `http://localhost/my_database/moj_chatbot_php/`

## Nastavenie OpenAI API kľúča

Máš dve možnosti, projekt použije najprv systémovú premennú a potom `.env`.

### Možnosť 1: .env súbor (odporúčané)

Do `.env` daj:

```env
OPENAI_API_KEY=sk-...tvoj_realny_kluc...
```

Súbor `.env` je v `.gitignore`, takže sa nepošle na GitHub.

### Možnosť 2: systémová premenná vo Windows

```powershell
setx OPENAI_API_KEY "sk-...tvoj_realny_kluc..."
```

Potom zavri a znova otvor terminál alebo server, aby sa nová premenná načítala.

## Poznámky k ďalšiemu rozvoju

- napojiť reálny AI backend (OpenAI alebo iné API)
- doplniť validácie a logovanie
- pridať testy pre backend časť
