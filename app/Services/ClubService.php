<?php

namespace App\Services;

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClubService
{
    public function create(User $owner, string $name, ?string $description = null): Club
    {
        $name = trim($name);

        if (ClubMember::query()->where('user_id', $owner->id)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'You are already in a club.',
            ]);
        }

        if ($this->nameExists($name)) {
            throw ValidationException::withMessages([
                'name' => 'A club with this name already exists.',
            ]);
        }

        return DB::transaction(function () use ($owner, $name, $description) {
            $club = Club::query()->create([
                'name' => $name,
                'slug' => $this->generateUniqueSlug($name),
                'description' => $description !== null && $description !== '' ? trim($description) : null,
            ]);

            ClubMember::query()->create([
                'club_id' => $club->id,
                'user_id' => $owner->id,
                'role' => ClubRole::Owner,
                'joined_at' => now(),
            ]);

            return $club;
        });
    }

    public function update(Club $club, string $name, ?string $description = null): Club
    {
        $name = trim($name);

        if ($this->nameExists($name, $club->id)) {
            throw ValidationException::withMessages([
                'name' => 'A club with this name already exists.',
            ]);
        }

        $club->update([
            'name' => $name,
            'description' => $description !== null && $description !== '' ? trim($description) : null,
        ]);

        return $club->fresh();
    }

    public function dissolve(Club $club): void
    {
        DB::transaction(function () use ($club) {
            $club->members()->delete();
            $club->delete();
        });
    }

    public function nameExists(string $name, ?int $exceptClubId = null): bool
    {
        $query = Club::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);

        if ($exceptClubId !== null) {
            $query->where('id', '!=', $exceptClubId);
        }

        return $query->exists();
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        if ($baseSlug === '') {
            $baseSlug = 'club';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (Club::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
