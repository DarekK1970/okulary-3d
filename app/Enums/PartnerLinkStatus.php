<?php

namespace App\Enums;

enum PartnerLinkStatus: string
{
    case EmailPending = 'email_pending';
    case Pending = 'pending';
    case Active = 'active';
    case SuspendedBacklink = 'suspended_backlink';
    case SuspendedUnreachable = 'suspended_unreachable';
    case Rejected = 'rejected';
    case Banned = 'banned';
}
