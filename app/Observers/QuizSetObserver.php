<?php

namespace App\Observers;

use App\Models\QuizSet;
use App\Services\Notifications\AutoContentNotificationService;

class QuizSetObserver
{
    public function created(QuizSet $quizSet): void
    {
        if ((string) ($quizSet->status ?? '') === 'published' && (bool) ($quizSet->is_enabled ?? true)) {
            app(AutoContentNotificationService::class)->maybeNotifyQuizSet($quizSet, 'created_published');
        }
    }

    public function updated(QuizSet $quizSet): void
    {
        if ((string) ($quizSet->status ?? '') !== 'published' || ! (bool) ($quizSet->is_enabled ?? true)) {
            return;
        }

        if ($quizSet->wasChanged('status') || $quizSet->wasChanged('is_enabled')) {
            app(AutoContentNotificationService::class)->maybeNotifyQuizSet($quizSet, 'updated_published');
        }
    }
}
