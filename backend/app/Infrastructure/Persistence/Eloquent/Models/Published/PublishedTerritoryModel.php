<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Published;

use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Eloquent pour la table dénormalisée optimisée pour la diffusion publique (published.territories).
 */
class PublishedTerritoryModel extends Model
{
    protected $table = 'published.territories';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'uuid',
        'code',
        'name',
        'ansd_code',
        'level',
        'level_label',
        'parent_id',
        'parent_code',
        'parent_name',
        'region_code',
        'region_name',
        'department_code',
        'department_name',
        'area_km2',
        'perimeter_m',
        'population',
        'population_year',
        'capital',
        'status',
        'quality_status',
        'metadata',
        'dataset_version_id',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'id' => 'integer',
        'level' => 'integer',
        'parent_id' => 'integer',
        'area_km2' => 'float',
        'perimeter_m' => 'float',
        'population' => 'integer',
        'population_year' => 'integer',
        'metadata' => 'array',
        'dataset_version_id' => 'integer',
        'published_at' => 'datetime',
    ];
}
