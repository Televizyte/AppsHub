<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Models\AppItem;
use App\Models\AppSection;
use App\Models\ContentPost;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use App\Services\BibleEngine\BibleEngineService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class QuoteEngine extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $navigationLabel = 'Quote Engine';
    protected static ?string $navigationGroup = 'Engagement';
    protected static ?int $navigationSort = 21;
    protected static ?string $slug = 'quote-engine';

    protected static string $view = 'filament.pages.quote-engine';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('quote_engine');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('quote_engine');
    }

    public ?App $currentApp = null;

    public string $activeQuoteTab = 'all';

    public string $modal = '';
    public string $modalBucket = 'sod_quotes';
    public string $modalBatchName = '';

    public string $singleQuoteText = '';
    public string $singleQuoteSource = '';
    public string $singleQuoteStatus = 'draft';

    public string $bulkText = '';
    public string $bulkStatus = 'draft';
    public ?TemporaryUploadedFile $bulkFile = null;

    public string $scheduleBatchName = '';
    public string $scheduleStartDate = '';
    public string $scheduleEndDate = '';
    public string $scheduleFrequency = 'daily';
    public string $scheduleStatus = 'published';
    public string $scheduleFallback = 'keep_current';

    public string $categoryLabel = '';
    public string $categoryDescription = '';
    public string $categoryIcon = 'Q';


    public string $scriptureSearch = '';
    public array $scriptureSearchResults = [];
    public string $selectedScriptureReference = '';
    public string $selectedScriptureText = '';
    public string $selectedScriptureTopic = '';
    public string $scripturePickerMode = 'create';
    public array $selectedScriptureReferences = [];
    public string $scriptureCardStatus = 'published';
    public string $scriptureCardBatchName = 'Default Batch';

    public array $stats = [];
    public array $tabs = [];
    public array $dailyCards = [];
    public array $frontendQuoteCards = [];
    public array $frontendScriptureCards = [];
    public array $quoteSets = [];


    public function mount(): void
    {
        $this->activeQuoteTab = request()->query('quote_tab', 'all');
        $this->modalBucket = $this->bucketForTab($this->activeQuoteTab) ?: 'sod_quotes';

        $this->refreshEngineData();
    }

    public function selectQuoteTab(string $tab): void
    {
        $this->activeQuoteTab = $tab;
        $this->modalBucket = $this->bucketForTab($tab) ?: 'sod_quotes';
        $this->refreshEngineData();

        $this->dispatch('quote-engine-tab-changed', tab: $tab);
    }

    public function openCreateCategory(): void
    {
        $this->resetModalFields();
        $this->modal = 'category';
    }

    public function openAddQuote(string $bucket, string $batch = ''): void
    {
        $this->resetModalFields();
        $this->modal = 'add';
        $this->modalBucket = $this->normalizeBucket($bucket);
        $this->modalBatchName = trim($batch) !== '' ? trim($batch) : 'Default Batch';
    }

    public function openBulkImport(string $bucket, string $batch = ''): void
    {
        $this->resetModalFields();
        $this->modal = 'bulk';
        $this->modalBucket = $this->normalizeBucket($bucket);
        $this->modalBatchName = trim($batch) !== '' ? trim($batch) : '';
        $this->bulkText = '';
        $this->bulkStatus = 'draft';
    }

    public function openAiDraft(string $bucket): void
    {
        $this->resetModalFields();
        $this->modal = 'ai';
        $this->modalBucket = $this->normalizeBucket($bucket);
    }


    public function openScripturePicker(string $query = ''): void
    {
        $this->resetModalFields();
        $this->modal = 'scripture_picker';
        $this->modalBucket = 'daily_scriptures';
        $this->scripturePickerMode = 'create';
        $this->selectedScriptureReferences = [];
        $this->scriptureSearch = trim($query) !== '' ? trim($query) : 'faith';
        $this->scriptureCardStatus = 'published';
        $this->scriptureCardBatchName = 'Default Batch';
        $this->searchScriptureLibrary();
    }



    public function openScripturePickerForSingle(): void
    {
        $this->resetValidation();
        $this->modal = 'scripture_picker';
        $this->modalBucket = 'daily_scriptures';
        $this->scripturePickerMode = 'single';
        $this->selectedScriptureReferences = [];
        $this->scriptureSearch = trim($this->scriptureSearch) !== '' ? trim($this->scriptureSearch) : 'faith';
        $this->searchScriptureLibrary();
    }

    public function openScripturePickerForBulk(): void
    {
        $this->resetValidation();
        $this->modal = 'scripture_picker';
        $this->modalBucket = 'daily_scriptures';
        $this->scripturePickerMode = 'bulk';
        $this->selectedScriptureReferences = [];
        $this->scriptureSearch = trim($this->scriptureSearch) !== '' ? trim($this->scriptureSearch) : 'faith';
        $this->searchScriptureLibrary();
    }

    public function backToDailyScriptureAdd(): void
    {
        $this->modal = 'add';
        $this->modalBucket = 'daily_scriptures';
        $this->modalBatchName = trim($this->modalBatchName) !== '' ? trim($this->modalBatchName) : 'Default Batch';
    }

    public function backToDailyScriptureBulk(): void
    {
        $this->modal = 'bulk';
        $this->modalBucket = 'daily_scriptures';
        $this->modalBatchName = trim($this->modalBatchName) !== '' ? trim($this->modalBatchName) : 'Default Batch';
    }

    public function toggleScriptureSelection(string $reference): void
    {
        $reference = trim($reference);
        if ($reference === '') return;
        $selected = array_values(array_filter($this->selectedScriptureReferences, fn ($item) => trim((string) $item) !== ''));
        if (in_array($reference, $selected, true)) {
            $this->selectedScriptureReferences = array_values(array_filter($selected, fn ($item) => $item !== $reference));
            return;
        }
        $selected[] = $reference;
        $this->selectedScriptureReferences = array_values(array_unique($selected));
    }

    public function addVisibleScripturesToSelection(): void
    {
        foreach ($this->scriptureSearchResults as $row) {
            $reference = trim((string) ($row['reference'] ?? ''));
            if ($reference !== '' && ! in_array($reference, $this->selectedScriptureReferences, true)) {
                $this->selectedScriptureReferences[] = $reference;
            }
        }
        $this->selectedScriptureReferences = array_values(array_unique($this->selectedScriptureReferences));
    }

    public function clearSelectedScriptures(): void
    {
        $this->selectedScriptureReferences = [];
    }

    public function useScriptureForSingle(string $reference): void
    {
        $row = $this->scriptureRowByReference($reference);
        if (! $row) {
            Notification::make()->title('Scripture not found')->warning()->send();
            return;
        }
        $this->singleQuoteText = (string) ($row['text'] ?? '');
        $this->singleQuoteSource = (string) ($row['reference'] ?? $reference);
        $this->modalBucket = 'daily_scriptures';
        $this->modalBatchName = trim($this->modalBatchName) !== '' ? trim($this->modalBatchName) : 'Default Batch';
        $this->modal = 'add';
        Notification::make()->title('Scripture inserted')->body(($row['reference'] ?? $reference).' is now in the form.')->success()->send();
    }

    public function insertSelectedScripturesIntoBulkImport(): void
    {
        $rows = $this->selectedScriptureRows();
        if (empty($rows)) {
            Notification::make()->title('No scriptures selected')->body('Select one or more scriptures first.')->warning()->send();
            return;
        }
        $lines = [];
        foreach ($rows as $row) {
            $text = trim((string) ($row['text'] ?? ''));
            $reference = trim((string) ($row['reference'] ?? ''));
            if ($text !== '') $lines[] = $text . ($reference !== '' ? ' | '.$reference : '');
        }
        $existing = trim($this->bulkText);
        $incoming = trim(implode("
", $lines));
        $this->bulkText = trim($existing . ($existing !== '' && $incoming !== '' ? "
" : '') . $incoming);
        $this->bulkStatus = $this->bulkStatus ?: 'draft';
        $this->modalBucket = 'daily_scriptures';
        $this->modalBatchName = trim($this->modalBatchName) !== '' ? trim($this->modalBatchName) : 'Default Batch';
        $this->modal = 'bulk';
        Notification::make()->title(count($lines).' scripture(s) inserted')->body('Review them in Bulk Import, then click Import Scriptures.')->success()->send();
    }

    public function createSelectedScripturesFromPicker(): void
    {
        $rows = $this->selectedScriptureRows();
        if (empty($rows)) {
            Notification::make()->title('No scriptures selected')->body('Select one or more scriptures first.')->warning()->send();
            return;
        }
        $created = 0;
        foreach ($rows as $row) {
            $ok = $this->createScripturePost(
                verse: (string) ($row['text'] ?? ''),
                reference: (string) ($row['reference'] ?? ''),
                topic: (string) ($row['topic'] ?? ''),
                batchName: trim($this->scriptureCardBatchName) !== '' ? trim($this->scriptureCardBatchName) : 'Default Batch',
                status: $this->scriptureCardStatus,
            );
            if ($ok) $created++;
        }
        if ($created > 0) {
            $this->activeQuoteTab = 'daily_scripture';
            $this->closeModal();
            $this->refreshEngineData();
            Notification::make()->title($created.' daily scripture card(s) created')->success()->send();
            $this->dispatch('quote-engine-keep-position');
        }
    }

    private function selectedScriptureRows(): array
    {
        $rows = [];
        foreach ($this->selectedScriptureReferences as $reference) {
            $row = $this->scriptureRowByReference((string) $reference);
            if ($row) $rows[] = $row;
        }
        return $rows;
    }

    private function scriptureRowByReference(string $reference): ?array
    {
        $reference = trim($reference);
        if ($reference === '') return null;
        foreach ($this->scriptureSearchResults as $row) {
            if (trim((string) ($row['reference'] ?? '')) === $reference) return $row;
        }
        foreach ($this->scriptureLibraryCatalog() as $row) {
            if (trim((string) ($row['reference'] ?? '')) === $reference) return $row;
        }
        $app = $this->currentApp ?: $this->activeApp();
        foreach (app(BibleEngineService::class)->pickerCatalog($app?->id, '', 500) as $row) {
            if (trim((string) ($row['reference'] ?? '')) === $reference) return $row;
        }
        return null;
    }

    public function searchScriptureLibrary(): void
    {
        $query = mb_strtolower(trim($this->scriptureSearch));
        $catalog = $this->scriptureLibraryCatalog();

        if ($query === '') {
            $this->scriptureSearchResults = array_slice($catalog, 0, 12);
            return;
        }

        $tokens = collect(preg_split('/\s+/', $query) ?: [])
            ->map(fn ($token) => trim((string) $token))
            ->filter()
            ->values()
            ->all();

        $matches = [];

        foreach ($catalog as $row) {
            $haystack = mb_strtolower(implode(' ', [
                $row['reference'] ?? '',
                $row['topic'] ?? '',
                $row['text'] ?? '',
                implode(' ', $row['tags'] ?? []),
            ]));

            $score = 0;
            foreach ($tokens as $token) {
                if ($token !== '' && str_contains($haystack, $token)) {
                    $score += 10;
                }
            }

            if (str_contains(mb_strtolower((string) ($row['reference'] ?? '')), $query)) {
                $score += 50;
            }

            if (str_contains(mb_strtolower((string) ($row['topic'] ?? '')), $query)) {
                $score += 25;
            }

            if ($score > 0) {
                $row['score'] = $score;
                $matches[] = $row;
            }
        }

        usort($matches, fn ($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
        $this->scriptureSearchResults = array_slice($matches, 0, 18);
    }

    public function selectScriptureFromLibrary(string $reference): void
    {
        $reference = trim($reference);

        foreach ($this->scriptureLibraryCatalog() as $row) {
            if (trim((string) ($row['reference'] ?? '')) === $reference) {
                $this->selectedScriptureReference = (string) ($row['reference'] ?? '');
                $this->selectedScriptureText = (string) ($row['text'] ?? '');
                $this->selectedScriptureTopic = (string) ($row['topic'] ?? '');
                $this->scriptureSearch = $this->selectedScriptureReference;
                $this->scriptureSearchResults = [$row];
                return;
            }
        }

        Notification::make()
            ->title('Scripture not found')
            ->body('Search again or paste the verse manually.')
            ->warning()
            ->send();
    }

    public function createScriptureFromPicker(): void
    {
        $verse = trim($this->selectedScriptureText);
        $reference = trim($this->selectedScriptureReference);

        if ($verse === '') {
            $this->addError('selectedScriptureText', 'Select or enter the scripture text.');
            return;
        }

        if ($reference === '') {
            $this->addError('selectedScriptureReference', 'Enter the scripture reference.');
            return;
        }

        $created = $this->createScripturePost(
            verse: $verse,
            reference: $reference,
            topic: trim($this->selectedScriptureTopic),
            batchName: trim($this->scriptureCardBatchName) !== '' ? trim($this->scriptureCardBatchName) : 'Default Batch',
            status: $this->scriptureCardStatus,
        );

        if (! $created) {
            return;
        }

        $this->activeQuoteTab = 'daily_scripture';
        $this->closeModal();
        $this->refreshEngineData();

        Notification::make()
            ->title('Daily scripture card created')
            ->body($reference . ' is ready in the Daily Scripture manager.')
            ->success()
            ->send();

        $this->dispatch('quote-engine-keep-position');
    }

    public function openSchedule(string $bucket, string $batch = ''): void
    {
        $this->resetModalFields();
        $this->modal = 'schedule';
        $this->modalBucket = $this->normalizeBucket($bucket);
        $this->scheduleBatchName = trim($batch) !== '' ? trim($batch) : 'Default Batch';
        $this->scheduleStartDate = now()->toDateString();
        $this->scheduleEndDate = '';
        $this->scheduleFrequency = 'daily';
        $this->scheduleStatus = 'published';
        $this->scheduleFallback = 'keep_current';
    }

    public function closeModal(): void
    {
        $this->modal = '';
        $this->resetValidation();
    }

    public function createCategory(): void
    {
        $app = $this->activeApp();

        if (! $app) {
            Notification::make()->title('No active app selected')->danger()->send();
            return;
        }

        $label = trim($this->categoryLabel);

        if ($label === '') {
            $this->addError('categoryLabel', 'Enter a category name.');
            return;
        }

        $slug = Str::slug($label, '_');
        $bucket = 'quote_' . $slug . (str_ends_with($slug, '_quotes') ? '' : '_quotes');

        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $categories = is_array($branding['quote_engine_categories'] ?? null)
            ? $branding['quote_engine_categories']
            : [];

        foreach ($categories as $category) {
            if (($category['bucket'] ?? '') === $bucket) {
                $this->addError('categoryLabel', 'This category already exists.');
                return;
            }
        }

        $categories[] = [
            'key' => 'custom_' . $slug,
            'bucket' => $bucket,
            'label' => $label,
            'short_label' => Str::limit($label, 18, ''),
            'description' => trim($this->categoryDescription) !== ''
                ? trim($this->categoryDescription)
                : $label . ' quote cards.',
            'icon' => trim($this->categoryIcon) !== '' ? Str::upper(Str::limit(trim($this->categoryIcon), 4, '')) : 'Q',
            'is_custom' => true,
            'created_at' => now()->toIso8601String(),
        ];

        $branding['quote_engine_categories'] = $categories;

        $app->forceFill(['branding_json' => $branding])->save();

        $this->activeQuoteTab = 'custom_' . $slug;
        $this->closeModal();
        $this->refreshEngineData();

        Notification::make()
            ->title('Quote category created')
            ->body($label . ' is now available as a Quote Engine tab.')
            ->success()
            ->send();

        $this->dispatch('quote-engine-keep-position');
    }

    public function addSingleQuote(): void
    {
        $quote = trim($this->singleQuoteText);

        if ($quote === '') {
            $this->addError('singleQuoteText', 'Enter the quote text.');
            return;
        }

        $created = $this->createQuotePost(
            bucket: $this->modalBucket,
            quote: $quote,
            source: trim($this->singleQuoteSource),
            batchName: $this->modalBatchName ?: 'Default Batch',
            status: $this->singleQuoteStatus,
            imported: false,
        );

        if (! $created) {
            return;
        }

        $this->activeQuoteTab = $this->tabForBucket($this->modalBucket);
        $this->closeModal();
        $this->refreshEngineData();

        Notification::make()
            ->title('Quote added')
            ->success()
            ->send();

        $this->dispatch('quote-engine-keep-position');
    }

    public function bulkImport(string $bucket = ''): void
    {
        $bucket = $this->normalizeBucket($bucket !== '' ? $bucket : $this->modalBucket);

        $text = trim($this->bulkText);

        if ($this->bulkFile instanceof TemporaryUploadedFile) {
            $uploadedText = @file_get_contents($this->bulkFile->getRealPath());
            if (is_string($uploadedText) && trim($uploadedText) !== '') {
                $text = trim($text . "\n" . $uploadedText);
            }
        }

        $lines = $this->parseBulkQuoteLines($text);

        if (empty($lines)) {
            Notification::make()
                ->title('Nothing to import')
                ->body('Paste quotes or upload a .txt file. Use Quote text | Source for source names.')
                ->warning()
                ->send();

            return;
        }

        $batchName = trim($this->modalBatchName) !== ''
            ? trim($this->modalBatchName)
            : 'Imported Batch ' . now()->format('M d, Y H:i');

        $status = in_array($this->bulkStatus, ['draft', 'published'], true) ? $this->bulkStatus : 'draft';

        $created = 0;

        foreach ($lines as $row) {
            $quote = trim((string) ($row['quote'] ?? ''));

            if ($quote === '') {
                continue;
            }

            if ($this->createQuotePost(
                bucket: $bucket,
                quote: $quote,
                source: trim((string) ($row['source'] ?? '')),
                batchName: $batchName,
                status: $status,
                imported: true,
            )) {
                $created++;
            }
        }

        $this->activeQuoteTab = $this->tabForBucket($bucket);
        $this->closeModal();
        $this->refreshEngineData();

        Notification::make()
            ->title('Quotes imported')
            ->body($created . ' quote' . ($created === 1 ? '' : 's') . ' added to ' . $this->labelForBucket($bucket) . '.')
            ->success()
            ->send();

        $this->dispatch('quote-engine-keep-position');
    }


    public function scheduleBatch(): void
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);
        $bucket = $this->normalizeBucket($this->modalBucket);
        $batchName = trim($this->scheduleBatchName) !== '' ? trim($this->scheduleBatchName) : 'Default Batch';

        if ($appId <= 0 || ! Schema::hasTable('content_posts')) {
            Notification::make()
                ->title('No active app found')
                ->body('Select an app before scheduling quotes.')
                ->danger()
                ->send();

            return;
        }

        try {
            $start = Carbon::parse($this->scheduleStartDate ?: now()->toDateString())->startOfDay();
        } catch (\Throwable) {
            $this->addError('scheduleStartDate', 'Enter a valid start date.');
            return;
        }

        $end = null;

        if (trim($this->scheduleEndDate) !== '') {
            try {
                $end = Carbon::parse($this->scheduleEndDate)->endOfDay();
            } catch (\Throwable) {
                $this->addError('scheduleEndDate', 'Enter a valid end date.');
                return;
            }
        }

        $frequency = in_array($this->scheduleFrequency, ['daily', 'weekly', 'monthly'], true)
            ? $this->scheduleFrequency
            : 'daily';

        $status = in_array($this->scheduleStatus, ['draft', 'published'], true)
            ? $this->scheduleStatus
            : 'published';

        $posts = ContentPost::query()
            ->where('app_id', $appId)
            ->where('bucket', $bucket)
            ->orderBy('id')
            ->get()
            ->filter(function (ContentPost $post) use ($batchName): bool {
                $meta = is_array($post->meta_json) ? $post->meta_json : [];
                $postBatch = trim((string) ($meta['quote_batch_name'] ?? 'Default Batch'));

                return $postBatch === $batchName;
            })
            ->values();

        if ($posts->isEmpty()) {
            Notification::make()
                ->title('No quotes in this batch')
                ->body('Add or import quotes before scheduling this batch.')
                ->warning()
                ->send();

            return;
        }

        $cursor = $start->copy();
        $scheduled = 0;

        foreach ($posts as $post) {
            if ($end && $cursor->greaterThan($end)) {
                break;
            }

            $meta = is_array($post->meta_json) ? $post->meta_json : [];
            $meta['schedule_status'] = 'scheduled';
            $meta['schedule_frequency'] = $frequency;
            $meta['schedule_batch_name'] = $batchName;
            $meta['quote_batch_name'] = $batchName;
            $meta['scheduled_for'] = $cursor->toDateString();
            $meta['schedule_start_date'] = $start->toDateString();
            $meta['schedule_end_date'] = $end ? $end->toDateString() : null;
            $meta['schedule_fallback'] = $this->scheduleFallback;
            $meta['schedule_updated_at'] = now()->toDateTimeString();

            $post->forceFill([
                'status' => $status,
                'publish_at' => $cursor->copy(),
                'published_at' => $status === 'published' ? ($post->published_at ?: now()) : null,
                'meta_json' => $meta,
            ])->save();

            $scheduled++;

            if ($frequency === 'weekly') {
                $cursor->addWeek();
            } elseif ($frequency === 'monthly') {
                $cursor->addMonthNoOverflow();
            } else {
                $cursor->addDay();
            }
        }

        $this->activeQuoteTab = $this->tabForBucket($bucket);
        $this->closeModal();
        $this->refreshEngineData();

        Notification::make()
            ->title('Batch scheduled')
            ->body($scheduled . ' quote(s) scheduled from ' . $start->toFormattedDateString() . '.')
            ->success()
            ->send();

        $this->dispatch('quote-engine-keep-position');
    }

    public function toggleQuote(int $id): void
    {
        $post = $this->findCurrentAppPost($id);

        if (! $post) {
            return;
        }

        $post->status = $post->status === 'published' ? 'draft' : 'published';
        $post->published_at = $post->status === 'published' ? now() : null;
        $post->save();

        $this->refreshEngineData();

        Notification::make()
            ->title($post->status === 'published' ? 'Quote published' : 'Quote moved to draft')
            ->success()
            ->send();

        $this->dispatch('quote-engine-keep-position');
    }

    public function toggleDailyFeature(int $id): void
    {
        $post = $this->findCurrentAppPost($id);

        if (! $post) {
            return;
        }

        $meta = is_array($post->meta_json) ? $post->meta_json : [];
        $currentlyFeatured = $this->hasDailyFeatureFlag($post);

        if ($currentlyFeatured) {
            $meta['show_in_daily_quote'] = false;
            $meta['show_in_daily_carousel'] = false;
            $meta['featured_daily'] = false;
            $meta['daily_feature_removed_at'] = now()->toDateTimeString();

            $post->forceFill([
                'is_featured' => false,
                'meta_json' => $meta,
            ])->save();

            Notification::make()
                ->title('Removed from frontend daily carousel')
                ->body('The quote remains in its category, but it will not be sent to the Home Daily Quote carousel.')
                ->success()
                ->send();
        } else {
            $meta['show_in_daily_quote'] = true;
            $meta['show_in_daily_carousel'] = true;
            $meta['featured_daily'] = true;
            $meta['daily_featured_at'] = now()->toDateTimeString();

            $post->forceFill([
                'status' => 'published',
                'published_at' => $post->published_at ?: now(),
                'is_featured' => true,
                'meta_json' => $meta,
            ])->save();

            Notification::make()
                ->title('Featured for frontend daily carousel')
                ->body('This quote can now appear in the Home Daily Quote carousel API payload.')
                ->success()
                ->send();
        }

        $this->refreshEngineData();
        $this->dispatch('quote-engine-keep-position');
    }

    public function copyQuote(int $id): void
    {
        $post = $this->findCurrentAppPost($id);

        if (! $post) {
            return;
        }

        $copy = $post->replicate();
        $copy->title = Str::limit((string) $post->title . ' Copy', 190, '');
        $copy->slug = null;
        $copy->status = 'draft';
        $copy->published_at = null;
        $copy->meta_json = array_merge(is_array($post->meta_json) ? $post->meta_json : [], [
            'copied_from_id' => $post->id,
            'quote_batch_name' => trim((string) data_get($post->meta_json, 'quote_batch_name', 'Default Batch')) ?: 'Default Batch',
        ]);
        $copy->save();

        $this->refreshEngineData();

        Notification::make()
            ->title('Quote copied')
            ->body('A draft copy has been created.')
            ->success()
            ->send();

        $this->dispatch('quote-engine-keep-position');
    }

    public function deleteQuote(int $id): void
    {
        $post = $this->findCurrentAppPost($id);

        if (! $post) {
            return;
        }

        $post->delete();

        $this->refreshEngineData();

        Notification::make()
            ->title('Quote deleted')
            ->success()
            ->send();

        $this->dispatch('quote-engine-keep-position');
    }

    public function clearBatch(string $bucket, string $batch): void
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);

        if ($appId <= 0 || ! Schema::hasTable('content_posts')) {
            return;
        }

        $bucket = $this->normalizeBucket($bucket);
        $batch = trim($batch);

        ContentPost::query()
            ->where('app_id', $appId)
            ->where('bucket', $bucket)
            ->get()
            ->each(function (ContentPost $post) use ($batch): void {
                $meta = is_array($post->meta_json) ? $post->meta_json : [];
                $postBatch = trim((string) ($meta['quote_batch_name'] ?? 'Default Batch'));

                if ($postBatch === $batch) {
                    $post->delete();
                }
            });

        $this->refreshEngineData();

        Notification::make()
            ->title('Batch cleared')
            ->body($batch . ' was cleared.')
            ->success()
            ->send();

        $this->dispatch('quote-engine-keep-position');
    }


    private function createScripturePost(
        string $verse,
        string $reference,
        string $topic,
        string $batchName,
        string $status,
    ): bool {
        $appId = (int) (ActiveApp::ensureId() ?? 0);

        if ($appId <= 0 || ! Schema::hasTable('content_posts')) {
            Notification::make()
                ->title('No active app found')
                ->body('Select an app before creating scripture cards.')
                ->danger()
                ->send();

            return false;
        }

        $status = in_array($status, ['draft', 'published'], true) ? $status : 'draft';
        $batchName = trim($batchName) !== '' ? trim($batchName) : 'Default Batch';

        $meta = array_merge($this->defaultDesignerMeta(), [
            'quote_text' => $verse,
            'quote_source' => $reference,
            'scripture_text' => $verse,
            'verse' => $verse,
            'reference' => $reference,
            'ref' => $reference,
            'scripture_reference' => $reference,
            'source' => $reference,
            'scripture_topic' => $topic,
            'quote_bucket' => 'daily_scriptures',
            'quote_batch_name' => $batchName,
            'show_in_daily_scripture' => true,
            'show_in_daily_carousel' => true,
            'scripture_picker_source' => 'starter_library',
            'schedule_status' => 'unscheduled',
            'schedule_frequency' => null,
            'scheduled_for' => null,
            'schedule_start_date' => null,
            'schedule_end_date' => null,
            'schedule_fallback' => 'keep_current',
        ]);

        ContentPost::query()->create([
            'app_id' => $appId,
            'bucket' => 'daily_scriptures',
            'status' => $status,
            'title' => Str::limit($verse, 80, ''),
            'subtitle' => $reference,
            'body_html' => e($verse),
            'meta_json' => $meta,
            'blocks_json' => [[
                'type' => 'scripture',
                'scripture_text' => $verse,
                'reference' => $reference,
                'meta' => $meta,
            ]],
            'tags_json' => array_values(array_filter(['Daily Scripture', $topic])),
            'published_at' => $status === 'published' ? now() : null,
            'is_featured' => true,
            'sort_order' => 0,
        ]);

        return true;
    }

    private function createQuotePost(
        string $bucket,
        string $quote,
        string $source,
        string $batchName,
        string $status,
        bool $imported = false,
    ): bool {
        $appId = (int) (ActiveApp::ensureId() ?? 0);

        if ($appId <= 0 || ! Schema::hasTable('content_posts')) {
            Notification::make()
                ->title('No active app found')
                ->body('Select an app before creating quotes.')
                ->danger()
                ->send();

            return false;
        }

        $bucket = $this->normalizeBucket($bucket);
        $status = in_array($status, ['draft', 'published'], true) ? $status : 'draft';
        $batchName = trim($batchName) !== '' ? trim($batchName) : 'Default Batch';

        $meta = array_merge($this->defaultDesignerMeta(), [
            'quote_text' => $quote,
            'quote_source' => $source,
            'source' => $source,
            'quote_bucket' => $bucket,
            'quote_batch_name' => $batchName,
            'schedule_status' => 'unscheduled',
            'schedule_frequency' => null,
            'scheduled_for' => null,
            'schedule_start_date' => null,
            'schedule_end_date' => null,
            'schedule_fallback' => 'keep_current',
            'imported_from_quote_engine' => $imported,
        ]);

        ContentPost::query()->create([
            'app_id' => $appId,
            'bucket' => $bucket,
            'status' => $status,
            'title' => Str::limit($quote, 80, ''),
            'subtitle' => $source,
            'body_html' => e($quote),
            'meta_json' => $meta,
            'blocks_json' => [[
                'type' => 'quote',
                'quote_text' => $quote,
                'quote_source' => $source,
                'meta' => $meta,
            ]],
            'tags_json' => [$this->labelForBucket($bucket)],
            'published_at' => $status === 'published' ? now() : null,
            'is_featured' => false,
            'sort_order' => 0,
        ]);

        return true;
    }

    private function refreshEngineData(): void
    {
        $this->currentApp = $this->activeApp();

        $this->tabs = $this->quoteTabs();
        $this->dailyCards = [
            $this->dailySummary('quote'),
            $this->dailySummary('scripture'),
        ];
        $this->frontendQuoteCards = $this->frontendFeaturedRows('quote');
        $this->frontendScriptureCards = $this->frontendFeaturedRows('scripture');

        $this->quoteSets = collect($this->tabs)
            ->reject(fn (array $tab): bool => $tab['key'] === 'all')
            ->map(fn (array $tab): array => $this->quoteSetForTab($tab))
            ->values()
            ->all();

        $this->stats = [
            'daily_ready' => collect($this->dailyCards)->where('enabled', true)->count(),
            'frontend_quotes' => count($this->frontendQuoteCards),
            'frontend_scriptures' => count($this->frontendScriptureCards),
            'categories' => collect($this->tabs)->where('key', '!=', 'all')->count(),
            'published' => collect($this->quoteSets)->sum('published'),
            'drafts' => collect($this->quoteSets)->sum('drafts'),
            'total_quotes' => collect($this->quoteSets)->sum('count'),
        ];
    }

    private function activeApp(): ?App
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);

        return $appId > 0 ? App::query()->find($appId) : null;
    }

    private function frontendFeaturedRows(string $kind): array
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);

        if ($appId <= 0 || ! Schema::hasTable('content_posts')) {
            return [];
        }

        $query = ContentPost::query()
            ->where('app_id', $appId)
            ->where('status', 'published')
            ->latest('id')
            ->limit(180);

        if ($kind === 'scripture') {
            // Match the actual backend/front-end reality for Daily Scripture:
            // published daily_scriptures rows PLUS the legacy Active Daily Card
            // that is still rendered from the Home daily scripture builder.
            $query->where(function ($q): void {
                $q->where('bucket', 'daily_scriptures')
                    ->orWhere('bucket', 'like', '%scripture%');
            });
        } else {
            // Mirror HubController::dailyQuoteItems(): daily_quotes are always
            // eligible, and other quote/motivation/SOD/custom/dynamic buckets
            // are eligible when featured/daily flagged.
            $query->where(function ($q): void {
                $q->where('bucket', 'daily_quotes')
                    ->orWhere(function ($inner): void {
                        $inner->where(function ($b): void {
                            $b->where('bucket', 'like', '%quote%')
                                ->orWhere('bucket', 'like', '%motivation%')
                                ->orWhere('bucket', 'sod_quotes')
                                ->orWhere('bucket', 'article_quotes')
                                ->orWhere('bucket', 'custom_quotes');
                        });
                    });
            });
        }

        $rows = $query->get()
            ->filter(function (ContentPost $post) use ($kind): bool {
                $bucket = $this->normalizeBucket((string) $post->bucket);

                if ($kind === 'scripture') {
                    return $bucket === 'daily_scriptures' || str_contains($bucket, 'scripture') || $this->hasDailyFeatureFlag($post);
                }

                if ($bucket === 'daily_quotes') {
                    return true;
                }

                return $this->hasDailyFeatureFlag($post);
            })
            ->take(80)
            ->map(fn (ContentPost $post): array => $this->frontendFeaturedRow($post, $kind))
            ->values()
            ->all();

        if ($kind === 'scripture') {
            $daily = $this->dailySummary('scripture');
            $mainText = trim((string) ($daily['main_text'] ?? ''));

            if ($mainText !== '') {
                $virtual = $this->frontendVirtualDailyRow($daily, 'scripture');
                $virtualTitle = mb_strtolower(trim((string) $virtual['title']));

                $alreadyListed = collect($rows)->contains(function (array $row) use ($virtualTitle): bool {
                    return mb_strtolower(trim((string) ($row['title'] ?? ''))) === $virtualTitle;
                });

                if (! $alreadyListed) {
                    array_unshift($rows, $virtual);
                }
            }
        }

        return array_values($rows);
    }

    private function frontendFeaturedRow(ContentPost $post, string $kind): array
    {
        $meta = is_array($post->meta_json) ? $post->meta_json : [];
        $quote = trim((string) ($meta['quote_text'] ?? $meta['scripture_text'] ?? $meta['verse'] ?? $post->title ?? strip_tags((string) $post->body_html)));
        $source = trim((string) ($meta['quote_source'] ?? $meta['source'] ?? $meta['reference'] ?? $meta['ref'] ?? $post->subtitle ?? $post->author_name ?? ''));
        $bucket = $this->normalizeBucket((string) $post->bucket);

        return [
            'id' => $post->id,
            'bucket' => $bucket,
            'bucket_label' => $this->labelForBucket($bucket),
            'title' => Str::limit($quote !== '' ? $quote : 'Untitled card', 120),
            'source' => $source,
            'status' => (string) ($post->status ?? 'draft'),
            'daily_featured' => $this->hasDailyFeatureFlag($post) || in_array($bucket, ['daily_quotes', 'daily_scriptures'], true),
            'publish_at' => $post->publish_at ? $post->publish_at->toDateString() : '',
            'edit_url' => url('/admin/beginner/quote-designer/' . $post->id . '/edit?return=quote-engine&quote_tab=' . urlencode($kind === 'scripture' ? 'frontend_scriptures' : 'frontend_quotes')),
        ];
    }

    private function frontendVirtualDailyRow(array $daily, string $kind): array
    {
        $mainText = trim((string) ($daily['main_text'] ?? ''));
        $source = trim((string) ($daily['support_text'] ?? ''));
        $label = $kind === 'scripture' ? 'Active Daily Scripture Card' : 'Active Daily Quote Card';

        return [
            'id' => 'active-daily-' . $kind,
            'bucket' => $kind === 'scripture' ? 'home_daily_scripture' : 'home_daily_quote',
            'bucket_label' => $label,
            'title' => Str::limit($mainText !== '' ? $mainText : ($daily['title'] ?? $label), 120),
            'source' => $source,
            'status' => (string) ($daily['status'] ?? 'Ready'),
            'daily_featured' => true,
            'publish_at' => '',
            'edit_url' => (string) ($daily['edit_url'] ?? '#'),
            'is_virtual' => true,
        ];
    }

    private function hasDailyFeatureFlag(ContentPost $post): bool
    {
        $meta = is_array($post->meta_json) ? $post->meta_json : [];

        return (bool) ($post->is_featured ?? false)
            || filter_var($meta['show_in_daily_quote'] ?? false, FILTER_VALIDATE_BOOLEAN)
            || filter_var($meta['show_in_daily_carousel'] ?? false, FILTER_VALIDATE_BOOLEAN)
            || filter_var($meta['featured_daily'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function dailySummary(string $kind): array
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);
        $homeKind = $kind === 'scripture' ? 'daily_scripture' : 'daily_quote';
        $sectionKey = $kind === 'scripture' ? 'home_daily_scripture' : 'home_daily_quote';

        $item = null;

        if ($appId > 0 && class_exists(AppSection::class) && class_exists(AppItem::class)) {
            $section = AppSection::query()
                ->where('app_id', $appId)
                ->where('key', $sectionKey)
                ->first();

            if ($section) {
                $items = AppItem::query()
                    ->where('section_id', $section->id)
                    ->orderByDesc('id')
                    ->get();

                $item = $items->first(function (AppItem $row) use ($homeKind) {
                    $payload = is_array($row->payload_json) ? $row->payload_json : [];

                    return ($payload['home_kind'] ?? null) === $homeKind
                        || ($payload['kind'] ?? null) === $homeKind
                        || ($payload['type'] ?? null) === $homeKind;
                });

                // Older daily cards may not have home_kind saved. If the section itself is the
                // daily quote/scripture section, the latest item should still be treated as valid.
                $item ??= $items->first();
            }
        }

        $payload = $item && is_array($item->payload_json) ? $item->payload_json : [];
        $meta = $item && is_array($item->meta_json ?? null) ? $item->meta_json : [];
        $merged = array_merge($meta, $payload);

        $mainText = $kind === 'scripture'
            ? $this->firstText($merged, ['verse', 'scripture', 'scripture_text', 'main_text', 'text', 'quote', 'quote_text'])
            : $this->firstText($merged, ['quote', 'quote_text', 'main_text', 'text', 'verse']);

        $supportText = $kind === 'scripture'
            ? $this->firstText($merged, ['ref', 'reference', 'scripture_reference', 'source', 'quote_source'])
            : $this->firstText($merged, ['source', 'quote_source', 'ref', 'reference']);

        return [
            'kind' => $kind,
            'label' => $kind === 'scripture' ? 'Daily Scripture' : 'Daily Quote',
            'description' => $kind === 'scripture'
                ? 'Home scripture card with verse, reference, note, and designer style.'
                : 'Home quote card with quote text, source, and designer style.',
            'icon' => $kind === 'scripture' ? 'B' : 'Q',
            'enabled' => (bool) ($item?->is_enabled ?? filled($mainText)),
            'title' => (string) ($item?->title ?? ($kind === 'scripture' ? 'Daily Scripture' : 'Daily Quote')),
            'main_text' => $mainText,
            'support_text' => $supportText,
            'background_mode' => (string) ($merged['background_mode'] ?? $merged['background'] ?? 'gradient'),
            'style_preset' => (string) ($merged['style_preset'] ?? 'custom'),
            'edit_url' => url('/admin/beginner/daily/' . $kind . '/edit?return=quote-engine'),
            'status' => $item || filled($mainText) ? 'Ready' : 'Needs Setup',
        ];
    }

    private function firstText(array $data, array $keys): string
    {
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_numeric($value)) {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function quoteSetForTab(array $tab): array
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);
        $bucket = $this->normalizeBucket((string) ($tab['bucket'] ?? ''));

        $posts = ($appId > 0 && Schema::hasTable('content_posts') && $bucket !== '')
            ? ContentPost::query()
                ->where('app_id', $appId)
                ->where('bucket', $bucket)
                ->latest('id')
                ->get()
            : collect();

        $virtualDailyItems = [];

        if (in_array($bucket, ['daily_quotes', 'daily_scriptures'], true)) {
            $dailyKind = $bucket === 'daily_scriptures' ? 'scripture' : 'quote';
            $daily = $this->dailySummary($dailyKind);

            if (filled($daily['main_text'] ?? '')) {
                $virtualDailyItems[] = [
                    'id' => 'daily-' . $dailyKind,
                    'is_virtual' => true,
                    'title' => Str::limit((string) $daily['main_text'], 90),
                    'source' => (string) ($daily['support_text'] ?? ''),
                    'status' => (string) ($daily['status'] ?? 'Ready'),
                    'enabled' => (bool) ($daily['enabled'] ?? true),
                    'edit_url' => (string) ($daily['edit_url'] ?? '#'),
                ];
            }
        }

        $groups = $posts
            ->groupBy(function (ContentPost $post): string {
                $meta = is_array($post->meta_json) ? $post->meta_json : [];

                return trim((string) ($meta['quote_batch_name'] ?? 'Default Batch')) ?: 'Default Batch';
            })
            ->map(function ($rows, string $batch) use ($bucket): array {
                $published = $rows->where('status', 'published')->count();

                return [
                    'name' => $batch,
                    'bucket' => $bucket,
                    'count' => $rows->count(),
                    'published' => $published,
                    'drafts' => $rows->count() - $published,
                    'items' => $rows->map(fn (ContentPost $post): array => $this->quoteRow($post))->values()->all(),
                ];
            })
            ->values()
            ->all();

        if (! empty($virtualDailyItems)) {
            array_unshift($groups, [
                'name' => 'Active Daily Card',
                'bucket' => $bucket,
                'count' => count($virtualDailyItems),
                'published' => collect($virtualDailyItems)->where('enabled', true)->count(),
                'drafts' => collect($virtualDailyItems)->where('enabled', false)->count(),
                'items' => $virtualDailyItems,
                'is_virtual' => true,
            ]);
        }

        if (empty($groups)) {
            $groups = [[
                'name' => 'Default Batch',
                'bucket' => $bucket,
                'count' => 0,
                'published' => 0,
                'drafts' => 0,
                'items' => [],
            ]];
        }

        $virtualCount = count($virtualDailyItems);
        $virtualPublished = collect($virtualDailyItems)->where('enabled', true)->count();

        return [
            'key' => $tab['key'],
            'bucket' => $bucket,
            'label' => $tab['label'],
            'short_label' => $tab['short_label'] ?? $tab['label'],
            'description' => $tab['description'],
            'icon' => $tab['icon'],
            'create_url' => url('/admin/beginner/quote-designer/create?bucket=' . urlencode($bucket) . '&return=quote-engine'),
            'count' => $posts->count() + $virtualCount,
            'published' => $posts->where('status', 'published')->count() + $virtualPublished,
            'drafts' => $posts->where('status', '!=', 'published')->count() + ($virtualCount - $virtualPublished),
            'groups' => $groups,
            'status' => ($posts->count() + $virtualCount) > 0 ? 'Active' : 'Empty',
        ];
    }

    private function quoteRow(ContentPost $post): array
    {
        $meta = is_array($post->meta_json) ? $post->meta_json : [];
        $quote = trim((string) ($meta['quote_text'] ?? $post->title ?? strip_tags((string) $post->body_html)));
        $source = trim((string) ($meta['quote_source'] ?? $meta['source'] ?? $post->subtitle ?? $post->author_name ?? ''));

        return [
            'id' => $post->id,
            'title' => Str::limit($quote !== '' ? $quote : 'Untitled quote', 90),
            'source' => $source,
            'status' => (string) ($post->status ?? 'draft'),
            'enabled' => ($post->status ?? 'draft') === 'published',
            'daily_featured' => $this->hasDailyFeatureFlag($post),
            'edit_url' => url('/admin/beginner/quote-designer/' . $post->id . '/edit?return=quote-engine&quote_tab=' . urlencode($this->activeQuoteTab)),
            'publish_at' => $post->publish_at ? $post->publish_at->toDateString() : null,
            'schedule_status' => (string) ($meta['schedule_status'] ?? 'unscheduled'),
            'scheduled_for' => (string) ($meta['scheduled_for'] ?? ($post->publish_at ? $post->publish_at->toDateString() : '')),
            'schedule_frequency' => (string) ($meta['schedule_frequency'] ?? ''),
        ];
    }

    private function quoteTabs(): array
    {
        $defaultTabs = [
            [
                'key' => 'all',
                'bucket' => '',
                'label' => 'All',
                'short_label' => 'All',
                'description' => 'Every quote category in one view.',
                'icon' => '∞',
            ],
            [
                'key' => 'daily_quote',
                'bucket' => 'daily_quotes',
                'label' => 'Daily Quote',
                'short_label' => 'Daily Quote',
                'description' => 'Quote cards prepared for daily home display.',
                'icon' => 'Q',
            ],
            [
                'key' => 'daily_scripture',
                'bucket' => 'daily_scriptures',
                'label' => 'Daily Scripture',
                'short_label' => 'Scripture',
                'description' => 'Scripture cards prepared for daily home display.',
                'icon' => 'B',
            ],
            [
                'key' => 'sod_quotes',
                'bucket' => 'sod_quotes',
                'label' => 'SOD Quotes',
                'short_label' => 'SOD Quotes',
                'description' => 'Seeds of Destiny and devotional quote cards.',
                'icon' => 'SOD',
            ],
            [
                'key' => 'motivational_quotes',
                'bucket' => 'motivational_quotes',
                'label' => 'Motivational Quotes',
                'short_label' => 'Motivation',
                'description' => 'Motivation and encouragement quote cards.',
                'icon' => 'M',
            ],
            [
                'key' => 'article_quotes',
                'bucket' => 'article_quotes',
                'label' => 'Article Quotes',
                'short_label' => 'Article',
                'description' => 'Extracted quotes from articles and teachings.',
                'icon' => 'A',
            ],
            [
                'key' => 'custom_quotes',
                'bucket' => 'custom_quotes',
                'label' => 'Custom Quotes',
                'short_label' => 'Custom',
                'description' => 'Any custom quote category or special campaign.',
                'icon' => 'C',
            ],
        ];

        $app = $this->currentApp ?: $this->activeApp();
        $branding = $app && is_array($app->branding_json) ? $app->branding_json : [];
        $custom = is_array($branding['quote_engine_categories'] ?? null)
            ? $branding['quote_engine_categories']
            : [];

        foreach ($custom as $category) {
            $key = trim((string) ($category['key'] ?? ''));
            $bucket = trim((string) ($category['bucket'] ?? ''));
            $label = trim((string) ($category['label'] ?? ''));

            if ($key === '' || $bucket === '' || $label === '') {
                continue;
            }

            $defaultTabs[] = [
                'key' => $key,
                'bucket' => $bucket,
                'label' => $label,
                'short_label' => (string) ($category['short_label'] ?? Str::limit($label, 18, '')),
                'description' => (string) ($category['description'] ?? $label . ' quote cards.'),
                'icon' => (string) ($category['icon'] ?? 'Q'),
                'is_custom' => true,
            ];
        }

        return $defaultTabs;
    }


    private function scriptureLibraryCatalog(): array
    {
        $app = $this->currentApp ?: $this->activeApp();

        return app(BibleEngineService::class)->pickerCatalog($app?->id, $this->scriptureSearch, 120);
    }

    private function parseBulkQuoteLines(string $text): array
    {
        $clean = trim(str_replace(["\r\n", "\r"], "\n", $text));

        if ($clean === '') {
            return [];
        }

        $chunks = preg_split("/\n\s*\n/", $clean) ?: [];

        if (count($chunks) <= 1) {
            $chunks = preg_split("/\n/", $clean) ?: [];
        }

        $rows = [];

        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);

            if ($chunk === '') {
                continue;
            }

            $source = '';

            if (str_contains($chunk, '|')) {
                [$quote, $source] = array_map('trim', explode('|', $chunk, 2));
            } elseif (preg_match('/^(.*?)\s+[-–—]\s+([^-–—]{2,80})$/u', $chunk, $match)) {
                $quote = trim($match[1]);
                $source = trim($match[2]);
            } else {
                $quote = $chunk;
            }

            $quote = trim((string) $quote, " \t\n\r\0\x0B\"“”");

            if ($quote !== '') {
                $rows[] = [
                    'quote' => $quote,
                    'source' => $source,
                ];
            }
        }

        return $rows;
    }

    private function findCurrentAppPost(int $id): ?ContentPost
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);

        if ($appId <= 0 || ! Schema::hasTable('content_posts')) {
            return null;
        }

        return ContentPost::query()
            ->where('app_id', $appId)
            ->whereKey($id)
            ->first();
    }

    private function resetModalFields(): void
    {
        $this->resetValidation();

        $this->singleQuoteText = '';
        $this->singleQuoteSource = '';
        $this->scriptureSearch = '';
        $this->scriptureSearchResults = [];
        $this->selectedScriptureReference = '';
        $this->selectedScriptureText = '';
        $this->selectedScriptureTopic = '';
        $this->scripturePickerMode = 'create';
        $this->selectedScriptureReferences = [];
        $this->scriptureCardStatus = 'published';
        $this->scriptureCardBatchName = 'Default Batch';
        $this->singleQuoteStatus = 'draft';
        $this->bulkText = '';
        $this->bulkStatus = 'draft';
        $this->bulkFile = null;
        $this->scheduleBatchName = '';
        $this->scheduleStartDate = '';
        $this->scheduleEndDate = '';
        $this->scheduleFrequency = 'daily';
        $this->scheduleStatus = 'published';
        $this->scheduleFallback = 'keep_current';
        $this->categoryLabel = '';
        $this->categoryDescription = '';
        $this->categoryIcon = 'Q';
    }

    private function normalizeBucket(string $bucket): string
{
    $bucket = strtolower(trim($bucket));
    $bucket = str_replace(['-', ' '], '_', $bucket);
    $bucket = preg_replace('/[^a-z0-9_]/', '', $bucket) ?: '';

    return match ($bucket) {
        'daily_quote', 'daily_quotes', 'daily_quotes_quotes' => 'daily_quotes',
        'daily_scripture', 'daily_scriptures', 'scripture', 'scriptures' => 'daily_scriptures',
        'sod', 'sod_quote', 'sod_quotes', 'quote', 'quotes' => 'sod_quotes',
        'motivation', 'motivation_quote', 'motivation_quotes', 'motivational_quote', 'motivational_quotes' => 'motivational_quotes',
        'article_quote', 'article_quotes' => 'article_quotes',
        'custom_quote', 'custom_quotes' => 'custom_quotes',
        default => $bucket !== '' ? $bucket : 'sod_quotes',
    };
}


    private function bucketForTab(string $tab): string
    {
        foreach ($this->quoteTabs() as $row) {
            if ($row['key'] === $tab) {
                return (string) ($row['bucket'] ?? '');
            }
        }

        return '';
    }

    private function tabForBucket(string $bucket): string
{
    $bucket = $this->normalizeBucket($bucket);

    foreach ($this->quoteTabs() as $row) {
        if (($row['bucket'] ?? '') === $bucket) {
            return (string) $row['key'];
        }
    }

    if (str_starts_with($bucket, 'quote_')) {
        return 'custom_' . preg_replace('/^quote_|_quotes$/', '', $bucket);
    }

    return $bucket !== '' ? $bucket : 'sod_quotes';
}


    private function labelForBucket(string $bucket): string
{
    $bucket = $this->normalizeBucket($bucket);

    foreach ($this->quoteTabs() as $row) {
        if (($row['bucket'] ?? '') === $bucket) {
            return (string) $row['label'];
        }
    }

    return Str::headline(preg_replace('/^quote_|_quotes$/', '', $bucket));
}


    private function defaultDesignerMeta(): array
    {
        return [
            'background_mode' => 'gradient',
            'bg_color' => '#280061',
            'bg_color_2' => '#E4007C',
            'text_color' => '#ffffff',
            'source_color' => '#FDE68A',
            'accent_color' => '#FACC15',
            'quote_size' => '42',
            'source_size' => '16',
            'source_weight' => '700',
            'font_weight' => '800',
            'text_align' => 'center',
            'content_width' => '86',
            'card_padding' => '34',
            'card_padding_x' => '34',
            'card_padding_y' => '34',
            'line_height' => '1.25',
            'text_shadow' => 'soft',
            'highlight_phrases' => '',
            'highlight_color' => '#FACC15',
            'highlight_scale' => '1.10',
            'highlight_weight' => '900',
            'show_quote_mark' => false,
            'overlay_strength' => '0',
        ];
    }
}