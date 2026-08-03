<?php

use App\Http\Controllers\Api\V1\LibraryController;

use App\Http\Controllers\Api\V1\QuizController;
use App\Http\Controllers\Api\V1\QuizEngineController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BootstrapController;
use App\Http\Controllers\Api\V1\BusinessEnquiryController;
use App\Http\Controllers\Api\V1\RouteRegistryController;
use App\Http\Controllers\Api\V1\HubController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\ContentRegistryController;
use App\Http\Controllers\Api\V1\WatchController;
use App\Http\Controllers\Api\V1\MediaUploadController;
use App\Http\Controllers\Api\V1\MediaLibraryController;
use App\Http\Controllers\Api\V1\AppBrandingController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\PostLikeController;
use App\Http\Controllers\Api\V1\PostCommentController;
use App\Http\Controllers\Api\V1\PostSaveController;
use App\Http\Controllers\Api\V1\FollowController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\ModerationController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\BibleEngineController;
use App\Http\Controllers\Api\V1\FeedController;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
| All endpoints MUST be strictly scoped by appSlug to enforce multi-app isolation.
| Protected by X-APP-TOKEN middleware + per-app throttling.
*/

Route::prefix('v1')->group(function () {
    Route::prefix('apps/{appSlug}')
        ->middleware(['app.token', 'throttle:apps'])
        ->group(function () {

            // Public bootstrap / registry
            Route::get('/bootstrap', [BootstrapController::class, 'show']);
            Route::post('/business-enquiries', [BusinessEnquiryController::class, 'store']);
            Route::get('/routes', [RouteRegistryController::class, 'index']);
            Route::get('/hub', [HubController::class, 'index']);

            // Community Feed Engine
            Route::get('/feed', [FeedController::class, 'index']);
            Route::get('/feed/{feedPost}', [FeedController::class, 'show'])->whereNumber('feedPost');
            Route::get('/feed/{feedPost}/comments', [FeedController::class, 'comments'])->whereNumber('feedPost');
            Route::post('/feed/{feedPost}/share', [FeedController::class, 'share'])->whereNumber('feedPost');

            // Quiz Engine v2 foundation: collections / categories / packs / progress.
            Route::get('/quiz/collections', [QuizEngineController::class, 'collections']);
            Route::get('/quiz/collections/{collection}', [QuizEngineController::class, 'collection']);
            Route::get('/quiz/categories', [QuizEngineController::class, 'categories']);
            Route::get('/quiz/packs', [QuizEngineController::class, 'packs']);
            Route::get('/quiz/packs/{pack}', [QuizEngineController::class, 'pack'])->whereNumber('pack');
            Route::get('/quiz/packs/{pack}/levels', [QuizEngineController::class, 'levels'])->whereNumber('pack');
            Route::get('/quiz/levels/{level}/questions', [QuizEngineController::class, 'questions'])->whereNumber('level');
            Route::post('/quiz/progress/save', [QuizEngineController::class, 'saveProgress']);
            Route::get('/quiz/progress/{pack}/{level?}', [QuizEngineController::class, 'progress'])->whereNumber('pack')->whereNumber('level');
            Route::post('/quiz/submit', [QuizEngineController::class, 'submit']);
            Route::post('/quiz/reset', [QuizEngineController::class, 'reset']);


            // Bible Engine / Scripture Library
            Route::get('/bible/translations', [BibleEngineController::class, 'translations']);
            Route::get('/bible/books', [BibleEngineController::class, 'books']);
            Route::get('/bible/books/{book}/chapters', [BibleEngineController::class, 'chapters']);
            Route::get('/bible/verses', [BibleEngineController::class, 'verses']);
            Route::get('/bible/search', [BibleEngineController::class, 'search']);
            Route::get('/bible/topics', [BibleEngineController::class, 'topics']);
            Route::get('/bible/topics/{topic}/verses', [BibleEngineController::class, 'topicVerses']);
            Route::get('/bible/collections', [BibleEngineController::class, 'collections']);

            // Unified discovery libraries
            Route::get('/libraries/short-videos', [LibraryController::class, 'shortVideos']);
            Route::get('/libraries/quotes-scripture', [LibraryController::class, 'quotesScripture']);

            // Books / Library Engine
            Route::get('/books', [BookController::class, 'index']);
            Route::get('/books/categories', [BookController::class, 'categories']);
            Route::get('/books/featured', [BookController::class, 'featured']);
            Route::get('/books/{book}', [BookController::class, 'show']);
            Route::get('/books/{book}/chapters', [BookController::class, 'chapters']);
            Route::get('/books/{book}/chapters/{chapter}', [BookController::class, 'chapter']);

            // Content read
            Route::get('/content', [ContentRegistryController::class, 'index']);
            Route::get('/content/{idOrSlug}', [ContentRegistryController::class, 'show']);
            Route::get('/posts/{postSlug}', [PostController::class, 'show']);

            // Public comments read
            Route::get('/content/{idOrSlug}/comments', [PostCommentController::class, 'index']);

            // Media library
            Route::get('/media', [MediaLibraryController::class, 'index']);
            Route::post('/media', [MediaLibraryController::class, 'upload']);
            Route::get('/media/{assetId}', [MediaLibraryController::class, 'show'])->whereNumber('assetId');
            Route::delete('/media/{assetId}', [MediaLibraryController::class, 'destroy'])->whereNumber('assetId');

            Route::post('/media/cover', [MediaUploadController::class, 'uploadCover']);
            Route::patch('/content/{idOrSlug}/cover', [MediaUploadController::class, 'setCoverUrl']);

            // Branding updates
            Route::patch('/branding/assets', [AppBrandingController::class, 'setAssets']);

            // Watch
            Route::get('/watch', [WatchController::class, 'index']);

            // Push device registration
            // This must stay outside auth:sanctum so first-time users can receive public app notifications.
            // The parent route group is already protected by X-APP-TOKEN via app.token middleware.
            Route::post('/push/device-token', [DeviceTokenController::class, 'upsert']);
            Route::post('/push/device-token/deactivate', [DeviceTokenController::class, 'deactivate']);

            // Analytics read
            Route::get('/analytics/summary', [AnalyticsController::class, 'summary']);
            Route::get('/analytics/top-posts', [AnalyticsController::class, 'topPosts']);
            Route::get('/analytics/recent-watch-activity', [AnalyticsController::class, 'recentWatchActivity']);

            // Public analytics tracking
            Route::post('/content/{idOrSlug}/view', [AnalyticsController::class, 'trackContentView']);
            Route::post('/watch/event', [AnalyticsController::class, 'trackWatchEvent']);

            // Auth
            Route::middleware(['app.auth_enabled'])->group(function () {
                Route::post('/auth/register', [AuthController::class, 'register']);
                Route::post('/auth/login', [AuthController::class, 'login']);
            });

            // Protected app-user actions
            Route::middleware(['app.auth_enabled', 'auth:sanctum', 'app.sanctum_scope'])->group(function () {
                Route::get('/auth/me', [AuthController::class, 'me']);
                Route::post('/auth/logout', [AuthController::class, 'logout']);
                Route::delete('/auth/account', [AuthController::class, 'destroyAccount']);


                // Community Feed protected actions
                Route::post('/feed/{feedPost}/like', [FeedController::class, 'like'])->whereNumber('feedPost');
                Route::delete('/feed/{feedPost}/like', [FeedController::class, 'unlike'])->whereNumber('feedPost');
                Route::post('/feed/{feedPost}/save', [FeedController::class, 'save'])->whereNumber('feedPost');
                Route::delete('/feed/{feedPost}/save', [FeedController::class, 'unsave'])->whereNumber('feedPost');
                Route::post('/feed/{feedPost}/comments', [FeedController::class, 'storeComment'])->whereNumber('feedPost');
                Route::post('/feed/{feedPost}/report', [FeedController::class, 'report'])->whereNumber('feedPost');

                // Likes
                Route::post('/content/{idOrSlug}/like', [PostLikeController::class, 'store']);
                Route::delete('/content/{idOrSlug}/like', [PostLikeController::class, 'destroy']);

                // Saved content
                Route::post('/content/{idOrSlug}/save', [PostSaveController::class, 'store']);
                Route::delete('/content/{idOrSlug}/save', [PostSaveController::class, 'destroy']);

                // Comments
                Route::post('/content/{idOrSlug}/comments', [PostCommentController::class, 'store']);
                Route::patch('/content/{idOrSlug}/comments/{commentId}/status', [PostCommentController::class, 'updateStatus'])
                    ->whereNumber('commentId');
                Route::delete('/content/{idOrSlug}/comments/{commentId}', [PostCommentController::class, 'destroy'])
                    ->whereNumber('commentId');

                // Follow
                Route::post('/users/{userId}/follow', [FollowController::class, 'store'])->whereNumber('userId');
                Route::delete('/users/{userId}/follow', [FollowController::class, 'destroy'])->whereNumber('userId');

                // Moderation
                Route::get('/moderation/summary', [ModerationController::class, 'summary']);
                Route::get('/moderation/comments', [ModerationController::class, 'comments']);
                Route::get('/moderation/activity', [ModerationController::class, 'activity']);
                Route::patch('/moderation/comments/{commentId}/status', [ModerationController::class, 'updateStatus'])
                    ->whereNumber('commentId');
                Route::delete('/moderation/comments/{commentId}', [ModerationController::class, 'destroy'])
                    ->whereNumber('commentId');
            });

            // Demo seed
            Route::post('/seed-demo', [\App\Http\Controllers\Api\V1\DemoSeedController::class, 'seed']);
        });
});

// BEGIN DROP 3.3K QUIZ API ROUTES
Route::get('/v1/apps/{appSlug}/quizzes', [QuizController::class, 'index']);
Route::get('/v1/apps/{appSlug}/quizzes/{key}', [QuizController::class, 'show']);
// END DROP 3.3K QUIZ API ROUTES

// Quiz Engine gameplay runtime endpoints (Phase 2A)
Route::post('/v1/apps/{appSlug}/quiz/attempt/start', [\App\Http\Controllers\Api\V1\QuizEngineController::class, 'startAttempt']);
Route::post('/v1/apps/{appSlug}/quiz/attempt/answer', [\App\Http\Controllers\Api\V1\QuizEngineController::class, 'answerAttempt']);
Route::post('/v1/apps/{appSlug}/quiz/attempt/complete', [\App\Http\Controllers\Api\V1\QuizEngineController::class, 'completeAttempt']);
