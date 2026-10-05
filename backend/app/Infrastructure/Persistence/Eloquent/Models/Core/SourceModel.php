<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle Eloquent pour les sources de données (core.sources).
 */
class SourceModel extends Model
{
    protected $table = 'core.sources';

    protected $fillable = [
        'uuid',
        'organization_id',
        'name',
        'slug',
        'description',
        'source_type',
        'url',
        'license',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'organization_id' => 'integer',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function datasets(): HasMany
    {
        return $this->hasMany(DatasetModel::class, 'source_id');
    }
}
