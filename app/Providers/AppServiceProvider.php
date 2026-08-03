<?php

namespace App\Providers;

use App\Models\Book;
use App\Models\ContentPost;
use App\Models\QuizSet;
use App\Observers\BookObserver;
use App\Observers\ContentPostObserver;
use App\Observers\QuizSetObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Named limiter used by: throttle:apps
        RateLimiter::for('apps', function (Request $request) {
            $appSlug = (string) $request->route('appSlug');
            $token = (string) $request->header('X-APP-TOKEN', '');
            $ip = (string) $request->ip();

            // Do not store raw tokens in limiter keys
            $tokenHash = $token === '' ? 'no-token' : substr(hash('sha256', $token), 0, 16);

            $key = 'apps|' . $appSlug . '|' . $tokenHash . '|' . $ip;

            // Safe default: 120 requests/min per (app + tokenHash + ip)
            return Limit::perMinute(120)->by($key);
        });

        ContentPost::observe(ContentPostObserver::class);
        Book::observe(BookObserver::class);
        QuizSet::observe(QuizSetObserver::class);
    }
}
