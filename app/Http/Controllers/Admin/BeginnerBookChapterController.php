<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookChapter;
use App\Models\MediaAsset;
use App\Support\ActiveApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BeginnerBookChapterController extends Controller
{
    public function index(Book $book): View
    {
        $activeAppId = $this->activeAppId();
        $this->guardBook($book, $activeAppId);

        return view('admin.beginner.books.chapters', [
            'book' => $book->load(['category', 'chapters']),
            'chapters' => $book->chapters()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create(Book $book): View
    {
        $activeAppId = $this->activeAppId();
        $this->guardBook($book, $activeAppId);

        return view('admin.beginner.books.chapter-create', [
            'book' => $book->load(['category', 'chapters']),
            'chapters' => $book->chapters()->orderBy('sort_order')->orderBy('id')->get(),
            'chapter' => new BookChapter([
                'status' => 'draft',
                'sort_order' => ((int) $book->chapters()->max('sort_order')) + 10,
            ]),
            'mediaAssets' => $this->mediaAssets($activeAppId),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request, Book $book): RedirectResponse
    {
        $activeAppId = $this->activeAppId();
        $this->guardBook($book, $activeAppId);

        $data = $this->validated($request);

        BookChapter::create([
            'book_id' => $book->id,
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($book, $data['title']),
            'subtitle' => $data['subtitle'] ?? null,
            'body_html' => $data['body_html'] ?? null,
            'summary' => $data['summary'] ?? null,
            'key_thought' => $data['key_thought'] ?? null,
            'reflection_questions' => $data['reflection_questions'] ?? null,
            'prayer_points' => $data['prayer_points'] ?? null,
            'action_steps' => $data['action_steps'] ?? null,
            'memory_verse' => $data['memory_verse'] ?? null,
            'status' => $data['status'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'meta_json' => [
                'editor_version' => 'book_builder_1_2',
                'read_aloud_ready' => true,
                'ai_ready' => true,
                'media_insert_ready' => true,
                'page_preview_ready' => true,
            ],
        ]);

        return redirect()
            ->route('admin.beginner.books.chapters.index', $book)
            ->with('status', 'Chapter created successfully.');
    }

    public function edit(BookChapter $chapter): View
    {
        $activeAppId = $this->activeAppId();
        $chapter->load('book.category', 'book.chapters');
        $this->guardChapter($chapter, $activeAppId);

        return view('admin.beginner.books.chapter-edit', [
            'book' => $chapter->book,
            'chapters' => $chapter->book->chapters()->orderBy('sort_order')->orderBy('id')->get(),
            'chapter' => $chapter,
            'mediaAssets' => $this->mediaAssets($activeAppId),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, BookChapter $chapter): RedirectResponse
    {
        $activeAppId = $this->activeAppId();
        $chapter->load('book');
        $this->guardChapter($chapter, $activeAppId);

        $data = $this->validated($request);
        $meta = is_array($chapter->meta_json) ? $chapter->meta_json : [];
        $meta['editor_version'] = 'book_builder_1_2';
        $meta['read_aloud_ready'] = true;
        $meta['ai_ready'] = true;
        $meta['media_insert_ready'] = true;
        $meta['page_preview_ready'] = true;

        $chapter->update([
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'body_html' => $data['body_html'] ?? null,
            'summary' => $data['summary'] ?? null,
            'key_thought' => $data['key_thought'] ?? null,
            'reflection_questions' => $data['reflection_questions'] ?? null,
            'prayer_points' => $data['prayer_points'] ?? null,
            'action_steps' => $data['action_steps'] ?? null,
            'memory_verse' => $data['memory_verse'] ?? null,
            'status' => $data['status'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'meta_json' => $meta,
        ]);

        return redirect()
            ->route('admin.beginner.books.chapters.index', $chapter->book)
            ->with('status', 'Chapter updated successfully.');
    }

    public function destroy(BookChapter $chapter): RedirectResponse
    {
        $activeAppId = $this->activeAppId();
        $chapter->load('book');
        $this->guardChapter($chapter, $activeAppId);
        $book = $chapter->book;

        $chapter->delete();

        return redirect()
            ->route('admin.beginner.books.chapters.index', $book)
            ->with('status', 'Chapter deleted successfully.');
    }

    public function move(BookChapter $chapter, string $direction): RedirectResponse
    {
        $activeAppId = $this->activeAppId();
        $chapter->load('book');
        $this->guardChapter($chapter, $activeAppId);

        $direction = strtolower(trim($direction));
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $query = BookChapter::query()->where('book_id', $chapter->book_id);

        $swap = $direction === 'up'
            ? (clone $query)->where('sort_order', '<', $chapter->sort_order)->orderByDesc('sort_order')->orderByDesc('id')->first()
            : (clone $query)->where('sort_order', '>', $chapter->sort_order)->orderBy('sort_order')->orderBy('id')->first();

        if ($swap) {
            $currentOrder = (int) $chapter->sort_order;
            $chapter->update(['sort_order' => (int) $swap->sort_order]);
            $swap->update(['sort_order' => $currentOrder]);
        }

        return redirect()->route('admin.beginner.books.chapters.index', $chapter->book);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'body_html' => ['nullable', 'string'],
            'summary' => ['nullable', 'string'],
            'key_thought' => ['nullable', 'string'],
            'reflection_questions' => ['nullable', 'string'],
            'prayer_points' => ['nullable', 'string'],
            'action_steps' => ['nullable', 'string'],
            'memory_verse' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published,hidden'],
            'sort_order' => ['nullable', 'integer'],
        ]);
    }

    private function mediaAssets(int $activeAppId)
    {
        return MediaAsset::query()
            ->where('type', 'image')
            ->where('is_active', true)
            ->where(function ($query) use ($activeAppId) {
                $query->whereNull('app_id')->orWhere('app_id', $activeAppId);
            })
            ->orderByDesc('id')
            ->limit(500)
            ->get();
    }

    private function uniqueSlug(Book $book, string $title): string
    {
        $base = Str::slug($title) ?: 'chapter';
        $slug = $base;
        $count = 2;

        while (BookChapter::query()->where('book_id', $book->id)->where('slug', $slug)->exists()) {
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

    private function guardChapter(BookChapter $chapter, int $activeAppId): void
    {
        abort_unless($chapter->book && (int) $chapter->book->app_id === $activeAppId, 404);
    }

    private function statusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'published' => 'Published',
            'hidden' => 'Hidden',
        ];
    }
}
