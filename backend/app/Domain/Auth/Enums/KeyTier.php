<?php

declare(strict_types=1);

namespace App\Domain\Auth\Enums;

/**
 * Tiers de rate limiting pour les clés API.
 * Public: 60 req/min
 * Developer: 600 req/min
 * Admin: unlimited (handled by Laravel)
 */
enum KeyTier: string
{
    case PUBLIC = 'public';
    case DEVELOPER = 'developer';
    case ADMIN = 'admin';

    public function getRateLimit(): int
    {
        return match ($this) {
            self::PUBLIC => 60,
            self::DEVELOPER => 600,
            self::ADMIN => 0, // 0 = unlimited
        };
    }

    public function getRateLimitPrefix(): string
    {
        return match ($this) {
            self::PUBLIC => 'api.public.',
            self::DEVELOPER => 'api.developer.',
            self::ADMIN => 'api.admin.',
        };
    }
}
