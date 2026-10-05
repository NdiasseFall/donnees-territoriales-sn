<?php

declare(strict_types=1);

namespace App\Domain\Auth\Enums;

/**
 * Permissions granulaires par clé API.
 */
enum ApiKeyPermission: string
{
    // Territoires
    case TERRITORIES_READ = 'territories.read';
    case TERRITORIES_WRITE = 'territories.write';

    // Recherche
    case SEARCH_READ = 'search.read';

    // Données brutes
    case DATASETS_READ = 'datasets.read';
    case DATASETS_WRITE = 'datasets.write';

    // Admin / Audit
    case AUDIT_READ = 'audit.read';
    case ADMINISTER = 'administer';
}
