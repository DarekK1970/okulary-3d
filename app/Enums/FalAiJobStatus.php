<?php

namespace App\Enums;

enum FalAiJobStatus: string
{
    case Queued = 'queued';
    case Submitted = 'submitted';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Succeeded, self::Failed, self::Cancelled], true);
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Queued => in_array($status, [self::Submitted, self::Failed, self::Cancelled], true),
            self::Submitted => in_array($status, [self::Processing, self::Succeeded, self::Failed, self::Cancelled], true),
            self::Processing => in_array($status, [self::Succeeded, self::Failed, self::Cancelled], true),
            self::Succeeded, self::Failed, self::Cancelled => false,
        };
    }
}
