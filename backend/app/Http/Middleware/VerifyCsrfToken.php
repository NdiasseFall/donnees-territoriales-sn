<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

/**
 * Protection CSRF — étendue de base. Les routes API (/api/*) ne sont pas
 * soumises au CSRF (elles s'authentifient par clé API / Sanctum token).
 */
class VerifyCsrfToken extends BaseVerifier
{
    /**
     * URIs exemptées de la vérification CSRF.
     *
     * @var list<string>
     */
    protected $except = [
        'api/*',
    ];
}
