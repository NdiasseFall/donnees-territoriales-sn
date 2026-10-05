<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Territory\ValueObjects\HierarchyLevel;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * US-004 — Hiérarchie territoriale (CDC §5.1, §22, §70).
 *
 * La hiérarchie est la structure la plus structurante du modèle : une
 * erreur ici se propage jusqu'aux URL publiques et à l'API.
 */
class HierarchyLevelTest extends TestCase
{
    public function test_country_is_level_zero(): void
    {
        $country = new HierarchyLevel(HierarchyLevel::COUNTRY);

        self::assertSame(0, $country->getLevel());
        self::assertSame('Pays', $country->getLabel());
    }

    public function test_documented_levels_have_expected_labels(): void
    {
        $expected = [
            0 => 'Pays',
            1 => 'Région',
            2 => 'Département',
            3 => 'Arrondissement',
            4 => 'Commune',
        ];

        foreach ($expected as $level => $label) {
            self::assertSame($label, (new HierarchyLevel($level))->getLabel());
        }
    }

    public function test_level_out_of_range_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HierarchyLevel(99);
    }

    public function test_negative_level_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HierarchyLevel(-1);
    }

    public function test_region_is_direct_parent_of_department(): void
    {
        $region = new HierarchyLevel(HierarchyLevel::REGION);
        $department = new HierarchyLevel(HierarchyLevel::DEPARTMENT);

        // CDC §70 : département → région.
        self::assertTrue($region->isDirectParentOf($department));
        self::assertFalse($department->isDirectParentOf($region));
    }

    public function test_department_is_direct_parent_of_commune(): void
    {
        $department = new HierarchyLevel(HierarchyLevel::DEPARTMENT);
        $commune = new HierarchyLevel(HierarchyLevel::COMMUNE);

        self::assertTrue($department->isDirectParentOf($commune));
    }

    public function test_non_adjacent_levels_are_not_direct_parents(): void
    {
        $region = new HierarchyLevel(HierarchyLevel::REGION);
        $commune = new HierarchyLevel(HierarchyLevel::COMMUNE);

        // Une région est bien ancêtre d'une commune, mais pas parent direct.
        self::assertTrue($region->isParentOf($commune));
        self::assertFalse($region->isDirectParentOf($commune));
    }

    public function test_level_never_is_parent_of_itself(): void
    {
        $commune = new HierarchyLevel(HierarchyLevel::COMMUNE);

        self::assertFalse($commune->isParentOf($commune));
        self::assertFalse($commune->isDirectParentOf($commune));
        self::assertTrue($commune->equals(new HierarchyLevel(HierarchyLevel::COMMUNE)));
    }

    public function test_country_is_ancestor_of_every_spaced_level(): void
    {
        $country = new HierarchyLevel(HierarchyLevel::COUNTRY);

        foreach ([1, 2, 3, 4, 5, 6] as $level) {
            self::assertTrue(
                $country->isParentOf(new HierarchyLevel($level)),
                sprintf('Le Pays doit être ancêtre du niveau %d.', $level)
            );
        }
    }
}
