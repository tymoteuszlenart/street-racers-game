<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOpenCupUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $unlockLevel = (int) config('game.open_cup.unlock_level', 5);
        $level = $request->user()?->playerProfile?->level ?? 1;

        if ($level < $unlockLevel) {
            abort(403, "Reach level {$unlockLevel} to access Open Cups.");
        }

        return $next($request);
    }
}
