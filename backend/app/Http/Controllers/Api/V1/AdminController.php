<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Endpoints d'administration (RBAC §55).
 *
 * Toutes ces routes exigent une clé API active portant la permission
 * `administer` (cf. middleware `api.key.permission:administer`). Un utilisateur
 * public ou développeur reçoit 403 — jamais un accès partiel.
 */
class AdminController extends Controller
{
    /**
     * Synthèse d'audit (exemple de ressource administrée).
     *
     * Protège l'accès aux données d'exploitation : seules les clés habilitées
     * (permission `administer`) peuvent lire cette vue agrégée (§55).
     */
    public function auditSummary(Request $request): JsonResponse
    {
        // Le total d'entrées d'audit, tolérant si le schéma est absent.
        $totalEvents = 0;
        try {
            $row = DB::selectOne('SELECT COUNT(*)::bigint AS total FROM audit.logs');
            $totalEvents = (int) ($row->total ?? 0);
        } catch (\Throwable) {
            $totalEvents = 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'audit_events_total' => $totalEvents,
                'granted_by_permission' => 'administer',
            ],
        ]);
    }
}
