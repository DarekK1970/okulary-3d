<?php

namespace App\Enums;

enum FalAiJobOperation: string
{
    case ImageToVideo = 'image_to_video';
    case VideoUpscale = 'video_upscale';
}
