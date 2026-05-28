<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTuningShopUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $level = $request->user()?->playerProfile?->level ?? 1;

        if ($level < 5) {
            abort(403, 'Reach level 5 to access the tuning shop.');
        }

        return $next($request);
    }
}
