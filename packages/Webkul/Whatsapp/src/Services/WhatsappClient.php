<?php

namespace Webkul\Whatsapp\Services;

use Illuminate\Support\Facades\Http;

class WhatsappClient
{
    protected string $base;

    protected string $secret;

    public function __construct()
    {
        $this->base = rtrim((string) config('whatsapp.service_url'), '/');
        $this->secret = (string) config('whatsapp.secret');
    }

    /**
     * Send a text message through the Node bridge.
     *
     * @return array{ok: bool, external_id?: string|null, chat_id?: string|null, error?: string, status?: int}
     */
    public function send(string $number, string $text): array
    {
        if ($this->base === '') {
            return ['ok' => false, 'error' => 'service_url_not_configured'];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-Whatsapp-Secret' => $this->secret])
                ->acceptJson()
                ->post($this->base.'/send', [
                    'number' => $number,
                    'text' => $text,
                ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'transport: '.$e->getMessage()];
        }

        $data = $response->json();

        if (! $response->successful()) {
            return [
                'ok' => false,
                'status' => $response->status(),
                'error' => $data['error'] ?? 'http_'.$response->status(),
            ];
        }

        return [
            'ok' => (bool) ($data['ok'] ?? false),
            'external_id' => $data['external_id'] ?? null,
            'chat_id' => $data['chat_id'] ?? null,
        ];
    }

    public function health(): array
    {
        if ($this->base === '') {
            return ['ok' => false, 'error' => 'service_url_not_configured'];
        }

        try {
            $response = Http::timeout(5)->acceptJson()->get($this->base.'/health');
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'transport: '.$e->getMessage()];
        }

        return $response->json() ?? ['ok' => false, 'error' => 'bad_response'];
    }
}
