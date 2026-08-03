<?php

namespace App\Console\Commands;

use App\Models\BibleBook;
use App\Models\BibleChapter;
use App\Models\BibleTopic;
use App\Models\BibleTranslation;
use App\Models\BibleVerse;
use App\Models\ScriptureCollection;
use App\Services\BibleEngine\BibleEngineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BibleEngineSeedStarter extends Command
{
    protected $signature = 'bible-engine:seed-starter {--translation=kjv} {--name=}';

    protected $description = 'Seed a starter Bible Engine scripture catalog for picker/testing. Not a full Bible import.';

    public function handle(): int
    {
        if (! Schema::hasTable('bible_translations')) {
            $this->error('Bible Engine tables are missing. Run php artisan migrate first.');
            return self::FAILURE;
        }

        $key = Str::slug((string) $this->option('translation')) ?: 'kjv';
        $translation = BibleTranslation::query()->updateOrCreate(
            ['key' => $key],
            [
                'name' => trim((string) $this->option('name')) !== '' ? (string) $this->option('name') : 'King James Version',
                'language' => 'en',
                'license_type' => 'public_domain_or_licensed',
                'copyright_note' => 'Starter references only. Confirm translation licensing before full production import.',
                'is_active' => true,
            ]
        );

        $service = app(BibleEngineService::class);
        $count = 0;

        foreach ($service->starterCatalog() as $item) {
            [$bookName, $chapter, $verse] = $this->parseReference((string) $item['reference']);
            if ($bookName === '') {
                continue;
            }

            $book = BibleBook::query()->firstOrCreate(
                ['translation_id' => $translation->id, 'slug' => Str::slug($bookName)],
                [
                    'book_number' => $this->bookNumber($bookName),
                    'testament' => $this->testament($bookName),
                    'name' => $bookName,
                    'short_name' => $bookName,
                    'chapter_count' => max(1, $chapter),
                ]
            );

            if ($book->chapter_count < $chapter) {
                $book->chapter_count = $chapter;
                $book->save();
            }

            $chapterRow = BibleChapter::query()->firstOrCreate(
                ['translation_id' => $translation->id, 'book_id' => $book->id, 'chapter_number' => $chapter],
                ['verse_count' => max(1, $verse)]
            );

            if ($chapterRow->verse_count < $verse) {
                $chapterRow->verse_count = $verse;
                $chapterRow->save();
            }

            $verseRow = BibleVerse::query()->updateOrCreate(
                [
                    'translation_id' => $translation->id,
                    'book_id' => $book->id,
                    'chapter_number' => $chapter,
                    'verse_number' => $verse,
                ],
                [
                    'chapter_id' => $chapterRow->id,
                    'book_name' => $bookName,
                    'reference' => (string) $item['reference'],
                    'text' => (string) $item['text'],
                    'search_text' => Str::lower((string) $item['reference'] . ' ' . (string) $item['topic'] . ' ' . (string) $item['text'] . ' ' . implode(' ', $item['tags'] ?? [])),
                    'meta_json' => [
                        'topic' => (string) $item['topic'],
                        'tags' => $item['tags'] ?? [],
                        'starter_seed' => true,
                    ],
                ]
            );

            foreach ((array) ($item['tags'] ?? []) as $tag) {
                $slug = Str::slug((string) $tag);
                if ($slug === '') {
                    continue;
                }
                $topic = BibleTopic::query()->firstOrCreate(
                    ['slug' => $slug],
                    ['name' => Str::headline((string) $tag), 'is_active' => true]
                );
                $topic->verses()->syncWithoutDetaching([$verseRow->id => ['weight' => 50]]);
            }

            $mainTopicSlug = Str::slug((string) $item['topic']);
            if ($mainTopicSlug !== '') {
                $topic = BibleTopic::query()->firstOrCreate(
                    ['slug' => $mainTopicSlug],
                    ['name' => (string) $item['topic'], 'is_active' => true]
                );
                $topic->verses()->syncWithoutDetaching([$verseRow->id => ['weight' => 80]]);
            }

            $count++;
        }

        $this->seedCollections();

        $this->info("Seeded/updated {$count} starter Bible Engine verses.");
        return self::SUCCESS;
    }

    private function seedCollections(): void
    {
        $collections = [
            'Healing Scriptures' => 'healing',
            'Faith Scriptures' => 'faith',
            'Prayer Scriptures' => 'prayer',
            'Wisdom Scriptures' => 'wisdom',
            'Encouragement Scriptures' => 'encouragement',
            'Protection Scriptures' => 'protection',
            'Victory Scriptures' => 'victory',
        ];

        foreach ($collections as $title => $topicSlug) {
            $collection = ScriptureCollection::query()->firstOrCreate(
                ['app_id' => null, 'slug' => Str::slug($title)],
                ['title' => $title, 'description' => $title . ' for reusable Bible Engine picker flows.', 'visibility' => 'global', 'is_active' => true]
            );

            $topic = BibleTopic::query()->where('slug', $topicSlug)->first();
            if (! $topic) {
                continue;
            }

            $verseIds = $topic->verses()->pluck('bible_verses.id')->values()->all();
            foreach ($verseIds as $index => $verseId) {
                $collection->verses()->syncWithoutDetaching([$verseId => ['sort_order' => $index + 1]]);
            }
        }
    }

    private function parseReference(string $reference): array
    {
        if (! preg_match('/^(.+?)\s+(\d+):(\d+)/', trim($reference), $matches)) {
            return ['', 1, 1];
        }

        return [trim($matches[1]), (int) $matches[2], (int) $matches[3]];
    }

    private function testament(string $bookName): string
    {
        $new = ['Matthew', 'Mark', 'Luke', 'John', 'Acts', 'Romans', '1 Corinthians', '2 Corinthians', 'Galatians', 'Ephesians', 'Philippians', 'Colossians', '1 Thessalonians', '2 Thessalonians', '1 Timothy', '2 Timothy', 'Titus', 'Philemon', 'Hebrews', 'James', '1 Peter', '2 Peter', '1 John', '2 John', '3 John', 'Jude', 'Revelation'];
        return in_array($bookName, $new, true) ? 'new' : 'old';
    }

    private function bookNumber(string $bookName): int
    {
        $books = [
            'Genesis','Exodus','Leviticus','Numbers','Deuteronomy','Joshua','Judges','Ruth','1 Samuel','2 Samuel','1 Kings','2 Kings','1 Chronicles','2 Chronicles','Ezra','Nehemiah','Esther','Job','Psalm','Proverbs','Ecclesiastes','Song of Solomon','Isaiah','Jeremiah','Lamentations','Ezekiel','Daniel','Hosea','Joel','Amos','Obadiah','Jonah','Micah','Nahum','Habakkuk','Zephaniah','Haggai','Zechariah','Malachi','Matthew','Mark','Luke','John','Acts','Romans','1 Corinthians','2 Corinthians','Galatians','Ephesians','Philippians','Colossians','1 Thessalonians','2 Thessalonians','1 Timothy','2 Timothy','Titus','Philemon','Hebrews','James','1 Peter','2 Peter','1 John','2 John','3 John','Jude','Revelation'
        ];
        $index = array_search($bookName, $books, true);
        return $index === false ? 999 : $index + 1;
    }
}
