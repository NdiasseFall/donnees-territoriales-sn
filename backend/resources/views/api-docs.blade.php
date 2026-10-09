<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Documentation OpenAPI 3.1 de l'API de la Plateforme Nationale de Données Territoriales du Sénégal (CDC §61).">
    <title>Documentation API — Plateforme Nationale de Données Territoriales du Sénégal</title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            background: #ffffff;
        }
    </style>
</head>
<body>
    {{--
        Documentation interactive Redoc (lecture anonyme, US-013).
        Le contrat OpenAPI servi par route('api.docs.spec') est la source de
        vérité unique, validée par @redocly/cli dans la CI (job « lint »).
    --}}
    <redoc spec-url="{{ $specUrl }}"></redoc>
    <script src="https://cdn.jsdelivr.net/npm/redoc@2.4.0/bundles/redoc.standalone.js"></script>
</body>
</html>