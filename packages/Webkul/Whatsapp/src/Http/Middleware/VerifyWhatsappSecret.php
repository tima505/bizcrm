<?php

namespace Webkul\Whatsapp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWhatsappSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('whatsapp.secret');
        $got = (string) $request->header('X-Whatsapp-Secret');

        if ($expected === '' || ! hash_equals($expected, $got)) {
            return response()->json(['ok' => false, 'error' => 'bad_secret'], 401);
        }

        return $next($request);
    }
}
