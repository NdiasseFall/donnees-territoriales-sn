<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Audit;

use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Eloquent pour la table immuable de traçabilité des actions (audit.logs).
 */
class AuditLogModel extends Model
{
    protected $table = 'audit.logs';

    public $timestamps = false;

    protected $fillable = [
        'action',
        'target_schema',
        'target_table',
        'target_id',
        'target_uuid',
        'actor_id',
        'actor_type',
        'ip_address',
        'user_agent',
        'details',
        'performed_at',
    ];

    protected $casts = [
        'target_id' => 'integer',
        'details' => 'array',
        'performed_at' => 'datetime',
    ];
}
