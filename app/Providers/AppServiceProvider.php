<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $email = $request->input('email');
            $key = is_string($email) ? Str::lower($email) : '';

            return Limit::perMinute(5)
                ->by($key.'|'.$request->ip())
                ->response($this->throttledResponse(...));
        });

        RateLimiter::for('api', function (Request $request): Limit {
            // The global limiter runs before route authentication; resolve Sanctum here so
            // authenticated requests are still keyed by user instead of falling back to IP.
            $user = $request->user() ?? Auth::guard('sanctum')->user();
            $key = $user !== null
                ? 'user:'.$user->getAuthIdentifier()
                : 'ip:'.$request->ip();

            return Limit::perMinute(60)
                ->by($key)
                ->response($this->throttledResponse(...));
        });
    }

    private function throttledResponse(Request $request, array $headers): JsonResponse
    {
        return response()->json(['message' => 'Too many requests.'], 429, $headers);
    }
}
