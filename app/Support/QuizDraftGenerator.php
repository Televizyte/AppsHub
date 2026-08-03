<?php

namespace App\Support;

use App\Models\QuizLevel;
use Illuminate\Support\Str;

class QuizDraftGenerator
{
    public function generate(string $sourceText, string $sourceTitle, int $count, string $mode, QuizLevel $level): array
    {
        $clean = $this->cleanText($sourceText);

        if (mb_strlen($clean) < 30) {
            return [];
        }

        $segments = $this->rankSegments($clean);
        $segments = $this->uniqueSegments($segments);

        if (empty($segments)) {
            return [];
        }

        $mode = $mode === 'mixed' ? 'mixed' : $mode;
        $questions = [];
        $usedQuestionSignatures = [];

        for ($i = 0; $i < $count; $i++) {
            $segment = $segments[$i % count($segments)];
            $style = $this->styleFor($mode, $i);
            $draft = $this->buildQuestion(
                segment: $segment,
                sourceTitle: $sourceTitle ?: (string) $level->quizSet->title,
                style: $style,
                allSegments: $segments,
                index: $i
            );

            $signature = md5(Str::lower($draft['question_text']));

            if (isset($usedQuestionSignatures[$signature])) {
                $fallbackIndex = ($i + 1) % count($segments);
                $draft = $this->buildQuestion(
                    segment: $segments[$fallbackIndex],
                    sourceTitle: $sourceTitle ?: (string) $level->quizSet->title,
                    style: $this->styleFor('mixed', $i + 1),
                    allSegments: $segments,
                    index: $i + 1
                );
                $signature = md5(Str::lower($draft['question_text']));
            }

            $usedQuestionSignatures[$signature] = true;
            $questions[] = $draft;
        }

        return $questions;
    }

