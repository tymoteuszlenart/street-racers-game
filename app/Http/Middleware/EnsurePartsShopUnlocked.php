<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePartsShopUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $level = $request->user()?->playerProfile?->level ?? 1;
        $unlockLevel = (int) config('game.parts_shop.unlock_level', 1);

        if ($level < $unlockLevel) {
            abort(403, "Reach level {$unlockLevel} to buy parts.");
        }

        return $next($request);
    }
}
