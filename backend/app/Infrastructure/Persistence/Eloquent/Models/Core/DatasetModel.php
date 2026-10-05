<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle Eloquent pour les jeux de données (core.datasets).
 */
class DatasetModel extends Model
{
    protected $table = 'core.datasets';

    protected $fillable = [
        'uuid',
        'source_id',
        'slug',
        'name',
        'description',
        'category',
        'tags',
        'access_level',
        'license',
        'metadata',
        'is_official',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'tags' => 'array',
        'metadata' => 'array',
        'is_official' => 'boolean',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(SourceModel::class, 'source_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DatasetVersionModel::class, 'dataset_id');
    }

    public function latestVersion(): HasMany
    {
        return $this->hasMany(DatasetVersionModel::class, 'dataset_id')
            ->where('status', 'published')
            ->latest('published_at');
    }
}
