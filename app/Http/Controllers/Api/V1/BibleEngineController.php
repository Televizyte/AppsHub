<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\BibleBook;
use App\Models\BibleChapter;
use App\Models\BibleTopic;
use App\Models\BibleTranslation;
use App\Services\BibleEngine\BibleEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BibleEngineController extends Controller
{
    public function translations(string $appSlug): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'engine' => 'bible_engine',
            'translations' => app(BibleEngineService::class)->translations(),
        ]);
    }

    public function books(string $appSlug, Request $request): JsonResponse
    {
        if (! Schema::hasTable('bible_books')) {
            return response()->json(['ok' => true, 'engine' => 'bible_engine', 'books' => []]);
        }

        $translationKey = trim((string) $request->query('translation', ''));
        $query = BibleBook::query()->with('translation:id,key,name')->orderBy('book_number');
        if ($translationKey !== '') {
            $query->whereHas('translation', fn ($q) => $q->where('key', $translationKey));
        }

        return response()->json([
            'ok' => true,
            'engine' => 'bible_engine',
            'books' => $query->get(['id', 'translation_id', 'book_number', 'testament', 'name', 'short_name', 'slug', 'chapter_count']),
        ]);
    }

    public function chapters(string $appSlug, string $book, Request $request): JsonResponse
    {
        if (! Schema::hasTable('bible_chapters')) {
            return response()->json(['ok' => true, 'engine' => 'bible_engine', 'chapters' => []]);
        }

        $translationKey = trim((string) $request->query('translation', ''));
        $query = BibleChapter::query()->with('book:id,name,slug')->orderBy('chapter_number')
            ->whereHas('book', fn ($q) => $q->where('slug', $book)->orWhere('name', $book));
        if ($translationKey !== '') {
            $query->whereHas('translation', fn ($q) => $q->where('key', $translationKey));
        }

        return response()->json([
            'ok' => true,
            'engine' => 'bible_engine',
            'chapters' => $query->get(['id', 'translation_id', 'book_id', 'chapter_number', 'verse_count']),
        ]);
    }

    public function verses(string $appSlug, Request $request): JsonResponse
    {
        $service = app(BibleEngineService::class);
        $q = trim((string) ($request->query('q') ?: $request->query('reference') ?: ''));

        return response()->json([
            'ok' => true,
            'engine' => 'bible_engine',
            'items' => $service->pickerCatalog($this->appId($appSlug), $q, (int) $request->query('limit', 80)),
        ]);
    }

    public function search(string $appSlug, Request $request): JsonResponse
    {
        return $this->verses($appSlug, $request);
    }

    public function topics(string $appSlug): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'engine' => 'bible_engine',
            'topics' => app(BibleEngineService::class)->topics(),
        ]);
    }

    public function topicVerses(string $appSlug, string $topic, Request $request): JsonResponse
    {
        if (! Schema::hasTable('bible_topics')) {
            return response()->json(['ok' => true, 'engine' => 'bible_engine', 'items' => []]);
        }

        $found = BibleTopic::query()
            ->where('slug', $topic)
            ->orWhere('name', $topic)
            ->first();

        if (! $found) {
            return response()->json(['ok' => true, 'engine' => 'bible_engine', 'items' => []]);
        }

        $items = $found->verses()->with('translation:id,key,name')->limit((int) $request->query('limit', 100))->get()
            ->map(fn ($verse) => [
                'id' => $verse->id,
                'reference' => $verse->reference,
                'topic' => $found->name,
                'text' => $verse->text,
                'translation_key' => optional($verse->translation)->key,
                'translation_name' => optional($verse->translation)->name,
                'source_engine' => 'bible_engine',
            ])->values();

        return response()->json(['ok' => true, 'engine' => 'bible_engine', 'topic' => $found, 'items' => $items]);
    }

    public function collections(string $appSlug): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'engine' => 'bible_engine',
            'collections' => app(BibleEngineService::class)->collections($this->appId($appSlug)),
        ]);
    }

    private function appId(string $appSlug): ?int
    {
        return App::query()->where('slug', $appSlug)->value('id');
    }
}
