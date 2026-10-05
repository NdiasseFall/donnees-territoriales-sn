<?php

declare(strict_types=1);

namespace App\Domain\Dataset\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object représentant une version sémantique de jeu de données (ex: v1.0.0 ou 2026.1).
 */
final class VersionNumber
{
    private string $version;

    public function __construct(string $version)
    {
        $normalized = trim($version);

        if (empty($normalized)) {
            throw new InvalidArgumentException('Le numéro de version ne peut pas être vide.');
        }

        $this->version = $normalized;
    }

    public function getValue(): string
    {
        return $this->version;
    }

    public function equals(VersionNumber $other): bool
    {
        return $this->version === $other->version;
    }

    public function __toString(): string
    {
        return $this->version;
    }
}
