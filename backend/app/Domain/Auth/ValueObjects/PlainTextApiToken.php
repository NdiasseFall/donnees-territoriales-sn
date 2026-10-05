<?php

declare(strict_types=1);

namespace App\Domain\Auth\ValueObjects;

/**
 * Value Object représentant un token brut (non haché) généré pour le client.
 * Ne doit JAMAIS être persisté — uniquement retourné une seule fois lors de la création.
 */
final class PlainTextApiToken
{
    private string $token;

    public function __construct(?string $token = null)
    {
        if ($token !== null) {
            $this->token = $token;
        } else {
            $this->token = bin2hex(random_bytes(32));
        }
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getHashed(): string
    {
        return hash('sha256', $this->token);
    }

    public function getPrefix(): string
    {
        return 'tnp_'.substr($this->token, 0, 12);
    }

    public static function isValidFormat(string $token): bool
    {
        return preg_match('/^tnp_[a-f0-9]{12}$/', $token) === 1;
    }
}
