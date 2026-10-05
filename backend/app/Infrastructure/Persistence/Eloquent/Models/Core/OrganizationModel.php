<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle Eloquent pour les organisations productrices de données (core.organizations).
 */
class OrganizationModel extends Model
{
    protected $table = 'core.organizations';

    protected $fillable = [
        'uuid',
        'slug',
        'name',
        'acronym',
        'description',
        'website_url',
        'contact_email',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function sources(): HasMany
    {
        return $this->hasMany(SourceModel::class, 'organization_id');
    }
}
