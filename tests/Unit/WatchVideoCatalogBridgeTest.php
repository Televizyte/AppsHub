<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WatchVideoCatalogBridgeTest extends TestCase
{
    private function projectFile(string $relativePath): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    public function test_watch_exposes_video_catalog_discovery_contract(): void
    {
        $source = file_get_contents(
            $this->projectFile('app/Http/Controllers/Api/V1/WatchController.php')
        );

        $this->assertIsString($source);

        foreach ([
            "'video_catalog'",
            "'contract_version' => '1.0'",
            "'source' => 'video_engine'",
            "'legacy_watch_compatibility' => true",
            '/video-channels',
            '/video-playlists',
            '/videos',
        ] as $expected) {
            $this->assertStringContainsString($expected, $source);
        }
    }

    public function test_legacy_watch_contract_remains_available(): void
    {
        $source = file_get_contents(
            $this->projectFile('app/Http/Controllers/Api/V1/WatchController.php')
        );

        $this->assertIsString($source);

        foreach ([
            "'primary_stream_url'",
            "'live_hls'",
            "'live_youtube'",
            "'commanding_day'",
            "'videos'",
            "'other_channels'",
            "'groups'",
            "'items'",
        ] as $expected) {
            $this->assertStringContainsString($expected, $source);
        }
    }

    public function test_canonical_video_api_routes_remain_registered_in_route_source(): void
    {
        $routes = file_get_contents(
            $this->projectFile('routes/api.php')
        );

        $this->assertIsString($routes);

        foreach ([
            '/video-channels',
            '/video-playlists',
            '/videos',
        ] as $expected) {
            $this->assertStringContainsString($expected, $routes);
        }
    }
}
