<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Add CORS headers for webhook requests
        if ($request->is('api/webhook') || $request->is('webhook')) {
            return $next($request)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }

        return $next($request);
    }
}
