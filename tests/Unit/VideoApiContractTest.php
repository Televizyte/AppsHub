<?php

namespace Tests\Unit;

use App\Models\MediaAsset;
use App\Models\Video;
use App\Models\VideoChannel;
use App\Services\Video\VideoQueryService;
use App\Support\Video\MediaAssetPublicUrl;
use App\Support\Video\VideoPayloadSerializer;
use App\Support\Video\VideoPlaybackPayload;
use PHPUnit\Framework\TestCase;

class VideoApiContractTest extends TestCase
{
    public function test_uploaded_playback_requires_active_same_app_video_media(): void
    {
        $video = $this->video(['source_type' => 'uploaded_video', 'provider' => 'appshub', 'media_asset_id' => 9]);
        $asset = new MediaAsset([
            'app_id' => 4, 'type' => 'video', 'is_active' => true,
            'url' => 'https://cdn.example/video.mp4?X-Amz-Signature=signed&X-Amz-Expires=300',
        ]);
        $asset->id = 9;
        $video->setRelation('mediaAsset', $asset);

        $payload = VideoPlaybackPayload::forVideo($video);
        $this->assertSame([
            'source_type', 'provider', 'url', 'provider_id', 'media_asset_id', 'mime_type',
            'is_live', 'duration_seconds', 'aspect_ratio', 'embed_allowed', 'external_open_allowed',
        ], array_keys($payload));
        $this->assertSame('https://cdn.example/video.mp4?X-Amz-Signature=signed&X-Amz-Expires=300', $payload['url']);
        $this->assertSame(9, $payload['media_asset_id']);

        $asset->app_id = 5;
        $this->assertNull(VideoPlaybackPayload::forVideo($video));
        $asset->app_id = 4;
        $asset->is_active = false;
        $this->assertNull(VideoPlaybackPayload::forVideo($video));
        $asset->is_active = true;
        $asset->type = 'image';
        $this->assertNull(VideoPlaybackPayload::forVideo($video));
    }

    public function test_external_hls_youtube_and_embed_payloads_are_provider_neutral(): void
    {
        $external = VideoPlaybackPayload::forVideo($this->video([
            'source_type' => 'external_video', 'provider' => 'external',
            'external_url' => 'https://video.example/watch?id=7&token=secret',
        ]));
        $this->assertNull($external);

        $external = VideoPlaybackPayload::forVideo($this->video([
            'source_type' => 'external_video', 'provider' => 'external',
            'external_url' => 'https://video.example/watch?id=7',
        ]));
        $this->assertTrue($external['external_open_allowed']);

        $hls = VideoPlaybackPayload::forVideo($this->video([
            'source_type' => 'hls', 'provider' => 'hls',
            'external_url' => 'https://video.example/live.m3u8', 'is_live' => true,
        ]));
        $this->assertSame('hls', $hls['source_type']);
        $this->assertTrue($hls['is_live']);

        $youtube = VideoPlaybackPayload::forVideo($this->video([
            'source_type' => 'youtube_video', 'provider' => 'youtube',
            'external_url' => 'https://www.youtube.com/watch?v=abc_123',
        ]));
        $this->assertSame('abc_123', $youtube['provider_id']);
        $this->assertSame('https://www.youtube.com/watch?v=abc_123', $youtube['url']);
        $this->assertTrue($youtube['embed_allowed']);

        $embed = $this->video([
            'source_type' => 'web_embed', 'provider' => 'web',
            'external_url' => 'https://tv.example/embed',
        ]);
        $this->assertNull(VideoPlaybackPayload::forVideo($embed));
        $embed->playback_settings_json = ['embed_allowed' => true];
        $this->assertTrue(VideoPlaybackPayload::forVideo($embed)['embed_allowed']);
    }

    public function test_invalid_and_incompatible_sources_fail_closed(): void
    {
        $this->assertNull(VideoPlaybackPayload::forVideo($this->video([
            'source_type' => 'external_video', 'provider' => 'external',
            'external_url' => 'javascript:alert(1)',
        ])));
        $this->assertNull(VideoPlaybackPayload::forVideo($this->video([
            'source_type' => 'youtube_video', 'provider' => 'youtube',
            'external_url' => 'https://www.youtube.com/playlist?list=playlist123',
        ])));
        $this->assertNull(VideoPlaybackPayload::forVideo($this->video([
            'source_type' => 'youtube_video', 'provider' => 'youtube',
            'external_url' => 'https://www.youtube.com/watch?v=video123&list=playlist123',
        ])));
        $this->assertNull(VideoPlaybackPayload::forVideo($this->video([
            'source_type' => 'youtube_video', 'provider' => 'youtube',
            'provider_video_id' => 'first',
            'external_url' => 'https://www.youtube.com/watch?v=second',
        ])));
        $this->assertNull(VideoPlaybackPayload::forVideo($this->video([
            'source_type' => 'hls', 'provider' => 'hls', 'media_asset_id' => 9,
            'external_url' => 'https://video.example/live.m3u8',
        ])));
    }

