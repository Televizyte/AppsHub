<?php

use App\Http\Controllers\Admin\BeginnerBookChapterController;
use App\Http\Controllers\Admin\BeginnerBookController;
use App\Http\Controllers\Admin\BeginnerContentChannelController;
use App\Http\Controllers\Admin\BeginnerContentCreateController;
use App\Http\Controllers\Admin\BeginnerContentEditorController;
use App\Http\Controllers\Admin\BeginnerDailyEditorController;
use App\Http\Controllers\Admin\BeginnerFeedPostController;
use App\Http\Controllers\Admin\BeginnerAdPlacementController;
use App\Http\Controllers\Admin\BeginnerPushNotificationController;
use App\Http\Controllers\Admin\BeginnerItemCreateController;
use App\Http\Controllers\Admin\BeginnerItemEditorController;
use App\Http\Controllers\Admin\BeginnerMediaCenterController;
use App\Http\Controllers\Admin\BeginnerQuoteDesignerController;
use App\Http\Controllers\Admin\BeginnerSectionCreateController;
use App\Http\Controllers\Admin\BeginnerSectionEditorController;
use App\Http\Controllers\Admin\TemplateApplyController;
use App\Http\Controllers\Public\LegalDocumentController;
use App\Http\Controllers\Public\AccountDeletionController;
use App\Http\Controllers\Public\SupportRequestController;
use App\Http\Controllers\Public\ContentShareController;
use App\Support\ActiveApp;
use App\Support\AdminAccess;
use Illuminate\Support\Facades\Route;



Route::get('/share/{appSlug}/{kind}/{idOrSlug}', [ContentShareController::class, 'show'])
    ->where('appSlug', '[a-z0-9-]+')
    ->where('kind', 'articles|shorts')
    ->name('public.content-share.show');

Route::get('/{appSlug}/account-deletion', [AccountDeletionController::class, 'show'])
    ->where('appSlug', '[a-z0-9-]+')
    ->name('public.canonical.account-deletion.show');

Route::post('/{appSlug}/account-deletion', [AccountDeletionController::class, 'store'])
    ->where('appSlug', '[a-z0-9-]+')
    ->middleware('throttle:5,10')
    ->name('public.canonical.account-deletion.store');

Route::get('/{appSlug}/support-request', [SupportRequestController::class, 'show'])
    ->where('appSlug', '[a-z0-9-]+')
    ->name('public.support-request.show');

Route::post('/{appSlug}/support-request', [SupportRequestController::class, 'store'])
    ->where('appSlug', '[a-z0-9-]+')
    ->middleware('throttle:8,10')
    ->name('public.support-request.store');

Route::get('/{appSlug}/{documentSlug}', [LegalDocumentController::class, 'show'])
    ->where('appSlug', '[a-z0-9-]+')
    ->where('documentSlug', 'privacy-policy|terms-of-use|support|community-guidelines|copyright-policy|content-usage|disclaimer|data-safety')
    ->name('public.legal.canonical');

Route::get('/account-deletion/{appSlug}', [AccountDeletionController::class, 'show'])
    ->where('appSlug', '[a-z0-9-]+')
    ->name('public.account-deletion.show');

Route::post('/account-deletion/{appSlug}', [AccountDeletionController::class, 'store'])
    ->where('appSlug', '[a-z0-9-]+')
    ->middleware('throttle:5,10')
    ->name('public.account-deletion.store');

Route::get('/legal/{appSlug}/{documentKey}', [LegalDocumentController::class, 'show'])
    ->where('appSlug', '[a-z0-9-]+')
    ->where('documentKey', 'privacy|terms|support|community|copyright|content-usage|disclaimer|data-safety')
    ->name('public.legal.show');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/switch-active-app/{id}', function (int $id) {
    abort_unless(AdminAccess::canUseApp($id), 403);

    ActiveApp::set($id);

    $return = (string) request()->query('return', '/admin/beginner-dashboard');

    if (! str_starts_with($return, '/admin/')) {
        $return = '/admin/beginner-dashboard';
    }

    return redirect($return);
})->middleware(['web', 'auth'])->name('admin.switch-active-app');

