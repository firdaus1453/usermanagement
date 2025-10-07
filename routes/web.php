<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

/**
 * Health Check Endpoint
 *
 * Returns JSON with application status and database connectivity
 */
Route::get('/health', function () {
    $checks = [
        'app' => 'ok',
        'time' => now()->toIso8601String(),
    ];

    try {
        DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Throwable $e) {
        $checks['database'] = 'down';
        \Log::error('Health check database failure', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    $status = in_array('down', $checks, true) ? 'degraded' : 'ok';

    return response()->json([
        'status' => $status,
        'checks' => $checks,
    ], $status === 'ok' ? 200 : 503);
})->name('health');
