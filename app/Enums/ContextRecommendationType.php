<?php

namespace App\Enums;

enum ContextRecommendationType: string
{
    case Tool = 'tool';
    case Product = 'product';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases()
        );
    }
}
