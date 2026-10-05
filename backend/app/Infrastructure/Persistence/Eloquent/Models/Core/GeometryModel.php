<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modèle Eloquent pour les géométries PostGIS (core.geometries).
 */
class GeometryModel extends Model
{
    protected $table = 'core.geometries';

    protected $fillable = [
        'uuid',
        'geometry_type',
        'srid',
        'area_km2',
        'perimeter_m',
        'is_valid',
        'validation_error',
        'created_by',
    ];

    protected $casts = [
        'srid' => 'integer',
        'area_km2' => 'float',
        'perimeter_m' => 'float',
        'is_valid' => 'boolean',
    ];

    public function territory(): HasOne
    {
        return $this->hasOne(TerritoryModel::class, 'geometry_id');
    }
}
