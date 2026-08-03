<?php

namespace App\Filament\Resources\FeedPostResource\Pages;

use App\Filament\Resources\FeedPostResource;
use App\Models\App;
use App\Models\FeedPost;
use App\Support\ActiveApp;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeedPosts extends ListRecords
{
    protected static string $resource = FeedPostResource::class;

    public function getTitle(): string
    {
        return 'Community Feed';
    }

    public function getHeading(): string
    {
        return 'Community Feed';
    }

    public function getSubheading(): ?string
    {
        $activeAppId = ActiveApp::ensureId();
        $appName = $activeAppId
            ? (App::query()->whereKey($activeAppId)->value('name') ?: 'the active app')
            : 'the active app';

        $query = FeedPost::query();

        if ($activeAppId) {
            $query->where('app_id', $activeAppId);
        }

        $count = (clone $query)->count();
        $published = (clone $query)->where('status', 'published')->count();
        $drafts = (clone $query)->where('status', 'draft')->count();

        return "Beginner-friendly feed workspace for {$appName}. {$count} total posts, {$published} published, {$drafts} drafts.";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('+ Create Community Post')
                ->icon('heroicon-o-plus'),
        ];
    }
}
