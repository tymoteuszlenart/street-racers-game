<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMechanicUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $level = $request->user()?->playerProfile?->level ?? 1;
        $unlockLevel = (int) config('game.mechanic.unlock_level', 10);

        if ($level < $unlockLevel) {
            abort(403, "Reach level {$unlockLevel} to access the mechanic.");
        }

        return $next($request);
    }
}
