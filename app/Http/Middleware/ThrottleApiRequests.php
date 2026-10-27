<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

final class ThrottleApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/*') || $request->is('api/v1/login')) {
            return $next($request);
        }

        return app(ThrottleRequests::class)->handle($request, $next, 'api');
    }
}
