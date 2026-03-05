<?php

declare(strict_types=1);

$allowedExtensions = ['php', 'yaml', 'yml', 'xml', 'md', 'env', 'dist'];
$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$errors = [];
$ignoredDirectories = ['.git', 'vendor', 'var', 'node_modules', 'dist'];

foreach ($iterator as $file) {
    $path = (string) $file;
    foreach ($ignoredDirectories as $ignoredDirectory) {
        if (str_contains($path, DIRECTORY_SEPARATOR . $ignoredDirectory . DIRECTORY_SEPARATOR)) {
            continue 2;
        }
    }

    $extension = pathinfo($path, PATHINFO_EXTENSION);
    if ($extension !== '' && !in_array($extension, $allowedExtensions, true)) {
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = "Cannot read file: {$path}";
        continue;
    }

    if (!mb_check_encoding($content, 'UTF-8')) {
        $errors[] = "Invalid UTF-8: {$path}";
    }

    if (str_starts_with($content, "\xEF\xBB\xBF")) {
        $errors[] = "BOM detected: {$path}";
    }
}

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error . PHP_EOL);
    }
    exit(1);
}

echo "Encoding check passed" . PHP_EOL;
