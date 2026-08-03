<?php

namespace App\Observers;

use App\Models\ContentPost;
use App\Services\Notifications\AutoContentNotificationService;

class ContentPostObserver
{
    public function created(ContentPost $post): void
    {
        if ((string) ($post->status ?? '') === 'published') {
            app(AutoContentNotificationService::class)->maybeNotifyContentPost($post, 'created_published');
        }
    }

    public function updated(ContentPost $post): void
    {
        if ((string) ($post->status ?? '') !== 'published') {
            return;
        }

        if ($post->wasChanged('status') || $post->wasChanged('publish_at') || $post->wasChanged('published_at')) {
            app(AutoContentNotificationService::class)->maybeNotifyContentPost($post, 'updated_published');
        }
    }
}
