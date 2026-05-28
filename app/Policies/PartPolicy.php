<?php

namespace App\Policies;

use App\Models\Part;
use App\Models\User;

class PartPolicy
{
    public function view(User $user, Part $part): bool
    {
        return $part->user_id === $user->id;
    }

    public function update(User $user, Part $part): bool
    {
        return $part->user_id === $user->id;
    }
}
