<?php

namespace App\Filament\Resources\ContentPostResource\Pages;

use App\Filament\Resources\ContentPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContentPost extends CreateRecord
{
    protected static string $resource = ContentPostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['blocks_json'] = $this->buildBlocksFromHtml($data['body_html'] ?? null);

        $data['meta_json'] = $this->mergeMetaIconKey(
            $data['meta_json'] ?? null,
            request()->input('data.icon_key')
        );

        return $data;
    }

    protected function getFormSchema(): array
    {
        return array_merge(
            parent::getFormSchema(),
            [
                \Filament\Forms\Components\Section::make('Live Preview')
                    ->description('This shows how your content will appear.')
                    ->schema([
                        \Filament\Forms\Components\ViewField::make('preview')
                            ->view('filament.preview.content-preview')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]
        );
    }

    private function buildBlocksFromHtml($html): array
    {
        if (!is_string($html)) return [];
        $html = trim($html);
        if ($html === '') return [];

        return [
            [
                'type' => 'html',
                'html' => $html,
            ],
        ];
    }

    private function mergeMetaIconKey($meta, $iconKey): ?string
    {
        if (is_string($meta) && trim($meta) !== '') {
            $decoded = json_decode($meta, true);
            $meta = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($meta)) {
            $meta = [];
        }

        $iconKey = is_string($iconKey) ? trim($iconKey) : '';

        if ($iconKey === '') {
            unset($meta['icon_key']);
        } else {
            $meta['icon_key'] = $iconKey;
        }

        return empty($meta) ? null : json_encode($meta, JSON_UNESCAPED_SLASHES);
    }
}
