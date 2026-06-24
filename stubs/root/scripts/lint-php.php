<?php

$root = realpath(__DIR__.'/..');

if ($root === false) {
    fwrite(STDERR, "Cannot resolve project root.\n");
    exit(1);
}

$skip = [
    $root.'/vendor',
    $root.'/node_modules',
    $root.'/storage/framework/cache',
    $root.'/storage/framework/sessions',
    $root.'/storage/framework/views',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();

    foreach ($skip as $prefix) {
        if (str_starts_with($path, $prefix.'/') || $path === $prefix) {
            continue 2;
        }
    }

    passthru('php -l '.escapeshellarg($path), $code);

    if ($code !== 0) {
        exit($code);
    }
}
