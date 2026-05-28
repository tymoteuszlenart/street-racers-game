<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    public function created(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->playerProfile()->create();
        });
    }
}
