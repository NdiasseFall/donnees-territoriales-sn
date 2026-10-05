<?php

declare(strict_types=1);

namespace App\Domain\Territory\Enums;

enum QualityStatus: string
{
    case UNKNOWN = 'UNKNOWN';
    case VALID = 'VALID';
    case WARNING = 'WARNING';
    case INVALID = 'INVALID';
    case VERIFIED = 'VERIFIED';
    case OFFICIAL = 'OFFICIAL';
}
