<?php

namespace Webkul\Telegram\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Telegram\Repositories\IntegrationRepository;

/**
 * Verify the `X-Telegram-Bot-Api-Secret-Token` header that Telegram includes
 * with every webhook call. The expected secret is read from the integration
 * row first (so rotating the secret via the UI takes effect immediately) and
 * falls back to the env value for CI/dev setups.
 */
class VerifyTelegramSecret
{
    public function __construct(protected IntegrationRepository $integrations) {}

    public function handle(Request $request, Closure $next): Response
    {
        $expected = $this->expectedSecret();
        $got = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');

        if ($expected === '' || ! hash_equals($expected, $got)) {
            return response()->json(['ok' => false, 'error' => 'bad_secret'], 401);
        }

        return $next($request);
    }

    protected function expectedSecret(): string
    {
        $row = $this->integrations->get('telegram');

        $secret = (string) ($row?->setting('webhook_secret') ?? '');

        if ($secret !== '') {
            return $secret;
        }

        return (string) config('telegram.webhook_secret', '');
    }
}
