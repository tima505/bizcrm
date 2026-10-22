<?php
/**
 * Boots Laravel, logs in as the first admin, dispatches a GET to the given
 * admin URI, prints a summary of SQL queries grouped by normalized pattern.
 *
 * Usage:
 *   php tools/profile_queries.php /admin/leads
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot the console kernel so facades & DB are initialised before we build the fake request.
$consoleKernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$consoleKernel->bootstrap();

$path = $argv[1] ?? '/admin/dashboard';

$firstUser = Illuminate\Support\Facades\DB::table('users')->orderBy('id')->first();
if (! $firstUser) {
    fwrite(STDERR, "No users found\n");
    exit(1);
}

Illuminate\Support\Facades\Auth::guard('user')->loginUsingId($firstUser->id);

$queries = [];
Illuminate\Support\Facades\DB::listen(function ($q) use (&$queries) {
    $queries[] = ['time' => $q->time, 'sql' => $q->sql];
});

$request = Illuminate\Http\Request::create($path, 'GET');
$request->setLaravelSession($app['session']->driver());
$app['session']->driver()->setId(bin2hex(random_bytes(20)));
$request->headers->set('Accept', 'text/html');

$t0 = microtime(true);
$response = $kernel->handle($request);
$totalMs = (microtime(true) - $t0) * 1000;

$byPattern = [];
$totalQueryMs = 0.0;
foreach ($queries as $q) {
    $totalQueryMs += $q['time'];
    $pattern = preg_replace('/\s+/', ' ', $q['sql']);
    $pattern = preg_replace("/'[^']*'/", '?', $pattern);
    $pattern = preg_replace('/\b\d+\b/', '?', $pattern);
    $pattern = substr($pattern, 0, 220);
    if (! isset($byPattern[$pattern])) {
        $byPattern[$pattern] = ['count' => 0, 'total_ms' => 0.0];
    }
    $byPattern[$pattern]['count']++;
    $byPattern[$pattern]['total_ms'] += $q['time'];
}

uasort($byPattern, fn ($a, $b) => $b['count'] <=> $a['count']);

printf("PATH: %s | HTTP %d | wall %.0fms | queries %d | SQL time %.1fms\n",
    $path, $response->getStatusCode(), $totalMs, count($queries), $totalQueryMs);
echo str_repeat('-', 100), PHP_EOL;
echo "Top patterns by count:\n";
$shown = 0;
foreach ($byPattern as $pattern => $stats) {
    if ($shown++ >= 15) break;
    printf("%3dx (%6.1fms) %s\n", $stats['count'], $stats['total_ms'], $pattern);
}

echo PHP_EOL, "Top patterns by total time:\n";
uasort($byPattern, fn ($a, $b) => $b['total_ms'] <=> $a['total_ms']);
$shown = 0;
foreach ($byPattern as $pattern => $stats) {
    if ($shown++ >= 10) break;
    printf("%6.1fms (%3dx) %s\n", $stats['total_ms'], $stats['count'], $pattern);
}

$kernel->terminate($request, $response);
