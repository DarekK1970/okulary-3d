<?php

namespace App\Enums;

enum OrchestratorItemStatus: string
{
    case Planned = 'planned';
    case DraftCreated = 'draft_created';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases()
        );
    }
}
