<?php

namespace App\Observers;

use App\Models\User;
use Throwable;

class UserObserver
{
    public function created(User $user): void
    {
        try {
            $user->playerProfile()->create();
        } catch (Throwable $e) {
            $user->delete();

            throw $e;
        }
    }
}
