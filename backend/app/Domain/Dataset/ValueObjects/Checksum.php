<?php

declare(strict_types=1);

namespace App\Domain\Dataset\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object représentant une empreinte cryptographique SHA-256 d'un fichier de données.
 */
final class Checksum
{
    private string $hash;

    public function __construct(string $hash)
    {
        $clean = strtolower(trim($hash));

        if (! preg_match('/^[a-f0-9]{64}$/', $clean)) {
            throw new InvalidArgumentException("Empreinte SHA-256 invalide : {$hash}");
        }

        $this->hash = $clean;
    }

    public static function fromFile(string $filePath): self
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("Fichier introuvable pour le calcul du checksum : {$filePath}");
        }

        $hash = hash_file('sha256', $filePath);
        if ($hash === false) {
            throw new InvalidArgumentException("Impossible de calculer l'empreinte SHA-256 du fichier.");
        }

        return new self($hash);
    }

    public function getValue(): string
    {
        return $this->hash;
    }

    public function equals(Checksum $other): bool
    {
        return hash_equals($this->hash, $other->hash);
    }

    public function __toString(): string
    {
        return $this->hash;
    }
}
