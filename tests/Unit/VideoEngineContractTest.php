<?php

namespace Tests\Unit;

use App\Filament\Pages\VideoEngine;
use App\Support\Video\VideoSourceContract;
use PHPUnit\Framework\TestCase;

class VideoEngineContractTest extends TestCase
{
    public function test_access_and_filament_registration_are_canonical(): void
    {
        $page = $this->source('app/Filament/Pages/VideoEngine.php');
        $access = $this->source('app/Support/AdminAccess.php');

        $this->assertSame(2, substr_count($page, "AdminAccess::page('video_engine')"));
        $this->assertStringContainsString("'video_engine' => self::has(['content.view', 'watch.manage'])", str_replace("'watch_builder', ", '', $access));
        $this->assertStringContainsString("protected static ?string \$navigationGroup = 'Shared Engines'", $page);
        $this->assertStringContainsString("protected static ?int \$navigationSort = 14", $page);
        $this->assertStringContainsString("protected static ?string \$slug = 'video-engine'", $page);
    }

    public function test_all_five_source_types_use_the_canonical_contract(): void
    {
        $cases = [
            ['source_type' => 'uploaded_video', 'media_asset_id' => 8, 'external_url' => 'https://ignored.example'],
            ['source_type' => 'external_video', 'external_url' => 'https://video.example/watch', 'media_asset_id' => 8],
            ['source_type' => 'hls', 'external_url' => 'https://video.example/live.m3u8', 'provider_video_id' => 'ignored'],
            ['source_type' => 'youtube_video', 'provider_video_id' => 'abc_123', 'media_asset_id' => 8],
            ['source_type' => 'web_embed', 'external_url' => 'https://video.example/embed', 'provider_video_id' => 'ignored'],
        ];

        $this->assertSame(VideoSourceContract::sourceTypes(), array_column($cases, 'source_type'));

        foreach ($cases as $case) {
            $canonical = VideoEngine::canonicalSourceFields($case);
            $this->assertTrue(VideoSourceContract::isValid($canonical), $case['source_type']);
        }

        $this->assertNull(VideoEngine::canonicalSourceFields($cases[0])['external_url']);
        $this->assertNull(VideoEngine::canonicalSourceFields($cases[1])['media_asset_id']);
        $this->assertNull(VideoEngine::canonicalSourceFields($cases[2])['provider_video_id']);
        $this->assertNull(VideoEngine::canonicalSourceFields($cases[3])['media_asset_id']);
        $this->assertNull(VideoEngine::canonicalSourceFields($cases[4])['provider_video_id']);
    }

    public function test_advanced_json_keys_are_preserved_recursively(): void
    {
        $existing = [
            'advanced_provider_option' => 'keep',
            'nested' => ['advanced' => true, 'beginner_value' => 'old'],
        ];

        $merged = VideoEngine::preserveAdvancedJson($existing, [
            'nested' => ['beginner_value' => 'new'],
            'embed_allowed' => true,
        ]);

        $this->assertSame('keep', $merged['advanced_provider_option']);
        $this->assertTrue($merged['nested']['advanced']);
        $this->assertSame('new', $merged['nested']['beginner_value']);
        $this->assertTrue($merged['embed_allowed']);
    }

    public function test_active_app_queries_and_writes_fail_closed(): void
    {
        $page = $this->source('app/Filament/Pages/VideoEngine.php');
        $activeApp = $this->source('app/Support/ActiveApp.php');

        $this->assertStringContainsString('ActiveApp::selectedId()', $page);
        $this->assertStringContainsString("->where('app_id', \$this->activeAppId ?? 0)", $page);
        $this->assertStringContainsString("if (! \$this->activeAppId) return []", $page);
        $this->assertStringContainsString("if (! \$this->activeAppId) return ['videos' => 0", $page);
        $this->assertStringContainsString('this never falls back to another app', strtolower($activeApp));

        $selected = $this->methodSource('app/Support/ActiveApp.php', 'selectedId');
        $this->assertStringNotContainsString('firstActiveId', $selected);
    }

    public function test_canonical_ownership_and_playlist_membership_guards_are_used(): void
    {
        $page = $this->source('app/Filament/Pages/VideoEngine.php');
        $item = $this->source('app/Models/VideoPlaylistItem.php');

        foreach (['VideoChannel::query()', 'VideoPlaylist::query()', 'Video::query()', 'MediaAsset::query()', 'VideoPlaylistItem::query()'] as $query) {
            $this->assertStringContainsString($query, $page);
        }
        $this->assertStringContainsString('syncPlaylistMembership($playlist, $videoIds)', $page);
        $this->assertStringContainsString("where('video_playlist_id', \$playlist->id)", $page);
        $this->assertStringContainsString("where('app_id', \$item->app_id)", $item);
        $this->assertStringContainsString('VideoDomainGuard::sameApp', $item);
    }

