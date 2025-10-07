<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestId
{
    /**
     * Handle an incoming request.
     *
     * Add unique request ID to all requests and responses for tracking/debugging
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate or retrieve existing request ID
        $requestId = $request->header('X-Request-Id') ?: (string) Str::uuid();

        // Store in request for later use
        $request->attributes->set('request_id', $requestId);

        // Add to log context for all subsequent log entries in this request
        Log::withContext([
            'request_id' => $requestId,
            'user_id' => $request->user()?->user_id,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'uri' => $request->getRequestUri(),
        ]);

        $response = $next($request);

        // Add request ID to response headers
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
