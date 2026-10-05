<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Core;

use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Eloquent pour les types de territoires (core.territory_types).
 */
class TerritoryTypeModel extends Model
{
    protected $table = 'core.territory_types';

    public $timestamps = false;

    protected $fillable = [
        'level',
        'code',
        'name_fr',
        'name_wo',
        'description',
        'is_administrative',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_administrative' => 'boolean',
    ];
}
