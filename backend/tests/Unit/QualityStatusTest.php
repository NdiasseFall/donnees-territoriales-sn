<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Territory\Enums\QualityStatus;
use PHPUnit\Framework\TestCase;

/**
 * US-007 — Règle métier « VALID ≠ OFFICIAL » (CDC §31).
 *
 * Cette règle est critique : une promotion automatique de statut contournerait
 * la validation institutionnelle. Ces tests la verrouillent au niveau du
 * domaine, indépendamment de toute base de données.
 */
class QualityStatusTest extends TestCase
{
    public function test_valid_and_official_are_distinct_statuses(): void
    {
        self::assertNotSame(
            QualityStatus::VALID,
            QualityStatus::OFFICIAL,
            'CDC §31 : VALID et OFFICIAL sont deux statuts distincts.'
        );
    }

    public function test_valid_status_does_not_imply_official(): void
    {
        $valid = QualityStatus::VALID;

        self::assertNotSame(
            QualityStatus::OFFICIAL,
            $valid,
            'Une géométrie techniquement valide ne doit jamais être considérée comme officielle.'
        );
    }

    public function test_all_documented_statuses_exist(): void
    {
        $expected = ['UNKNOWN', 'VALID', 'WARNING', 'INVALID', 'VERIFIED', 'OFFICIAL'];

        $actual = array_map(
            static fn (QualityStatus $case): string => $case->name,
            QualityStatus::cases()
        );

        foreach ($expected as $status) {
            self::assertContains($status, $actual, sprintf('Statut "%s" manquant (CDC §31).', $status));
        }
    }

    public function test_unknown_is_the_neutral_fallback_status(): void
    {
        // L'enum est un backed enum : le statut neutre est porté par la valeur
        // de la case UNKNOWN, aucune promotion implicite n'est possible.
        self::assertSame('UNKNOWN', QualityStatus::UNKNOWN->value);
        self::assertNotSame(
            QualityStatus::VALID,
            QualityStatus::UNKNOWN,
            'Une géométrie non contrôlée ne doit pas être VALID.'
        );
    }

    public function test_statuses_are_backed_by_strings(): void
    {
        // L'API et la base stockent le statut sous forme de texte (§27).
        foreach (QualityStatus::cases() as $case) {
            self::assertIsString($case->value);
            self::assertSame($case->name, $case->value, 'La valeur doit rester traçable.');
        }
    }
}
