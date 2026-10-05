<?php

declare(strict_types=1);

namespace App\Domain\Territory\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object représentant un code territorial officiel (ex: SN, SN-DK, SN-TH-MB).
 */
final class TerritoryCode
{
    private string $value;

    public function __construct(string $code)
    {
        $normalized = strtoupper(trim($code));

        if (empty($normalized)) {
            throw new InvalidArgumentException('Le code territorial ne peut pas être vide.');
        }

        if (! preg_match('/^[A-Z0-9_\-]+$/', $normalized)) {
            throw new InvalidArgumentException("Format de code territorial invalide : {$code}");
        }

        $this->value = $normalized;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(TerritoryCode $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
