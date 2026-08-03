<?php

namespace App\Filament\Resources\ContentPostResource\Pages;

use App\Filament\Resources\ContentPostResource;
use App\Models\AppItem;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContentPost extends EditRecord
{
    protected static string $resource = ContentPostResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        if ($this->isFromBeginner()) {
            $actions[] = Actions\Action::make('return_to_beginner')
                ->label('Return to Beginner')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => $this->beginnerReturnUrl());
        }

        $actions[] = Actions\DeleteAction::make();

        return $actions;
    }

    protected function getRedirectUrl(): string
    {
        if ($this->isFromBeginner()) {
            return $this->beginnerReturnUrl();
        }

        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
                    ->description('See how this content looks before publishing.')
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

    private function isFromBeginner(): bool
    {
        return request()->boolean('from_beginner')
            || request()->filled('return')
            || request()->filled('item_id')
            || request()->filled('bucket');
    }

    private function beginnerReturnUrl(): string
    {
        $record = $this->getRecord();

        $bucket = $this->normalizeBucket(
            (string) request()->query('bucket', $record->bucket ?? 'motivation')
        );

        $returnTo = $this->sanitizeReturnTo(
            (string) request()->query('return', 'channels')
        );

        $itemId = (int) request()->query('item_id', 0);

        if ($returnTo === 'item' && $itemId > 0) {
            $item = AppItem::query()
                ->with('section')
                ->where('id', $itemId)
                ->first();

            if ($item && $item->section) {
                return route('admin.beginner.items.edit', [
                    'appItem' => $item->id,
                    'tab' => $item->section->tab_key,
                    'return' => 'dashboard',
                ]);
            }
        }

        if ($returnTo === 'dashboard') {
            return '/admin/beginner-dashboard?tab=inspire';
        }

        if ($returnTo === 'destination') {
            return '/admin/destination-builder?tab=inspire';
        }

        return route('admin.beginner.content-posts.channel', [
            'bucket' => $bucket,
            'item_id' => $itemId ?: null,
        ]);
    }

    private function normalizeBucket(string $bucket): string
    {
        return match (strtolower(trim($bucket))) {
            'highlight',
            'message_highlights',
            'message-highlight',
            'message-highlights' => 'highlights',

            'inside',
            'inside-dunamis',
            'inside_dunamis_articles',
            'articles',
            'article' => 'inside_dunamis',

            'sod-quote',
            'sod-quotes',
            'quotes' => 'sod_quotes',

            default => strtolower(trim($bucket)),
        };
    }

    private function sanitizeReturnTo(string $value): string
    {
        return match (trim($value)) {
            'item' => 'item',
            'dashboard' => 'dashboard',
            'destination' => 'destination',
            default => 'channels',
        };
    }

    private function buildBlocksFromHtml($html): array
    {
        if (! is_string($html)) {
            return [];
        }

        $html = trim($html);

        if ($html === '') {
            return [];
        }

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
        } elseif (! is_array($meta)) {
            $meta = [];
        }

        $iconKey = is_string($iconKey) ? trim($iconKey) : '';

        if ($iconKey === '') {
            unset($meta['icon_key']);
        } else {
            $meta['icon_key'] = $iconKey;
        }

        return empty($meta)
            ? null
            : json_encode($meta, JSON_UNESCAPED_SLASHES);
    }
}
