<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\ContentPost;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use App\Services\BibleEngine\BibleEngineService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BibleEngine extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Bible Engine';
    protected static ?string $navigationGroup = 'Shared Engines';
    protected static ?int $navigationSort = 10;
    protected static ?string $slug = 'bible-engine';

    protected static string $view = 'filament.pages.bible-engine';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('bible_engine');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('bible_engine');
    }

    public string $activeTab = 'dashboard';
    public string $searchQuery = 'faith';
    public string $selectedTopic = '';
    public string $selectedTranslation = '';

    public array $stats = [];
    public array $topics = [];
    public array $translations = [];
    public array $collections = [];
    public array $searchResults = [];
    public array $selectedVerses = [];

    public string $collectionTitle = '';
    public string $collectionDescription = '';

    public string $dailyBatchName = 'Bible Engine Batch';
    public string $dailyBatchStartDate = '';
    public string $dailyBatchStatus = 'published';


    public function mount(): void
    {
        $this->dailyBatchStartDate = now()->toDateString();
        $this->refreshWorkspace();
        $this->searchBible();
    }

    public function selectTab(string $tab): void
    {
        $allowed = ['dashboard', 'picker', 'batch', 'collections', 'library'];
        $this->activeTab = in_array($tab, $allowed, true) ? $tab : 'dashboard';
        $this->refreshWorkspace();
    }

    public function refreshWorkspace(): void
    {
        $this->stats = [
            'translations' => $this->countTable('bible_translations'),
            'books' => $this->countTable('bible_books'),
            'chapters' => $this->countTable('bible_chapters'),
            'verses' => $this->countTable('bible_verses'),
            'topics' => $this->countTable('bible_topics'),
            'collections' => $this->countTable('scripture_collections'),
        ];

        $this->translations = $this->loadTranslations();
        $this->topics = $this->loadTopics();
        $this->collections = $this->loadCollections();
    }

    public function searchBible(): void
    {
        if (! Schema::hasTable('bible_verses')) {
            $this->searchResults = [];
            return;
        }

        $query = trim($this->searchQuery);
        if ($query === '' && trim($this->selectedTopic) !== '') {
            $query = trim($this->selectedTopic);
        }
        if ($query === '') {
            $query = 'faith';
        }

        $appId = (int) (ActiveApp::ensureId() ?? 0);
        $rows = app(BibleEngineService::class)->pickerCatalog($appId, $query, 120);

        $translation = trim($this->selectedTranslation);
        if ($translation !== '') {
            $rows = array_values(array_filter($rows, function (array $row) use ($translation): bool {
                return strtolower((string) ($row['translation_key'] ?? '')) === strtolower($translation);
            }));
        }

        $this->searchResults = array_slice($rows, 0, 80);
        $this->activeTab = 'picker';
    }

    public function searchTopic(string $topic): void
    {
        $this->selectedTopic = $topic;
        $this->searchQuery = $topic;
        $this->searchBible();
    }

    public function addVerseToTray(int $verseId): void
    {
        $row = $this->findVerseInCurrentResults($verseId) ?: $this->verseRow($verseId);
        if (! $row) {
            Notification::make()->title('Verse not found')->danger()->send();
            return;
        }

        foreach ($this->selectedVerses as $selected) {
            if ((int) ($selected['id'] ?? 0) === $verseId) {
                Notification::make()->title('Already selected')->body((string) ($row['reference'] ?? 'Verse'))->warning()->send();
                return;
            }
        }

        $this->selectedVerses[] = $row;

        Notification::make()
            ->title('Verse added')
            ->body((string) ($row['reference'] ?? 'Selected verse'))
            ->success()
            ->send();
    }

    public function addVisibleResultsToTray(): void
    {
        foreach (array_slice($this->searchResults, 0, 20) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0 && ! $this->trayHasVerse($id)) {
                $this->selectedVerses[] = $row;
            }
        }

        Notification::make()->title('Visible verses added')->success()->send();
    }

    public function removeVerseFromTray(int $verseId): void
    {
        $this->selectedVerses = array_values(array_filter($this->selectedVerses, fn (array $row): bool => (int) ($row['id'] ?? 0) !== $verseId));
    }

    public function clearTray(): void
    {
        $this->selectedVerses = [];
    }

    public function createCollectionFromTray(): void
    {
        if (empty($this->selectedVerses)) {
            Notification::make()->title('No verses selected')->body('Add scriptures to the tray first.')->warning()->send();
            return;
        }

        if (! Schema::hasTable('scripture_collections') || ! Schema::hasTable('scripture_collection_items')) {
            Notification::make()->title('Collection tables missing')->danger()->send();
            return;
        }

        $title = trim($this->collectionTitle) !== '' ? trim($this->collectionTitle) : $this->suggestedCollectionTitle();
        $now = now();

        $collectionId = DB::table('scripture_collections')->insertGetId([
            'app_id' => ActiveApp::ensureId(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::lower(Str::random(5)),
            'description' => trim($this->collectionDescription),
            'visibility' => ActiveApp::ensureId() ? 'app' : 'global',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (array_values($this->selectedVerses) as $index => $row) {
            DB::table('scripture_collection_items')->insert([
                'collection_id' => $collectionId,
                'verse_id' => (int) ($row['id'] ?? 0),
                'sort_order' => $index + 1,
                'custom_note' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->collectionTitle = '';
        $this->collectionDescription = '';
        $this->refreshWorkspace();

        Notification::make()->title('Scripture collection created')->body($title)->success()->send();
    }

    public function createDailyScriptureBatchFromTray(): void
    {
        if (empty($this->selectedVerses)) {
            Notification::make()->title('No verses selected')->body('Add scriptures to the tray first.')->warning()->send();
            return;
        }

        if (! Schema::hasTable('content_posts')) {
            Notification::make()->title('Content posts table missing')->danger()->send();
            return;
        }

        $appId = (int) (ActiveApp::ensureId() ?? 0);
        if ($appId <= 0) {
            Notification::make()->title('No active app selected')->body('Select an app before creating app daily scripture cards.')->danger()->send();
            return;
        }

        $status = in_array($this->dailyBatchStatus, ['draft', 'published'], true) ? $this->dailyBatchStatus : 'draft';
        $batchName = trim($this->dailyBatchName) !== '' ? trim($this->dailyBatchName) : 'Bible Engine Batch';
        $startDate = $this->safeDate($this->dailyBatchStartDate);
        $created = 0;

        foreach (array_values($this->selectedVerses) as $index => $row) {
            $publishDate = $startDate->copy()->addDays($index);
            $this->createDailyScripturePost($appId, $row, $batchName, $status, $publishDate);
            $created++;
        }

        Notification::make()
            ->title('Daily scripture batch created')
            ->body($created . ' scripture card(s) prepared from Bible Engine.')
            ->success()
            ->send();
    }

    private function createDailyScripturePost(int $appId, array $row, string $batchName, string $status, Carbon $publishDate): void
    {
        $verse = trim((string) ($row['text'] ?? ''));
        $reference = trim((string) ($row['reference'] ?? ''));
        $topic = trim((string) ($row['topic'] ?? 'Bible Engine'));
        $translationKey = trim((string) ($row['translation_key'] ?? ''));
        $translationName = trim((string) ($row['translation_name'] ?? ''));

        $meta = [
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
            'source_engine' => 'bible_engine',
            'bible_verse_id' => (int) ($row['id'] ?? 0),
            'translation_key' => $translationKey,
            'translation_name' => $translationName,
            'book' => (string) ($row['book'] ?? ''),
            'chapter' => (int) ($row['chapter'] ?? 0),
            'verse_number' => (int) ($row['verse'] ?? 0),
            'schedule_status' => 'scheduled',
            'schedule_frequency' => 'daily',
            'scheduled_for' => $publishDate->toDateString(),
            'schedule_start_date' => $this->safeDate($this->dailyBatchStartDate)->toDateString(),
            'schedule_end_date' => $this->safeDate($this->dailyBatchStartDate)->copy()->addDays(max(count($this->selectedVerses) - 1, 0))->toDateString(),
            'schedule_fallback' => 'keep_current',
            'background_mode' => 'gradient',
            'bg_color' => '#101A3F',
            'bg_color_2' => '#7C2D92',
            'text_color' => '#FFFFFF',
            'source_color' => '#FFE9A6',
            'card_format' => 'square',
            'format_ratio' => '1:1',
            'text_align' => 'center',
            'vertical_align' => 'center',
            'font_family' => 'default',
            'quote_size' => 26,
            'source_size' => 15,
            'card_padding' => 34,
            'content_width' => 88,
            'show_quote_mark' => true,
        ];

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
            'tags_json' => array_values(array_filter(['Daily Scripture', $topic, $translationKey])),
            'published_at' => $status === 'published' ? $publishDate : null,
            'is_featured' => true,
            'sort_order' => 0,
        ]);
    }

    private function countTable(string $table): int
    {
        return Schema::hasTable($table) ? (int) DB::table($table)->count() : 0;
    }

    private function loadTranslations(): array
    {
        if (! Schema::hasTable('bible_translations')) return [];

        return DB::table('bible_translations')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function loadTopics(): array
    {
        if (! Schema::hasTable('bible_topics')) return [];

        return DB::table('bible_topics')
            ->orderBy('name')
            ->limit(60)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function loadCollections(): array
    {
        if (! Schema::hasTable('scripture_collections')) return [];

        return DB::table('scripture_collections')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function findVerseInCurrentResults(int $verseId): ?array
    {
        foreach ($this->searchResults as $row) {
            if ((int) ($row['id'] ?? 0) === $verseId) {
                return $row;
            }
        }

        return null;
    }

    private function verseRow(int $verseId): ?array
    {
        if (! Schema::hasTable('bible_verses')) return null;

        $row = DB::table('bible_verses as v')
            ->leftJoin('bible_translations as t', 't.id', '=', 'v.translation_id')
            ->leftJoin('bible_books as b', 'b.id', '=', 'v.book_id')
            ->select([
                'v.id',
                'v.reference',
                'v.text',
                'v.chapter_number',
                'v.verse_number',
                'v.book_name',
                't.key as translation_key',
                't.name as translation_name',
                'b.name as book',
            ])
            ->where('v.id', $verseId)
            ->first();

        if (! $row) return null;

        return [
            'id' => (int) $row->id,
            'reference' => (string) $row->reference,
            'topic' => 'Bible Engine',
            'text' => (string) $row->text,
            'tags' => [],
            'translation_key' => (string) $row->translation_key,
            'translation_name' => (string) $row->translation_name,
            'book' => (string) ($row->book ?: $row->book_name),
            'chapter' => (int) $row->chapter_number,
            'verse' => (int) $row->verse_number,
            'source_engine' => 'bible_engine',
        ];
    }

    private function trayHasVerse(int $verseId): bool
    {
        foreach ($this->selectedVerses as $row) {
            if ((int) ($row['id'] ?? 0) === $verseId) return true;
        }

        return false;
    }

    private function suggestedCollectionTitle(): string
    {
        if (trim($this->selectedTopic) !== '') {
            return Str::title($this->selectedTopic) . ' Scriptures';
        }

        if (trim($this->searchQuery) !== '') {
            return Str::title($this->searchQuery) . ' Scriptures';
        }

        return 'Selected Scriptures';
    }

    private function safeDate(string $date): Carbon
    {
        try {
            return trim($date) !== '' ? Carbon::parse($date) : now();
        } catch (\Throwable) {
            return now();
        }
    }
}
