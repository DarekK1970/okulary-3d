<?php

namespace App\Enums;

enum OrchestratorPlanStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Completed = 'completed';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases()
        );
    }
}
