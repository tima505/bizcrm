<?php

namespace Webkul\Telegram\Services;

use Illuminate\Support\Facades\Http;
use Webkul\Telegram\Repositories\IntegrationRepository;

class TelegramClient
{
    protected string $apiBase;

    public function __construct(protected IntegrationRepository $integrations)
    {
        $this->apiBase = rtrim((string) config('telegram.api_base', 'https://api.telegram.org'), '/');
    }

    /**
     * Resolve the bot token. Prefer the per-channel row (set via the admin
     * UI), fall back to the env value for dev/CI.
     */
    public function token(): ?string
    {
        $row = $this->integrations->get('telegram');

        $token = $row?->setting('token');

        if ($token) {
            return $token;
        }

        $env = (string) config('telegram.token', '');

        return $env !== '' ? $env : null;
    }

    /**
     * Thin call(method, params). Returns the Bot API `result` payload on
     * success or ['_error' => string] on failure.
     */
    public function call(string $method, array $params = [], int $timeout = 15, ?string $tokenOverride = null): array
    {
        $token = $tokenOverride ?? $this->token();

        if (! $token) {
            return ['_error' => 'no_token'];
        }

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post($this->apiBase.'/bot'.$token.'/'.$method, $params);
        } catch (\Throwable $e) {
            return ['_error' => 'transport: '.$e->getMessage()];
        }

        $data = $response->json();

        if (! is_array($data) || empty($data['ok'])) {
            $desc = is_array($data) ? ($data['description'] ?? 'unknown') : 'bad_response';

            return ['_error' => $desc, '_status' => $response->status()];
        }

        return is_array($data['result']) ? $data['result'] : ['value' => $data['result']];
    }

    public function getMe(?string $tokenOverride = null): array
    {
        return $this->call('getMe', [], 10, $tokenOverride);
    }

    /**
     * Send a text message. $chatId is the raw Telegram chat id (an integer
     * stored as string in our DB).
     */
    public function sendMessage(string $chatId, string $text): array
    {
        $result = $this->call('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => true,
        ]);

        if (isset($result['_error'])) {
            return [
                'ok' => false,
                'error' => $result['_error'],
                'status' => $result['_status'] ?? null,
            ];
        }

        return [
            'ok' => true,
            'external_id' => isset($result['message_id']) ? (string) $result['message_id'] : null,
            'chat_id' => isset($result['chat']['id']) ? (string) $result['chat']['id'] : $chatId,
        ];
    }

    public function setWebhook(string $url, string $secret): array
    {
        return $this->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['message'],
            'drop_pending_updates' => false,
        ]);
    }

    public function deleteWebhook(bool $drop = false): array
    {
        return $this->call('deleteWebhook', [
            'drop_pending_updates' => $drop,
        ]);
    }

    public function getWebhookInfo(): array
    {
        return $this->call('getWebhookInfo');
    }

    /**
     * Long-polling fetch. $timeout seconds the Telegram server will hold the
     * request open waiting for an update. $offset is `last processed id + 1`.
     */
    public function getUpdates(int $offset = 0, int $timeout = 25): array
    {
        $result = $this->call('getUpdates', [
            'offset' => $offset,
            'timeout' => $timeout,
            'allowed_updates' => ['message'],
        ], $timeout + 10);

        if (isset($result['_error'])) {
            return ['ok' => false, 'error' => $result['_error'], 'updates' => []];
        }

        // The Bot API returns a top-level array which `call()` wraps into
        // ['value' => [...]] when the result is not an associative array.
        $updates = $result['value'] ?? $result;

        return ['ok' => true, 'updates' => is_array($updates) ? $updates : []];
    }
}
