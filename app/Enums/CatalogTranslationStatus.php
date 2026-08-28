<?php

namespace App\Enums;

enum CatalogTranslationStatus: string
{
    case Source = 'source';
    case Draft = 'draft';
    case Review = 'review';
    case Ready = 'ready';

    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases()
        );
    }

    public static function publicValues(): array
    {
        return [self::Source->value, self::Ready->value];
    }
}
