<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle Eloquent pour les entités territoriales (core.territories).
 */
class TerritoryModel extends Model
{
    protected $table = 'core.territories';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'normalized_name',
        'ansd_code',
        'iso_code',
        'fips_code',
        'type_id',
        'parent_id',
        'geometry_id',
        'population',
        'population_year',
        'capital',
        'status',
        'quality_status',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'population' => 'integer',
        'population_year' => 'integer',
        'type_id' => 'integer',
        'parent_id' => 'integer',
        'geometry_id' => 'integer',
    ];

    public function territoryType(): BelongsTo
    {
        return $this->belongsTo(TerritoryTypeModel::class, 'type_id');
    }

    public function geometry(): BelongsTo
    {
        return $this->belongsTo(GeometryModel::class, 'geometry_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TerritoryModel::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(TerritoryModel::class, 'parent_id');
    }
}