    protected function cleanText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text));
        $text = preg_replace('/\s+/', ' ', $text) ?: '';
        $text = preg_replace('/[“”]/u', '"', $text) ?: $text;
        $text = preg_replace("/[‘’]/u", "'", $text) ?: $text;

        return trim($text);
    }

    protected function rankSegments(string $clean): array
    {
        $raw = preg_split('/(?<=[.!?])\s+|[\r\n]+/', $clean) ?: [];
        $segments = [];

        foreach ($raw as $piece) {
            $piece = trim($piece);

            if (mb_strlen($piece) < 45) {
                continue;
            }

            $piece = Str::limit($piece, 260, '');
            $score = $this->scoreSegment($piece);

            $segments[] = [
                'text' => $piece,
                'score' => $score,
            ];
        }

        if (empty($segments)) {
            foreach (str_split($clean, 220) as $chunk) {
                $chunk = trim($chunk);

                if ($chunk !== '') {
                    $segments[] = [
                        'text' => $chunk,
                        'score' => $this->scoreSegment($chunk),
                    ];
                }
            }
        }

        usort($segments, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_values(array_map(fn ($item) => $item['text'], $segments));
    }

    protected function scoreSegment(string $segment): int
    {
        $lower = Str::lower($segment);
        $score = 0;

        $keywords = [
            'god', 'lord', 'spirit', 'faith', 'power', 'scripture', 'prayer',
            'wisdom', 'destiny', 'word', 'grace', 'truth', 'life', 'heart',
            'obedience', 'presence', 'access', 'purpose', 'kingdom', 'christ',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                $score += 8;
            }
        }

        $length = mb_strlen($segment);
        if ($length >= 80 && $length <= 180) {
            $score += 10;
        } elseif ($length > 180) {
            $score += 4;
        }

        if (preg_match('/\b(because|therefore|so that|in order|through|by|when|if)\b/i', $segment)) {
            $score += 6;
        }

        if (preg_match('/\b\d+[:.]\d+\b/', $segment)) {
            $score += 5;
        }

        return $score;
    }

    protected function uniqueSegments(array $segments): array
    {
        $seen = [];
        $unique = [];

        foreach ($segments as $segment) {
            $signature = md5(Str::lower(preg_replace('/[^a-z0-9]+/i', '', Str::limit($segment, 120, ''))));

            if (isset($seen[$signature])) {
                continue;
            }

            $seen[$signature] = true;
            $unique[] = $segment;
        }

        return $unique;
    }

    protected function styleFor(string $mode, int $index): string
    {
        if ($mode !== 'mixed') {
            return $mode;
        }

        $styles = ['exact_extract', 'fine_tune', 'application', 'scripture_reflection', 'main_lesson'];

        return $styles[$index % count($styles)];
    }

    protected function buildQuestion(string $segment, string $sourceTitle, string $style, array $allSegments, int $index): array
    {
        $answer = $this->answerFromSegment($segment);
        $question = match ($style) {
            'exact_extract' => 'According to the source text, which statement best reflects the teaching?',
            'application' => 'How can this teaching be applied in daily life?',
            'scripture_reflection' => 'What spiritual truth is emphasized by this part of the lesson?',
            'main_lesson' => 'What is the main lesson from this part of ' . ($sourceTitle ?: 'the teaching') . '?',
            default => 'What key understanding should be taken from this part of the teaching?',
        };

        if ($style === 'exact_extract') {
            $question .= ' "' . Str::limit($segment, 95, '...') . '"';
        }

        $distractors = $this->buildDistractors($answer, $allSegments, $index);
        $options = $this->shuffleOptions([$answer, ...$distractors], $index);

        return [
            'question_text' => $question,
            'option_a' => $options['A'],
            'option_b' => $options['B'],
            'option_c' => $options['C'],
            'option_d' => $options['D'],
            'correct_option' => $options['correct'],
            'explanation' => $this->buildExplanation($answer, $style),
            'draft_quality' => $this->qualityLabel($segment, $distractors),
        ];
    }

    protected function answerFromSegment(string $segment): string
    {
        $segment = trim($segment);
        $segment = preg_replace('/^\W+|\W+$/', '', $segment) ?: $segment;

        return Str::limit($segment, 155, '');
    }

    protected function buildDistractors(string $answer, array $segments, int $index): array
    {
        $pool = collect($segments)
            ->filter(fn ($segment) => Str::lower($segment) !== Str::lower($answer))
            ->values();

        $templates = [
            'The lesson is mainly about personal opinion rather than spiritual truth.',
            'The source teaches that preparation, obedience, and faith are not important.',
            'The main focus is unrelated to the message and should be corrected during review.',
            'The passage only discusses outward activity without any heart or spiritual lesson.',
            'The teaching suggests that growth happens without discipline, prayer, or understanding.',
        ];

        $distractors = [];

        for ($i = 0; $i < 3; $i++) {
            $candidate = $pool[($index + $i + 1) % max(1, $pool->count())] ?? null;

            if ($candidate && mb_strlen((string) $candidate) > 45) {
                $distractors[] = Str::limit((string) $candidate, 145, '');
            } else {
                $distractors[] = $templates[($index + $i) % count($templates)];
            }
        }

        return array_values(array_unique($distractors));
    }

    protected function shuffleOptions(array $options, int $index): array
    {
        $options = array_values(array_pad(array_slice($options, 0, 4), 4, 'Review and replace this option.'));
        $correct = $options[0];

        $rotation = $index % 4;
        for ($i = 0; $i < $rotation; $i++) {
            $first = array_shift($options);
            $options[] = $first;
        }

        $keys = ['A', 'B', 'C', 'D'];
        $mapped = [];

        foreach ($keys as $i => $key) {
            $mapped[$key] = $options[$i];
            if ($options[$i] === $correct) {
                $mapped['correct'] = $key;
            }
        }

        $mapped['correct'] ??= 'A';

        return $mapped;
    }

    protected function buildExplanation(string $answer, string $style): string
    {
        $prefix = match ($style) {
            'application' => 'This option best applies the lesson because it connects the teaching to practical response.',
            'scripture_reflection' => 'This option best captures the spiritual truth emphasized in the source text.',
            'exact_extract' => 'This option is closest to the source text and should be checked against the original wording.',
            default => 'This option best summarizes the key lesson from the selected part of the teaching.',
        };

        return $prefix . ' Review before publishing. Source basis: ' . Str::limit($answer, 160, '');
    }

    protected function qualityLabel(string $segment, array $distractors): string
    {
        if (mb_strlen($segment) > 80 && count(array_unique($distractors)) >= 3) {
            return 'good_draft';
        }

        return 'needs_review';
    }
}
