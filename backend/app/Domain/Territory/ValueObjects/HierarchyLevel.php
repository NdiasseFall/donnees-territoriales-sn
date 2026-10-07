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

    /**
     * Paires (parent, enfant) autorisées — la relation "parent direct"
     * n'est PAS une simple adjacence de niveau (+1).
     *
     * Références CDC :
     * - §70 Contrôles hiérarchiques : commune → département,
     *   département → région, localité → commune, absence d'orphelin ;
     * - §39 chaîne parents : Sénégal → Thiès → Mbour → Commune de Mbour
     *   (pays → région → département → commune, sans arrondissement).
     *
     * @var list<array{int, int}>
     */
    private const DIRECT_PARENT_PAIRS = [
        [self::COUNTRY, self::REGION],             // §70 : pays → région
        [self::REGION, self::DEPARTMENT],          // §70 : département → région
        [self::DEPARTMENT, self::ARRONDISSEMENT],  // arrondissement rattaché au département
        [self::DEPARTMENT, self::COMMUNE],         // §70 + §39 : commune → département
        [self::ARRONDISSEMENT, self::COMMUNE],     // communes rurales sous arrondissement
        [self::COMMUNE, self::DISTRICT_VILLAGE],   // §70 : localité → commune
        [self::DISTRICT_VILLAGE, self::HAMLET],
    ];

    /**
     * Vrai si $this est un parent direct autorisé de $other (CDC §70/§39).
     */
    public function isDirectParentOf(HierarchyLevel $other): bool
    {
        return in_array(
            [$this->level, $other->level],
            self::DIRECT_PARENT_PAIRS,
            true
        );
    }

    public function equals(HierarchyLevel $other): bool
    {
        return $this->level === $other->level;
    }
}
