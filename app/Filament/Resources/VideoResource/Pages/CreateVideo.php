<?php

namespace App\Filament\Resources\VideoResource\Pages;

use App\Filament\Resources\VideoResource;
use App\Support\Video\VideoSourceContract;
use Filament\Resources\Pages\CreateRecord;

class CreateVideo extends CreateRecord
{
    protected static string $resource = VideoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = VideoResource::applyActiveAppToCreateData($data);

        $data = $this->normalizeSource($data);

        $data['status'] = $data['status'] ?? 'draft';
        $data['visibility'] = $data['visibility'] ?? 'public';

        if (in_array($data['status'], ['published', 'publish', 'active'], true)) {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        return $data;
    }

    private function normalizeSource(array $data): array
    {
        $type = (string) ($data['source_type'] ?? '');

        $data['provider'] = trim((string) ($data['provider'] ?? ''));

        if ($data['provider'] === '') {
            $data['provider'] = match ($type) {
                VideoSourceContract::UPLOADED_VIDEO => 'appshub',
                VideoSourceContract::YOUTUBE_VIDEO => 'youtube',
                VideoSourceContract::HLS => 'hls',
                VideoSourceContract::WEB_EMBED => 'web',
                default => 'external',
            };
        }

        if ($type === VideoSourceContract::UPLOADED_VIDEO) {
            $data['provider_video_id'] = null;
            $data['external_url'] = null;
        } elseif (in_array($type, [
            VideoSourceContract::EXTERNAL_VIDEO,
            VideoSourceContract::HLS,
            VideoSourceContract::WEB_EMBED,
        ], true)) {
            $data['media_asset_id'] = null;
            $data['provider_video_id'] = null;
        } elseif ($type === VideoSourceContract::YOUTUBE_VIDEO) {
            $data['media_asset_id'] = null;
        }

        if ($type === VideoSourceContract::WEB_EMBED) {
            $settings = is_array($data['playback_settings_json'] ?? null)
                ? $data['playback_settings_json']
                : [];

            $settings['embed_allowed'] ??= true;
            $data['playback_settings_json'] = $settings;
        }

        return $data;
    }
}
