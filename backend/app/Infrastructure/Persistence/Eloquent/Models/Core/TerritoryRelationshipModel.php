<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle Eloquent pour les relations hiérarchiques et spatiales (core.territory_relationships).
 */
class TerritoryRelationshipModel extends Model
{
    protected $table = 'core.territory_relationships';

    public $timestamps = false;

    protected $fillable = [
        'parent_id',
        'child_id',
        'relationship_type',
        'depth',
        'overlap_percentage',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'child_id' => 'integer',
        'depth' => 'integer',
        'overlap_percentage' => 'float',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TerritoryModel::class, 'parent_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(TerritoryModel::class, 'child_id');
    }
}
