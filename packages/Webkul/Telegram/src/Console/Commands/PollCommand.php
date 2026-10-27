<?php

namespace Webkul\Telegram\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Telegram\Services\IncomingHandler;
use Webkul\Telegram\Services\TelegramClient;

/**
 * Long-polling worker for development. Blocks on getUpdates in a loop and
 * hands each `message` update to IncomingHandler. Ctrl-C to stop.
 *
 * On production a webhook is used instead — see `telegram:webhook` command.
 */
class PollCommand extends Command
{
    protected $signature = 'telegram:poll
                            {--once : Pull one batch and exit (useful for cron / tests)}';

    protected $description = 'Fetch Telegram updates via long polling and store them in CRM';

    public function handle(TelegramClient $client, IncomingHandler $handler): int
    {
        $token = $client->token();

        if (! $token) {
            $this->error('No Telegram bot token configured. Open Settings → Integrations or set TELEGRAM_BOT_TOKEN.');

            return self::FAILURE;
        }

        $me = $client->getMe();

        if (isset($me['_error'])) {
            $this->error('getMe failed: '.$me['_error']);

            return self::FAILURE;
        }

        $this->info(sprintf('Connected as @%s (%s)', $me['username'] ?? '?', $me['first_name'] ?? ''));

        $timeout = (int) config('telegram.poll_timeout', 25);
        $offset = 0;
        $once = (bool) $this->option('once');

        $running = true;

        // pcntl is a POSIX-only extension — not available on Windows at all.
        // On Linux we wire SIGINT/SIGTERM to flip $running so an in-flight
        // getUpdates finishes cleanly before exit. On Windows the loop just
        // dies on Ctrl-C, which is fine for dev.
        if (extension_loaded('pcntl') && function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
            pcntl_async_signals(true);

            foreach ([SIGINT, SIGTERM] as $sig) {
                pcntl_signal($sig, function () use (&$running) {
                    $running = false;
                });
            }
        }

        while ($running) {
            $batch = $client->getUpdates($offset, $timeout);

            if (! ($batch['ok'] ?? false)) {
                $this->warn('getUpdates error: '.($batch['error'] ?? 'unknown').' — sleeping 3s');
                sleep(3);

                if ($once) {
                    return self::FAILURE;
                }

                continue;
            }

            foreach ($batch['updates'] as $update) {
                $offset = max($offset, ((int) ($update['update_id'] ?? 0)) + 1);

                try {
                    $result = $handler->handle($update);
                } catch (\Throwable $e) {
                    $this->error('handler error: '.$e->getMessage());

                    continue;
                }

                if ($result['status'] === 'ok') {
                    $this->line(sprintf(
                        '[in] #%d  chat=%s  "%s"',
                        $result['message_id'],
                        $update['message']['chat']['id'] ?? '?',
                        mb_substr((string) ($update['message']['text'] ?? ''), 0, 60)
                    ));
                }
            }

            if ($once) {
                return self::SUCCESS;
            }
        }

        $this->info('Stopped by signal.');

        return self::SUCCESS;
    }
}
