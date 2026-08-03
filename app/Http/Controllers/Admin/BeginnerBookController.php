<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\MediaAsset;
use App\Services\Books\BookCoverRenderer;
use App\Support\ActiveApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BeginnerBookController extends Controller
{
    public function index(Request $request): View
    {
        $activeAppId = $this->activeAppId();

        $books = Book::query()
            ->with(['category', 'chapters'])
            ->where('app_id', $activeAppId)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('admin.beginner.books.index', [
            'books' => $books,
            'stats' => [
                'total' => $books->count(),
                'published' => $books->where('status', 'published')->count(),
                'draft' => $books->where('status', 'draft')->count(),
                'featured' => $books->where('is_featured', true)->count(),
                'chapters' => $books->sum(fn (Book $book) => $book->chapters->count()),
            ],
        ]);
    }

    public function create(): View
    {
        $activeAppId = $this->activeAppId();

        return view('admin.beginner.books.create', [
            'book' => new Book([
                'book_type' => 'manual',
                'status' => 'draft',
                'access_type' => 'free',
                'sort_order' => 0,
                'meta_json' => $this->defaultMeta(),
            ]),
            'categories' => $this->categories($activeAppId),
            'mediaAssets' => $this->mediaAssets($activeAppId),
            'bookTypeOptions' => $this->bookTypeOptions(),
            'statusOptions' => $this->statusOptions(),
            'accessOptions' => $this->accessOptions(),
            'coverRatioOptions' => $this->coverRatioOptions(),
        ]);
    }

    public function store(Request $request, BookCoverRenderer $coverRenderer): RedirectResponse
    {
        $activeAppId = $this->activeAppId();
        $data = $this->validated($request);
        $categoryId = $this->resolveCategoryId($activeAppId, $data);
        $coverImageUrl = $this->resolveCoverImageUrl($request, $activeAppId, $data['title']);
        $data['_resolved_cover_image_url'] = $coverImageUrl;
        $fileData = $this->resolveBookFile($request, $activeAppId, $data['title']);
        $status = (string) $data['status'];

        $book = Book::create([
            'app_id' => $activeAppId,
            'book_category_id' => $categoryId,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'slug' => $this->uniqueSlug($activeAppId, $data['title']),
            'author_name' => $data['author_name'] ?? null,
            'description' => $data['description'] ?? null,
            'cover_image_url' => $coverImageUrl,
            'book_type' => $data['book_type'],
            'status' => $status,
            'access_type' => $data['access_type'],
            'file_url' => $fileData['url'] ?? ($data['file_url'] ?? null),
            'file_path' => $fileData['path'] ?? null,
            'external_url' => $data['external_url'] ?? null,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'is_downloadable' => (bool) ($data['is_downloadable'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'published_at' => $status === 'published' ? now() : null,
            'meta_json' => $this->bookMeta([], $data),
        ]);

        $this->refreshFinalCover($book, $coverRenderer);

        return redirect()
            ->route('admin.beginner.books.edit', $book)
            ->with('status', 'Book created successfully. The final designed cover image has been prepared when cover designer is enabled.');
    }

    public function edit(Book $book): View
    {
        $activeAppId = $this->activeAppId();
        $this->guardBook($book, $activeAppId);

        return view('admin.beginner.books.edit', [
            'book' => $book->load(['category', 'chapters']),
            'categories' => $this->categories($activeAppId),
            'mediaAssets' => $this->mediaAssets($activeAppId),
            'bookTypeOptions' => $this->bookTypeOptions(),
            'statusOptions' => $this->statusOptions(),
            'accessOptions' => $this->accessOptions(),
            'coverRatioOptions' => $this->coverRatioOptions(),
        ]);
    }

    public function update(Request $request, Book $book, BookCoverRenderer $coverRenderer): RedirectResponse
    {
        $activeAppId = $this->activeAppId();
        $this->guardBook($book, $activeAppId);

        if ($request->input('_beginner_action') === 'delete') {
            return $this->destroy($request, $book);
        }

        $data = $this->validated($request);
        $categoryId = $this->resolveCategoryId($activeAppId, $data);
        $coverImageUrl = $this->resolveCoverImageUrl($request, $activeAppId, $data['title'], $book->cover_image_url);
        $data['_resolved_cover_image_url'] = $coverImageUrl;
        $fileData = $this->resolveBookFile($request, $activeAppId, $data['title']);
        $status = (string) $data['status'];
        $meta = $this->bookMeta(is_array($book->meta_json) ? $book->meta_json : [], $data);

        $book->update([
            'book_category_id' => $categoryId,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'author_name' => $data['author_name'] ?? null,
            'description' => $data['description'] ?? null,
            'cover_image_url' => $coverImageUrl,
            'book_type' => $data['book_type'],
            'status' => $status,
            'access_type' => $data['access_type'],
            'file_url' => $fileData['url'] ?? ($data['file_url'] ?? $book->file_url),
            'file_path' => $fileData['path'] ?? $book->file_path,
            'external_url' => $data['external_url'] ?? null,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'is_downloadable' => (bool) ($data['is_downloadable'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'published_at' => $status === 'published' ? ($book->published_at ?: now()) : null,
            'meta_json' => $meta,
        ]);

        $this->refreshFinalCover($book->fresh(), $coverRenderer);

        return redirect()
            ->route('admin.beginner.books.edit', $book)
            ->with('status', 'Book updated successfully. The frozen final cover image was refreshed when cover designer is enabled.');
    }

    public function destroy(Request $request, Book $book): RedirectResponse
    {
        $activeAppId = $this->activeAppId();
        $this->guardBook($book, $activeAppId);

        if ($request->isMethod('put') || $request->isMethod('patch')) {
            $request->validate([
                'delete_confirmation' => ['required', 'in:DELETE'],
            ]);
        }

        $book->delete();

        return redirect()
            ->route('admin.beginner.books.index')
            ->with('status', 'Book deleted successfully.');
    }

    private function refreshFinalCover(?Book $book, BookCoverRenderer $coverRenderer): void
    {
        if (! $book || ! Schema::hasColumn('books', 'final_cover_image_url')) {
            return;
        }

        $result = $coverRenderer->render($book);

        if (! $result) {
            return;
        }

        $book->forceFill([
            'final_cover_image_url' => $result['url'] ?? null,
            'final_cover_image_path' => $result['path'] ?? null,
            'final_cover_mime' => $result['mime'] ?? null,
        ])->saveQuietly();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'book_category_id' => ['nullable', 'integer', 'exists:book_categories,id'],
            'new_category_name' => ['nullable', 'string', 'max:120'],
            'cover_image_url' => ['nullable', 'string', 'max:1000'],
            'cover_image_file' => ['nullable', 'image', 'max:8192'],
            'media_asset_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'book_type' => ['required', 'in:manual,pdf,external,compiled'],
            'cover_ratio' => ['nullable', 'string', 'max:50'],
            'display_style' => ['nullable', 'string', 'max:80'],
            'book_page_size' => ['nullable', 'string', 'max:80'],
            'target_pages' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'reader_font' => ['nullable', 'string', 'max:80'],
            'page_theme' => ['nullable', 'string', 'max:80'],
            'cover_designer_enabled' => ['nullable', 'boolean'],
            'cover_designer_bg_image_url' => ['nullable', 'string', 'max:1000'],
            'cover_designer_sync_bg' => ['nullable', 'boolean'],
            'cover_designer_bg_color' => ['nullable', 'string', 'max:40'],
            'cover_designer_gradient_color' => ['nullable', 'string', 'max:40'],
            'cover_designer_overlay' => ['nullable', 'integer', 'min:0', 'max:90'],
            'cover_designer_title' => ['nullable', 'string', 'max:255'],
            'cover_designer_subtitle' => ['nullable', 'string', 'max:255'],
            'cover_designer_author' => ['nullable', 'string', 'max:255'],
            'cover_designer_badge' => ['nullable', 'string', 'max:80'],
            'cover_designer_text_color' => ['nullable', 'string', 'max:40'],
            'cover_designer_text_position' => ['nullable', 'string', 'max:40'],
            'cover_designer_title_size' => ['nullable', 'integer', 'min:16', 'max:72'],
            'cover_designer_subtitle_size' => ['nullable', 'integer', 'min:10', 'max:36'],
            'cover_designer_author_size' => ['nullable', 'integer', 'min:10', 'max:32'],
            'cover_designer_badge_size' => ['nullable', 'integer', 'min:8', 'max:24'],
            'cover_designer_font_family' => ['nullable', 'string', 'max:80'],
            'cover_designer_text_align' => ['nullable', 'string', 'max:40'],
            'cover_designer_text_shadow' => ['nullable', 'string', 'max:40'],
            'cover_designer_bg_fit' => ['nullable', 'string', 'max:40'],
            'cover_designer_bg_position' => ['nullable', 'string', 'max:40'],
            'cover_designer_layout_style' => ['nullable', 'string', 'max:40'],
            'cover_designer_text_width' => ['nullable', 'integer', 'min:45', 'max:100'],
            'cover_designer_panel_opacity' => ['nullable', 'integer', 'min:0', 'max:80'],
            'status' => ['required', 'in:draft,published,hidden'],
            'access_type' => ['required', 'in:free,login_required,premium,token'],
            'book_file' => ['nullable', 'file', 'mimes:pdf', 'max:51200'],
            'file_url' => ['nullable', 'string', 'max:1000'],
            'external_url' => ['nullable', 'string', 'max:1000'],
            'is_featured' => ['nullable', 'boolean'],
            'is_downloadable' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);
    }

    private function defaultMeta(): array
    {
        return [
            'cover_ratio' => 'portrait_3_4',
            'display_style' => 'book_cover',
            'book_page_size' => 'standard_6x9',
            'target_pages' => null,
            'reader_font' => 'serif',
            'page_theme' => 'classic',
            'cover_designer' => [
                'enabled' => false,
                'bg_image_url' => null,
                'sync_bg' => true,
                'bg_color' => '#0B1F4D',
                'gradient_color' => '#E2388A',
                'overlay' => 42,
                'title' => null,
                'subtitle' => null,
                'author' => null,
                'badge' => 'New Book',
                'text_color' => '#FFFFFF',
                'text_position' => 'bottom',
                'title_size' => 34,
                'subtitle_size' => 15,
                'author_size' => 13,
                'badge_size' => 10,
                'font_family' => 'display',
                'text_align' => 'left',
                'text_shadow' => 'soft',
                'bg_fit' => 'cover',
                'bg_position' => 'center',
                'layout_style' => 'bold',
                'text_width' => 88,
                'panel_opacity' => 0,
            ],
        ];
    }

    private function bookMeta(array $existing, array $data): array
    {
        $existing['engine'] = 'book_library';
        $existing['created_from'] = $existing['created_from'] ?? 'beginner_books';
        $existing['ai_ready'] = true;
        $existing['compilation_ready'] = true;
        $existing['read_aloud_ready'] = true;
        $existing['display_style'] = $data['display_style'] ?? 'book_cover';
        $existing['cover_ratio'] = $data['cover_ratio'] ?? ($existing['cover_ratio'] ?? 'portrait_3_4');
        $existing['frontend_card_style'] = 'portrait_book_card';
        $existing['reader_engine'] = 'book_reader';
        $existing['book_page_size'] = $data['book_page_size'] ?? ($existing['book_page_size'] ?? 'standard_6x9');
        $existing['target_pages'] = isset($data['target_pages']) && $data['target_pages'] !== '' ? (int) $data['target_pages'] : ($existing['target_pages'] ?? null);
        $existing['reader_font'] = $data['reader_font'] ?? ($existing['reader_font'] ?? 'serif');
        $existing['page_theme'] = $data['page_theme'] ?? ($existing['page_theme'] ?? 'classic');
        $existing['cover_designer'] = $this->coverDesignerMeta($existing['cover_designer'] ?? [], $data);

        return $existing;
    }

    private function coverDesignerMeta(array $existing, array $data): array
    {
        $syncBg = array_key_exists('cover_designer_sync_bg', $data)
            ? (bool) $data['cover_designer_sync_bg']
            : (bool) ($existing['sync_bg'] ?? true);

        $resolvedCover = trim((string) ($data['_resolved_cover_image_url'] ?? ''));
        $submittedBg = trim((string) ($data['cover_designer_bg_image_url'] ?? ''));
        $previousBg = trim((string) ($existing['bg_image_url'] ?? ''));
        $bgImage = $syncBg && $resolvedCover !== '' ? $resolvedCover : ($submittedBg !== '' ? $submittedBg : ($previousBg !== '' ? $previousBg : null));

        return [
            'enabled' => (bool) ($data['cover_designer_enabled'] ?? ($existing['enabled'] ?? false)),
            'bg_image_url' => $bgImage,
            'sync_bg' => $syncBg,
            'bg_color' => $data['cover_designer_bg_color'] ?? ($existing['bg_color'] ?? '#0B1F4D'),
            'gradient_color' => $data['cover_designer_gradient_color'] ?? ($existing['gradient_color'] ?? '#E2388A'),
            'overlay' => isset($data['cover_designer_overlay']) ? (int) $data['cover_designer_overlay'] : (int) ($existing['overlay'] ?? 42),
            'title' => $data['cover_designer_title'] ?? ($existing['title'] ?? null),
            'subtitle' => $data['cover_designer_subtitle'] ?? ($existing['subtitle'] ?? null),
            'author' => $data['cover_designer_author'] ?? ($existing['author'] ?? null),
            'badge' => $data['cover_designer_badge'] ?? ($existing['badge'] ?? 'New Book'),
            'text_color' => $data['cover_designer_text_color'] ?? ($existing['text_color'] ?? '#FFFFFF'),
            'text_position' => $data['cover_designer_text_position'] ?? ($existing['text_position'] ?? 'bottom'),
            'title_size' => isset($data['cover_designer_title_size']) ? (int) $data['cover_designer_title_size'] : (int) ($existing['title_size'] ?? 34),
            'subtitle_size' => isset($data['cover_designer_subtitle_size']) ? (int) $data['cover_designer_subtitle_size'] : (int) ($existing['subtitle_size'] ?? 15),
            'author_size' => isset($data['cover_designer_author_size']) ? (int) $data['cover_designer_author_size'] : (int) ($existing['author_size'] ?? 13),
            'badge_size' => isset($data['cover_designer_badge_size']) ? (int) $data['cover_designer_badge_size'] : (int) ($existing['badge_size'] ?? 10),
            'font_family' => $data['cover_designer_font_family'] ?? ($existing['font_family'] ?? 'display'),
            'text_align' => $data['cover_designer_text_align'] ?? ($existing['text_align'] ?? 'left'),
            'text_shadow' => $data['cover_designer_text_shadow'] ?? ($existing['text_shadow'] ?? 'soft'),
            'bg_fit' => $data['cover_designer_bg_fit'] ?? ($existing['bg_fit'] ?? 'cover'),
            'bg_position' => $data['cover_designer_bg_position'] ?? ($existing['bg_position'] ?? 'center'),
            'layout_style' => $data['cover_designer_layout_style'] ?? ($existing['layout_style'] ?? 'bold'),
            'text_width' => isset($data['cover_designer_text_width']) ? (int) $data['cover_designer_text_width'] : (int) ($existing['text_width'] ?? 88),
            'panel_opacity' => isset($data['cover_designer_panel_opacity']) ? (int) $data['cover_designer_panel_opacity'] : (int) ($existing['panel_opacity'] ?? 0),
        ];
    }

    private function resolveCategoryId(int $activeAppId, array $data): ?int
    {
        $categoryId = (int) ($data['book_category_id'] ?? 0);

        if ($categoryId > 0) {
            $exists = BookCategory::query()
                ->where('id', $categoryId)
                ->where('app_id', $activeAppId)
                ->exists();

            if ($exists) {
                return $categoryId;
            }
        }

        $newName = trim((string) ($data['new_category_name'] ?? ''));

        if ($newName === '') {
            return null;
        }

        $baseSlug = Str::slug($newName) ?: 'category';
        $slug = $baseSlug;
        $count = 2;

        while (BookCategory::query()->where('app_id', $activeAppId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $count;
            $count++;
        }

        $category = BookCategory::create([
            'app_id' => $activeAppId,
            'name' => $newName,
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return (int) $category->id;
    }

    private function resolveCoverImageUrl(Request $request, int $activeAppId, string $title, ?string $fallback = null): ?string
    {
        if ($request->hasFile('cover_image_file')) {
            return $this->storeUploadedCover($request, $activeAppId, $title);
        }

        $mediaAssetId = (int) $request->input('media_asset_id', 0);

        if ($mediaAssetId > 0) {
            $asset = MediaAsset::query()
                ->where('id', $mediaAssetId)
                ->where('type', 'image')
                ->where('is_active', true)
                ->where(function ($query) use ($activeAppId) {
                    $query->whereNull('app_id')->orWhere('app_id', $activeAppId);
                })
                ->first();

            if ($asset) {
                return trim((string) ($asset->url ?: Storage::disk($asset->disk ?: 'public')->url($asset->path)));
            }
        }

        $manualUrl = trim((string) $request->input('cover_image_url', ''));

        return $manualUrl !== '' ? $manualUrl : $fallback;
    }

    private function resolveBookFile(Request $request, int $activeAppId, string $title): array
    {
        $file = $request->file('book_file');

        if (! $file) {
            return [];
        }

        $safeTitle = Str::slug($title ?: 'book');
        $filename = $safeTitle . '-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(8)) . '.pdf';
        $path = $file->storeAs('assets/app-' . $activeAppId . '/books', $filename, 'public');
        $url = Storage::disk('public')->url($path);

        MediaAsset::create([
            'app_id' => $activeAppId,
            'type' => 'document',
            'label' => $title ?: 'Book PDF',
            'bucket' => 'books',
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => null,
            'height' => null,
            'tags_json' => [
                'source' => 'beginner_books',
                'usage' => 'book_pdf',
            ],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return ['path' => $path, 'url' => $url];
    }

    private function storeUploadedCover(Request $request, int $activeAppId, string $title): string
    {
        $file = $request->file('cover_image_file');

        if (! $file) {
            return '';
        }

        $safeTitle = Str::slug($title ?: 'book-cover');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
        $filename = $safeTitle . '-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(8)) . '.' . $extension;
        $path = $file->storeAs('assets/app-' . $activeAppId . '/book-covers', $filename, 'public');
        $url = Storage::disk('public')->url($path);

        MediaAsset::create([
            'app_id' => $activeAppId,
            'type' => 'image',
            'label' => $title ?: 'Book Cover',
            'bucket' => 'book_covers',
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => null,
            'height' => null,
            'tags_json' => [
                'source' => 'beginner_books',
                'usage' => 'book_cover',
            ],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return $url;
    }

    private function mediaAssets(int $activeAppId)
    {
        return MediaAsset::query()
            ->whereIn('type', ['image', 'document'])
            ->where('is_active', true)
            ->where(function ($query) use ($activeAppId) {
                $query->whereNull('app_id')->orWhere('app_id', $activeAppId);
            })
            ->orderByDesc('id')
            ->limit(400)
            ->get();
    }

    private function categories(int $activeAppId)
    {
        return BookCategory::query()
            ->where('app_id', $activeAppId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function uniqueSlug(int $appId, string $title): string
    {
        $base = Str::slug($title) ?: 'book';
        $slug = $base;
        $count = 2;

        while (Book::query()->where('app_id', $appId)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $count;
            $count++;
        }

        return $slug;
    }

    private function activeAppId(): int
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        abort_unless($activeAppId > 0, 404);
        return $activeAppId;
    }

    private function guardBook(Book $book, int $activeAppId): void
    {
        abort_unless((int) $book->app_id === $activeAppId, 404);
    }

    private function bookTypeOptions(): array
    {
        return [
            'manual' => 'Manual Book / Chapter Book',
            'pdf' => 'PDF Book',
            'external' => 'External Link Book',
            'compiled' => 'Compiled Book',
        ];
    }

    private function coverRatioOptions(): array
    {
        return [
            'portrait_3_4' => 'Portrait Book Cover (3:4)',
            'portrait_4_5' => 'Tall Book Cover (4:5)',
            'portrait_2_3' => 'Classic Paperback (2:3)',
            'square_1_1' => 'Square Booklet (1:1)',
            'landscape_16_9' => 'Landscape Manual (16:9)',
        ];
    }

    private function statusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'published' => 'Published',
            'hidden' => 'Hidden',
        ];
    }

    private function accessOptions(): array
    {
        return [
            'free' => 'Free',
            'login_required' => 'Login Required',
            'premium' => 'Premium Ready',
            'token' => 'Token / Pay Per Read Ready',
        ];
    }
}
