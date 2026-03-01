<?php

namespace App\Enum;

enum TranscodingStatus: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';
    case NOT_APPLICABLE = 'NOT_APPLICABLE'; // Pour les images et PDF
}