Route::get('/_debug/set-active-app/{id}', function (int $id) {
    abort_unless(AdminAccess::canUseApp($id), 403);

    ActiveApp::set($id);

    return redirect('/admin/beginner-dashboard');
})->middleware(['web', 'auth']);

Route::middleware(['web', 'auth'])->prefix('admin/beginner')->group(function () {
    Route::post('/media-center/upload', [BeginnerMediaCenterController::class, 'upload'])->name('admin.beginner.media-center.upload');


    // Beginner Feed Engine full-page editor routes. Keeps create/edit out of the advanced resource UI.
    Route::get('/feed-posts/create', [BeginnerFeedPostController::class, 'create'])->name('admin.beginner.feed-posts.create');
    Route::post('/feed-posts', [BeginnerFeedPostController::class, 'store'])->name('admin.beginner.feed-posts.store');
    Route::get('/feed-posts/{feedPost}/edit', [BeginnerFeedPostController::class, 'edit'])->name('admin.beginner.feed-posts.edit');
    Route::put('/feed-posts/{feedPost}', [BeginnerFeedPostController::class, 'update'])->name('admin.beginner.feed-posts.update');



    Route::post('/ads/sync-defaults', [BeginnerAdPlacementController::class, 'syncDefaults'])->name('admin.beginner.ads.sync-defaults');
    Route::patch('/ads/master-toggle', [BeginnerAdPlacementController::class, 'toggleMaster'])->name('admin.beginner.ads.toggle-master');
    Route::patch('/ads/provider', [BeginnerAdPlacementController::class, 'updateProvider'])->name('admin.beginner.ads.update-provider');
    Route::patch('/ads/global-settings', [BeginnerAdPlacementController::class, 'updateGlobalSettings'])->name('admin.beginner.ads.update-global-settings');
    Route::patch('/ads/toggle-rule', [BeginnerAdPlacementController::class, 'toggleRule'])->name('admin.beginner.ads.toggle-rule');


    Route::get('/push/create', [BeginnerPushNotificationController::class, 'create'])->name('admin.beginner.push.create');
    Route::get('/push/{pushNotification}/edit', [BeginnerPushNotificationController::class, 'edit'])->name('admin.beginner.push.edit');
    Route::get('/push/{pushNotification}/view', [BeginnerPushNotificationController::class, 'show'])->name('admin.beginner.push.view');
    Route::post('/push/store', [BeginnerPushNotificationController::class, 'store'])->name('admin.beginner.push.store');
    Route::patch('/push/{pushNotification}/update', [BeginnerPushNotificationController::class, 'update'])->name('admin.beginner.push.update');
    Route::patch('/push/{pushNotification}/send-now', [BeginnerPushNotificationController::class, 'sendNow'])->name('admin.beginner.push.send-now');
    Route::patch('/push/{pushNotification}/cancel', [BeginnerPushNotificationController::class, 'cancel'])->name('admin.beginner.push.cancel');
    Route::post('/push/{pushNotification}/duplicate', [BeginnerPushNotificationController::class, 'duplicate'])->name('admin.beginner.push.duplicate');
    Route::delete('/push/{pushNotification}/delete', [BeginnerPushNotificationController::class, 'delete'])->name('admin.beginner.push.delete');

    Route::get('/daily/{kind}/edit', [BeginnerDailyEditorController::class, 'edit'])->name('admin.beginner.daily.edit');
    Route::put('/daily/{kind}', [BeginnerDailyEditorController::class, 'update'])->name('admin.beginner.daily.update');

    Route::get('/content-posts/channel/{bucket}', [BeginnerContentChannelController::class, 'index'])->name('admin.beginner.content-posts.channel');
    Route::get('/content-posts/create', [BeginnerContentCreateController::class, 'create'])->name('admin.beginner.content-posts.create');
    Route::post('/content-posts', [BeginnerContentCreateController::class, 'store'])->name('admin.beginner.content-posts.store');
    Route::get('/content-posts/{contentPost}/edit', [BeginnerContentEditorController::class, 'edit'])->name('admin.beginner.content-posts.edit');
    Route::put('/content-posts/{contentPost}', [BeginnerContentEditorController::class, 'update'])->name('admin.beginner.content-posts.update');

    Route::get('/quote-designer/create', [BeginnerQuoteDesignerController::class, 'create'])->name('admin.beginner.quote-designer.create');
    Route::post('/quote-designer', [BeginnerQuoteDesignerController::class, 'store'])->name('admin.beginner.quote-designer.store');
    Route::get('/quote-designer/{contentPost}/edit', [BeginnerQuoteDesignerController::class, 'edit'])->name('admin.beginner.quote-designer.edit');
    Route::put('/quote-designer/{contentPost}', [BeginnerQuoteDesignerController::class, 'update'])->name('admin.beginner.quote-designer.update');

    Route::get('/sections/create', [BeginnerSectionCreateController::class, 'create'])->name('admin.beginner.sections.create');
    Route::post('/sections', [BeginnerSectionCreateController::class, 'store'])->name('admin.beginner.sections.store');
    Route::get('/sections/{appSection}/edit', [BeginnerSectionEditorController::class, 'edit'])->name('admin.beginner.sections.edit');
    Route::put('/sections/{appSection}', [BeginnerSectionEditorController::class, 'update'])->name('admin.beginner.sections.update');
    Route::patch('/sections/{appSection}/move/{direction}', [BeginnerSectionEditorController::class, 'move'])->name('admin.beginner.sections.move');
    Route::delete('/sections/{appSection}', [BeginnerSectionEditorController::class, 'destroy'])->name('admin.beginner.sections.destroy');

    Route::get('/items/create', [BeginnerItemCreateController::class, 'create'])->name('admin.beginner.items.create');
    Route::post('/items', [BeginnerItemCreateController::class, 'store'])->name('admin.beginner.items.store');
    Route::get('/items/{appItem}/edit', [BeginnerItemEditorController::class, 'edit'])->name('admin.beginner.items.edit');
    Route::put('/items/{appItem}', [BeginnerItemEditorController::class, 'update'])->name('admin.beginner.items.update');
    Route::patch('/items/{appItem}/move/{direction}', [BeginnerItemEditorController::class, 'move'])->name('admin.beginner.items.move');
    Route::delete('/items/{appItem}', [BeginnerItemEditorController::class, 'destroy'])->name('admin.beginner.items.destroy');

    Route::get('/books', [BeginnerBookController::class, 'index'])->name('admin.beginner.books.index');
    Route::get('/books/create', [BeginnerBookController::class, 'create'])->name('admin.beginner.books.create');
    Route::post('/books', [BeginnerBookController::class, 'store'])->name('admin.beginner.books.store');
    Route::get('/books/{book}/edit', [BeginnerBookController::class, 'edit'])->name('admin.beginner.books.edit');
    Route::put('/books/{book}', [BeginnerBookController::class, 'update'])->name('admin.beginner.books.update');
    Route::delete('/books/{book}', [BeginnerBookController::class, 'destroy'])->name('admin.beginner.books.destroy');

    Route::get('/books/{book}/chapters', [BeginnerBookChapterController::class, 'index'])->name('admin.beginner.books.chapters.index');
    Route::get('/books/{book}/chapters/create', [BeginnerBookChapterController::class, 'create'])->name('admin.beginner.books.chapters.create');
    Route::post('/books/{book}/chapters', [BeginnerBookChapterController::class, 'store'])->name('admin.beginner.books.chapters.store');
    Route::get('/books/chapters/{chapter}/edit', [BeginnerBookChapterController::class, 'edit'])->name('admin.beginner.books.chapters.edit');
    Route::put('/books/chapters/{chapter}', [BeginnerBookChapterController::class, 'update'])->name('admin.beginner.books.chapters.update');
    Route::delete('/books/chapters/{chapter}', [BeginnerBookChapterController::class, 'destroy'])->name('admin.beginner.books.chapters.destroy');
    Route::patch('/books/chapters/{chapter}/move/{direction}', [BeginnerBookChapterController::class, 'move'])->name('admin.beginner.books.chapters.move');
});

