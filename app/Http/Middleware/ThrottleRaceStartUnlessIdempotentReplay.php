<?php

namespace App\Http\Middleware;

use App\Models\RaceAttempt;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRaceStartUnlessIdempotentReplay
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypassThrottle($request)) {
            return $next($request);
        }

        return app(ThrottleRequests::class)->handle($request, $next, 'race-start');
    }

    private function shouldBypassThrottle(Request $request): bool
    {
        $user = $request->user();
        $key = $request->input('idempotency_key');

        if ($user === null || ! is_string($key) || $key === '') {
            return false;
        }

        return RaceAttempt::query()
            ->where('user_id', $user->id)
            ->where('idempotency_key', $key)
            ->exists();
    }
}
