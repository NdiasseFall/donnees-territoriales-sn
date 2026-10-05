<?php

declare(strict_types=1);

namespace App\Domain\Territory\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object représentant le niveau hiérarchique d'une entité territoriale au Sénégal.
 * Niveau 0 : Pays (Sénégal)
 * Niveau 1 : Région (14)
 * Niveau 2 : Département (46)
 * Niveau 3 : Arrondissement
 * Niveau 4 : Commune (557+)
 * Niveau 5 : Quartier / Village
 * Niveau 6 : Hameau / Lieu-dit
 */
final class HierarchyLevel
{
    public const COUNTRY = 0;

    public const REGION = 1;

    public const DEPARTMENT = 2;

    public const ARRONDISSEMENT = 3;

    public const COMMUNE = 4;

    public const DISTRICT_VILLAGE = 5;

    public const HAMLET = 6;

    private const LABELS = [
        self::COUNTRY => 'Pays',
        self::REGION => 'Région',
        self::DEPARTMENT => 'Département',
        self::ARRONDISSEMENT => 'Arrondissement',
        self::COMMUNE => 'Commune',
        self::DISTRICT_VILLAGE => 'Quartier / Village',
        self::HAMLET => 'Hameau',
    ];

    public function __construct(private readonly int $level)
    {
        if ($this->level < self::COUNTRY || $this->level > self::HAMLET) {
            throw new InvalidArgumentException("Niveau hiérarchique invalide : {$this->level}. Valeurs autorisées de 0 à 6.");
        }
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getLabel(): string
    {
        return self::LABELS[$this->level] ?? 'Inconnu';
    }

    public function isParentOf(HierarchyLevel $other): bool
    {
        return $this->level < $other->level;
    }

    public function isDirectParentOf(HierarchyLevel $other): bool
    {
        return ($this->level + 1) === $other->level;
    }

    public function equals(HierarchyLevel $other): bool
    {
        return $this->level === $other->level;
    }
}
