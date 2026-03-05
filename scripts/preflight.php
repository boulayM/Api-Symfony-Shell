<?php

declare(strict_types=1);

$mode = $argv[1] ?? 'dev';
$root = dirname(__DIR__);
$envFile = $mode === 'test' ? '.env.test' : '.env';
$envPath = $root . DIRECTORY_SEPARATOR . $envFile;

if (!is_file($envPath)) {
    fwrite(STDERR, "Missing env file: {$envFile}" . PHP_EOL);
    exit(1);
}

$content = (string) file_get_contents($envPath);
if (!preg_match('/^DATABASE_URL="?([^"\r\n]+)"?/m', $content, $matches)) {
    fwrite(STDERR, 'DATABASE_URL not found' . PHP_EOL);
    exit(1);
}

$databaseUrl = $matches[1];
if ($mode === 'test' && !str_contains($databaseUrl, 'test')) {
    fwrite(STDERR, 'Unsafe test database URL: expected name containing "test"' . PHP_EOL);
    exit(1);
}

echo "Preflight {$mode} OK" . PHP_EOL;