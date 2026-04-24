<?php
session_start();
require_once __DIR__ . '/functions.php';

priprav_session_spravy();

$spravy = $_SESSION['messages'];
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
        <section class="chat-panel">
            <header class="chat-header">
                <div>
                    <p class="chat-title">Moj chat</p>
                    <p class="chat-subtitle">Jednoduchy PHP chat so session pamatou</p>
                </div>
                <a class="reset-button" href="chat.php?action=reset">Novy chat</a>
            </header>

            <div id="chat-messages" class="chat-messages" aria-live="polite">
                <?php foreach ($spravy as $sprava): ?>
                    <?php $jePouzivatel = ($sprava['role'] ?? '') === 'user'; ?>
                    <article class="message-row <?php echo $jePouzivatel ? 'message-row--user' : 'message-row--assistant'; ?>">
                        <div class="message-bubble <?php echo $jePouzivatel ? 'message-bubble--user' : 'message-bubble--assistant'; ?>">
                            <p class="message-label"><?php echo $jePouzivatel ? 'Ty' : 'Asistent'; ?></p>
                            <p class="message-text"><?php echo esc_text((string) ($sprava['text'] ?? '')); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <form class="chat-form" action="chat.php" method="POST">
                <label for="message" class="sr-only">Sprava</label>
                <textarea
                    id="message"
                    name="message"
                    rows="2"
                    maxlength="1200"
                    required
                    placeholder="Napis spravu..."
                ></textarea>
                <div class="chat-form__footer">
                    <p>Enter posle spravu, Shift+Enter pridava novy riadok</p>
                    <button type="submit">Odoslat</button>
                </div>
            </form>
        </section>
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

        chatMessages.scrollTop = chatMessages.scrollHeight;
    </script>
</body>
</html>