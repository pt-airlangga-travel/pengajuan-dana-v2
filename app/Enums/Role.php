<?php

namespace App\Enums;

enum Role: string
{
    case SystemAdmin = '001';
    case CreativeMember = '002';
    case OrganizerAdmin = '003';
    case InspiringManager = '004';
    case EagleTreasurer = '005';
    case FriendlyVisitor = '006';

    public function label(): string
    {
        return match ($this) {
            self::SystemAdmin => 'System Admin',
            self::CreativeMember => 'Creative Member',
            self::OrganizerAdmin => 'Organizer Admin',
            self::InspiringManager => 'Inspiring Manager',
            self::EagleTreasurer => 'Eagle Treasurer',
            self::FriendlyVisitor => 'Friendly Visitor',
        };
    }

    public static function fromDefinedId(string $id): ?self
    {
        return self::tryFrom($id);
    }
}
