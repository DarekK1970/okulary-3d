<?php

namespace App\Enums;

enum NewsletterDeliveryStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
