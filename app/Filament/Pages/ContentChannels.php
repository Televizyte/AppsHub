<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Models\Book;
use App\Models\ContentPost;
use App\Support\ActiveApp;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentChannels extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Content Channels';
    protected static ?string $navigationGroup = 'Workspace';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.content-channels';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('content_channels');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('content_channels');
    }

    public ?App $currentApp = null;

    public array $groups = [];
    public array $channels = [];
    public array $modules = [];
    public array $summary = [];


    public function mount(): void
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);

        $this->currentApp = $appId > 0 ? App::query()->find($appId) : null;
        $this->groups = $this->buildGroups();
        $this->channels = $this->buildChannels($appId);
        $this->modules = $this->buildModules($appId);
        $this->summary = $this->buildSummary($this->channels, $this->modules);
    }

    protected function buildGroups(): array
    {
        return [
            [
                'key' => 'reading',
                'label' => 'Reading Content',
                'description' => 'Articles, devotionals, teachings, updates, highlights, and written inspiration.',
                'icon_svg' => $this->svg('book'),
            ],
            [
                'key' => 'visuals',
                'label' => 'Visual Content',
                'description' => 'Quote cards, daily graphics, scripture designs, thumbnails, and shareable images.',
                'icon_svg' => $this->svg('image'),
            ],
            [
                'key' => 'video',
                'label' => 'Video Content',
                'description' => 'Short videos, clips, reels-style feeds, video posts, and message highlight media.',
                'icon_svg' => $this->svg('play'),
            ],
            [
                'key' => 'interactive',
                'label' => 'Interactive Content',
                'description' => 'Quiz content, learning prompts, Bible challenges, and future interactive modules.',
                'icon_svg' => $this->svg('puzzle'),
            ],
            [
                'key' => 'library',
                'label' => 'Library Content',
                'description' => 'Books, chapters, PDF/EPUB/Text reader content, and long-form library resources.',
                'icon_svg' => $this->svg('library'),
            ],
        ];
    }

    protected function channelMap(): array
    {
        return [
            'articles' => [
                'group' => 'reading',
                'label' => 'General Articles',
                'description' => 'General long-form articles, stories, guides, announcements, or teaching content.',
                'tone' => 'Article Library',
                'icon_svg' => $this->svg('document'),
                'kind' => 'article',
                'preview_mode' => 'article',
            ],
            'inside_dunamis' => [
                'group' => 'reading',
                'label' => 'News & Updates',
                'description' => 'Reusable update channel for app news, ministry updates, community posts, or featured stories.',
                'tone' => 'Updates',
                'icon_svg' => $this->svg('newspaper'),
                'kind' => 'article',
                'preview_mode' => 'article',
            ],
            'sod' => [
                'group' => 'reading',
                'label' => 'Devotionals',
                'description' => 'Daily or periodic devotional content. The title can be customized per app.',
                'tone' => 'Devotional',
                'icon_svg' => $this->svg('spark'),
                'kind' => 'devotional',
                'preview_mode' => 'article',
            ],
            'motivation' => [
                'group' => 'reading',
                'label' => 'Motivation',
                'description' => 'Encouragement posts, inspirational reading content, and short motivational messages.',
                'tone' => 'Inspiration',
                'icon_svg' => $this->svg('bolt'),
                'kind' => 'article',
                'preview_mode' => 'article',
            ],
            'wordification' => [
                'group' => 'reading',
                'label' => 'Word Study',
                'description' => 'Word-based teachings, reflections, studies, and structured learning content.',
                'tone' => 'Teaching',
                'icon_svg' => $this->svg('book-open'),
                'kind' => 'article',
                'preview_mode' => 'article',
            ],
            'highlights' => [
                'group' => 'reading',
                'label' => 'Message Highlights',
                'description' => 'Important takeaways, summaries, recap points, and highlighted messages.',
                'tone' => 'Highlights',
                'icon_svg' => $this->svg('star'),
                'kind' => 'article',
                'preview_mode' => 'article',
            ],
            'sod_quotes' => [
                'group' => 'visuals',
                'label' => 'Quote Cards',
                'description' => 'Reusable shareable quote cards, scripture graphics, and visual inspiration designs.',
                'tone' => 'Quote Designer',
                'icon_svg' => $this->svg('quote'),
                'kind' => 'quote',
                'preview_mode' => 'quote_card',
            ],
            'short_videos' => [
                'group' => 'video',
                'label' => 'Short Videos',
                'description' => 'Short video feed content with thumbnails, captions, duration, and playback metadata.',
                'tone' => 'Short Feed',
                'icon_svg' => $this->svg('play'),
                'kind' => 'video',
                'preview_mode' => 'video',
            ],
        ];
    }

    protected function buildChannels(int $appId): array
    {
        $channelMap = $this->channelMap();

        $posts = collect();

        if ($appId > 0) {
            $columns = [
                'id',
                'app_id',
                'bucket',
                'status',
                'title',
                'subtitle',
                'cover_image_url',
                'is_featured',
                'sort_order',
                'published_at',
                'updated_at',
                'created_at',
                'meta_json',
                'body_html',
            ];

            foreach (['body', 'content', 'excerpt', 'summary'] as $optionalColumn) {
                if (Schema::hasColumn('content_posts', $optionalColumn)) {
                    $columns[] = $optionalColumn;
                }
            }

            $posts = ContentPost::query()
                ->where('app_id', $appId)
                ->whereIn('bucket', array_keys($channelMap))
                ->orderByDesc('updated_at')
                ->get($columns);
        }

        return collect($channelMap)
            ->map(function (array $meta, string $bucket) use ($posts): array {
                $bucketPosts = $posts->where('bucket', $bucket)->values();

                return [
                    ...$this->emptyChannel($bucket, $meta),
                    'total' => $bucketPosts->count(),
                    'published' => $bucketPosts->where('status', 'published')->count(),
                    'draft' => $bucketPosts->where('status', 'draft')->count(),
                    'featured' => $bucketPosts->where('is_featured', true)->count(),
                    'latest' => $bucketPosts->take(12)->map(fn (ContentPost $post): array => $this->postPayload($post, $meta))->values()->all(),
                    'manage_url' => route('admin.beginner.content-posts.channel', ['bucket' => $bucket]),
                    'create_url' => $this->createUrl($bucket),
                    'is_module' => false,
                ];
            })
            ->values()
            ->all();
    }

    protected function buildModules(int $appId): array
    {
        $bookCount = 0;

        if ($appId > 0 && class_exists(Book::class)) {
            try {
                $bookCount = Book::query()->where('app_id', $appId)->count();
            } catch (\Throwable $e) {
                $bookCount = 0;
            }
        }

        $dailyScriptureUrl = Route::has('admin.beginner.daily.edit')
            ? route('admin.beginner.daily.edit', ['kind' => 'scripture'])
            : null;

        $dailyQuoteUrl = Route::has('admin.beginner.daily.edit')
            ? route('admin.beginner.daily.edit', ['kind' => 'quote'])
            : null;

        $booksUrl = Route::has('admin.beginner.books.index')
            ? route('admin.beginner.books.index')
            : null;

        $dailyScripture = $this->dailyCardPayload($appId, 'scripture');
        $dailyQuote = $this->dailyCardPayload($appId, 'quote');

        return [
            [
                'key' => 'daily_scripture',
                'group' => 'visuals',
                'label' => 'Daily Scripture',
                'description' => 'Manage the scripture card, scripture text, reference, and shareable scripture design.',
                'tone' => 'Daily Card',
                'icon_svg' => $this->svg('cross'),
                'kind' => 'module',
                'preview_mode' => 'daily_card',
                'daily_kind' => 'scripture',
                'daily_preview' => $dailyScripture,
                'total' => 1,
                'published' => 1,
                'draft' => 0,
                'featured' => 0,
                'status' => $dailyScriptureUrl ? 'Ready' : 'Queued',
                'manage_url' => $dailyScriptureUrl,
                'create_url' => $dailyScriptureUrl,
                'latest' => [],
                'is_module' => true,
            ],
            [
                'key' => 'daily_quote',
                'group' => 'visuals',
                'label' => 'Daily Quote',
                'description' => 'Manage the quote card, author/source, design colors, and reusable quote presentation.',
                'tone' => 'Daily Card',
                'icon_svg' => $this->svg('quote'),
                'kind' => 'module',
                'preview_mode' => 'daily_card',
                'daily_kind' => 'quote',
                'daily_preview' => $dailyQuote,
                'total' => 1,
                'published' => 1,
                'draft' => 0,
                'featured' => 0,
                'status' => $dailyQuoteUrl ? 'Ready' : 'Queued',
                'manage_url' => $dailyQuoteUrl,
                'create_url' => $dailyQuoteUrl,
                'latest' => [],
                'is_module' => true,
            ],
            [
                'key' => 'quiz_builder',
                'group' => 'interactive',
                'label' => 'Quiz Builder',
                'description' => 'Future quiz engine for devotionals, articles, Bible quiz, learning questions, and result tracking.',
                'tone' => 'Interactive Engine',
                'icon_svg' => $this->svg('puzzle'),
                'kind' => 'module',
                'preview_mode' => 'empty',
                'total' => 0,
                'published' => 0,
                'draft' => 0,
                'featured' => 0,
                'status' => 'Queued',
                'manage_url' => null,
                'create_url' => null,
                'latest' => [],
                'is_module' => true,
            ],
            [
                'key' => 'books_library',
                'group' => 'library',
                'label' => 'Books & Library',
                'description' => 'Manage books, chapters, reader payloads, covers, PDF/EPUB/Text materials, and library publishing.',
                'tone' => 'Reader Engine',
                'icon_svg' => $this->svg('library'),
                'kind' => 'module',
                'preview_mode' => 'book',
                'total' => $bookCount,
                'published' => $bookCount,
                'draft' => 0,
                'featured' => 0,
                'status' => $booksUrl ? 'Ready' : 'Queued',
                'manage_url' => $booksUrl,
                'create_url' => Route::has('admin.beginner.books.create') ? route('admin.beginner.books.create') : $booksUrl,
                'latest' => $this->bookPreviewPayload($appId),
                'is_module' => true,
            ],
        ];
    }

    protected function emptyChannel(string $bucket, array $meta): array
    {
        return [
            'bucket' => $bucket,
            'key' => $bucket,
            'group' => $meta['group'],
            'label' => $meta['label'],
            'description' => $meta['description'],
            'tone' => $meta['tone'],
            'kind' => $meta['kind'],
            'preview_mode' => $meta['preview_mode'] ?? 'article',
            'icon_svg' => $meta['icon_svg'],
            'total' => 0,
            'published' => 0,
            'draft' => 0,
            'featured' => 0,
            'latest' => [],
            'manage_url' => route('admin.beginner.content-posts.channel', ['bucket' => $bucket]),
            'create_url' => $this->createUrl($bucket),
            'status' => 'Ready',
        ];
    }

    protected function postPayload(ContentPost $post, array $channelMeta = []): array
    {
        $meta = is_array($post->meta_json ?? null) ? $post->meta_json : [];
        $cover = $this->publicUrl((string) ($post->cover_image_url ?? ''));

        $body = '';
        foreach (['body_html', 'body', 'content', 'excerpt', 'summary'] as $field) {
            if (isset($post->{$field}) && is_string($post->{$field}) && trim($post->{$field}) !== '') {
                $body = trim((string) $post->{$field});
                break;
            }
        }

        $cleanBody = trim(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", $body)));

        $videoUrl = (string) ($meta['video_url'] ?? $meta['url'] ?? '');
        $duration = (string) ($meta['video_duration'] ?? $meta['duration'] ?? '');

        $quote = (string) (
            $meta['quote_text']
            ?? $meta['quote']
            ?? $meta['scripture_text']
            ?? $meta['verse']
            ?? $post->title
            ?? ''
        );

        $quoteSource = (string) (
            $meta['quote_source']
            ?? $meta['source']
            ?? $meta['scripture_reference']
            ?? $meta['reference']
            ?? $post->subtitle
            ?? ''
        );

        return [
            'id' => (int) $post->id,
            'bucket' => (string) $post->bucket,
            'title' => (string) ($post->title ?: 'Untitled content'),
            'subtitle' => (string) ($post->subtitle ?? ''),
            'body_preview' => Str::limit($cleanBody, 420),
            'status' => (string) ($post->status ?? 'draft'),
            'cover' => $cover,
            'video_url' => $videoUrl,
            'duration' => $duration,
            'is_featured' => (bool) $post->is_featured,
            'sort_order' => (int) ($post->sort_order ?? 0),
            'updated_human' => $post->updated_at?->diffForHumans(),
            'published_human' => $post->published_at?->diffForHumans(),
            'preview_mode' => (string) ($channelMeta['preview_mode'] ?? 'article'),
            'quote' => $quote,
            'quote_source' => $quoteSource,
            'design' => $this->designPayload($meta, $cover),
            'edit_url' => $this->editUrlForPost($post, $channelMeta),
        ];
    }

    protected function designPayload(array $meta, ?string $cover = null): array
    {
        $backgroundMode = strtolower((string) ($meta['background_mode'] ?? ($cover ? 'image' : 'gradient')));
        $fontSize = (int) ($meta['quote_size'] ?? $meta['title_size'] ?? $meta['font_size'] ?? 28);
        $sourceSize = (int) ($meta['source_size'] ?? $meta['font_size'] ?? 14);
        $overlay = (int) ($meta['overlay_strength'] ?? 48);

        return [
            'background_mode' => $backgroundMode,
            'background_image' => $this->publicUrl((string) ($meta['background_image_url'] ?? $meta['image_url'] ?? $cover ?? '')),
            'bg_color' => (string) ($meta['bg_color'] ?? $meta['background_color'] ?? '#160042'),
            'bg_color_2' => (string) ($meta['bg_color_2'] ?? $meta['gradient_to'] ?? '#e2388a'),
            'text_color' => (string) ($meta['text_color'] ?? '#ffffff'),
            'accent_color' => (string) ($meta['accent_color'] ?? '#38bdf8'),
            'font_size' => max(10, min(64, $fontSize)),
            'source_size' => max(8, min(34, $sourceSize)),
            'font_weight' => (string) ($meta['font_weight'] ?? '900'),
            'text_align' => (string) ($meta['text_align'] ?? 'center'),
            'vertical_align' => (string) ($meta['vertical_align'] ?? 'center'),
            'content_width' => (int) ($meta['content_width'] ?? 86),
            'card_padding' => (int) ($meta['card_padding'] ?? 34),
            'line_height' => (string) ($meta['line_height'] ?? '1.35'),
            'format_ratio' => (string) ($meta['format_ratio'] ?? '4 / 5'),
            'show_quote_mark' => (bool) ($meta['show_quote_mark'] ?? true),
            'overlay_strength' => max(0, min(100, $overlay)),
        ];
    }

    protected function editUrlForPost(ContentPost $post, array $channelMeta = []): string
    {
        $bucket = (string) $post->bucket;
        $previewMode = (string) ($channelMeta['preview_mode'] ?? '');

        if ($bucket === 'sod_quotes' || $previewMode === 'quote_card') {
            return route('admin.beginner.quote-designer.edit', [
                'contentPost' => $post->id,
                'bucket' => $bucket,
                'return' => 'channels',
            ]);
        }

        return route('admin.beginner.content-posts.edit', [
            'contentPost' => $post->id,
            'bucket' => $bucket,
            'return' => 'channels',
        ]);
    }

    protected function dailyCardPayload(int $appId, string $kind): array
    {
        $fallback = [
            'title' => $kind === 'scripture' ? 'Daily Scripture' : 'Daily Quote',
            'main' => $kind === 'scripture' ? 'Scripture text will appear here.' : 'Quote text will appear here.',
            'sub' => $kind === 'scripture' ? 'Reference' : 'Source',
            'note' => '',
            'design' => $this->designPayload([], null),
        ];

        if ($appId <= 0 || ! Schema::hasTable('app_sections') || ! Schema::hasTable('app_items')) {
            return $fallback;
        }

        $keys = $kind === 'scripture'
            ? ['home_daily_scripture', 'daily_scripture']
            : ['home_daily_quote', 'daily_quote'];

        try {
            $section = \DB::table('app_sections')
                ->where('app_id', $appId)
                ->whereIn('key', $keys)
                ->orderBy('sort_order')
                ->first();

            if (! $section) {
                return $fallback;
            }

            $item = \DB::table('app_items')
                ->where('section_id', $section->id)
                ->orderBy('sort_order')
                ->first();

            if (! $item) {
                return $fallback;
            }

            $payload = is_string($item->payload_json ?? null)
                ? json_decode($item->payload_json, true)
                : (array) ($item->payload_json ?? []);

            if (! is_array($payload)) {
                $payload = [];
            }

            $cover = $this->publicUrl((string) ($item->image_url ?? $payload['image_url'] ?? $payload['background_image_url'] ?? ''));

            if ($kind === 'scripture') {
                $main = (string) ($payload['scripture_text'] ?? $payload['verse'] ?? $item->title ?? $fallback['main']);
                $sub = (string) ($payload['scripture_reference'] ?? $payload['reference'] ?? $item->subtitle ?? $fallback['sub']);
                $note = (string) ($payload['scripture_note'] ?? $payload['note'] ?? '');
            } else {
                $main = (string) ($payload['quote_text'] ?? $payload['quote'] ?? $item->title ?? $fallback['main']);
                $sub = (string) ($payload['quote_source'] ?? $payload['source'] ?? $item->subtitle ?? $fallback['sub']);
                $note = '';
            }

            return [
                'title' => (string) ($section->title ?? $fallback['title']),
                'main' => $main,
                'sub' => $sub,
                'note' => $note,
                'design' => $this->designPayload($payload, $cover),
            ];
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    protected function bookPreviewPayload(int $appId): array
    {
        if ($appId <= 0 || ! class_exists(Book::class)) {
            return [];
        }

        try {
            return Book::query()
                ->where('app_id', $appId)
                ->orderByDesc('updated_at')
                ->limit(6)
                ->get()
                ->map(function ($book): array {
                    $cover = (string) ($book->cover_image_url ?? $book->cover_path ?? '');
                    return [
                        'id' => (int) $book->id,
                        'title' => (string) ($book->title ?? 'Untitled Book'),
                        'subtitle' => (string) ($book->subtitle ?? $book->author ?? ''),
                        'cover' => $this->publicUrl($cover),
                        'status' => (bool) ($book->is_published ?? $book->is_active ?? true) ? 'Published' : 'Draft',
                        'edit_url' => Route::has('admin.beginner.books.edit') ? route('admin.beginner.books.edit', ['book' => $book->id]) : null,
                    ];
                })
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function publicUrl(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (Str::startsWith($value, ['http://', 'https://', '/'])) {
            return $value;
        }

        try {
            return Storage::disk('public')->exists($value)
                ? Storage::disk('public')->url($value)
                : $value;
        } catch (\Throwable $e) {
            return $value;
        }
    }

    protected function createUrl(string $bucket): string
    {
        if ($bucket === 'sod_quotes' && Route::has('admin.beginner.quote-designer.create')) {
            return route('admin.beginner.quote-designer.create', [
                'bucket' => $bucket,
                'return' => 'channels',
            ]);
        }

        return route('admin.beginner.content-posts.create', [
            'bucket' => $bucket,
            'return' => 'channels',
        ]);
    }

    protected function buildSummary(array $channels, array $modules = []): array
    {
        $collection = collect($channels);
        $moduleCollection = collect($modules);

        return [
            'channels' => $collection->count(),
            'modules' => $moduleCollection->count(),
            'total' => (int) $collection->sum('total'),
            'published' => (int) $collection->sum('published'),
            'draft' => (int) $collection->sum('draft'),
            'featured' => (int) $collection->sum('featured'),
            'video' => (int) $collection->where('kind', 'video')->sum('total'),
            'visuals' => (int) $collection->where('group', 'visuals')->sum('total') + (int) $moduleCollection->where('group', 'visuals')->sum('total'),
            'library' => (int) $moduleCollection->where('group', 'library')->sum('total'),
        ];
    }

    protected function svg(string $name): string
    {
        return match ($name) {
            'book' => '<svg viewBox="0 0 24 24" fill="none"><path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H20v16H7.5A2.5 2.5 0 0 0 5 21.5v-16Z" stroke="currentColor" stroke-width="1.8"/><path d="M5 5.5A2.5 2.5 0 0 0 2.5 3H2v16h.5A2.5 2.5 0 0 1 5 21.5" stroke="currentColor" stroke-width="1.8"/></svg>',
            'book-open' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 6.4C10.5 4.9 8.1 4 5 4v14c3.1 0 5.5.9 7 2.4" stroke="currentColor" stroke-width="1.8"/><path d="M12 6.4C13.5 4.9 15.9 4 19 4v14c-3.1 0-5.5.9-7 2.4V6.4Z" stroke="currentColor" stroke-width="1.8"/></svg>',
            'library' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 5h4v14H4V5Zm6 0h4v14h-4V5Zm6 0h4v14h-4V5Z" stroke="currentColor" stroke-width="1.8"/><path d="M3 19h18" stroke="currentColor" stroke-width="1.8"/></svg>',
            'image' => '<svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="m4 17 5.2-5.2a1.5 1.5 0 0 1 2.1 0L14 14.5l1.2-1.2a1.5 1.5 0 0 1 2.1 0L21 17" stroke="currentColor" stroke-width="1.8"/><circle cx="16.5" cy="9.5" r="1.4" fill="currentColor"/></svg>',
            'play' => '<svg viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="16" height="16" rx="4" stroke="currentColor" stroke-width="1.8"/><path d="M10 8.5v7l6-3.5-6-3.5Z" fill="currentColor"/></svg>',
            'document' => '<svg viewBox="0 0 24 24" fill="none"><path d="M6 3h8l4 4v14H6V3Z" stroke="currentColor" stroke-width="1.8"/><path d="M14 3v5h5M8.5 12h7M8.5 16h7" stroke="currentColor" stroke-width="1.8"/></svg>',
            'newspaper' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 6h13v12H4V6Z" stroke="currentColor" stroke-width="1.8"/><path d="M17 9h3v7.5A1.5 1.5 0 0 1 18.5 18H17V9Z" stroke="currentColor" stroke-width="1.8"/><path d="M7 9h7M7 12h7M7 15h4" stroke="currentColor" stroke-width="1.8"/></svg>',
            'spark' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 2.8 13.9 9 20 12l-6.1 3L12 21.2 10.1 15 4 12l6.1-3L12 2.8Z" stroke="currentColor" stroke-width="1.8"/></svg>',
            'bolt' => '<svg viewBox="0 0 24 24" fill="none"><path d="M13 2 5 13h6l-1 9 9-13h-6l0-7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
            'star' => '<svg viewBox="0 0 24 24" fill="none"><path d="m12 3 2.6 5.3 5.9.9-4.2 4.1 1 5.8L12 16.4 6.7 19l1-5.8-4.2-4.1 5.9-.9L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
            'quote' => '<svg viewBox="0 0 24 24" fill="none"><path d="M8.4 10H5.2C5.4 6.9 7 5 9.7 4.1l.8 1.8C9.2 6.4 8.5 7.3 8.4 8.6H11V15H4.8v-4.7c0-.1 0-.2.1-.3h3.5Zm10 0h-3.2c.2-3.1 1.8-5 4.5-5.9l.8 1.8c-1.3.5-2 1.4-2.1 2.7H21V15h-6.2v-4.7c0-.1 0-.2.1-.3h3.5Z" fill="currentColor"/></svg>',
            'puzzle' => '<svg viewBox="0 0 24 24" fill="none"><path d="M9 3h6v4a2 2 0 1 0 0 4v4h-4a2 2 0 1 1-4 0H3V9h4a2 2 0 1 0 0-4h2V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
            'cross' => '<svg viewBox="0 0 24 24" fill="none"><path d="M10 3h4v6h5v4h-5v8h-4v-8H5V9h5V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
            default => '<svg viewBox="0 0 24 24" fill="none"><rect x="5" y="5" width="14" height="14" rx="3" stroke="currentColor" stroke-width="1.8"/></svg>',
        };
    }

    protected function getViewData(): array
    {
        return [
            'currentApp' => $this->currentApp,
            'groups' => $this->groups,
            'channels' => $this->channels,
            'modules' => $this->modules,
            'summary' => $this->summary,
        ];
    }
}
