param([string]$file)

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

# Zistiť, či je to git-rebase-todo súbor (pre interaktívny rebase) alebo commit message
$content = Get-Content $file

if ($file -like '*git-rebase-todo*') {
    # Toto je rebase-todo súbor - zmení pick na reword pre všetky commity
    $lines = @()
    foreach ($line in $content) {
        # Ak riadok začína s "pick" a obsahuje commit message, zmení na "reword"
        $newLine = $line -replace '^pick ', 'reword '
        $lines += $newLine
    }
    Set-Content $file -Value $lines
} else {
    # Toto je commit message - zmení staré správy na nové slovenské správy
    $newContent = $content -join "`n"
    foreach ($old in $mapping.Keys) {
        $newContent = $newContent -replace [regex]::Escape($old), $mapping[$old]
    }
    Set-Content $file -Value $newContent
}
