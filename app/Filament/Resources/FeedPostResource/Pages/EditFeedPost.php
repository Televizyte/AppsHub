<?php

namespace App\Filament\Resources\FeedPostResource\Pages;

use App\Filament\Resources\FeedPostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFeedPost extends EditRecord
{
    protected static string $resource = FeedPostResource::class;

    public function getTitle(): string
    {
        return 'Edit Community Post';
    }

    public function getHeading(): string
    {
        return 'Edit Community Post';
    }

    public function getSubheading(): ?string
    {
        return 'Update the feed post, media, app link, publishing status, and advanced placement options.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = FeedPostResource::applyActiveAppToCreateData($data);

        if (($data['status'] ?? null) === 'published') {
            $data['approval_status'] = 'approved';
            $data['published_at'] = $data['published_at'] ?? now();
        }

        if (blank($data['created_by_type'] ?? null)) {
            $data['created_by_type'] = 'admin';
        }

        if (blank($data['visibility'] ?? null)) {
            $data['visibility'] = 'public';
        }

        return $data;
    }
}
