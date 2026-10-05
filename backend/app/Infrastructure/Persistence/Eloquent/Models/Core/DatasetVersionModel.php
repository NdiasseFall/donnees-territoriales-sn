<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle Eloquent pour les versions de jeux de données (core.dataset_versions).
 */
class DatasetVersionModel extends Model
{
    protected $table = 'core.dataset_versions';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'dataset_id',
        'version_number',
        'description',
        'status',
        'feature_count',
        'checksum_sha256',
        'file_path',
        'file_size_bytes',
        'validation_summary',
        'created_by',
        'published_by',
        'published_at',
        'created_at',
    ];

    protected $casts = [
        'dataset_id' => 'integer',
        'feature_count' => 'integer',
        'file_size_bytes' => 'integer',
        'validation_summary' => 'array',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(DatasetModel::class, 'dataset_id');
    }
}