Route::middleware(['web', 'auth'])->prefix('admin')->group(function () {
    Route::post('/template-library/{builderTemplate}/apply', [TemplateApplyController::class, 'apply'])
        ->name('admin.template-library.apply');

    Route::post('/template-library/save-section/{appSection}', [TemplateApplyController::class, 'saveSection'])
        ->name('admin.template-library.save-section');

    Route::post('/template-library/save-tab/{tab}', [TemplateApplyController::class, 'saveTab'])
        ->whereIn('tab', ['home', 'watch', 'inspire', 'explore', 'more'])
        ->name('admin.template-library.save-tab');
});

// BEGIN DROP 3.3J BEGINNER WATCH BUILDER ROUTES
Route::middleware(['web', 'auth'])
    ->prefix('admin/beginner/watch-links')
    ->name('admin.beginner.watch-links.')
    ->group(function () {
        Route::post('/', [\App\Http\Controllers\Admin\BeginnerWatchLinkController::class, 'store'])->name('store');
        Route::patch('/{watchLink}', [\App\Http\Controllers\Admin\BeginnerWatchLinkController::class, 'update'])->name('update');
        Route::patch('/{watchLink}/toggle', [\App\Http\Controllers\Admin\BeginnerWatchLinkController::class, 'toggle'])->name('toggle');
        Route::patch('/{watchLink}/move/{direction}', [\App\Http\Controllers\Admin\BeginnerWatchLinkController::class, 'move'])
            ->whereIn('direction', ['up', 'down'])
            ->name('move');
        Route::delete('/{watchLink}', [\App\Http\Controllers\Admin\BeginnerWatchLinkController::class, 'delete'])->name('delete');
    });
