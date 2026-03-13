<?php

// Configuration
$rootPath = realpath(__DIR__.'/../');
$ignoreDirs = [
    '.git',
    'vendor',
    'node_modules',
    'storage',
    'public/build',
    'archive',
    '.idea',
    '.vscode',
    'reports',
];

// Binary extensions to skip line counting
$binaryExtensions = [
    'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg',
    'zip', 'phar', 'woff', 'woff2', 'ttf', 'eot',
    'mp3', 'mp4', 'pdf', 'exe', 'dll', 'so',
];

$stats = [];
$totalFiles = 0;
$totalLines = 0;

echo "Scanning project at: $rootPath\n";
echo 'Ignoring: '.implode(', ', $ignoreDirs)."\n\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isDir()) {
        continue;
    }

    $path = $file->getPathname();
    $relativePath = ltrim(str_replace($rootPath, '', $path), '/\\');

    // Check ignored dirs
    $ignored = false;
    foreach ($ignoreDirs as $ignore) {
        // Check if path starts with ignored dir or contains it
        if (strpos($relativePath, $ignore.DIRECTORY_SEPARATOR) === 0 ||
            $relativePath === $ignore ||
            strpos($relativePath, DIRECTORY_SEPARATOR.$ignore.DIRECTORY_SEPARATOR) !== false) {
            $ignored = true;
            break;
        }
    }
    if ($ignored) {
        continue;
    }

    // Determine extension
    $filename = $file->getFilename();
    $ext = pathinfo($filename, PATHINFO_EXTENSION);

    // Special handling for blade files
    if (str_ends_with($filename, '.blade.php')) {
        $ext = 'blade.php';
    } elseif ($ext === '') {
        $ext = '(no extension)';
    }

    if (! isset($stats[$ext])) {
        $stats[$ext] = ['files' => 0, 'lines' => 0];
    }

    $stats[$ext]['files']++;
    $totalFiles++;

    // Count lines for non-binary files
    if (! in_array(strtolower($ext), $binaryExtensions) && is_readable($path)) {
        // Use a generator-like approach or simple file() depending on memory needs.
        // For stats, file() is usually fine unless files are massive.
        // Using exec wc -l is faster on linux but less portable. Let's stick to PHP.
        $lines = 0;
        $handle = fopen($path, 'r');
        if ($handle) {
            while (! feof($handle)) {
                $line = fgets($handle);
                if ($line !== false) {
                    $lines++;
                }
            }
            fclose($handle);
        }

        $stats[$ext]['lines'] += $lines;
        $totalLines += $lines;
    }
}

// Sort by line count desc
uasort($stats, function ($a, $b) {
    return $b['lines'] <=> $a['lines'];
});

// Output
$mask = "%-15s %-10s %-15s\n";
echo sprintf($mask, 'Type', 'Files', 'Lines of Code');
echo str_repeat('-', 40)."\n";

foreach ($stats as $ext => $data) {
    echo sprintf($mask, $ext, number_format($data['files']), number_format($data['lines']));
}

echo str_repeat('-', 40)."\n";
echo sprintf($mask, 'TOTAL', number_format($totalFiles), number_format($totalLines));
echo "\n";
