<?php

namespace App\Enums;

enum LenticularJobStatus: string
{
    case Queued = 'queued';
    case Leased = 'leased';
    case Downloading = 'downloading';
    case Processing = 'processing';
    case Uploading = 'uploading';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
