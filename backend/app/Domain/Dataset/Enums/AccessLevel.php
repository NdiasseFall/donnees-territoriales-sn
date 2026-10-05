<?php

declare(strict_types=1);

namespace App\Domain\Dataset\Enums;

enum AccessLevel: string
{
    case PUBLIC = 'PUBLIC';
    case REGISTERED = 'REGISTERED';
    case RESTRICTED = 'RESTRICTED';
    case INTERNAL = 'INTERNAL';
    case CONFIDENTIAL = 'CONFIDENTIAL';
}
