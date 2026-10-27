<?php

namespace Webkul\Telegram\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\Telegram\Repositories\IntegrationRepository;
use Webkul\Telegram\Services\TelegramClient;

class IntegrationController extends Controller
{
    public function __construct(
        protected IntegrationRepository $integrations,
        protected TelegramClient $telegram,
    ) {}

    public function index()
    {
        $telegram = $this->integrations->get('telegram');

        return view('telegram::admin.index', [
            'telegram' => $telegram,
        ]);
    }

    public function connectTelegram(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'regex:/^\d+:[A-Za-z0-9_-]{20,}$/'],
        ], [
            'token.required' => trans('telegram::app.errors.token-required'),
            'token.regex' => trans('telegram::app.errors.token-format'),
        ]);

        $token = trim($data['token']);

        $info = $this->telegram->getMe($token);

        if (isset($info['_error'])) {
            Log::warning('telegram.connect.getMe_failed', ['error' => $info['_error']]);

            return redirect()
                ->route('admin.settings.integrations.index')
                ->withErrors(['token' => trans('telegram::app.errors.token-verify-failed', ['error' => $info['_error']])]);
        }

        $existing = $this->integrations->get('telegram');
        $existingSettings = $existing ? $existing->settings : [];

        $webhookSecret = (string) ($existingSettings['webhook_secret'] ?? '');

        if ($webhookSecret === '') {
            $webhookSecret = Str::random(48);
        }

        $this->integrations->save(
            'telegram',
            [
                'token' => $token,
                'webhook_secret' => $webhookSecret,
            ],
            [
                'enabled' => true,
                'display_name' => trim(($info['first_name'] ?? '').' '.($info['last_name'] ?? '')) ?: ('@'.($info['username'] ?? '')),
                'username' => $info['username'] ?? null,
                'external_id' => isset($info['id']) ? (string) $info['id'] : null,
                'verified_at' => now(),
            ]
        );

        session()->flash('success', trans('telegram::app.index.connected', ['bot' => '@'.($info['username'] ?? '')]));

        return redirect()->route('admin.settings.integrations.index');
    }

    public function disconnectTelegram()
    {
        $this->integrations->disable('telegram');

        session()->flash('info', trans('telegram::app.index.disconnected'));

        return redirect()->route('admin.settings.integrations.index');
    }
}
