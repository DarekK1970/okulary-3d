<?php

namespace App\Enums;

enum DiscoveryDecision: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $decision): string => $decision->value,
            self::cases()
        );
    }
}
