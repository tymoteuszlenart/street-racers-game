<?php

namespace App\Services;

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClubMembershipService
{
    public function join(User $user, Club $club): ClubMember
    {
        if (ClubMember::query()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'club' => 'You are already in a club.',
            ]);
        }

        $club->loadCount('members');

        if ($club->isFull()) {
            throw ValidationException::withMessages([
                'club' => 'This club is full.',
            ]);
        }

        return ClubMember::query()->create([
            'club_id' => $club->id,
            'user_id' => $user->id,
            'role' => ClubRole::Member,
            'joined_at' => now(),
        ]);
    }

    public function leave(User $user): void
    {
        $membership = ClubMember::query()->where('user_id', $user->id)->firstOrFail();

        if ($membership->role === ClubRole::Owner) {
            throw ValidationException::withMessages([
                'club' => 'Transfer ownership or dissolve the club before leaving.',
            ]);
        }

        $membership->delete();
    }

    public function kick(User $actor, ClubMember $target): void
    {
        $actorMembership = $this->membershipFor($actor, $target->club_id);

        if (! $actorMembership->role->canKick($target->role)) {
            throw ValidationException::withMessages([
                'member' => 'You cannot remove this member.',
            ]);
        }

        if ($target->user_id === $actor->id) {
            throw ValidationException::withMessages([
                'member' => 'You cannot remove yourself. Use leave instead.',
            ]);
        }

        $target->delete();
    }

    public function promote(User $actor, ClubMember $target): ClubMember
    {
        $actorMembership = $this->membershipFor($actor, $target->club_id);

        if (! $actorMembership->role->canPromote()) {
            throw ValidationException::withMessages([
                'member' => 'You cannot promote members.',
            ]);
        }

        if ($target->role !== ClubRole::Member) {
            throw ValidationException::withMessages([
                'member' => 'Only members can be promoted to manager.',
            ]);
        }

        $target->update(['role' => ClubRole::Manager]);

        return $target->fresh();
    }

    public function demote(User $actor, ClubMember $target): ClubMember
    {
        $actorMembership = $this->membershipFor($actor, $target->club_id);

        if (! $actorMembership->role->canDemote()) {
            throw ValidationException::withMessages([
                'member' => 'You cannot demote managers.',
            ]);
        }

        if ($target->role !== ClubRole::Manager) {
            throw ValidationException::withMessages([
                'member' => 'Only managers can be demoted.',
            ]);
        }

        $target->update(['role' => ClubRole::Member]);

        return $target->fresh();
    }

    public function transferOwnership(User $actor, ClubMember $target): void
    {
        $actorMembership = $this->membershipFor($actor, $target->club_id);

        if (! $actorMembership->role->canTransferOwnership()) {
            throw ValidationException::withMessages([
                'member' => 'Only the owner can transfer ownership.',
            ]);
        }

        if ($target->user_id === $actor->id) {
            throw ValidationException::withMessages([
                'member' => 'You already own this club.',
            ]);
        }

        if ($target->role === ClubRole::Owner) {
            throw ValidationException::withMessages([
                'member' => 'This member is already the owner.',
            ]);
        }

        DB::transaction(function () use ($actorMembership, $target) {
            $actorMembership->update(['role' => ClubRole::Manager]);
            $target->update(['role' => ClubRole::Owner]);
        });
    }

    private function membershipFor(User $user, int $clubId): ClubMember
    {
        return ClubMember::query()
            ->where('user_id', $user->id)
            ->where('club_id', $clubId)
            ->firstOrFail();
    }
}
