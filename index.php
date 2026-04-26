<?php
session_start();
require_once __DIR__ . '/functions.php';

priprav_session_spravy();

$spravy = $_SESSION['messages'];
$jeUvodnyStav = je_uvodny_stav_chatu($spravy);
?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moj chat</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="app-shell">
        <header class="horna-lista">
            <div class="znacka">Moj chat</div>
            <a class="reset-link" href="chat.php?action=reset">Novy rozhovor</a>
        </header>

        <section class="obsah" aria-live="polite">
            <?php if ($jeUvodnyStav): ?>
                <div class="uvod">
                    <h1>Kde by sme mali zacat?</h1>
                </div>
            <?php else: ?>
                <div id="chat-messages" class="chat-messages">
                    <?php foreach ($spravy as $sprava): ?>
                        <?php $jePouzivatel = ($sprava['role'] ?? '') === 'user'; ?>
                        <article class="message-row <?php echo $jePouzivatel ? 'message-row--user' : 'message-row--assistant'; ?>">
                            <span class="avatar avatar--<?php echo $jePouzivatel ? 'user' : 'assistant'; ?>" aria-hidden="true">
                                <?php if ($jePouzivatel): ?>
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.5"/>
                                        <path d="M5.8 18.2C6.7 15.5 9.1 14 12 14C14.9 14 17.3 15.5 18.2 18.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="6.2" y="6.2" width="11.6" height="11.6" rx="2.4" stroke="currentColor" stroke-width="1.5"/>
                                        <path d="M9.3 10.1H14.7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                                        <path d="M9.3 13.9H14.7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                                    </svg>
                                <?php endif; ?>
                            </span>
                            <div class="message-bubble <?php echo $jePouzivatel ? 'message-bubble--user' : 'message-bubble--assistant'; ?>">
                                <p class="message-label"><?php echo $jePouzivatel ? 'Vy' : 'Asistent'; ?></p>
                                <p class="message-text"><?php echo esc_text((string) ($sprava['text'] ?? '')); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <form class="chat-form" action="chat.php" method="POST">
            <label for="message" class="sr-only">Sprava</label>
            <div class="input-obal">
                <span class="plus-ikona" aria-hidden="true">+</span>
                <textarea
                    id="message"
                    name="message"
                    rows="1"
                    maxlength="1200"
                    required
                    placeholder="Spytaj sa hocico..."
                ></textarea>
                <button type="submit" class="odoslat" aria-label="Odoslat spravu">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M5 12H19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M14 7L19 12L14 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <p class="napoveda">Enter odosle spravu, Shift+Enter prida novy riadok</p>
        </form>
    </main>

    <script>
        const textarea = document.getElementById('message');
        const chatMessages = document.getElementById('chat-messages');

        const resizeTextarea = () => {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 180) + 'px';
        };

        textarea.addEventListener('input', resizeTextarea);
        resizeTextarea();

        textarea.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                textarea.form.submit();
            }
        });

        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>