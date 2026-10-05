<?php

declare(strict_types=1);

namespace App\Domain\Territory\Enums;

enum TerritoryStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case PENDING = 'PENDING';
    case DISPUTED = 'DISPUTED';
    case HISTORICAL = 'HISTORICAL';
}
