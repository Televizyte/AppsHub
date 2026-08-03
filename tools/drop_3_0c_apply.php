<?php

$root = getcwd();
$path = $root . '/resources/views/filament/pages/beginner-dashboard.blade.php';

if (! file_exists($path)) {
    fwrite(STDERR, "ERROR: File not found: {$path}\nRun this script from /var/www/appshub\n");
    exit(1);
}

$contents = file_get_contents($path);
if ($contents === false) {
    fwrite(STDERR, "ERROR: Unable to read file: {$path}\n");
    exit(1);
}

$backupDir = $root . '/backups/drop-3-0c-force-hotfix-' . date('Ymd-His');
if (! is_dir($backupDir) && ! mkdir($backupDir, 0755, true)) {
    fwrite(STDERR, "ERROR: Unable to create backup directory: {$backupDir}\n");
    exit(1);
}

$backupPath = $backupDir . '/beginner-dashboard.blade.php.bak';
copy($path, $backupPath);

$original = $contents;

// 1) Add capability reader once, immediately after active app resolution.
if (strpos($contents, 'AppCapabilities::forApp($activeApp)') === false) {
    $needle = <<<'BLADE'
        if ($activeAppId > 0) {
            $activeApp = \App\Models\App::query()->find($activeAppId);
        }

        $defaultTabs = [
BLADE;

    $replacement = <<<'BLADE'
        if ($activeAppId > 0) {
            $activeApp = \App\Models\App::query()->find($activeAppId);
        }

        $capabilities = \App\Support\AppCapabilities::forApp($activeApp);
        $can = fn (string $key): bool => (bool) ($capabilities[$key] ?? false);

        $defaultTabs = [
BLADE;

    if (strpos($contents, $needle) === false) {
        fwrite(STDERR, "ERROR: Could not find active app block to insert capabilities. File may have changed.\nBackup created at: {$backupPath}\n");
        exit(1);
    }

    $contents = str_replace($needle, $replacement, $contents);
}

// 2) Replace old detected-feature visibility with saved capability visibility.
$replacements = [
    "'show' => true,\n            ],\n            [\n                'title' => 'Content Channels'," => "'show' => $can('destination_builder'),\n            ],\n            [\n                'title' => 'Content Channels',",
    "'show' => $hasContentFeature," => "'show' => $can('content_channels'),",
    "'show' => $hasWatchFeature," => "'show' => $can('watch_links'),",
    "'show' => $hasShortVideoFeature," => "'show' => $can('short_videos'),",
    "'show' => $hasBookFeature," => "'show' => $can('books'),",
    "'show' => $hasDailyScripture," => "'show' => $can('daily_scripture'),",
    "'show' => $hasDailyQuote," => "'show' => $can('daily_quote'),",
    "'show' => $mediaCount > 0," => "'show' => $can('media'),",
];

foreach ($replacements as $search => $replace) {
    $contents = str_replace($search, $replace, $contents);
}

if ($contents === $original) {
    fwrite(STDERR, "ERROR: No changes were made. The dashboard file may already be different than expected.\nBackup created at: {$backupPath}\n");
    exit(1);
}

if (file_put_contents($path, $contents) === false) {
    fwrite(STDERR, "ERROR: Unable to write updated dashboard file.\nBackup created at: {$backupPath}\n");
    exit(1);
}

echo "Drop 3.0C force hotfix applied successfully.\n";
echo "Updated file: {$path}\n";
echo "Backup saved to: {$backupPath}\n";
echo "Next: run php artisan optimize:clear\n";
