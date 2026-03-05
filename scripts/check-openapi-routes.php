<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$swaggerPath = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'swagger.yaml';

if (!is_file($swaggerPath)) {
    fwrite(STDERR, "Missing docs/swagger.yaml" . PHP_EOL);
    exit(1);
}

$swagger = (string) file_get_contents($swaggerPath);
$expected = [
    '/api/health',
    '/api/csrf',
    '/api/auth/login',
    '/api/auth/register',
    '/api/auth/verify-email',
    '/api/auth/me',
    '/api/auth/refresh',
    '/api/auth/logout',
    '/api/auth/logout-all',
    '/api/admin/users',
    '/api/admin/users/export',
    '/api/admin/users/register',
    '/api/admin/users/{id}',
    '/api/admin/users/me',
    '/api/admin/audit-logs',
    '/api/admin/audit-logs/export',
    '/api/admin/audit-logs/{id}',
    '/api/public/health',
    '/api/public/users/me',
];

$missing = [];
foreach ($expected as $path) {
    if (!str_contains($swagger, $path . ':')) {
        $missing[] = $path;
    }
}

if ($missing !== []) {
    fwrite(STDERR, 'Missing OpenAPI paths: ' . implode(', ', $missing) . PHP_EOL);
    exit(1);
}

echo 'OpenAPI contract check passed' . PHP_EOL;