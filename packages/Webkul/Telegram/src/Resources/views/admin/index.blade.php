<x-admin::layouts>
    <x-slot:title>
        {{ __('telegram::app.index.title') }}
    </x-slot>

    <div class="mb-5 flex items-center justify-between gap-4 max-sm:flex-wrap">
        <div>
            <p class="text-2xl font-semibold dark:text-white">
                {{ __('telegram::app.index.title') }}
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('telegram::app.index.subtitle') }}
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @elseif (session('info'))
        <div class="mb-4 rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/40 dark:text-sky-300">
            {{ session('info') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-300">
            @foreach ($errors->all() as $msg)
                <div>{{ $msg }}</div>
            @endforeach
        </div>
    @endif

    @php
        $connected = $telegram && $telegram->enabled && $telegram->verified_at;
        $hasToken = $telegram && !empty($telegram->settings['token'] ?? null);
    @endphp

    <div class="box-shadow max-w-3xl rounded-lg bg-white p-6 dark:bg-gray-900">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sky-600 dark:bg-sky-900/40 dark:text-sky-300">
                    {{-- Telegram paper plane --}}
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor"><path d="M9.03 15.47 8.9 19.5c.37 0 .54-.16.74-.35l1.78-1.7 3.68 2.69c.68.37 1.17.17 1.35-.63l2.44-11.46h.01c.21-1-.37-1.39-1.03-1.14L3.9 10.76c-.98.38-.97.93-.17 1.17l3.6 1.12 8.36-5.27c.4-.26.76-.12.47.14z"/></svg>
                </div>
                <div>
                    <div class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ __('telegram::app.index.telegram-title') }}
                    </div>
                    <div class="mt-0.5 max-w-md text-sm text-gray-500 dark:text-gray-400">
                        {{ __('telegram::app.index.telegram-hint') }}
                    </div>
                </div>
            </div>

            @if ($connected)
                <span class="shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    ● {{ __('telegram::app.index.status-connected') }}
                </span>
            @else
                <span class="shrink-0 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    ○ {{ __('telegram::app.index.status-disconnected') }}
                </span>
            @endif
        </div>

        @if ($connected)
            <div class="mb-5 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200">
                <div>
                    <strong>{{ __('telegram::app.index.bot-info', ['name' => '@'.$telegram->username]) }}</strong>
                    @if ($telegram->display_name)
                        <span class="text-gray-500 dark:text-gray-400"> — {{ $telegram->display_name }}</span>
                    @endif
                </div>
                @if ($telegram->verified_at)
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('telegram::app.index.verified-at', ['date' => $telegram->verified_at->format('d.m.Y H:i')]) }}
                    </div>
                @endif
            </div>
        @endif

        <form action="{{ route('admin.settings.integrations.telegram.connect') }}" method="POST" class="space-y-3">
            @csrf

            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('telegram::app.index.token-label') }}
            </label>
            <input
                type="password"
                name="token"
                autocomplete="off"
                spellcheck="false"
                placeholder="{{ __('telegram::app.index.token-placeholder') }}"
                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            />
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('telegram::app.index.token-help') }}
            </p>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="rounded-md bg-brandColor px-4 py-2 text-sm font-medium text-white hover:opacity-90">
                    {{ $hasToken ? __('telegram::app.index.reconnect') : __('telegram::app.index.connect') }}
                </button>

                @if ($connected)
                    <button
                        type="submit"
                        formaction="{{ route('admin.settings.integrations.telegram.disconnect') }}"
                        class="rounded-md border border-rose-200 bg-white px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 dark:border-rose-900/40 dark:bg-transparent dark:text-rose-400 dark:hover:bg-rose-950/40"
                    >
                        {{ __('telegram::app.index.disconnect') }}
                    </button>
                @endif
            </div>
        </form>

        <div class="mt-6 border-t border-gray-200 pt-4 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-300">
            <div class="mb-2 font-medium text-gray-800 dark:text-gray-100">
                {{ __('telegram::app.index.how-to-run') }}
            </div>
            <div class="space-y-1 text-xs">
                <div>
                    {{ __('telegram::app.index.how-to-run-dev') }}
                    <code class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] dark:bg-gray-800">{{ __('telegram::app.index.how-to-run-dev-cmd') }}</code>
                </div>
                <div>
                    {{ __('telegram::app.index.how-to-run-prod') }}
                    <code class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] dark:bg-gray-800">{{ __('telegram::app.index.how-to-run-prod-cmd') }}</code>
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
