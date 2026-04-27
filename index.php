<?php
session_start();
require_once __DIR__ . '/functions.php';

priprav_session_spravy();

$spravy = ziskaj_aktivne_spravy();
$chaty = zoznam_chatov();
$aktivnyChatId = ziskaj_aktivny_chat_id();
$jeUvodnyStav = je_uvodny_stav_chatu($spravy);
?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Môj chat</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="app-shell">
        <div class="layout">
            <aside class="sidebar" aria-label="História chatov">
                <div class="sidebar-top">
                    <a class="sidebar-new" href="chat.php?action=reset">✎ Nový chat</a>
                    <div class="sidebar-search" aria-hidden="true">⌕ Prehľadávať chaty</div>
                </div>
                <div class="sidebar-header">
                    <h2>Nedávne chaty</h2>
                </div>
                <div class="sidebar-list">
                    <?php foreach ($chaty as $chat): ?>
                        <?php
                        $chatId = (string) ($chat['id'] ?? '');
                        $nazov = (string) ($chat['title'] ?? 'Nový chat');
                        $aktivny = $chatId !== '' && $chatId === $aktivnyChatId;
                        ?>
                        <a
                            class="sidebar-chat-link <?php echo $aktivny ? 'sidebar-chat-link--active' : ''; ?>"
                            href="chat.php?action=switch&amp;id=<?php echo rawurlencode($chatId); ?>"
                        >
                            <span class="sidebar-chat-title"><?php echo esc_text($nazov); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>

            <section class="chat-panel">
                <header class="horna-lista">
                    <div class="znacka">Môj chat</div>
                    <a class="reset-link" href="chat.php?action=reset">Nový rozhovor</a>
                </header>

                <section class="obsah" aria-live="polite">
                    <?php if ($jeUvodnyStav): ?>
                        <div class="uvod">
                            <h1>Kde by sme mali začať?</h1>
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

                <form id="chat-form" class="chat-form" action="chat.php" method="POST">
                    <label for="message" class="sr-only">Správa</label>
                    <div class="input-obal">
                        <span class="plus-ikona" aria-hidden="true">+</span>
                        <textarea
                            id="message"
                            name="message"
                            rows="1"
                            maxlength="1200"
                            required
                            placeholder="Spýtaj sa hocičo..."
                        ></textarea>
                        <button id="odoslat-btn" type="submit" class="odoslat" aria-label="Odoslať správu">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M5 12H19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M14 7L19 12L14 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    <p class="napoveda">Enter odošle správu, Shift+Enter pridá nový riadok</p>
                </form>
            </section>
        </div>
    </main>

    <script>
        /** @type {HTMLTextAreaElement | null} */
        const textarea = document.getElementById('message');
        /** @type {HTMLDivElement | null} */
        let chatMessages = document.getElementById('chat-messages');
        /** @type {HTMLFormElement | null} */
        const chatForm = document.getElementById('chat-form');
        /** @type {HTMLButtonElement | null} */
        const odoslatBtn = document.getElementById('odoslat-btn');
        /** @type {HTMLElement | null} */
        const obsah = document.querySelector('.obsah');

        let odosielaniePrebieha = false;

        const escapeText = (value) => value;

        const vytvorBublinu = (role, text, thinking = false) => {
            const jePouzivatel = role === 'user';

            const row = document.createElement('article');
            row.className = 'message-row ' + (jePouzivatel ? 'message-row--user' : 'message-row--assistant');

            const avatar = document.createElement('span');
            avatar.className = 'avatar avatar--' + (jePouzivatel ? 'user' : 'assistant');
            avatar.setAttribute('aria-hidden', 'true');

            if (jePouzivatel) {
                avatar.innerHTML = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.5"/><path d="M5.8 18.2C6.7 15.5 9.1 14 12 14C14.9 14 17.3 15.5 18.2 18.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
            } else {
                avatar.innerHTML = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6.2" y="6.2" width="11.6" height="11.6" rx="2.4" stroke="currentColor" stroke-width="1.5"/><path d="M9.3 10.1H14.7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M9.3 13.9H14.7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>';
            }

            const bubble = document.createElement('div');
            bubble.className = 'message-bubble ' + (jePouzivatel ? 'message-bubble--user' : 'message-bubble--assistant');

            const label = document.createElement('p');
            label.className = 'message-label';
            label.textContent = jePouzivatel ? 'Vy' : 'Asistent';

            const content = document.createElement('p');
            content.className = 'message-text' + (thinking ? ' message-text--thinking' : '');
            content.textContent = escapeText(text);

            bubble.appendChild(label);
            bubble.appendChild(content);
            row.appendChild(avatar);
            row.appendChild(bubble);

            return { row, content };
        };

        const pripravChatPriPrvejSprave = () => {
            if (!obsah) {
                return;
            }

            const uvod = obsah.querySelector('.uvod');
            if (uvod) {
                uvod.remove();
            }

            if (!chatMessages) {
                const novyChat = document.createElement('div');
                novyChat.id = 'chat-messages';
                novyChat.className = 'chat-messages';
                obsah.appendChild(novyChat);
                chatMessages = novyChat;
            }
        };

        const resizeTextarea = () => {
            if (!textarea) {
                return;
            }

            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 180) + 'px';
        };

        if (textarea) {
            textarea.addEventListener('input', resizeTextarea);
            resizeTextarea();
        }

        if (textarea && chatForm) {
            textarea.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    chatForm.requestSubmit();
                }
            });
        }

        if (chatForm && textarea && odoslatBtn) {
            chatForm.addEventListener('submit', async (event) => {
                if (odosielaniePrebieha) {
                    event.preventDefault();
                    return;
                }

                event.preventDefault();

                const text = textarea.value.trim();
                if (text === '') {
                    return;
                }

                odosielaniePrebieha = true;
                odoslatBtn.disabled = true;
                odoslatBtn.classList.add('odoslat--loading');

                pripravChatPriPrvejSprave();

                const userNode = vytvorBublinu('user', text);
                const thinkingNode = vytvorBublinu('assistant', 'Premýšľam…', true);

                if (chatMessages) {
                    chatMessages.appendChild(userNode.row);
                    chatMessages.appendChild(thinkingNode.row);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }

                textarea.value = '';
                resizeTextarea();

                try {
                    const formData = new FormData();
                    formData.append('message', text);
                    formData.append('ajax', '1');

                    const response = await fetch(chatForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'fetch'
                        }
                    });

                    const data = await response.json();
                    const assistantText = response.ok && data && data.ok
                        ? String(data.assistant || '')
                        : String((data && data.error) || 'Nepodarilo sa získať odpoveď.');

                    thinkingNode.content.classList.remove('message-text--thinking');
                    thinkingNode.content.textContent = assistantText;
                } catch (error) {
                    thinkingNode.content.classList.remove('message-text--thinking');
                    thinkingNode.content.textContent = 'Prepáč, nastala chyba spojenia. Skús to znova.';
                } finally {
                    odosielaniePrebieha = false;
                    odoslatBtn.disabled = false;
                    odoslatBtn.classList.remove('odoslat--loading');
                    textarea.focus();

                    if (chatMessages) {
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                }
            });
        }

        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>