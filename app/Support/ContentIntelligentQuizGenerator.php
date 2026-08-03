<?php

namespace App\Support;

use App\Models\QuizLevel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ContentIntelligentQuizGenerator
{
    public function __construct(
        protected QuizDraftGenerator $fallback
    ) {
    }

    public function generate(string $sourceText, string $sourceTitle, int $count, string $mode, QuizLevel $level): array
    {
        $sourceText = $this->cleanText($sourceText);

        if (mb_strlen($sourceText) < 30) {
            return [];
        }

        if (! $this->canUseOpenAi()) {
            return $this->fallback->generate($sourceText, $sourceTitle, $count, $mode, $level);
        }

        try {
            $generated = $this->generateWithOpenAi($sourceText, $sourceTitle, $count, $mode, $level);

            if (! empty($generated)) {
                return $generated;
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $this->fallback->generate($sourceText, $sourceTitle, $count, $mode, $level);
    }

    protected function canUseOpenAi(): bool
    {
        $provider = strtolower((string) env('CONTENT_INTELLIGENCE_PROVIDER', 'local'));

        if (! in_array($provider, ['openai', 'auto'], true)) {
            return false;
        }

        return filled((string) env('OPENAI_API_KEY', ''));
    }

    protected function generateWithOpenAi(string $sourceText, string $sourceTitle, int $count, string $mode, QuizLevel $level): array
    {
        $model = (string) env('OPENAI_MODEL', 'gpt-4o-mini');
        $timeout = (int) env('CONTENT_INTELLIGENCE_TIMEOUT', 45);

        $response = Http::withToken((string) env('OPENAI_API_KEY'))
            ->timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'temperature' => 0.62,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are Content Intelligent AI for a Christian Bible study quiz engine. Create accurate, thoughtful, non-repetitive quiz questions with Bible references when the source provides or implies them. Return only valid JSON.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildPrompt($sourceText, $sourceTitle, $count, $mode, $level),
                    ],
                ],
            ]);

        if (! $response->successful()) {
            return [];
        }

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');
        $payload = $this->decodeJson($content);
        $items = is_array($payload) ? ($payload['questions'] ?? []) : [];

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(fn ($item) => $this->normalizeQuestion($item))
            ->filter()
            ->take($count)
            ->values()
            ->all();
    }

    protected function buildPrompt(string $sourceText, string $sourceTitle, int $count, string $mode, QuizLevel $level): string
    {
        $quizSet = $level->quizSet;
        $difficulty = $level->difficulty ?: $quizSet->difficulty ?: 'custom';
        $quizType = $quizSet->type ?: 'general';
        $title = trim($sourceTitle) !== '' ? $sourceTitle : $quizSet->title;

        $modeInstruction = match ($mode) {
            'exact_extract' => 'Keep questions close to the source text. Do not invent new teaching outside the supplied text.',
            'fine_tune' => 'Improve clarity and learning value while staying faithful to the source.',
            'application' => 'Focus on practical life application and personal understanding.',
            'scripture_reflection' => 'Focus on spiritual truth, Bible reflection, Bible references, and godly application.',
            'mixed' => 'Use a healthy mix of recall, understanding, application, reference, and spiritual reflection.',
            default => 'Use a balanced quiz style with recall, understanding, reference, and application.',
        };

        return <<<PROMPT
Create {$count} quiz questions for this app quiz.

QUIZ TITLE:
{$title}

QUIZ TYPE:
{$quizType}

CUSTOM LEVEL:
Level {$level->level_number} - {$level->title}

CUSTOM DIFFICULTY / GROUP LABEL:
{$difficulty}

GENERATION MODE:
{$mode}

MODE INSTRUCTION:
{$modeInstruction}

RULES:
- Return valid JSON only.
- Use exactly this JSON shape:
{
  "questions": [
    {
      "question_text": "string",
      "option_a": "string",
      "option_b": "string",
      "option_c": "string",
      "option_d": "string",
      "correct_option": "A",
      "explanation": "string",
      "answer_note": "string",
      "bible_reference": "Genesis 1:2",
      "bible_book": "Genesis",
      "chapter_start": 1,
      "verse_start": 2,
      "chapter_end": 1,
      "verse_end": 2,
      "study_focus": "creation",
      "question_kind": "recall"
    }
  ]
}
- correct_option must be one of: A, B, C, D.
- Make wrong options believable but clearly wrong.
- Avoid repeating the same question wording.
- Every Bible question should include a bible_reference when possible.
- answer_note should explain why the answer is correct and help the learner study.
- explanation may be short, but answer_note should be useful.
- chapter/verse fields should be numbers when the reference is clear, otherwise null.
- Do not include markdown, comments, or extra text outside JSON.

SOURCE TEXT:
{$sourceText}
PROMPT;
    }

    protected function normalizeQuestion(mixed $item): ?array
    {
        if (! is_array($item)) {
            return null;
        }

        $questionText = trim((string) ($item['question_text'] ?? ''));
        $optionA = trim((string) ($item['option_a'] ?? ''));
        $optionB = trim((string) ($item['option_b'] ?? ''));
        $optionC = trim((string) ($item['option_c'] ?? ''));
        $optionD = trim((string) ($item['option_d'] ?? ''));
        $correct = strtoupper(trim((string) ($item['correct_option'] ?? '')));
        $explanation = trim((string) ($item['explanation'] ?? ''));
        $answerNote = trim((string) ($item['answer_note'] ?? $explanation));

        if ($questionText === '' || $optionA === '' || $optionB === '' || $optionC === '' || $optionD === '') {
            return null;
        }

        if (! in_array($correct, ['A', 'B', 'C', 'D'], true)) {
            $correct = 'A';
        }

        return [
            'question_text' => Str::limit($questionText, 5000, ''),
            'option_a' => Str::limit($optionA, 1000, ''),
            'option_b' => Str::limit($optionB, 1000, ''),
            'option_c' => Str::limit($optionC, 1000, ''),
            'option_d' => Str::limit($optionD, 1000, ''),
            'correct_option' => $correct,
            'explanation' => Str::limit($explanation ?: $answerNote ?: 'Review this generated explanation before publishing.', 5000, ''),
            'answer_note' => Str::limit($answerNote ?: $explanation ?: 'Review this answer note before publishing.', 5000, ''),
            'bible_reference' => $this->nullableString($item['bible_reference'] ?? null, 255),
            'bible_book' => $this->nullableString($item['bible_book'] ?? null, 80),
            'chapter_start' => $this->nullableInt($item['chapter_start'] ?? null),
            'verse_start' => $this->nullableInt($item['verse_start'] ?? null),
            'chapter_end' => $this->nullableInt($item['chapter_end'] ?? null),
            'verse_end' => $this->nullableInt($item['verse_end'] ?? null),
            'study_focus' => $this->nullableString($item['study_focus'] ?? null, 120),
            'question_kind' => $this->nullableString($item['question_kind'] ?? null, 80),
            'draft_quality' => 'ai_generated_review_required',
        ];
    }

    protected function nullableString(mixed $value, int $limit): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : Str::limit($value, $limit, '');
    }

    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    protected function decodeJson(string $content): ?array
    {
        $content = trim($content);

        if ($content === '') {
            return null;
        }

        $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?: $content;
        $content = preg_replace('/\s*```$/', '', $content) ?: $content;

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function cleanText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text));
        $text = preg_replace('/\s+/', ' ', $text) ?: '';

        return trim($text);
    }
}
