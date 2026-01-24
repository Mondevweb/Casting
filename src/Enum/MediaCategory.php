<?php

namespace App\Enum;

enum MediaCategory: string
{
    case PHOTO = 'PHOTO';
    case CV = 'CV';
    case VIDEO_SCENE = 'VIDEO_SCENE';
    case VIDEO_DEMO = 'VIDEO_DEMO';
    case VIDEO_PRES = 'VIDEO_PRES';
}