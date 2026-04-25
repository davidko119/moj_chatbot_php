<?php
session_start();

if (!isset($_SESSION['messages']) || !is_array($_SESSION['messages'])) {
    $_SESSION['messages'] = [
        [
            'role' => 'assistant',
            'text' => 'Ahoj, som tvoj AI asistent. Napis mi, s cim ti mam pomoct.'
        ]
    ];
}

$messages = $_SESSION['messages'];

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moj AI Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Manrope', 'sans-serif'],
                        display: ['Space Grotesk', 'sans-serif']
                    },
                    colors: {
                        ink: '#09090B',
                        panel: '#101014',
                        edge: '#1F1F26',
                        signal: '#F4F4F5'
                    },
                    boxShadow: {
                        soft: '0 12px 42px rgba(0, 0, 0, 0.32)'
                    }
                }
            }
        };
    </script>
</head>
<body class="min-h-screen bg-ink text-zinc-100 antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(70,70,85,0.45),_rgba(9,9,11,1)_45%)]"></div>

    <main class="mx-auto flex min-h-screen w-full max-w-6xl flex-col p-4 md:p-8">
        <section class="flex min-h-[82vh] flex-1 flex-col overflow-hidden rounded-3xl border border-edge bg-panel/95 shadow-soft backdrop-blur">
            <header class="flex items-center justify-between border-b border-edge px-5 py-4 md:px-7">
                <div>
                    <p class="font-display text-lg font-semibold tracking-tight text-signal md:text-xl">Moj AI Chat</p>
                    <p class="text-xs text-zinc-400 md:text-sm">Clean chat interface inspired by modern AI tools</p>
                </div>
                <a
                    href="chat.php?action=reset"
                    class="rounded-full border border-zinc-700/80 px-4 py-2 text-xs font-medium text-zinc-300 transition hover:border-zinc-500 hover:text-zinc-100"
                >
                    Novy chat
                </a>
            </header>

            <div id="chat-messages" class="flex-1 space-y-4 overflow-y-auto px-4 py-6 md:px-7">
                <?php foreach ($messages as $message): ?>
                    <?php $isUser = ($message['role'] ?? '') === 'user'; ?>
                    <article class="flex <?php echo $isUser ? 'justify-end' : 'justify-start'; ?>">
                        <div class="max-w-[85%] rounded-2xl border px-4 py-3 md:max-w-[78%] <?php echo $isUser ? 'border-zinc-600 bg-zinc-200 text-zinc-900' : 'border-edge bg-zinc-900/80 text-zinc-100'; ?>">
                            <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider <?php echo $isUser ? 'text-zinc-600' : 'text-zinc-400'; ?>">
                                <?php echo $isUser ? 'Ty' : 'Asistent'; ?>
                            </p>
                            <p class="whitespace-pre-wrap text-sm leading-relaxed md:text-[15px]">
                                <?php echo escape_html((string) ($message['text'] ?? '')); ?>
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <form class="border-t border-edge p-4 md:p-6" action="chat.php" method="POST">
                <label for="message" class="sr-only">Sprava</label>
                <div class="rounded-2xl border border-edge bg-zinc-900/80 p-2 md:p-3">
                    <textarea
                        id="message"
                        name="message"
                        rows="2"
                        maxlength="1200"
                        required
                        placeholder="Napis spravu..."
                        class="w-full resize-none bg-transparent px-2 py-2 text-sm text-zinc-100 outline-none placeholder:text-zinc-500 md:text-[15px]"
                    ></textarea>
                    <div class="flex items-center justify-between border-t border-edge/80 px-2 pt-3">
                        <p class="text-xs text-zinc-500">Enter pre odoslanie, Shift+Enter pre novy riadok</p>
                        <button
                            type="submit"
                            class="rounded-full bg-zinc-100 px-5 py-2 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400"
                        >
                            Odoslat
                        </button>
                    </div>
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