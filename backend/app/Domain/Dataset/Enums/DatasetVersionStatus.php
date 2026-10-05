<?php

declare(strict_types=1);

namespace App\Domain\Dataset\Enums;

enum DatasetVersionStatus: string
{
    case DRAFT = 'draft';
    case PROCESSING = 'processing';
    case REVIEW = 'review';
    case VALIDATED = 'validated';
    case PUBLISHED = 'published';
    case DEPRECATED = 'deprecated';
    case REJECTED = 'rejected';
}
