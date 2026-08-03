<?php

namespace App\Observers;

use App\Models\Book;
use App\Services\Notifications\AutoContentNotificationService;

class BookObserver
{
    public function created(Book $book): void
    {
        if ((string) ($book->status ?? '') === 'published') {
            app(AutoContentNotificationService::class)->maybeNotifyBook($book, 'created_published');
        }
    }

    public function updated(Book $book): void
    {
        if ((string) ($book->status ?? '') !== 'published') {
            return;
        }

        if ($book->wasChanged('status') || $book->wasChanged('published_at')) {
            app(AutoContentNotificationService::class)->maybeNotifyBook($book, 'updated_published');
        }
    }
}
