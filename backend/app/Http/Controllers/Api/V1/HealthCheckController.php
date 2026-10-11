<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthCheckController extends Controller
{
    public function health(): JsonResponse
    {
        $status = 'healthy';
        $httpStatus = 200;
        $services = [];

        // Test de la connexion PostgreSQL / PostGIS
        try {
            $postgisVersion = DB::selectOne('SELECT PostGIS_Full_Version() as ver');
            $services['database'] = [
                'status' => 'up',
                'engine' => 'PostgreSQL + PostGIS',
                'postgis_version' => $postgisVersion->ver ?? 'active',
            ];
        } catch (Throwable $e) {
            // US-003 / US-022 : une base injoignable est un état critique.
            // Le healthcheck renvoie 503 (pas 200) pour que les sondes
            // d'orchestration (Docker, load balancer) détectent la panne.
            $status = 'degraded';
            $httpStatus = 503;
            $services['database'] = [
                'status' => 'down',
                'error' => $e->getMessage(),
            ];
        }

        return response()->json([
            'status' => $status,
            'app_name' => 'Plateforme Nationale de Données Territoriales du Sénégal',
            'api_version' => 'v1.0.0',
            'srid_default' => 4326,
            'timestamp' => now()->toIso8601String(),
            'services' => $services,
        ], $httpStatus);
    }
}
