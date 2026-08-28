<?php

namespace App\Enums;

enum ArticleTranslationStatus: string
{
    case Source = 'source';
    case Draft = 'draft';
    case Review = 'review';
    case Ready = 'ready';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases()
        );
    }

    /**
     * @return list<string>
     */
    public static function publicValues(): array
    {
        return [
            self::Source->value,
            self::Ready->value,
        ];
    }
}
