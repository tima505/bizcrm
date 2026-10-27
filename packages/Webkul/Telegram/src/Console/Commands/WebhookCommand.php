<?php

namespace Webkul\Telegram\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Webkul\Telegram\Repositories\IntegrationRepository;
use Webkul\Telegram\Services\TelegramClient;

/**
 * Register / inspect / unregister the Telegram webhook. Used on production
 * where the server is reachable from the public internet.
 */
class WebhookCommand extends Command
{
    protected $signature = 'telegram:webhook
                            {action=info : info|set|delete}
                            {--url= : Base URL (defaults to telegram.webhook_url / APP_URL)}
                            {--secret= : Secret token (random if omitted on set)}';

    protected $description = 'Manage the Telegram webhook (info|set|delete)';

    public function handle(TelegramClient $client, IntegrationRepository $integrations): int
    {
        $action = (string) $this->argument('action');

        return match ($action) {
            'info' => $this->info_(),
            'set' => $this->set($client, $integrations),
            'delete' => $this->delete($client, $integrations),
            default => $this->failWith('Unknown action: '.$action),
        };
    }

    protected function info_(): int
    {
        $client = app(TelegramClient::class);
        $info = $client->getWebhookInfo();

        if (isset($info['_error'])) {
            $this->error($info['_error']);

            return self::FAILURE;
        }

        $this->line(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    protected function set(TelegramClient $client, IntegrationRepository $integrations): int
    {
        $base = (string) ($this->option('url') ?: config('telegram.webhook_url') ?: config('app.url'));

        if ($base === '') {
            $this->error('No webhook URL. Pass --url or set TELEGRAM_WEBHOOK_URL / APP_URL.');

            return self::FAILURE;
        }

        $secret = (string) $this->option('secret');

        if ($secret === '') {
            $row = $integrations->get('telegram');
            $secret = (string) ($row?->setting('webhook_secret') ?? config('telegram.webhook_secret', ''));
        }

        if ($secret === '') {
            $secret = Str::random(48);
            $this->warn('No secret provided — generated: '.$secret);
            $this->warn('Store this in Settings → Integrations or TELEGRAM_WEBHOOK_SECRET so the middleware accepts callbacks.');
        }

        $url = rtrim($base, '/').'/api/telegram/webhook';

        $result = $client->setWebhook($url, $secret);

        if (isset($result['_error'])) {
            $this->error('setWebhook failed: '.$result['_error']);

            return self::FAILURE;
        }

        $this->info('Webhook registered at '.$url);

        return self::SUCCESS;
    }

    protected function delete(TelegramClient $client, IntegrationRepository $integrations): int
    {
        $result = $client->deleteWebhook();

        if (isset($result['_error'])) {
            $this->error('deleteWebhook failed: '.$result['_error']);

            return self::FAILURE;
        }

        $this->info('Webhook deleted.');

        return self::SUCCESS;
    }

    protected function failWith(string $msg): int
    {
        $this->error($msg);

        return self::FAILURE;
    }
}
