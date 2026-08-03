<?php

namespace App\Services\BibleEngine;

use App\Models\BibleTopic;
use App\Models\BibleTranslation;
use App\Models\BibleVerse;
use App\Models\ScriptureCollection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BibleEngineService
{
    public function pickerCatalog(?int $appId = null, string $query = '', int $limit = 80): array
    {
        $query = trim($query);

        if ($this->hasBibleTables() && BibleVerse::query()->exists()) {
            return $this->searchVerses($query, $limit);
        }

        return $this->filterStarterCatalog($query, $limit);
    }

    public function searchVerses(string $query = '', int $limit = 80): array
    {
        if (! $this->hasBibleTables()) {
            return $this->filterStarterCatalog($query, $limit);
        }

        $builder = BibleVerse::query()
            ->with(['translation:id,key,name,language', 'topics:id,name,slug'])
            ->orderBy('id')
            ->limit(max(1, min($limit, 250)));

        $query = trim($query);
        if ($query !== '') {
            $terms = preg_split('/\s+/', Str::lower($query)) ?: [];
            $builder->where(function ($where) use ($query, $terms) {
                $where->where('reference', 'like', '%' . $query . '%')
                    ->orWhere('book_name', 'like', '%' . $query . '%')
                    ->orWhere('text', 'like', '%' . $query . '%')
                    ->orWhere('search_text', 'like', '%' . $query . '%')
                    ->orWhereHas('topics', function ($topicQuery) use ($query, $terms) {
                        $topicQuery->where('name', 'like', '%' . $query . '%')
                            ->orWhere('slug', 'like', '%' . Str::slug($query) . '%');
                        foreach ($terms as $term) {
                            if (strlen($term) >= 3) {
                                $topicQuery->orWhere('name', 'like', '%' . $term . '%')
                                    ->orWhere('slug', 'like', '%' . $term . '%');
                            }
                        }
                    });
            });
        }

        return $builder->get()->map(fn (BibleVerse $verse) => $this->formatVerse($verse))->values()->all();
    }

    public function translations(): array
    {
        if (! Schema::hasTable('bible_translations')) {
            return [];
        }

        return BibleTranslation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'key', 'name', 'language', 'license_type', 'copyright_note'])
            ->toArray();
    }

    public function topics(?int $limit = null): array
    {
        if (! Schema::hasTable('bible_topics')) {
            return [];
        }

        $query = BibleTopic::query()->where('is_active', true)->orderBy('name');
        if ($limit !== null) {
            $query->limit(max(1, min($limit, 250)));
        }

        return $query->get(['id', 'name', 'slug', 'description'])->toArray();
    }

    public function collections(?int $appId = null): array
    {
        if (! Schema::hasTable('scripture_collections')) {
            return [];
        }

        return ScriptureCollection::query()
            ->where('is_active', true)
            ->where(function ($q) use ($appId) {
                $q->where('visibility', 'global')
                    ->orWhereNull('app_id');
                if ($appId) {
                    $q->orWhere('app_id', $appId);
                }
            })
            ->orderBy('title')
            ->get(['id', 'app_id', 'title', 'slug', 'description', 'visibility'])
            ->toArray();
    }

    public function starterCatalog(): array
    {
        return [
            ['reference' => 'Isaiah 60:1', 'topic' => 'Light and glory', 'text' => 'Arise, shine; for thy light is come, and the glory of the LORD is risen upon thee.', 'tags' => ['light', 'glory', 'purpose', 'rising']],
            ['reference' => 'Romans 8:37', 'topic' => 'Victory', 'text' => 'Nay, in all these things we are more than conquerors through him that loved us.', 'tags' => ['victory', 'courage', 'faith', 'conquerors']],
            ['reference' => 'Jeremiah 30:17', 'topic' => 'Healing', 'text' => 'For I will restore health unto thee, and I will heal thee of thy wounds, saith the LORD.', 'tags' => ['healing', 'health', 'restoration']],
            ['reference' => 'Psalm 107:20', 'topic' => 'Healing', 'text' => 'He sent his word, and healed them, and delivered them from their destructions.', 'tags' => ['healing', 'word', 'deliverance']],
            ['reference' => 'Proverbs 3:5-6', 'topic' => 'Guidance', 'text' => 'Trust in the LORD with all thine heart; and lean not unto thine own understanding. In all thy ways acknowledge him, and he shall direct thy paths.', 'tags' => ['proverbs', 'guidance', 'trust', 'wisdom', 'direction']],
            ['reference' => 'Proverbs 4:23', 'topic' => 'Heart', 'text' => 'Keep thy heart with all diligence; for out of it are the issues of life.', 'tags' => ['proverbs', 'heart', 'diligence', 'life']],
            ['reference' => 'Proverbs 18:10', 'topic' => 'Protection', 'text' => 'The name of the LORD is a strong tower: the righteous runneth into it, and is safe.', 'tags' => ['proverbs', 'protection', 'safety', 'name']],
            ['reference' => 'Proverbs 16:3', 'topic' => 'Commitment', 'text' => 'Commit thy works unto the LORD, and thy thoughts shall be established.', 'tags' => ['proverbs', 'work', 'commit', 'established']],
            ['reference' => 'Ecclesiastes 3:1', 'topic' => 'Timing', 'text' => 'To every thing there is a season, and a time to every purpose under the heaven.', 'tags' => ['ecclesiastes', 'time', 'season', 'purpose']],
            ['reference' => 'Ecclesiastes 9:10', 'topic' => 'Diligence', 'text' => 'Whatsoever thy hand findeth to do, do it with thy might.', 'tags' => ['ecclesiastes', 'work', 'diligence', 'might']],
            ['reference' => 'Ecclesiastes 12:13', 'topic' => 'Reverence', 'text' => 'Fear God, and keep his commandments: for this is the whole duty of man.', 'tags' => ['ecclesiastes', 'fear god', 'commandments', 'duty']],
            ['reference' => 'Philippians 4:13', 'topic' => 'Strength', 'text' => 'I can do all things through Christ which strengtheneth me.', 'tags' => ['new testament', 'strength', 'faith', 'ability']],
            ['reference' => 'Philippians 4:6-7', 'topic' => 'Peace and prayer', 'text' => 'Be careful for nothing; but in every thing by prayer and supplication with thanksgiving let your requests be made known unto God.', 'tags' => ['new testament', 'prayer', 'peace', 'thanksgiving']],
            ['reference' => 'Matthew 6:33', 'topic' => 'Kingdom priority', 'text' => 'But seek ye first the kingdom of God, and his righteousness; and all these things shall be added unto you.', 'tags' => ['new testament', 'kingdom', 'priority', 'righteousness']],
            ['reference' => 'Mark 11:24', 'topic' => 'Prayer and faith', 'text' => 'What things soever ye desire, when ye pray, believe that ye receive them, and ye shall have them.', 'tags' => ['new testament', 'prayer', 'faith', 'believe']],
            ['reference' => 'Hebrews 11:1', 'topic' => 'Faith', 'text' => 'Now faith is the substance of things hoped for, the evidence of things not seen.', 'tags' => ['new testament', 'faith', 'hope', 'evidence']],
            ['reference' => '2 Timothy 1:7', 'topic' => 'Boldness', 'text' => 'For God hath not given us the spirit of fear; but of power, and of love, and of a sound mind.', 'tags' => ['new testament', 'boldness', 'power', 'love', 'sound mind']],
            ['reference' => '3 John 1:2', 'topic' => 'Prosperity and health', 'text' => 'Beloved, I wish above all things that thou mayest prosper and be in health, even as thy soul prospereth.', 'tags' => ['new testament', 'prosperity', 'health', 'soul']],
            ['reference' => 'James 1:5', 'topic' => 'Wisdom', 'text' => 'If any of you lack wisdom, let him ask of God, that giveth to all men liberally.', 'tags' => ['new testament', 'wisdom', 'ask', 'direction']],
            ['reference' => 'John 14:27', 'topic' => 'Peace', 'text' => 'Peace I leave with you, my peace I give unto you: not as the world giveth, give I unto you.', 'tags' => ['new testament', 'peace', 'comfort', 'heart']],
            ['reference' => 'Romans 10:17', 'topic' => 'Faith by the word', 'text' => 'So then faith cometh by hearing, and hearing by the word of God.', 'tags' => ['new testament', 'faith', 'word', 'hearing']],
            ['reference' => 'Ephesians 3:20', 'topic' => 'Possibility', 'text' => 'Now unto him that is able to do exceeding abundantly above all that we ask or think.', 'tags' => ['new testament', 'possibility', 'power', 'abundance']],
            ['reference' => '1 John 4:4', 'topic' => 'Overcoming', 'text' => 'Greater is he that is in you, than he that is in the world.', 'tags' => ['new testament', 'overcoming', 'greater', 'victory']],
            ['reference' => 'Psalm 119:105', 'topic' => 'Guidance', 'text' => 'Thy word is a lamp unto my feet, and a light unto my path.', 'tags' => ['word', 'light', 'guidance']],
            ['reference' => 'Lamentations 3:22-23', 'topic' => 'Mercy', 'text' => 'It is of the LORD\'S mercies that we are not consumed, because his compassions fail not. They are new every morning.', 'tags' => ['mercy', 'compassion', 'morning']],
        ];
    }

    private function filterStarterCatalog(string $query, int $limit): array
    {
        $items = $this->starterCatalog();
        $query = Str::lower(trim($query));

        if ($query !== '') {
            $items = array_values(array_filter($items, function (array $item) use ($query) {
                $haystack = Str::lower(($item['reference'] ?? '') . ' ' . ($item['topic'] ?? '') . ' ' . ($item['text'] ?? '') . ' ' . implode(' ', $item['tags'] ?? []));
                return str_contains($haystack, $query);
            }));
        }

        return array_slice($items, 0, max(1, min($limit, 250)));
    }

    private function formatVerse(BibleVerse $verse): array
    {
        $topics = $verse->topics->pluck('name')->filter()->values()->all();
        $topic = $topics[0] ?? (string) data_get($verse->meta_json, 'topic', 'Scripture');

        return [
            'id' => $verse->id,
            'reference' => $verse->reference,
            'topic' => $topic,
            'text' => $verse->text,
            'tags' => array_values(array_unique(array_merge($topics, (array) data_get($verse->meta_json, 'tags', [])))),
            'translation_key' => optional($verse->translation)->key,
            'translation_name' => optional($verse->translation)->name,
            'book' => $verse->book_name,
            'chapter' => $verse->chapter_number,
            'verse' => $verse->verse_number,
            'source_engine' => 'bible_engine',
        ];
    }

    private function hasBibleTables(): bool
    {
        return Schema::hasTable('bible_translations')
            && Schema::hasTable('bible_verses')
            && Schema::hasTable('bible_topics');
    }
}
