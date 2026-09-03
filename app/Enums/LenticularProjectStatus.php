<?php

namespace App\Enums;

enum LenticularProjectStatus: string
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
}
