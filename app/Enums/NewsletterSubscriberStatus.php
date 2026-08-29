<?php

namespace App\Enums;

enum NewsletterSubscriberStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Unsubscribed = 'unsubscribed';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases()
        );
    }
}
