<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Modèle Eloquent pour les clés API (auth.api_keys).
 */
class ApiKeyModel extends Model
{
    protected $table = 'auth.api_keys';

    public $timestamps = true;

    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'token_hash',
        'token_prefix',
        'tier',
        'permissions',
        'description',
        'expires_at',
        'is_active',
        'last_used_at',
        'created_by_ip',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Authenticatable::class, 'user_id');
    }
}
