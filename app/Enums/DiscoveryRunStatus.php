<?php

namespace App\Enums;

enum DiscoveryRunStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