    public function test_video_serializer_is_allowlisted_and_does_not_leak_storage_fields(): void
    {
        $channel = new VideoChannel(['app_id' => 4, 'slug' => 'channel', 'title' => 'Channel']);
        $channel->id = 2;
        $video = $this->video([
            'id' => 8, 'slug' => 'video', 'title' => 'Video',
            'source_type' => 'external_video', 'provider' => 'external',
            'external_url' => 'https://video.example/watch',
            'settings_json' => ['public' => ['autoplay' => false], 'internal_secret' => 'hidden'],
        ]);
        $video->setRelation('channel', $channel);
        $video->setRelation('thumbnailMediaAsset', null);

        $payload = VideoPayloadSerializer::video($video);
        $this->assertSame([
            'id', 'slug', 'title', 'description', 'channel', 'thumbnail', 'playback',
            'featured', 'sort_order', 'published_at', 'settings',
        ], array_keys($payload));

        $encoded = json_encode($payload);
        $this->assertStringNotContainsString('disk', $encoded);
        $this->assertStringNotContainsString('path', $encoded);
        $this->assertStringNotContainsString('playback_settings_json', $encoded);
        $this->assertStringNotContainsString('internal_secret', $encoded);
        $this->assertSame(['autoplay' => false], $payload['settings']);
    }

    public function test_media_url_sanitizer_preserves_public_parameters_and_rejects_credentials(): void
    {
        $this->assertSame(
            'https://www.youtube.com/playlist?list=public123',
            MediaAssetPublicUrl::sanitize('https://www.youtube.com/playlist?list=public123#fragment')
        );
        $this->assertNull(MediaAssetPublicUrl::sanitize('https://video.example/watch?token=secret'));
        $this->assertNull(MediaAssetPublicUrl::sanitize('/private/video.mp4'));
        $this->assertNull(MediaAssetPublicUrl::sanitize('/storage/video.mp4'));
        $this->assertNull(MediaAssetPublicUrl::sanitize('file:///private/video.mp4'));
        $this->assertNull(MediaAssetPublicUrl::sanitize('https://user:password@example.com/video.mp4'));
    }

    public function test_pagination_bounds_are_stable(): void
    {
        $service = new VideoQueryService();
        $this->assertSame(20, VideoQueryService::DEFAULT_PER_PAGE);
        $this->assertSame(50, VideoQueryService::MAX_PER_PAGE);
        $this->assertSame(20, $service->perPage(null));
        $this->assertSame(20, $service->perPage(0));
        $this->assertSame(50, $service->perPage(500));
        $this->assertSame(1, $service->page(null));
        $this->assertSame(1, $service->page(-5));
        $this->assertSame(3, $service->page(3));
    }

    public function test_routes_and_controller_are_app_scoped_structurally(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2) . '/routes/api.php');
        foreach (['/video-channels', '/video-playlists', '/videos'] as $route) {
            $this->assertStringContainsString("Route::get('{$route}'", $routes);
        }

        $controller = file_get_contents(dirname(__DIR__, 2) . '/app/Http/Controllers/Api/V1/VideoController.php');
        $this->assertStringContainsString('resolveActiveApp($request, $appSlug)', $controller);
        $this->assertStringContainsString("where('is_active', true)", $controller);

        $service = file_get_contents(dirname(__DIR__, 2) . '/app/Services/Video/VideoQueryService.php');
        $this->assertStringContainsString("where('app_id', \$appId)", $service);
        $this->assertStringContainsString('publiclyVisible()', $service);
        $this->assertStringContainsString("whereHas('channel'", $service);
    }

    private function video(array $attributes): Video
    {
        $video = new Video(array_merge([
            'app_id' => 4,
            'video_channel_id' => 2,
            'slug' => 'sample',
            'title' => 'Sample',
            'provider' => 'external',
            'is_live' => false,
            'is_featured' => false,
            'sort_order' => 0,
        ], $attributes));
        $video->setRelation('mediaAsset', null);

        return $video;
    }
}
