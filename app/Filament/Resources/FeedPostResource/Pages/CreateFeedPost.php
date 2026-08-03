<?php

namespace App\Filament\Resources\FeedPostResource\Pages;

use App\Filament\Resources\FeedPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFeedPost extends CreateRecord
{
    protected static string $resource = FeedPostResource::class;

    public function getTitle(): string
    {
        return 'Create Community Post';
    }

    public function getHeading(): string
    {
        return 'Create Community Post';
    }

    public function getSubheading(): ?string
    {
        return 'Publish a controlled feed update, announcement, article share, short video share, quote, live reminder, testimony, or achievement post.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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

        if (blank($data['approval_status'] ?? null)) {
            $data['approval_status'] = ($data['status'] ?? null) === 'published' ? 'approved' : 'approved';
        }

        return $data;
    }
}