    public function test_query_string_preserves_horizontal_tab_and_editor_state(): void
    {
        $page = $this->source('app/Filament/Pages/VideoEngine.php');
        $view = $this->source('resources/views/filament/pages/video-engine.blade.php');

        foreach (["'activeTab' => ['as' => 'tab'", "'workspaceMode' => ['as' => 'workspace'", "'editorTab' => ['as' => 'editor_tab'", "'libraryView' => ['as' => 'view'", "'search' => ['as' => 'search'", "'editingChannelId' => ['as' => 'channel'", "'editingPlaylistId' => ['as' => 'playlist'", "'editingVideoId' => ['as' => 'video'"] as $state) {
            $this->assertStringContainsString($state, $page);
        }
        $this->assertSame(['overview', 'videos', 'channels', 'playlists', 'preview'], VideoEngine::tabs());
        foreach (['Overview', 'Videos', 'Channels', 'Playlists', 'Preview'] as $tab) {
            $this->assertStringContainsString("'{$tab}'", $view);
        }
    }

    public function test_beginner_hides_raw_resources_and_advanced_mode_keeps_them_registered(): void
    {
        foreach (['VideoResource.php', 'VideoChannelResource.php', 'VideoPlaylistResource.php'] as $resource) {
            $source = $this->source('app/Filament/Resources/' . $resource);
            $this->assertStringContainsString('public static function shouldRegisterNavigation(): bool', $source);
            $this->assertStringContainsString('return AdminMode::isAdvanced();', $source);
            $this->assertStringNotContainsString('protected static bool $shouldRegisterNavigation = false', $source);
        }
    }

    public function test_editors_are_internal_tabbed_workspaces_and_save_keeps_context(): void
    {
        $page = $this->source('app/Filament/Pages/VideoEngine.php');
        $view = $this->source('resources/views/filament/pages/video-engine.blade.php');

        foreach (['video_editor', 'channel_editor', 'playlist_editor'] as $workspace) {
            $this->assertStringContainsString($workspace, $page);
        }
        $this->assertStringContainsString("@if(\$workspaceMode === 'video_editor')", $view);
        $this->assertStringContainsString("@elseif(\$workspaceMode === 'channel_editor')", $view);
        $this->assertStringContainsString("'video_editor' => ['content' => 'Content', 'source' => 'Source', 'thumbnail' => 'Thumbnail', 'publishing' => 'Publishing', 'preview' => 'Preview']", $view);
        $this->assertStringContainsString("default => ['details' => 'Details', 'videos' => 'Videos', 'thumbnail' => 'Thumbnail', 'publishing' => 'Publishing', 'preview' => 'Preview']", $view);
        $this->assertStringContainsString("'savePlaylist'", $view);
        foreach (['content', 'details', 'source', 'thumbnail', 'videos', 'publishing', 'preview'] as $tab) {
            $this->assertStringContainsString("'{$tab}'", $page);
        }
        foreach (['saveVideo', 'saveChannel', 'savePlaylist'] as $method) {
            $source = $this->reflectionSource(VideoEngine::class, $method);
            $this->assertStringContainsString("dispatch('video-engine-keep-position')", $source);
            $this->assertStringNotContainsString('closeEditor()', $source);
        }
        $this->assertStringNotContainsString('/admin/videos/', $view);
        $this->assertStringNotContainsString('/admin/video-channels/', $view);
        $this->assertStringNotContainsString('/admin/video-playlists/', $view);
    }

    public function test_beginner_view_has_safe_empty_states_and_no_delete_actions(): void
    {
        $view = $this->source('resources/views/filament/pages/video-engine.blade.php');

        $this->assertStringContainsString('Select an active app', $view);
        $this->assertStringContainsString('@if(!$activeAppId)', $view);
        $this->assertStringNotContainsString('wire:click="delete', $view);
    }

    public function test_beginner_media_picker_reuses_the_shared_active_app_media_system(): void
    {
        $page = $this->source('app/Filament/Pages/VideoEngine.php');
        $view = $this->source('resources/views/filament/pages/video-engine.blade.php');

        $this->assertStringContainsString("->where('type', 'image')->where('is_active', true)", $page);
        $this->assertStringContainsString("\$this->owned(MediaAsset::query(), \$assetId)", $page);
        $this->assertStringContainsString("where('type', 'video')->where('is_active', true)", $page);
        $this->assertStringContainsString('selectVideoAsset', $view);
        $this->assertStringContainsString("route('admin.beginner.media-center.upload')", $view);
        $this->assertStringContainsString('selectMediaAsset(Number(data.asset.id), target)', $view);
        $this->assertStringNotContainsString('function parse', strtolower($page));
        $this->assertStringNotContainsString('wire:click="delete', $view);
    }

    public function test_beginner_view_uses_canonical_visual_workspace_contract(): void
    {
        $view = $this->source('resources/views/filament/pages/video-engine.blade.php');

        foreach (['ve-hero', 've-tabs', 've-stats', 've-library', 've-focus', 've-media-grid'] as $class) {
            $this->assertStringContainsString($class, $view);
        }
        $this->assertStringContainsString('appearance: none', $view);
        $this->assertStringContainsString('video-engine-card', $view);
        $this->assertStringNotContainsString('settings_json', $view);
    }

    private function source(string $relative): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/' . $relative);
    }

    private function methodSource(string $relative, string $method): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relative;
        $reflection = new \ReflectionMethod(\App\Support\ActiveApp::class, $method);

        return implode('', array_slice(file($path), $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
    }

    private function reflectionSource(string $class, string $method): string
    {
        $reflection = new \ReflectionMethod($class, $method);

        return implode('', array_slice(file($reflection->getFileName()), $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
    }
}
