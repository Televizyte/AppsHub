<?php

$root = getcwd();
$backupDir = $root . '/backups/drop-3-0d-sidebar-' . date('Ymd-His');

if (! is_dir($backupDir)) {
    mkdir($backupDir, 0775, true);
}

$targets = [
    'app/Filament/Pages/DestinationBuilder.php' => 'destination_builder',
    'app/Filament/Pages/ContentChannels.php' => 'content_channels',
    'app/Filament/Pages/AnalyticsDashboard.php' => 'analytics',
    'app/Filament/Pages/ModerationDashboard.php' => 'moderation',

    'app/Filament/Resources/ContentPostResource.php' => 'content_channels',
    'app/Filament/Resources/WatchLinkResource.php' => 'watch_links',
    'app/Filament/Resources/MediaAssetResource.php' => 'media',
    'app/Filament/Resources/IconPresetResource.php' => 'media',
    'app/Filament/Resources/PushNotificationResource.php' => 'notifications',
    'app/Filament/Resources/AdProfileResource.php' => 'ads',
    'app/Filament/Resources/AdRuleResource.php' => 'ads',
];

function methodForCapability(string $capability): string
{
    return <<<PHP_METHOD

    public static function shouldRegisterNavigation(): bool
    {
        if (! \\App\\Support\\AdminMode::isBeginner()) {
            return true;
        }

        \$activeAppId = (int) (\\App\\Support\\ActiveApp::ensureId() ?? 0);

        \$activeApp = \$activeAppId > 0
            ? \\App\\Models\\App::query()->find(\$activeAppId)
            : null;

        return \\App\\Support\\AppCapabilities::enabled(\$activeApp, '{$capability}');
    }

PHP_METHOD;
}

function patchNavigationMethod(string $content, string $capability): array
{
    $method = methodForCapability($capability);

    $pattern = '/\n\s*public\s+static\s+function\s+shouldRegisterNavigation\s*\(\)\s*:\s*bool\s*\{.*?\n\s*\}/s';

    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, $method, $content, 1);
        return [$content, 'replaced'];
    }

    $insertPattern = '/(protected\s+static\s+\?int\s+\$navigationSort\s*=\s*[^;]+;\s*)/';

    if (preg_match($insertPattern, $content)) {
        $content = preg_replace($insertPattern, '$1' . $method, $content, 1);
        return [$content, 'inserted'];
    }

    return [$content, 'failed'];
}

$results = [];

foreach ($targets as $relativePath => $capability) {
    $file = $root . '/' . $relativePath;

    if (! file_exists($file)) {
        $results[] = "MISSING: {$relativePath}";
        continue;
    }

    $backupPath = $backupDir . '/' . str_replace(['/', '\\'], '__', $relativePath) . '.bak';
    copy($file, $backupPath);

    $content = file_get_contents($file);
    [$patched, $status] = patchNavigationMethod($content, $capability);

    if ($status === 'failed') {
        $results[] = "FAILED: {$relativePath} ({$capability})";
        continue;
    }

    file_put_contents($file, $patched);
    $results[] = strtoupper($status) . ": {$relativePath} => {$capability}";
}

echo "Drop 3.0D sidebar capability patch completed.\n";
echo "Backup folder: {$backupDir}\n\n";

foreach ($results as $result) {
    echo "- {$result}\n";
}

