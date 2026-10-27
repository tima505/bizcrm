<x-admin::layouts>
    <x-slot:title>
        {{ __('whatsapp::app.index.title') }}
    </x-slot>

    {{-- Tailwind JIT in Admin only scans Admin/src/Resources, so arbitrary --}}
    {{-- values used below (md:w-[320px], h-[calc(...)], min-h-[38px]) never --}}
    {{-- make it into the built CSS. Inline the rules so the layout works --}}
    {{-- without a Vite rebuild. --}}
    @pushOnce('styles')
    <style>
        #wa-messages {
            height: calc(100vh - 180px);
        }
        @media (max-width: 767px) {
            #wa-messages { height: calc(100vh - 150px); }
        }
        @media (min-width: 768px) {
            #wa-chat-aside {
                width: 300px !important;
                flex: 0 0 300px !important;
            }
            /* `hidden` on the thread section is only meant for the mobile */
            /* list-view state; on >=md the thread panel is always visible. */
            #wa-thread-section {
                display: flex !important;
            }
        }
        #wa-send-text {
            min-height: 38px;
            max-height: 128px;
        }
        .wa-bubble { max-width: 75%; }
        /* Chat title: wrap to two lines, then ellipsis — not a single-line */
        /* truncate — because real Telegram display names can be long. */
        #wa-thread-title {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
            line-height: 1.2;
        }
        /* Composer send button: icon-only pill on phones, text on desktop. */
        .wa-send-btn-icon { display: inline-flex; }
        .wa-send-btn-text { display: none; }
        @media (min-width: 768px) {
            .wa-send-btn-icon { display: none; }
            .wa-send-btn-text { display: inline-flex; }
        }
        /* Only show the Enter/Shift+Enter hint on desktop — on mobile Enter */
        /* just inserts a newline because the on-screen keyboard needs it. */
        .wa-send-hint { display: none; }
        @media (min-width: 768px) {
            .wa-send-hint { display: block; }
        }
    </style>
    @endPushOnce

    <div class="mb-5 flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-2xl font-semibold dark:text-white">
            {{ __('whatsapp::app.index.title') }}
        </p>
    </div>

    <div
        id="wa-messages"
        class="flex h-[calc(100vh-180px)] gap-0 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 max-md:h-[calc(100vh-150px)]"
        data-thread-url="{{ route('admin.messages.thread') }}"
        data-send-url="{{ route('admin.messages.send') }}"
        data-lead-url-template="{{ route('admin.leads.view', ['id' => '__ID__']) }}"
        data-csrf="{{ csrf_token() }}"
        data-today="{{ __('whatsapp::app.thread.today') }}"
        data-yesterday="{{ __('whatsapp::app.thread.yesterday') }}"
        data-yesterday-short="{{ __('whatsapp::app.time.yesterday') }}"
        data-open-lead="{{ __('whatsapp::app.thread.open-lead') }}"
        data-service-start="{{ __('telegram::app.service.start') }}"
    >
        <aside id="wa-chat-aside" class="flex w-full flex-col border-r border-gray-200 bg-gray-50/60 dark:border-gray-800 dark:bg-gray-900 md:w-[320px] md:shrink-0">
            <div class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-800 dark:border-gray-800 dark:text-white">
                {{ __('whatsapp::app.index.title') }}
            </div>
            <ul id="wa-chat-list" class="flex-1 overflow-y-auto">
                @forelse ($chats as $chat)
                    <li>
                        <button
                            type="button"
                            class="wa-chat-item flex w-full items-center gap-3 border-b border-gray-100 px-3 py-3 text-left transition hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800"
                            data-chat-id="{{ $chat['chat_id'] }}"
                            data-number="{{ $chat['from_number'] }}"
                            data-name="{{ $chat['name'] }}"
                            data-lead-id="{{ $chat['lead_id'] ?? '' }}"
                        >
                            <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brandColor/15 text-sm font-semibold text-brandColor">
                                {{ $chat['initial'] }}
                                @if (($chat['channel'] ?? 'whatsapp') === 'telegram')
                                    <span class="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-sky-500 text-white ring-2 ring-white dark:ring-gray-900" title="Telegram">
                                        <svg viewBox="0 0 24 24" class="h-2.5 w-2.5" fill="currentColor"><path d="M9.03 15.47 8.9 19.5c.37 0 .54-.16.74-.35l1.78-1.7 3.68 2.69c.68.37 1.17.17 1.35-.63l2.44-11.46h.01c.21-1-.37-1.39-1.03-1.14L3.9 10.76c-.98.38-.97.93-.17 1.17l3.6 1.12 8.36-5.27c.4-.26.76-.12.47.14z"/></svg>
                                    </span>
                                @else
                                    <span class="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500 text-white ring-2 ring-white dark:ring-gray-900" title="WhatsApp">
                                        <svg viewBox="0 0 24 24" class="h-2.5 w-2.5" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.5 15.3L2 22l4.9-1.3A10 10 0 1 0 12 2zm3.1 14.5c-.5-.3-1.6-.9-1.9-1-.3-.1-.5-.1-.7.2-.2.3-.9 1-1 1.2-.2.2-.4.2-.7.1-.5-.3-1.3-.8-2-1.5-.6-.6-1.2-1.5-1.4-1.9-.1-.3 0-.4.1-.6.1-.2.3-.4.5-.6.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5-.1-.1-.7-1.5-.9-2-.2-.6-.4-.5-.6-.5h-.5c-.2 0-.5.1-.7.3-.3.3-.9.9-.9 2.2 0 1.3.9 2.6 1.1 2.8.1.2 1.8 2.9 4.5 3.9.6.3 1.1.4 1.5.5.6.2 1.2.2 1.7.1.5-.1 1.6-.6 1.8-1.3.2-.6.2-1.2.2-1.3-.1-.1-.3-.2-.6-.3z"/></svg>
                                    </span>
                                @endif
                            </span>
                            <span class="flex min-w-0 flex-1 flex-col">
                                <span class="flex items-baseline justify-between gap-2">
                                    <span class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $chat['name'] }}
                                    </span>
                                    <span class="shrink-0 text-[11px] text-gray-500 dark:text-gray-400 wa-chat-time" data-iso="{{ $chat['last_at'] }}">
                                        {{ $chat['last_at'] }}
                                    </span>
                                </span>
                                <span class="mt-0.5 flex items-center justify-between gap-2">
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
                    <li class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('whatsapp::app.index.no-chats') }}
                    </li>
                @endforelse
            </ul>
        </aside>

        <section id="wa-thread-section" class="hidden min-w-0 flex-1 flex-col bg-[#f0f2f5] dark:bg-gray-900/50 md:flex">
            <div
                id="wa-thread-header"
                class="flex items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-white"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        id="wa-back"
                        class="icon-left-arrow -ml-1 shrink-0 rounded-md p-1 text-2xl text-gray-500 hover:bg-gray-100 md:hidden"
                        aria-label="{{ __('admin::app.layouts.back') }}"
                    ></button>
                    <span id="wa-thread-avatar" class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brandColor/15 text-sm font-semibold text-brandColor"></span>
                    <span id="wa-thread-title" class="min-w-0 flex-1">
                        {{ __('whatsapp::app.index.pick-chat') }}
                    </span>
                </div>
                <a
                    id="wa-open-lead"
                    href="#"
                    class="hidden shrink-0 rounded border border-brandColor/40 bg-brandColor/5 px-3 py-1 text-xs font-medium text-brandColor hover:bg-brandColor/10"
                >
                    {{ __('whatsapp::app.thread.open-lead') }}
                </a>
            </div>

            <div id="wa-thread" class="flex-1 overflow-y-auto px-4 py-4">
                <div id="wa-thread-placeholder" class="flex h-full flex-col items-center justify-center gap-3 text-center text-gray-500 dark:text-gray-400">
                    <svg class="h-16 w-16 opacity-40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                    </svg>
                    <div class="text-sm font-medium">
                        {{ __('whatsapp::app.index.pick-chat') }}
                    </div>
                    <div class="max-w-xs text-xs">
                        {{ __('whatsapp::app.index.pick-chat-hint') }}
                    </div>
                </div>
            </div>

            <form
                id="wa-send-form"
                class="flex flex-col gap-1 border-t border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-end gap-2">
                    <textarea
                        id="wa-send-text"
                        rows="1"
                        class="flex-1 resize-none rounded-md border border-gray-300 bg-white px-3 py-2 text-sm leading-5 placeholder:text-gray-400 focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        placeholder="{{ __('whatsapp::app.send.placeholder') }}"
                        disabled
                    ></textarea>
                    <button
                        type="submit"
                        class="shrink-0 rounded-full bg-brandColor text-white transition disabled:cursor-not-allowed disabled:opacity-50 md:rounded-md"
                        disabled
                        id="wa-send-btn"
                        aria-label="{{ __('whatsapp::app.send.button-aria') }}"
                    >
                        <span class="wa-send-btn-text px-4 py-2 text-sm font-medium">
                            {{ __('whatsapp::app.send.button') }}
                        </span>
                        <span class="wa-send-btn-icon h-10 w-10 items-center justify-center">
                            {{-- paper plane --}}
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true"><path d="M3.4 20.5 21 12 3.4 3.5l-.4 6.6 12 1.9-12 1.9z"/></svg>
                        </span>
                    </button>
                </div>
                <div class="wa-send-hint px-1 text-[11px] text-gray-500 dark:text-gray-400">
                    {{ __('whatsapp::app.send.hint') }}
                </div>
            </form>
        </section>
    </div>

    @pushOnce('scripts')
    <script>
    (function () {
        // Admin layout mounts Vue on window.load and replaces #app's subtree,
        // so any ref cached via getElementById before mount points at a
        // detached node. Keep everything lazy and use event delegation.
        const rootInitial = document.getElementById('wa-messages');
        if (!rootInitial) return;

        const threadUrl = rootInitial.dataset.threadUrl;
        const sendUrl = rootInitial.dataset.sendUrl;
        const leadUrlTemplate = rootInitial.dataset.leadUrlTemplate;
        const csrf = rootInitial.dataset.csrf;
        const todayLabel = rootInitial.dataset.today || 'Today';
        const yesterdayLabel = rootInitial.dataset.yesterday || 'Yesterday';
        const yesterdayShort = rootInitial.dataset.yesterdayShort || 'yesterday';
        const openLeadLabel = rootInitial.dataset.openLead || 'Open lead';
        const serviceStartLabel = rootInitial.dataset.serviceStart || 'Chat started';

        const $ = (id) => document.getElementById(id);

        let activeChatId = null;
        let activeNumber = null;
        let activeName = null;
        let activeLeadId = null;
        let pollTimer = null;
        let lastThreadKey = '';

        const emptyLabel = @json(__('whatsapp::app.thread.empty'));
        const sendFailLabel = @json(__('whatsapp::app.send.failed'));
        const noChatsLabel = @json(__('whatsapp::app.index.no-chats'));
        const pickLabel = @json(__('whatsapp::app.index.pick-chat'));
        const anonymousLabel = @json(__('whatsapp::app.chat.anonymous'));

        const monthNames = [
            @json(__('Jan')), @json(__('Feb')), @json(__('Mar')),
            @json(__('Apr')), @json(__('May')), @json(__('Jun')),
            @json(__('Jul')), @json(__('Aug')), @json(__('Sep')),
            @json(__('Oct')), @json(__('Nov')), @json(__('Dec'))
        ];
        // Fallback to Russian/English month names if translations resolve to the key.
        const monthsRu = ['янв.','февр.','марта','апр.','мая','июня','июля','авг.','сент.','окт.','нояб.','дек.'];

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));
        }

        function pad2(n) { return n < 10 ? '0' + n : '' + n; }

        function startOfDay(d) { return new Date(d.getFullYear(), d.getMonth(), d.getDate()); }

        function formatShortTime(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            if (isNaN(d.getTime())) return '';

            const now = new Date();
            const dayDiff = Math.round((startOfDay(now) - startOfDay(d)) / 86400000);

            if (dayDiff === 0) {
                return pad2(d.getHours()) + ':' + pad2(d.getMinutes());
            }
            if (dayDiff === 1) {
                return yesterdayShort;
            }
            if (dayDiff > 0 && dayDiff < 7) {
                return d.toLocaleDateString(undefined, { weekday: 'short' });
            }
            return pad2(d.getDate()) + '.' + pad2(d.getMonth() + 1);
        }

        function formatBubbleTime(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            if (isNaN(d.getTime())) return '';
            return pad2(d.getHours()) + ':' + pad2(d.getMinutes());
        }

        function formatDateSeparator(iso) {
            const d = new Date(iso);
            const now = new Date();
            const dayDiff = Math.round((startOfDay(now) - startOfDay(d)) / 86400000);
            if (dayDiff === 0) return todayLabel;
            if (dayDiff === 1) return yesterdayLabel;
            const day = d.getDate();
            const month = monthsRu[d.getMonth()] || monthNames[d.getMonth()] || (d.getMonth() + 1);
            if (d.getFullYear() === now.getFullYear()) {
                return day + ' ' + month;
            }
            return day + ' ' + month + ' ' + d.getFullYear();
        }

        function refreshVisibleChatTimes() {
            document.querySelectorAll('.wa-chat-time').forEach(el => {
                const iso = el.dataset.iso || el.textContent.trim();
                const short = formatShortTime(iso);
                if (short && el.textContent.trim() !== short) {
                    el.textContent = short;
                }
            });
        }

        function renderThread(messages) {
            if (!activeChatId) return;

            const threadEl = $('wa-thread');
            if (!threadEl) return;

            const key = JSON.stringify(messages.map(m => [m.id, m.status]));
            if (key === lastThreadKey) return;
            lastThreadKey = key;

            if (!messages.length) {
                threadEl.innerHTML = `
                    <div class="flex h-full items-center justify-center text-sm text-gray-500">
                        ${escapeHtml(emptyLabel)}
                    </div>
                `;
                return;
            }

            const atBottom = (threadEl.scrollHeight - threadEl.scrollTop - threadEl.clientHeight) < 40;

            const parts = [];
            let lastDayKey = null;
            for (const m of messages) {
                const iso = m.sent_at;
                const d = iso ? new Date(iso) : null;
                const dayKey = d && !isNaN(d.getTime())
                    ? d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate()
                    : 'nodate';

                if (dayKey !== lastDayKey) {
                    lastDayKey = dayKey;
                    const label = iso ? formatDateSeparator(iso) : '';
                    if (label) {
                        parts.push(`
                            <div class="my-3 flex justify-center">
                                <span class="rounded-full bg-white/80 px-3 py-0.5 text-[11px] font-medium text-gray-600 shadow-sm dark:bg-gray-800 dark:text-gray-300">
                                    ${escapeHtml(label)}
                                </span>
                            </div>
                        `);
                    }
                }

                // Bot /start command: render as a centered gray service row
                // instead of a bubble. It is noise next to real messages, but
                // keeping it in the thread preserves the "they said hi first"
                // chronology.
                if (m.content === '/start' && m.direction === 'incoming') {
                    parts.push(`
                        <div class="my-2 flex justify-center">
                            <span class="rounded-full bg-gray-100 px-3 py-0.5 text-[11px] text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                ${escapeHtml(serviceStartLabel)}
                            </span>
                        </div>
                    `);
                    continue;
                }

                const isOut = m.direction === 'outgoing';
                const align = isOut ? 'ml-auto' : 'mr-auto';
                const bubbleCls = isOut
                    ? 'bg-brandColor text-white'
                    : 'bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100';
                const timeCls = isOut ? 'text-white/70' : 'text-gray-500 dark:text-gray-400';

                parts.push(`
                    <div class="mb-1.5 flex ${isOut ? 'justify-end' : 'justify-start'}">
                        <div class="wa-bubble rounded-lg px-3 py-2 text-sm leading-snug shadow-sm ${bubbleCls}">
                            <div class="whitespace-pre-wrap break-words">${escapeHtml(m.content)}</div>
                            <div class="mt-1 text-right text-[10px] ${timeCls}">${escapeHtml(formatBubbleTime(iso))}</div>
                        </div>
                    </div>
                `);
            }

            threadEl.innerHTML = parts.join('');
            if (atBottom) threadEl.scrollTop = threadEl.scrollHeight;
        }

        function renderChatList(chats) {
            const chatListEl = $('wa-chat-list');
            if (!chatListEl) return;

            if (!chats || !chats.length) {
                chatListEl.innerHTML = `<li class="px-4 py-6 text-center text-sm text-gray-500">${escapeHtml(noChatsLabel)}</li>`;
                return;
            }

            chatListEl.innerHTML = chats.map(c => {
                const activeCls = c.chat_id === activeChatId
                    ? 'bg-brandColor/10 dark:bg-brandColor/20'
                    : '';
                const unreadBadge = c.unread > 0
                    ? `<span class="shrink-0 rounded-full bg-brandColor px-1.5 py-0.5 text-[10px] font-semibold leading-none text-white">${escapeHtml(c.unread)}</span>`
                    : '';
                const outArrow = c.direction === 'outgoing' ? '<span class="text-gray-400">↗</span> ' : '';
                const leadAttr = c.lead_id ? `data-lead-id="${escapeHtml(c.lead_id)}"` : '';
                const name = c.name || c.phone || anonymousLabel;
                const channel = c.channel || 'whatsapp';
                const channelBadge = channel === 'telegram'
                    ? `<span class="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-sky-500 text-white ring-2 ring-white dark:ring-gray-900" title="Telegram"><svg viewBox="0 0 24 24" class="h-2.5 w-2.5" fill="currentColor"><path d="M9.03 15.47 8.9 19.5c.37 0 .54-.16.74-.35l1.78-1.7 3.68 2.69c.68.37 1.17.17 1.35-.63l2.44-11.46h.01c.21-1-.37-1.39-1.03-1.14L3.9 10.76c-.98.38-.97.93-.17 1.17l3.6 1.12 8.36-5.27c.4-.26.76-.12.47.14z"/></svg></span>`
                    : `<span class="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500 text-white ring-2 ring-white dark:ring-gray-900" title="WhatsApp"><svg viewBox="0 0 24 24" class="h-2.5 w-2.5" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.5 15.3L2 22l4.9-1.3A10 10 0 1 0 12 2zm3.1 14.5c-.5-.3-1.6-.9-1.9-1-.3-.1-.5-.1-.7.2-.2.3-.9 1-1 1.2-.2.2-.4.2-.7.1-.5-.3-1.3-.8-2-1.5-.6-.6-1.2-1.5-1.4-1.9-.1-.3 0-.4.1-.6.1-.2.3-.4.5-.6.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5-.1-.1-.7-1.5-.9-2-.2-.6-.4-.5-.6-.5h-.5c-.2 0-.5.1-.7.3-.3.3-.9.9-.9 2.2 0 1.3.9 2.6 1.1 2.8.1.2 1.8 2.9 4.5 3.9.6.3 1.1.4 1.5.5.6.2 1.2.2 1.7.1.5-.1 1.6-.6 1.8-1.3.2-.6.2-1.2.2-1.3-.1-.1-.3-.2-.6-.3z"/></svg></span>`;

                return `
                    <li>
                        <button
                            type="button"
                            class="wa-chat-item flex w-full items-center gap-3 border-b border-gray-100 px-3 py-3 text-left transition hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800 ${activeCls}"
                            data-chat-id="${escapeHtml(c.chat_id)}"
                            data-number="${escapeHtml(c.from_number || '')}"
                            data-name="${escapeHtml(name)}"
                            ${leadAttr}
                        >
                            <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brandColor/15 text-sm font-semibold text-brandColor">
                                ${escapeHtml(c.initial || '?')}
                                ${channelBadge}
                            </span>
                            <span class="flex min-w-0 flex-1 flex-col">
                                <span class="flex items-baseline justify-between gap-2">
                                    <span class="truncate text-sm font-medium text-gray-900 dark:text-white">${escapeHtml(name)}</span>
                                    <span class="shrink-0 text-[11px] text-gray-500 dark:text-gray-400">${escapeHtml(formatShortTime(c.last_at))}</span>
                                </span>
                                <span class="mt-0.5 flex items-center justify-between gap-2">
                                    <span class="truncate text-xs text-gray-500 dark:text-gray-400">${outArrow}${escapeHtml(c.preview || '')}</span>
                                    ${unreadBadge}
                                </span>
                            </span>
                        </button>
                    </li>
                `;
            }).join('');
        }

        function updateHeader() {
            const titleEl = $('wa-thread-title');
            const avatarEl = $('wa-thread-avatar');
            const openLeadLink = $('wa-open-lead');

            if (!activeChatId) {
                if (titleEl) titleEl.textContent = pickLabel;
                if (avatarEl) {
                    avatarEl.classList.add('hidden');
                    avatarEl.classList.remove('inline-flex');
                }
                if (openLeadLink) openLeadLink.classList.add('hidden');
                return;
            }

            const label = activeName || activeChatId;
            if (titleEl) titleEl.textContent = label;
            if (avatarEl) {
                avatarEl.classList.remove('hidden');
                avatarEl.classList.add('inline-flex');
                const letter = (label.match(/\p{L}|\d/u) || ['?'])[0].toUpperCase();
                avatarEl.textContent = letter;
            }
            if (openLeadLink) {
                if (activeLeadId) {
                    openLeadLink.href = leadUrlTemplate.replace('__ID__', activeLeadId);
                    openLeadLink.textContent = openLeadLabel;
                    openLeadLink.classList.remove('hidden');
                } else {
                    openLeadLink.classList.add('hidden');
                }
            }
        }

        function removePlaceholder() {
            const ph = $('wa-thread-placeholder');
            if (ph) ph.remove();
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
                if (activeChatId && typeof data.lead_id !== 'undefined') {
                    activeLeadId = data.lead_id || null;
                    updateHeader();
                }
                renderThread(data.messages || []);
            } catch (e) {
                // network glitch — next poll will retry
            }
        }

        function isMobile() {
            return window.matchMedia('(max-width: 767px)').matches;
        }

        function showThreadPane() {
            const aside = $('wa-chat-aside');
            const section = $('wa-thread-section');
            if (!aside || !section) return;
            if (isMobile()) {
                aside.classList.add('hidden');
                aside.classList.remove('flex');
                section.classList.remove('hidden');
                section.classList.add('flex');
            } else {
                aside.classList.remove('hidden');
                aside.classList.add('flex');
                section.classList.remove('hidden');
                section.classList.add('flex');
            }
        }

        function showListPane() {
            const aside = $('wa-chat-aside');
            const section = $('wa-thread-section');
            if (!aside || !section) return;
            if (isMobile()) {
                aside.classList.remove('hidden');
                aside.classList.add('flex');
                section.classList.add('hidden');
                section.classList.remove('flex');
            } else {
                aside.classList.remove('hidden');
                aside.classList.add('flex');
                section.classList.remove('hidden');
                section.classList.add('flex');
            }
        }

        function selectChat(chatId, number, label, leadId) {
            activeChatId = chatId;
            activeNumber = number || chatId;
            activeName = label || chatId;
            activeLeadId = leadId ? Number(leadId) : null;
            lastThreadKey = '';

            removePlaceholder();
            updateHeader();
            showThreadPane();

            const threadEl = $('wa-thread');
            if (threadEl) threadEl.innerHTML = '';

            const textInput = $('wa-send-text');
            const sendBtn = $('wa-send-btn');
            if (textInput) { textInput.disabled = false; autosize(textInput); setTimeout(() => textInput.focus(), 0); }
            if (sendBtn) sendBtn.disabled = false;

            loadThread();
        }

        function goBackToList() {
            activeChatId = null;
            activeNumber = null;
            activeName = null;
            activeLeadId = null;
            lastThreadKey = '';
            updateHeader();
            showListPane();

            const threadEl = $('wa-thread');
            if (threadEl) threadEl.innerHTML = '';

            const textInput = $('wa-send-text');
            const sendBtn = $('wa-send-btn');
            if (textInput) { textInput.disabled = true; textInput.value = ''; }
            if (sendBtn) sendBtn.disabled = true;
        }

        function autosize(el) {
            if (!el) return;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 128) + 'px';
        }

        async function doSend() {
            const textInput = $('wa-send-text');
            const sendBtn = $('wa-send-btn');
            if (!textInput || !sendBtn) return;

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
                    autosize(textInput);
                    lastThreadKey = '';
                    loadThread();
                }
            } catch (err) {
                alert(sendFailLabel);
            } finally {
                sendBtn.disabled = false;
                textInput.focus();
            }
        }

        // Delegate click/submit/keydown/input from document so handlers
        // survive Vue mount.
        document.addEventListener('click', (ev) => {
            const target = ev.target;
            if (!target || !target.closest) return;

            if (target.closest('#wa-back')) {
                ev.preventDefault();
                goBackToList();
                return;
            }

            const btn = target.closest('.wa-chat-item');
            if (!btn) return;
            if (!document.getElementById('wa-messages')?.contains(btn)) return;

            const label = btn.dataset.name || btn.dataset.number || btn.dataset.chatId;
            selectChat(btn.dataset.chatId, btn.dataset.number, label, btn.dataset.leadId || null);
        });

        window.addEventListener('resize', () => {
            // On viewport change, keep the correct pane visible. Debounced via rAF.
            if (window._waResizeRaf) return;
            window._waResizeRaf = requestAnimationFrame(() => {
                window._waResizeRaf = null;
                if (activeChatId) {
                    showThreadPane();
                } else {
                    showListPane();
                }
            });
        });

        document.addEventListener('submit', (ev) => {
            const form = ev.target.closest ? ev.target.closest('#wa-send-form') : null;
            if (!form) return;
            ev.preventDefault();
            doSend();
        });

        document.addEventListener('keydown', (ev) => {
            if (!ev.target || ev.target.id !== 'wa-send-text') return;
            // On mobile the on-screen keyboard's return key must insert a
            // newline, not fire a send. Only hijack Enter when the viewport
            // is wide enough to be a real keyboard.
            if (isMobile()) return;
            if (ev.key === 'Enter' && !ev.shiftKey && !ev.ctrlKey && !ev.metaKey) {
                ev.preventDefault();
                doSend();
            }
        });

        document.addEventListener('input', (ev) => {
            if (!ev.target || ev.target.id !== 'wa-send-text') return;
            autosize(ev.target);
        });

        function startPolling() {
            refreshVisibleChatTimes();
            if (pollTimer) return;
            pollTimer = setInterval(() => {
                loadThread();
                refreshVisibleChatTimes();
            }, 3000);
            loadThread();
        }

        if (document.readyState === 'complete') {
            startPolling();
        } else {
            window.addEventListener('load', startPolling, { once: true });
        }
    })();
    </script>
    @endPushOnce
</x-admin::layouts>
