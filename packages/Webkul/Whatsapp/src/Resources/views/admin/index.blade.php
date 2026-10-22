<x-admin::layouts>
    <x-slot:title>
        {{ __('whatsapp::app.index.title') }}
    </x-slot>

    <div class="mb-5 flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-2xl font-semibold dark:text-white">
            {{ __('whatsapp::app.index.title') }}
        </p>
    </div>

    <div
        id="wa-messages"
        class="flex h-[calc(100vh-200px)] gap-0 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
        data-thread-url="{{ route('admin.messages.thread') }}"
        data-send-url="{{ route('admin.messages.send') }}"
        data-csrf="{{ csrf_token() }}"
        data-yesterday="{{ __('whatsapp::app.time.yesterday') }}"
    >
        <aside class="flex w-[300px] shrink-0 flex-col border-r border-gray-200 dark:border-gray-800">
            <div class="border-b border-gray-200 px-3 py-2.5 text-sm font-semibold dark:border-gray-800 dark:text-white">
                {{ __('whatsapp::app.index.title') }}
            </div>
            <ul id="wa-chat-list" class="flex-1 overflow-y-auto">
                @forelse ($chats as $chat)
                    <li>
                        <button
                            type="button"
                            class="wa-chat-item flex w-full items-center gap-3 border-b border-gray-100 px-3 py-2.5 text-left hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                            data-chat-id="{{ $chat['chat_id'] }}"
                            data-number="{{ $chat['from_number'] }}"
                            data-name="{{ $chat['name'] }}"
                        >
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brandColor/10 text-sm font-semibold text-brandColor">
                                {{ $chat['initial'] }}
                            </span>
                            <span class="flex min-w-0 flex-1 flex-col">
                                <span class="flex items-baseline justify-between gap-2">
                                    <span class="truncate text-sm font-medium text-gray-800 dark:text-white">
                                        {{ $chat['name'] }}
                                    </span>
                                    <span class="shrink-0 text-[11px] text-gray-500 dark:text-gray-400 wa-chat-time" data-iso="{{ $chat['last_at'] }}">
                                        {{ $chat['last_at'] }}
                                    </span>
                                </span>
                                <span class="flex items-center justify-between gap-2">
                                    <span class="truncate text-xs text-gray-500 dark:text-gray-400">
                                        @if ($chat['direction'] === 'outgoing')
                                            <span class="text-gray-400">↗</span>
                                        @endif
                                        {{ $chat['preview'] }}
                                    </span>
                                    @if ($chat['unread'] > 0)
                                        <span class="shrink-0 rounded-full bg-brandColor px-1.5 py-0.5 text-[10px] font-semibold leading-none text-white">
                                            {{ $chat['unread'] }}
                                        </span>
                                    @endif
                                </span>
                            </span>
                        </button>
                    </li>
                @empty
                    <li class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('whatsapp::app.index.no-chats') }}
                    </li>
                @endforelse
            </ul>
        </aside>

        <section class="flex flex-1 flex-col">
            <div
                id="wa-thread-header"
                class="border-b border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-800 dark:border-gray-800 dark:text-white"
            >
                {{ __('whatsapp::app.index.pick-chat') }}
            </div>

            <div id="wa-thread" class="flex-1 space-y-2 overflow-y-auto p-4"></div>

            <form
                id="wa-send-form"
                class="flex gap-2 border-t border-gray-200 p-3 dark:border-gray-800"
            >
                <input
                    id="wa-send-text"
                    type="text"
                    class="flex-1 rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    placeholder="{{ __('whatsapp::app.send.placeholder') }}"
                    disabled
                />
                <button
                    type="submit"
                    class="rounded bg-brandColor px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                    disabled
                    id="wa-send-btn"
                >
                    {{ __('whatsapp::app.send.button') }}
                </button>
            </form>
        </section>
    </div>

    @pushOnce('scripts')
    <script>
    (function () {
        const root = document.getElementById('wa-messages');
        if (!root) return;

        const threadUrl = root.dataset.threadUrl;
        const sendUrl = root.dataset.sendUrl;
        const csrf = root.dataset.csrf;
        const yesterdayLabel = root.dataset.yesterday || 'yesterday';

        const threadEl = document.getElementById('wa-thread');
        const headerEl = document.getElementById('wa-thread-header');
        const chatListEl = document.getElementById('wa-chat-list');
        const textInput = document.getElementById('wa-send-text');
        const sendBtn = document.getElementById('wa-send-btn');
        const sendForm = document.getElementById('wa-send-form');

        let activeChatId = null;
        let activeNumber = null;
        let pollTimer = null;
        let lastThreadKey = '';

        const emptyLabel = @json(__('whatsapp::app.thread.empty'));
        const sendFailLabel = @json(__('whatsapp::app.send.failed'));
        const noChatsLabel = @json(__('whatsapp::app.index.no-chats'));

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));
        }

        function pad2(n) {
            return n < 10 ? '0' + n : '' + n;
        }

        function formatShortTime(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            if (isNaN(d.getTime())) return '';

            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const that = new Date(d.getFullYear(), d.getMonth(), d.getDate());
            const dayDiff = Math.round((today - that) / 86400000);

            if (dayDiff === 0) {
                return pad2(d.getHours()) + ':' + pad2(d.getMinutes());
            }
            if (dayDiff === 1) {
                return yesterdayLabel;
            }
            if (dayDiff < 7 && dayDiff > 0) {
                return d.toLocaleDateString(undefined, { weekday: 'short' });
            }
            return pad2(d.getDate()) + '.' + pad2(d.getMonth() + 1);
        }

        function formatFullTime(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            if (isNaN(d.getTime())) return '';
            return pad2(d.getDate()) + '.' + pad2(d.getMonth() + 1) + ' ' + pad2(d.getHours()) + ':' + pad2(d.getMinutes());
        }

        // Convert initial server-rendered timestamps to short display.
        document.querySelectorAll('.wa-chat-time').forEach(el => {
            const iso = el.dataset.iso || el.textContent.trim();
            el.textContent = formatShortTime(iso);
        });

        function renderThread(messages) {
            if (!activeChatId) {
                return;
            }

            const key = JSON.stringify(messages.map(m => [m.id, m.status]));
            if (key === lastThreadKey) return;
            lastThreadKey = key;

            if (!messages.length) {
                threadEl.innerHTML = `<div class="text-sm text-gray-500">${escapeHtml(emptyLabel)}</div>`;
                return;
            }

            threadEl.innerHTML = messages.map(m => {
                const isOut = m.direction === 'outgoing';
                const align = isOut ? 'ml-auto bg-brandColor/10 border-brandColor/30' : 'mr-auto bg-gray-100 dark:bg-gray-800';
                return `
                    <div class="max-w-[70%] rounded border px-3 py-2 text-sm ${align}">
                        <div class="whitespace-pre-wrap break-words">${escapeHtml(m.content)}</div>
                        <div class="mt-1 text-[10px] text-gray-500">${escapeHtml(formatFullTime(m.sent_at))}</div>
                    </div>
                `;
            }).join('');

            threadEl.scrollTop = threadEl.scrollHeight;
        }

        function renderChatList(chats) {
            if (!chats || !chats.length) {
                chatListEl.innerHTML = `<li class="px-3 py-4 text-sm text-gray-500">${escapeHtml(noChatsLabel)}</li>`;
                return;
            }

            chatListEl.innerHTML = chats.map(c => {
                const activeCls = c.chat_id === activeChatId ? 'bg-gray-50 dark:bg-gray-800' : '';
                const unreadBadge = c.unread > 0
                    ? `<span class="shrink-0 rounded-full bg-brandColor px-1.5 py-0.5 text-[10px] font-semibold leading-none text-white">${escapeHtml(c.unread)}</span>`
                    : '';
                const outArrow = c.direction === 'outgoing' ? '<span class="text-gray-400">↗</span> ' : '';

                return `
                    <li>
                        <button
                            type="button"
                            class="wa-chat-item flex w-full items-center gap-3 border-b border-gray-100 px-3 py-2.5 text-left hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800 ${activeCls}"
                            data-chat-id="${escapeHtml(c.chat_id)}"
                            data-number="${escapeHtml(c.from_number || '')}"
                            data-name="${escapeHtml(c.name || '')}"
                        >
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brandColor/10 text-sm font-semibold text-brandColor">
                                ${escapeHtml(c.initial || '?')}
                            </span>
                            <span class="flex min-w-0 flex-1 flex-col">
                                <span class="flex items-baseline justify-between gap-2">
                                    <span class="truncate text-sm font-medium text-gray-800 dark:text-white">${escapeHtml(c.name || c.phone || c.chat_id)}</span>
                                    <span class="shrink-0 text-[11px] text-gray-500 dark:text-gray-400">${escapeHtml(formatShortTime(c.last_at))}</span>
                                </span>
                                <span class="flex items-center justify-between gap-2">
                                    <span class="truncate text-xs text-gray-500 dark:text-gray-400">${outArrow}${escapeHtml(c.preview || '')}</span>
                                    ${unreadBadge}
                                </span>
                            </span>
                        </button>
                    </li>
                `;
            }).join('');

            bindChatItems();
        }

        async function loadThread() {
            try {
                const url = new URL(threadUrl, window.location.origin);
                if (activeChatId) url.searchParams.set('chat_id', activeChatId);

                const res = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                renderChatList(data.chats || []);
                renderThread(data.messages || []);
            } catch (e) {
                // network glitch — next poll will retry
            }
        }

        function selectChat(chatId, number, label) {
            activeChatId = chatId;
            activeNumber = number || chatId;
            headerEl.textContent = label || chatId;
            lastThreadKey = '';
            threadEl.innerHTML = '';
            textInput.disabled = false;
            sendBtn.disabled = false;
            loadThread();
        }

        function bindChatItems() {
            document.querySelectorAll('.wa-chat-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    selectChat(
                        btn.dataset.chatId,
                        btn.dataset.number,
                        btn.dataset.name || btn.dataset.number || btn.dataset.chatId,
                    );
                });
            });
        }

        sendForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const text = textInput.value.trim();
            if (!text || !activeChatId) return;

            sendBtn.disabled = true;

            try {
                const res = await fetch(sendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        chat_id: activeChatId,
                        number: activeNumber,
                        text: text,
                    }),
                });

                if (!res.ok) {
                    alert(sendFailLabel);
                } else {
                    textInput.value = '';
                    lastThreadKey = '';
                    loadThread();
                }
            } catch (err) {
                alert(sendFailLabel);
            } finally {
                sendBtn.disabled = false;
                textInput.focus();
            }
        });

        bindChatItems();

        pollTimer = setInterval(loadThread, 3000);
        loadThread();
    })();
    </script>
    @endPushOnce
</x-admin::layouts>
