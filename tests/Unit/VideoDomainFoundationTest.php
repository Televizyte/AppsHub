<?php

namespace Tests\Unit;

use App\Models\MediaAsset;
use App\Models\Video;
use App\Models\VideoChannel;
use App\Models\VideoPlaylist;
use App\Models\VideoPlaylistItem;
use App\Support\Video\VideoDomainGuard;
use App\Support\Video\VideoSourceContract;
use PHPUnit\Framework\TestCase;

class VideoDomainFoundationTest extends TestCase
{
    public function test_source_types_are_canonical_and_exclude_youtube_playlists(): void
    {
        $this->assertSame([
            'uploaded_video', 'external_video', 'hls', 'youtube_video', 'web_embed',
        ], VideoSourceContract::sourceTypes());
        $this->assertFalse(VideoSourceContract::isValidSourceType('youtube_playlist'));
    }

    public function test_source_requirements_fail_closed(): void
    {
        $this->assertFalse(VideoSourceContract::isValid(['source_type' => 'uploaded_video']));
        $this->assertTrue(VideoSourceContract::isValid(['source_type' => 'uploaded_video', 'media_asset_id' => 8]));

        foreach (['external_video', 'hls', 'web_embed'] as $sourceType) {
            $this->assertFalse(VideoSourceContract::isValid(['source_type' => $sourceType]));
            $this->assertFalse(VideoSourceContract::isValid(['source_type' => $sourceType, 'external_url' => 'javascript:alert(1)']));
            $this->assertTrue(VideoSourceContract::isValid(['source_type' => $sourceType, 'external_url' => 'https://video.example/source']));
        }

        $this->assertFalse(VideoSourceContract::isValid(['source_type' => 'youtube_video']));
        $this->assertTrue(VideoSourceContract::isValid(['source_type' => 'youtube_video', 'provider_video_id' => 'abc123']));
        $this->assertTrue(VideoSourceContract::isValid(['source_type' => 'youtube_video', 'external_url' => 'https://www.youtube.com/watch?v=abc123']));
        $this->assertFalse(VideoSourceContract::isValid(['source_type' => 'youtube_video', 'external_url' => 'https://www.youtube.com/playlist?list=abc123']));
        $this->assertFalse(VideoSourceContract::isValid(['source_type' => 'youtube_video', 'provider_video_id' => 'https://youtu.be/abc123']));
        $this->assertFalse(VideoSourceContract::isValid([
            'source_type' => 'youtube_video',
            'provider_video_id' => 'abc123',
            'external_url' => 'https://www.youtube.com/playlist?list=playlist123',
        ]));
    }

    public function test_sources_reject_fields_from_incompatible_modes(): void
    {
        $this->assertFalse(VideoSourceContract::isValid([
            'source_type' => 'uploaded_video',
            'media_asset_id' => 8,
            'external_url' => 'https://video.example/source',
        ]));
        $this->assertFalse(VideoSourceContract::isValid([
            'source_type' => 'hls',
            'media_asset_id' => 8,
            'external_url' => 'https://video.example/live.m3u8',
        ]));
    }

    public function test_app_guard_rejects_missing_and_cross_app_identity(): void
    {
        $this->assertTrue(VideoDomainGuard::sameApp(4, '4', 4));
        $this->assertFalse(VideoDomainGuard::sameApp(4, 5));
        $this->assertFalse(VideoDomainGuard::sameApp(4, null));
        $this->assertFalse(VideoDomainGuard::sameApp(4));
    }

    public function test_model_helpers_enforce_same_app_ownership(): void
    {
        $channel = new VideoChannel(['app_id' => 10]);
        $otherChannel = new VideoChannel(['app_id' => 11]);
        $playlist = new VideoPlaylist(['app_id' => 10]);
        $video = new Video(['app_id' => 10, 'source_type' => 'uploaded_video']);
        $otherVideo = new Video(['app_id' => 11]);
        $videoAsset = new MediaAsset(['app_id' => 10, 'type' => 'video']);
        $globalAsset = new MediaAsset(['app_id' => null, 'type' => 'video']);

        $this->assertTrue($playlist->acceptsChannel($channel));
        $this->assertFalse($playlist->acceptsChannel($otherChannel));
        $this->assertTrue($video->acceptsChannel($channel));
        $this->assertFalse($video->acceptsChannel($otherChannel));
        $this->assertTrue($video->acceptsMediaAsset($videoAsset));
        $this->assertFalse($video->acceptsMediaAsset($globalAsset));

        $item = new VideoPlaylistItem(['app_id' => 10]);
        $this->assertTrue($item->acceptsMembership($playlist, $video));
        $this->assertFalse($item->acceptsMembership($playlist, $otherVideo));
    }

    public function test_models_define_fail_closed_persistence_guards(): void
    {
        foreach ([VideoChannel::class, VideoPlaylist::class, Video::class, VideoPlaylistItem::class] as $model) {
            $method = new \ReflectionMethod($model, 'booted');
            $source = implode('', array_slice(
                file($method->getFileName()),
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            ));

            $this->assertStringContainsString("where('app_id'", $source);
            $this->assertStringContainsString('DomainException', $source);
            $this->assertStringContainsString("isDirty('app_id')", $source);
        }
    }

    public function test_fillable_and_cast_contracts_are_stable(): void
    {
        $video = new Video();
        foreach (['app_id', 'video_channel_id', 'source_type', 'provider', 'media_asset_id', 'external_url'] as $field) {
            $this->assertContains($field, $video->getFillable());
        }
        $this->assertSame('boolean', $video->getCasts()['is_live']);
        $this->assertSame('array', $video->getCasts()['playback_settings_json']);

        $this->assertSame('array', (new VideoChannel())->getCasts()['notification_settings_json']);
        $this->assertContains('provider_playlist_id', (new VideoPlaylist())->getFillable());
        $this->assertSame('array', (new VideoPlaylistItem())->getCasts()['settings_json']);
    }

    public function test_migrations_define_required_app_scope_and_uniqueness(): void
    {
        foreach (['video_channels', 'video_playlists', 'videos'] as $table) {
            $source = $this->migrationSource($table);
            $this->assertStringContainsString("foreignId('app_id')->constrained('apps')", $source);
            $this->assertStringContainsString("unique(['app_id', 'slug'])", $source);
        }

        $items = $this->migrationSource('video_playlist_items');
        $this->assertStringContainsString("foreignId('app_id')->constrained('apps')", $items);
        $this->assertStringContainsString("unique(['video_playlist_id', 'video_id'])", $items);
    }

    private function migrationSource(string $table): string
    {
        $matches = glob(dirname(__DIR__, 2) . '/database/migrations/*_create_' . $table . '_table.php');
        $this->assertCount(1, $matches);

        return (string) file_get_contents($matches[0]);
    }
}
