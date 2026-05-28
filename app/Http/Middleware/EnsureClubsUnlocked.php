<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClubsUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $unlockLevel = config('game.clubs.unlock_level');
        $level = $request->user()?->playerProfile?->level ?? 1;

        if ($level < $unlockLevel) {
            abort(403, "Reach level {$unlockLevel} to access clubs.");
        }

        return $next($request);
    }
}
