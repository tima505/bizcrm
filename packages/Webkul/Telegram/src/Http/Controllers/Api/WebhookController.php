<?php

namespace Webkul\Telegram\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\Telegram\Services\IncomingHandler;

class WebhookController extends Controller
{
    public function __construct(protected IncomingHandler $handler) {}

    public function __invoke(Request $request): JsonResponse
    {
        $update = $request->all();

        try {
            $result = $this->handler->handle($update);
        } catch (\Throwable $e) {
            Log::error('telegram.webhook', [
                'error' => $e->getMessage(),
                'update_id' => $update['update_id'] ?? null,
            ]);

            // Always 200 so Telegram does not spam retries on broken rows.
            return response()->json(['ok' => true, 'processed' => false]);
        }

        return response()->json(['ok' => true, 'result' => $result]);
    }
}
