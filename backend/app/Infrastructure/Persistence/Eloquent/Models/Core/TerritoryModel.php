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
        'parent_id',
        'territory_type_id',
        'geometry_id',
        'source_version_id',
        'code',
        'code_ansd',
        'code_anat',
        'name',
        'official_name',
        'slug',
        'level',
        'status',
        'country_code',
        'area_sqkm',
        'population_census',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'level' => 'integer',
        'territory_type_id' => 'integer',
        'parent_id' => 'integer',
        'geometry_id' => 'integer',
        'source_version_id' => 'integer',
        'population_census' => 'integer',
        'area_sqkm' => 'float',
    ];

    public function territoryType(): BelongsTo
    {
        return $this->belongsTo(TerritoryTypeModel::class, 'territory_type_id');
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
