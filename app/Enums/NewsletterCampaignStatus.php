<?php

namespace App\Enums;

enum NewsletterCampaignStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Sending = 'sending';
    case Sent = 'sent';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases()
        );
    }
}
