<?php

namespace App\Enums;

enum ClubRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Member = 'member';

    public function canKick(self $target): bool
    {
        return match ($this) {
            self::Owner => in_array($target, [self::Member, self::Manager], true),
            self::Manager => $target === self::Member,
            self::Member => false,
        };
    }

    public function canPromote(): bool
    {
        return $this === self::Owner;
    }

    public function canDemote(): bool
    {
        return $this === self::Owner;
    }

    public function canManageClub(): bool
    {
        return in_array($this, [self::Owner, self::Manager], true);
    }

    public function canDissolve(): bool
    {
        return $this === self::Owner;
    }

    public function canTransferOwnership(): bool
    {
        return $this === self::Owner;
    }
}