// END DROP 3.3J BEGINNER WATCH BUILDER ROUTES

// BEGIN DROP 3.3K BEGINNER QUIZ ROUTES
Route::middleware(['web', 'auth'])
    ->prefix('admin/beginner/quizzes')
    ->name('admin.beginner.quizzes.')
    ->group(function () {
        Route::post('/sets', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'storeSet'])->name('sets.store');
        Route::patch('/sets/{quizSet}', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'updateSet'])->name('sets.update');
        Route::patch('/sets/{quizSet}/toggle-status', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'toggleSetStatus'])->name('sets.toggle-status');
        Route::delete('/sets/{quizSet}', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'deleteSet'])->name('sets.delete');
                Route::delete('/levels/{quizLevel}', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'deleteLevel'])->name('levels.delete');
                Route::delete('/levels/{quizLevel}/questions', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'clearLevelQuestions'])->name('levels.clear-questions');
                        Route::post('/levels/{quizLevel}/generate-drafts', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'generateDraftQuestions'])->name('levels.generate-drafts');
        Route::post('/levels/{quizLevel}/import-questions', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'importQuestions'])->name('levels.import-questions');
        Route::patch('/levels/{quizLevel}/enable-drafts', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'enableLevelDrafts'])->name('levels.enable-drafts');
        Route::patch('/levels/{quizLevel}', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'updateLevel'])->name('levels.update');
                        Route::post('/sets/{quizSet}/groups', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'storeGroup'])->name('groups.store');
        Route::patch('/groups/{quizStudyGroup}', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'updateGroup'])->name('groups.update');
        Route::delete('/groups/{quizStudyGroup}', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'deleteGroup'])->name('groups.delete');
        Route::post('/sets/{quizSet}/levels', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'storeLevel'])->name('levels.store');
        Route::post('/sets/{quizSet}/questions', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'storeQuestion'])->name('questions.store');
        Route::patch('/questions/{quizQuestion}', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'updateQuestion'])->name('questions.update');
        Route::patch('/questions/{quizQuestion}/toggle', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'toggleQuestion'])->name('questions.toggle');
        Route::post('/questions/{quizQuestion}/duplicate', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'duplicateQuestion'])->name('questions.duplicate');
        Route::delete('/questions/{quizQuestion}', [\App\Http\Controllers\Admin\BeginnerQuizController::class, 'deleteQuestion'])->name('questions.delete');
    });
// END DROP 3.3K BEGINNER QUIZ ROUTES

/*
|--------------------------------------------------------------------------
| AppsHub auth fallback
|--------------------------------------------------------------------------
| Some Laravel/Livewire middleware expects a route named "login" when a
| session expires. Filament login lives under /admin/login, so this small
| fallback prevents Route [login] not defined errors.
*/
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');
