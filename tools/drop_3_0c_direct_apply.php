<?php

$root = getcwd();
$file = $root . '/resources/views/filament/pages/beginner-dashboard.blade.php';

if (! file_exists($file)) {
    fwrite(STDERR, "File not found: {$file}\n");
    exit(1);
}

$backupDir = $root . '/backups/drop-3-0c-direct-' . date('Ymd-His');
if (! is_dir($backupDir)) {
    mkdir($backupDir, 0775, true);
}

$backup = $backupDir . '/beginner-dashboard.blade.php.bak';
copy($file, $backup);

$content = file_get_contents($file);

$replacements = [
    "'show' => true,\n            ],\n            [\n                'title' => 'Content Channels'," =>
    "'show' => \\App\\Support\\AppCapabilities::enabled(\$activeApp, 'destination_builder'),\n            ],\n            [\n                'title' => 'Content Channels',",

    "'show' => \$hasContentFeature,\n            ],\n            [\n                'title' => 'Watch Links'," =>
    "'show' => \\App\\Support\\AppCapabilities::enabled(\$activeApp, 'content_channels'),\n            ],\n            [\n                'title' => 'Watch Links',",

    "'show' => \$hasWatchFeature,\n            ],\n            [\n                'title' => 'Short Videos'," =>
    "'show' => \\App\\Support\\AppCapabilities::enabled(\$activeApp, 'watch_links'),\n            ],\n            [\n                'title' => 'Short Videos',",

    "'show' => \$hasShortVideoFeature,\n            ],\n            [\n                'title' => 'Books & Library'," =>
    "'show' => \\App\\Support\\AppCapabilities::enabled(\$activeApp, 'short_videos'),\n            ],\n            [\n                'title' => 'Books & Library',",

    "'show' => \$hasBookFeature,\n            ],\n            [\n                'title' => 'Daily Scripture'," =>
    "'show' => \\App\\Support\\AppCapabilities::enabled(\$activeApp, 'books'),\n            ],\n            [\n                'title' => 'Daily Scripture',",

    "'show' => \$hasDailyScripture,\n            ],\n            [\n                'title' => 'Daily Quote'," =>
    "'show' => \\App\\Support\\AppCapabilities::enabled(\$activeApp, 'daily_scripture'),\n            ],\n            [\n                'title' => 'Daily Quote',",

    "'show' => \$hasDailyQuote,\n            ],\n            [\n                'title' => 'Media Library'," =>
    "'show' => \\App\\Support\\AppCapabilities::enabled(\$activeApp, 'daily_quote'),\n            ],\n            [\n                'title' => 'Media Library',",

    "'show' => \$mediaCount > 0,\n            ]," =>
    "'show' => \\App\\Support\\AppCapabilities::enabled(\$activeApp, 'media'),\n            ],",
];

$changed = 0;

foreach ($replacements as $search => $replace) {
    if (str_contains($content, $search)) {
        $content = str_replace($search, $replace, $content);
        $changed++;
    } else {
        fwrite(STDERR, "Pattern not found:\n{$search}\n\n");
    }
}

if ($changed < count($replacements)) {
    fwrite(STDERR, "Only {$changed} of " . count($replacements) . " replacements applied. Backup kept at {$backup}\n");
    file_put_contents($file . '.failed-preview', $content);
    exit(1);
}

file_put_contents($file, $content);

echo "Drop 3.0C direct patch applied successfully.\n";
echo "Backup saved to: {$backup}\n";
