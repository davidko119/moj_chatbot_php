# Mapovanie starých správ na nové slovenské správy
$mapping = @{
    'docs: add full Slovak diacritics in README description' = 'docs: Pridanie úplných slovenských diakriitik v popise README'
    'docs: fix Slovak diacritics in README title' = 'docs: Oprava slovenských diakriitik v titulku README'
    'feat: add OpenAI chat flow, compact chat history sidebar and clean UI' = 'feat: Pridanie OpenAI chat toku, kompaktného bočného panela a čistého UI'
    'docs: update README with GitHub project description and env setup' = 'docs: Aktualizácia README s popisom GitHub projektu a nastavením prostredia'
    'feat: add .env key loading and update README setup' = 'feat: Pridanie načítavania .env kľúča a aktualizácia nastavenia README'
    'feat(ui): clean svetly styl podobny chat rozhraniu' = 'feat(ui): Čistý svetlý štýl podobný chat rozhraniu'
    'feat(ui): cierny-biely chat styl, ikonky a slovenske texty' = 'feat(ui): Čierny-biely chat štýl, ikonky a slovenské texty'
    'docs: zjednotene README v slovencine' = 'docs: Zjednotené README v slovenčine'
    'refactor(chat): slovenske helpery a reset' = 'refactor(chat): Slovenské helpery a reset'
    'chore: jednoducha priprava na OpenAI API kluc' = 'chore: Jednoduchá príprava na OpenAI API kľúč'
    'docs: pridane README s popisom struktury projektu' = 'docs: Pridané README s popisom štruktúry projektu'
}

# Vytvoriť editor skript
$editorScript = @"
param([string]`$file)
`$content = Get-Content `$file -Raw
`$mapping = @{
    'docs: add full Slovak diacritics in README description' = 'docs: Pridanie úplných slovenských diakriitik v popise README'
    'docs: fix Slovak diacritics in README title' = 'docs: Oprava slovenských diakriitik v titulku README'
    'feat: add OpenAI chat flow, compact chat history sidebar and clean UI' = 'feat: Pridanie OpenAI chat toku, kompaktného bočného panela a čistého UI'
    'docs: update README with GitHub project description and env setup' = 'docs: Aktualizácia README s popisom GitHub projektu a nastavením prostredia'
    'feat: add .env key loading and update README setup' = 'feat: Pridanie načítavania .env kľúča a aktualizácia nastavenia README'
    'feat(ui): clean svetly styl podobny chat rozhraniu' = 'feat(ui): Čistý svetlý štýl podobný chat rozhraniu'
    'feat(ui): cierny-biely chat styl, ikonky a slovenske texty' = 'feat(ui): Čierny-biely chat štýl, ikonky a slovenské texty'
    'docs: zjednotene README v slovencine' = 'docs: Zjednotené README v slovenčine'
    'refactor(chat): slovenske helpery a reset' = 'refactor(chat): Slovenské helpery a reset'
    'chore: jednoducha priprava na OpenAI API kluc' = 'chore: Jednoduchá príprava na OpenAI API kľúč'
    'docs: pridane README s popisom struktury projektu' = 'docs: Pridané README s popisom štruktúry projektu'
}
foreach (`$old in `$mapping.Keys) {
    `$content = `$content -replace [regex]::Escape(`$old), `$mapping[`$old]
}
Set-Content `$file -Value `$content
"@

Set-Content -Path "C:\MAMP\htdocs\my_database\moj_chatbot_php\edit_message.ps1" -Value $editorScript
