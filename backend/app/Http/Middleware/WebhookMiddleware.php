<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WebhookMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Disable CSRF for webhook requests
        if ($request->is('api/webhook') || $request->is('webhook')) {
            return $next($request);
        }

        return $next($request);
    }
}
