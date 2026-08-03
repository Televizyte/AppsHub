<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookChapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BookController extends Controller
{
    public function index(Request $request, string $appSlug)
    {
        $app = $this->resolveApp($request, $appSlug);

        if (! $app) {
            return $this->appNotFound();
        }

        if (! Schema::hasTable('books')) {
            return response()->json([
                'ok' => false,
                'error' => 'BOOKS_TABLE_MISSING',
                'message' => 'Books table not found.',
            ], 500);
        }

        $category = trim((string) $request->query('category', ''));
        $search = trim((string) $request->query('search', ''));
        $perPage = min(50, max(1, (int) $request->query('per_page', 24)));
        $page = max(1, (int) $request->query('page', 1));

        $qb = Book::query()
            ->with(['category', 'chapters', 'app'])
            ->where('app_id', (int) $app->id)
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });

        if ($category !== '') {
            $qb->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        if ($search !== '') {
            $qb->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('subtitle', 'like', '%' . $search . '%')
                    ->orWhere('author_name', 'like', '%' . $search . '%');
            });
        }

        $total = (clone $qb)->count();
        $books = $qb->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get();

        $items = $books->map(fn (Book $book) => $this->shapeBook($book, false))->values()->all();

        return response()->json([
            'ok' => true,
            'app' => $this->shapeApp($app),
            'meta' => [
                'api_version' => 'v1.books.1.12',
                'screen' => 'books_library',
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil(max(1, $total) / $perPage),
            ],
            'items' => $items,
            'data' => $items,
        ]);
    }

    public function categories(Request $request, string $appSlug)
    {
        $app = $this->resolveApp($request, $appSlug);

        if (! $app) {
            return $this->appNotFound();
        }

        $categories = BookCategory::query()
            ->where('app_id', (int) $app->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'ok' => true,
            'app' => $this->shapeApp($app),
            'items' => $categories->map(fn (BookCategory $category) => [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
                'description' => $category->description,
                'sort_order' => (int) $category->sort_order,
            ])->values()->all(),
        ]);
    }

    public function featured(Request $request, string $appSlug)
    {
        $app = $this->resolveApp($request, $appSlug);

        if (! $app) {
            return $this->appNotFound();
        }

        $books = Book::query()
            ->with(['category', 'chapters', 'app'])
            ->where('app_id', (int) $app->id)
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        return response()->json([
            'ok' => true,
            'app' => $this->shapeApp($app),
            'items' => $books->map(fn (Book $book) => $this->shapeBook($book, false))->values()->all(),
        ]);
    }

    public function show(Request $request, string $appSlug, string $book)
    {
        $app = $this->resolveApp($request, $appSlug);

        if (! $app) {
            return $this->appNotFound();
        }

        $bookModel = $this->resolveBook((int) $app->id, $book, true);

        if (! $bookModel) {
            return response()->json([
                'ok' => false,
                'error' => 'BOOK_NOT_FOUND',
                'message' => 'Book not found.',
            ], 404);
        }

        $item = $this->shapeBook($bookModel, true);

        return response()->json([
            'ok' => true,
            'app' => $this->shapeApp($app),
            'item' => $item,
            'book' => $item,
            'data' => $item,
        ]);
    }

    public function chapters(Request $request, string $appSlug, string $book)
    {
        $app = $this->resolveApp($request, $appSlug);

        if (! $app) {
            return $this->appNotFound();
        }

        $bookModel = $this->resolveBook((int) $app->id, $book, true);

        if (! $bookModel) {
            return response()->json([
                'ok' => false,
                'error' => 'BOOK_NOT_FOUND',
                'message' => 'Book not found.',
            ], 404);
        }

        $chapters = $bookModel->chapters()
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'ok' => true,
            'app' => $this->shapeApp($app),
            'book' => $this->shapeBook($bookModel, false),
            'items' => $chapters->map(fn (BookChapter $chapter) => $this->shapeChapter($chapter, false))->values()->all(),
        ]);
    }

    public function chapter(Request $request, string $appSlug, string $book, string $chapter)
    {
        $app = $this->resolveApp($request, $appSlug);

        if (! $app) {
            return $this->appNotFound();
        }

        $bookModel = $this->resolveBook((int) $app->id, $book, true);

        if (! $bookModel) {
            return response()->json([
                'ok' => false,
                'error' => 'BOOK_NOT_FOUND',
                'message' => 'Book not found.',
            ], 404);
        }

        $chapterModel = $bookModel->chapters()
            ->where('status', 'published')
            ->where(function ($q) use ($chapter) {
                if (ctype_digit($chapter)) {
                    $q->where('id', (int) $chapter);
                } else {
                    $q->where('slug', $chapter);
                }
            })
            ->first();

        if (! $chapterModel) {
            return response()->json([
                'ok' => false,
                'error' => 'CHAPTER_NOT_FOUND',
                'message' => 'Chapter not found.',
            ], 404);
        }

        $item = $this->shapeChapter($chapterModel, true);

        return response()->json([
            'ok' => true,
            'app' => $this->shapeApp($app),
            'book' => $this->shapeBook($bookModel, false),
            'item' => $item,
            'chapter' => $item,
            'data' => $item,
        ]);
    }

    private function resolveApp(Request $request, string $appSlug): ?object
    {
        $currentApp = $request->attributes->get('current_app');

        if ($currentApp instanceof App) {
            return (object) [
                'id' => (int) $currentApp->id,
                'name' => (string) $currentApp->name,
                'slug' => (string) $currentApp->slug,
                'is_active' => (bool) $currentApp->is_active,
            ];
        }

        return App::query()
            ->select(['id', 'name', 'slug', 'is_active'])
            ->where('slug', $appSlug)
            ->where('is_active', true)
            ->first();
    }

    private function resolveBook(int $appId, string $book, bool $publishedOnly): ?Book
    {
        return Book::query()
            ->with(['category', 'chapters', 'app'])
            ->where('app_id', $appId)
            ->when($publishedOnly, function ($q) {
                $q->where('status', 'published')
                    ->where(function ($visibilityQuery) {
                        $visibilityQuery->whereNull('published_at')
                            ->orWhere('published_at', '<=', now());
                    });
            })
            ->where(function ($q) use ($book) {
                if (ctype_digit($book)) {
                    $q->where('id', (int) $book);
                } else {
                    $q->where('slug', $book);
                }
            })
            ->first();
    }

    private function shapeBook(Book $book, bool $includeChapters): array
    {
        $chapters = $book->chapters->where('status', 'published')->sortBy([['sort_order', 'asc'], ['id', 'asc']])->values();
        $meta = is_array($book->meta_json) ? $book->meta_json : [];
        $plainText = trim(strip_tags($chapters->pluck('body_html')->implode(' ')));
        $wordCount = str_word_count($plainText);
        $estimatedPages = max(1, (int) ceil($wordCount / 420));
        $estimatedMinutes = max(1, (int) ceil($wordCount / 180));
        $coverRatio = $meta['cover_ratio'] ?? 'portrait_3_4';
        $displayStyle = $meta['display_style'] ?? 'book_cover';
        $coverDesigner = $this->shapeCoverDesigner($meta['cover_designer'] ?? [], $book);
        $rawCover = $book->cover_image_src;
        $finalCover = $book->final_cover_image_src;
        $bestCover = $finalCover ?: $rawCover;

        return [
            'id' => (int) $book->id,
            'type' => 'book',
            'title' => $book->title,
            'subtitle' => $book->subtitle,
            'slug' => $book->slug,
            'author_name' => $book->author_name,
            'description' => $book->description,
            'book_type' => $book->book_type,
            'status' => $book->status,
            'access_type' => $book->access_type,
            'is_featured' => (bool) $book->is_featured,
            'is_downloadable' => (bool) $book->is_downloadable,
            'cover_image_url' => $bestCover,
            'raw_cover_image_url' => $rawCover,
            'final_cover_image_url' => $finalCover,
            'rendered_cover_url' => $finalCover,
            'thumbnail_url' => $bestCover,
            'image_url' => $bestCover,
            'cover_ratio' => $coverRatio,
            'display_style' => $displayStyle,
            'frontend_card_style' => 'portrait_book_card',
            'book_page_size' => $meta['book_page_size'] ?? 'standard_6x9',
            'target_pages' => $meta['target_pages'] ?? null,
            'reader_font' => $meta['reader_font'] ?? 'serif',
            'page_theme' => $meta['page_theme'] ?? 'classic',
            'cover_designer' => $coverDesigner,
            'file_url' => $book->file_src,
            'external_url' => $book->external_url,
            'published_at' => optional($book->published_at)->toISOString(),
            'category' => $book->category ? [
                'id' => (int) $book->category->id,
                'name' => $book->category->name,
                'slug' => $book->category->slug,
            ] : null,
            'chapter_count' => $chapters->count(),
            'estimated_pages' => $estimatedPages,
            'estimated_reading_minutes' => $estimatedMinutes,
            'word_count' => $wordCount,
            'toc' => $chapters->map(fn (BookChapter $chapter) => $this->shapeChapter($chapter, false))->values()->all(),
            'chapters' => $includeChapters ? $chapters->map(fn (BookChapter $chapter) => $this->shapeChapter($chapter, false))->values()->all() : null,
            'payload' => [
                'engine' => 'book_reader',
                'reader_mode' => $book->book_type,
                'read_aloud' => in_array($book->book_type, ['manual', 'compiled'], true),
                'cover_ratio' => $coverRatio,
                'display_style' => $displayStyle,
                'book_page_size' => $meta['book_page_size'] ?? 'standard_6x9',
                'reader_font' => $meta['reader_font'] ?? 'serif',
                'page_theme' => $meta['page_theme'] ?? 'classic',
                'cover_designer' => $coverDesigner,
                'final_cover_image_url' => $finalCover,
                'rendered_cover_url' => $finalCover,
                'raw_cover_image_url' => $rawCover,
                'api_url' => '/api/v1/apps/' . optional($book->app)->slug . '/books/' . $book->slug,
            ],
        ];
    }

    private function shapeCoverDesigner(array $designer, Book $book): array
    {
        return [
            'enabled' => (bool) ($designer['enabled'] ?? false),
            'bg_image_url' => $designer['bg_image_url'] ?? $book->cover_image_src,
            'sync_bg' => (bool) ($designer['sync_bg'] ?? true),
            'bg_color' => $designer['bg_color'] ?? '#0B1F4D',
            'gradient_color' => $designer['gradient_color'] ?? '#E2388A',
            'overlay' => (int) ($designer['overlay'] ?? 42),
            'title' => $designer['title'] ?? $book->title,
            'subtitle' => $designer['subtitle'] ?? $book->subtitle,
            'author' => $designer['author'] ?? $book->author_name,
            'badge' => $designer['badge'] ?? null,
            'text_color' => $designer['text_color'] ?? '#FFFFFF',
            'text_position' => $designer['text_position'] ?? 'bottom',
            'title_size' => (int) ($designer['title_size'] ?? 34),
            'subtitle_size' => (int) ($designer['subtitle_size'] ?? 15),
            'author_size' => (int) ($designer['author_size'] ?? 13),
            'badge_size' => (int) ($designer['badge_size'] ?? 10),
            'font_family' => $designer['font_family'] ?? 'display',
            'text_align' => $designer['text_align'] ?? 'left',
            'text_shadow' => $designer['text_shadow'] ?? 'soft',
            'bg_fit' => $designer['bg_fit'] ?? 'cover',
            'bg_position' => $designer['bg_position'] ?? 'center',
            'layout_style' => $designer['layout_style'] ?? 'bold',
            'text_width' => (int) ($designer['text_width'] ?? 88),
            'panel_opacity' => (int) ($designer['panel_opacity'] ?? 0),
        ];
    }

    private function shapeChapter(BookChapter $chapter, bool $includeBody): array
    {
        $plainText = trim(strip_tags((string) $chapter->body_html));
        $wordCount = str_word_count($plainText);

        return [
            'id' => (int) $chapter->id,
            'type' => 'book_chapter',
            'title' => $chapter->title,
            'subtitle' => $chapter->subtitle,
            'slug' => $chapter->slug,
            'summary' => $chapter->summary,
            'key_thought' => $chapter->key_thought,
            'reflection_questions' => $chapter->reflection_questions,
            'prayer_points' => $chapter->prayer_points,
            'action_steps' => $chapter->action_steps,
            'memory_verse' => $chapter->memory_verse,
            'status' => $chapter->status,
            'sort_order' => (int) $chapter->sort_order,
            'word_count' => $wordCount,
            'estimated_pages' => max(1, (int) ceil($wordCount / 420)),
            'estimated_reading_minutes' => max(1, (int) ceil($wordCount / 180)),
            'body_html' => $includeBody ? $chapter->body_html : null,
            'payload' => [
                'engine' => 'content_reader',
                'reader_mode' => 'chapter',
                'read_aloud' => true,
            ],
        ];
    }

    private function shapeApp(object $app): array
    {
        return [
            'id' => (int) $app->id,
            'name' => (string) $app->name,
            'slug' => (string) $app->slug,
        ];
    }

    private function appNotFound()
    {
        return response()->json([
            'ok' => false,
            'error' => 'APP_NOT_FOUND',
            'message' => 'App not found or inactive.',
        ], 404);
    }
}
