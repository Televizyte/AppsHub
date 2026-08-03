<?php

namespace App\Filament\Resources\AppRouteResource\Pages;

use App\Filament\Resources\AppRouteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAppRoute extends CreateRecord
{
    protected static string $resource = AppRouteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = AppRouteResource::applyActiveAppToCreateData($data);

        $iconKey = $data['icon_key'] ?? null;
        unset($data['icon_key']);

        $data['meta_json'] = $this->mergeMetaIconKey($data['meta_json'] ?? null, $iconKey);

        return $data;
    }

    private function mergeMetaIconKey($metaValue, $iconKey): ?array
    {
        $meta = $this->decodeMeta($metaValue);

        $iconKey = is_string($iconKey) ? trim($iconKey) : '';

        if ($iconKey !== '') {
            $meta['icon_key'] = $iconKey;
        } else {
            unset($meta['icon_key']);
        }

        return empty($meta) ? null : $meta;
    }

    private function decodeMeta($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $string = trim($value);

            if ($string === '' || strtolower($string) === 'null') {
                return [];
            }

            $json = json_decode($string, true);

            return is_array($json) ? $json : [];
        }

        return [];
    }
}
