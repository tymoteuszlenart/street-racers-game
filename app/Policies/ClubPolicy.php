<?php

namespace App\Policies;

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;

class ClubPolicy
{
    public function view(User $user, Club $club): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->clubMember === null;
    }

    public function update(User $user, Club $club): bool
    {
        $membership = $this->membership($user, $club);

        return $membership !== null && $membership->role->canManageClub();
    }

    public function join(User $user, Club $club): bool
    {
        if ($user->clubMember !== null) {
            return false;
        }

        $club->loadCount('members');

        return ! $club->isFull();
    }

    public function leave(User $user, Club $club): bool
    {
        $membership = $this->membership($user, $club);

        return $membership !== null && $membership->role !== ClubRole::Owner;
    }

    public function kick(User $user, Club $club, ClubMember $target): bool
    {
        if ($target->club_id !== $club->id) {
            return false;
        }

        $actorMembership = $this->membership($user, $club);

        if ($actorMembership === null) {
            return false;
        }

        return $actorMembership->role->canKick($target->role)
            && $target->user_id !== $user->id;
    }

    public function manageRoles(User $user, Club $club): bool
    {
        $membership = $this->membership($user, $club);

        return $membership !== null && $membership->role === ClubRole::Owner;
    }

    public function transferOwnership(User $user, Club $club, ClubMember $target): bool
    {
        if ($target->club_id !== $club->id || $target->user_id === $user->id) {
            return false;
        }

        $membership = $this->membership($user, $club);

        return $membership !== null && $membership->role->canTransferOwnership();
    }

    public function delete(User $user, Club $club): bool
    {
        $membership = $this->membership($user, $club);

        return $membership !== null && $membership->role->canDissolve();
    }

    private function membership(User $user, Club $club): ?ClubMember
    {
        if ($user->relationLoaded('clubMember')) {
            $membership = $user->clubMember;

            return $membership !== null && $membership->club_id === $club->id
                ? $membership
                : null;
        }

        return ClubMember::query()
            ->where('user_id', $user->id)
            ->where('club_id', $club->id)
            ->first();
    }
}
