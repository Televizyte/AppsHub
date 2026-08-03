<?php

namespace App\Services\Books;

use App\Models\Book;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class BookCoverRenderer
{
    /**
     * The admin Live Design preview is 252px wide. All cover controls are stored
     * as designer units against that preview width, then scaled into the final PNG.
     */
    private const PREVIEW_BASE_WIDTH = 252;

    public function render(Book $book): ?array
    {
        $meta = is_array($book->meta_json) ? $book->meta_json : [];
        $designer = is_array($meta['cover_designer'] ?? null) ? $meta['cover_designer'] : [];

        if (! (bool) ($designer['enabled'] ?? false)) {
            return null;
        }

        if (! extension_loaded('gd') || ! function_exists('imagecreatetruecolor')) {
            return $this->renderSvgFallback($book, $designer, $meta);
        }

        try {
            return $this->renderPng($book, $designer, $meta);
        } catch (Throwable $e) {
            report($e);
            return $this->renderSvgFallback($book, $designer, $meta);
        }
    }

    private function renderPng(Book $book, array $designer, array $meta): array
    {
        [$width, $height] = $this->canvasSize((string) ($meta['cover_ratio'] ?? 'portrait_3_4'));

        $image = imagecreatetruecolor($width, $height);
        imagesavealpha($image, true);
        imagealphablending($image, true);

        [$startR, $startG, $startB] = $this->hexToRgb((string) ($designer['bg_color'] ?? '#0B1F4D'));
        [$endR, $endG, $endB] = $this->hexToRgb((string) ($designer['gradient_color'] ?? '#E2388A'));

        for ($y = 0; $y < $height; $y++) {
            $t = $height > 1 ? $y / ($height - 1) : 0;
            $r = (int) round($startR + (($endR - $startR) * $t));
            $g = (int) round($startG + (($endG - $startG) * $t));
            $b = (int) round($startB + (($endB - $startB) * $t));
            imageline($image, 0, $y, $width, $y, imagecolorallocate($image, $r, $g, $b));
        }

        $bg = $this->loadBackground((string) ($designer['bg_image_url'] ?? $book->cover_image_src ?? ''));
        if ($bg) {
            $this->copyBackground(
                $image,
                $bg,
                $width,
                $height,
                (string) ($designer['bg_fit'] ?? 'cover'),
                (string) ($designer['bg_position'] ?? 'center')
            );
            imagedestroy($bg);
        }

        $overlay = max(0, min(90, (int) ($designer['overlay'] ?? 42)));
        if ($overlay > 0) {
            imagefilledrectangle(
                $image,
                0,
                0,
                $width,
                $height,
                imagecolorallocatealpha($image, 0, 0, 0, (int) round(127 * ($overlay / 100)))
            );
        }

        $this->drawSpine($image, $width, $height);
        $this->drawCoverText($image, $book, $designer, $width, $height);

        $safeSlug = Str::slug((string) ($book->slug ?: $book->title ?: 'book')) ?: 'book';
        $filename = $safeSlug . '-cover-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(6)) . '.png';
        $path = 'assets/app-' . (int) $book->app_id . '/book-covers/final/' . $filename;
        $absolute = Storage::disk('public')->path($path);

        if (! is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0775, true);
        }

        imagepng($image, $absolute, 9);
        imagedestroy($image);

        $url = Storage::disk('public')->url($path);
        $this->registerMediaAsset($book, $path, $url, 'image/png', filesize($absolute) ?: null);

        return [
            'path' => $path,
            'url' => $url,
            'mime' => 'image/png',
        ];
    }

    private function renderSvgFallback(Book $book, array $designer, array $meta): array
    {
        [$width, $height] = $this->canvasSize((string) ($meta['cover_ratio'] ?? 'portrait_3_4'));
        $safeSlug = Str::slug((string) ($book->slug ?: $book->title ?: 'book')) ?: 'book';
        $filename = $safeSlug . '-cover-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(6)) . '.svg';
        $path = 'assets/app-' . (int) $book->app_id . '/book-covers/final/' . $filename;

        $bg = e((string) ($designer['bg_image_url'] ?? $book->cover_image_src ?? ''));
        $bgColor = e((string) ($designer['bg_color'] ?? '#0B1F4D'));
        $gradient = e((string) ($designer['gradient_color'] ?? '#E2388A'));
        $textColor = e((string) ($designer['text_color'] ?? '#FFFFFF'));
        $overlay = max(0, min(90, (int) ($designer['overlay'] ?? 42))) / 100;

        $title = e($this->applyTitleCase((string) ($designer['title'] ?? $book->title ?? 'Book'), (string) ($designer['title_case'] ?? 'as_typed')));
        $subtitle = e((string) ($designer['subtitle'] ?? $book->subtitle ?? ''));
        $author = e(strtoupper((string) ($designer['author'] ?? $book->author_name ?? '')));
        $badge = e(strtoupper((string) ($designer['badge'] ?? '')));

        $scale = $width / self::PREVIEW_BASE_WIDTH;
        $titleSize = max(12, (int) ($designer['title_size'] ?? 34)) * $scale;
        $subtitleSize = max(8, (int) ($designer['subtitle_size'] ?? 15)) * $scale;
        $authorSize = max(8, (int) ($designer['author_size'] ?? 13)) * $scale;
        $badgeSize = max(6, (int) ($designer['badge_size'] ?? 10)) * $scale;

        $align = in_array(($designer['text_align'] ?? 'left'), ['left', 'center', 'right'], true) ? $designer['text_align'] : 'left';
        $anchor = $align === 'center' ? 'middle' : ($align === 'right' ? 'end' : 'start');
        $textWidth = max(35, min(100, (int) ($designer['text_width'] ?? 88))) / 100;
        $boxW = $width * $textWidth;
        $side = 22 * $scale;
        $x = $align === 'center' ? $width / 2 : ($align === 'right' ? $width - $side : $side);
        $position = (string) ($designer['text_position'] ?? 'bottom');
        $startY = $position === 'top' ? 60 * $scale : ($position === 'center' ? (int) ($height * .43) : (int) ($height * .60));
        $fontFamily = $this->svgFontFamily((string) ($designer['font_family'] ?? 'display'));

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{$bgColor}"/><stop offset="1" stop-color="{$gradient}"/></linearGradient>
    <filter id="shadow"><feDropShadow dx="0" dy="8" stdDeviation="10" flood-color="#000" flood-opacity="0.55"/></filter>
  </defs>
  <rect width="100%" height="100%" fill="url(#g)"/>
SVG;

        if ($bg !== '') {
            $svg .= "\n  <image href=\"{$bg}\" x=\"0\" y=\"0\" width=\"{$width}\" height=\"{$height}\" preserveAspectRatio=\"xMidYMid slice\"/>";
        }

        $svg .= "\n  <rect width=\"100%\" height=\"100%\" fill=\"#000\" opacity=\"{$overlay}\"/>";
        $svg .= "\n  <rect width=\"14%\" height=\"100%\" fill=\"#000\" opacity=\"0.25\"/>";
        $svg .= "\n  <g filter=\"url(#shadow)\" fill=\"{$textColor}\" text-anchor=\"{$anchor}\" font-family=\"{$fontFamily}\">";

        if ($badge !== '') {
            $svg .= "\n    <text x=\"{$x}\" y=\"" . ($startY - (18 * $scale)) . "\" font-size=\"{$badgeSize}\" font-weight=\"900\" letter-spacing=\"" . (2 * $scale) . "\">{$badge}</text>";
        }

        $lineY = $startY + ($titleSize * .9);
        foreach ($this->svgLines($title, $boxW, $titleSize) as $line) {
            $svg .= "\n    <text x=\"{$x}\" y=\"{$lineY}\" font-size=\"{$titleSize}\" font-weight=\"900\" letter-spacing=\"" . (-1.2 * $scale) . "\">{$line}</text>";
            $lineY += $titleSize * 1.02;
        }

        if ($subtitle !== '') {
            $lineY += 8 * $scale;
            foreach ($this->svgLines($subtitle, $boxW, $subtitleSize) as $line) {
                $svg .= "\n    <text x=\"{$x}\" y=\"{$lineY}\" font-size=\"{$subtitleSize}\" font-weight=\"700\">{$line}</text>";
                $lineY += $subtitleSize * 1.25;
            }
        }

        if ($author !== '') {
            $lineY += 16 * $scale;
            $svg .= "\n    <text x=\"{$x}\" y=\"{$lineY}\" font-size=\"{$authorSize}\" font-weight=\"900\" letter-spacing=\"" . (2 * $scale) . "\" opacity=\"0.86\">{$author}</text>";
        }

        $svg .= "\n  </g>\n</svg>\n";

        Storage::disk('public')->put($path, $svg);
        $url = Storage::disk('public')->url($path);
        $this->registerMediaAsset($book, $path, $url, 'image/svg+xml', strlen($svg));

        return [
            'path' => $path,
            'url' => $url,
            'mime' => 'image/svg+xml',
        ];
    }

    private function canvasSize(string $ratio): array
    {
        return match ($ratio) {
            'portrait_4_5' => [960, 1200],
            'portrait_2_3' => [800, 1200],
            'square_1_1' => [1080, 1080],
            'landscape_16_9' => [1600, 900],
            default => [900, 1200],
        };
    }

    private function loadBackground(string $url)
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $contents = null;

        if (Str::startsWith($url, ['/storage/', 'storage/'])) {
            $relative = Str::after($url, 'storage/');
            $relative = ltrim(Str::after($relative, '/storage/'), '/');
            if (Storage::disk('public')->exists($relative)) {
                $contents = Storage::disk('public')->get($relative);
            }
        } elseif (Str::startsWith($url, config('app.url'))) {
            $path = parse_url($url, PHP_URL_PATH) ?: '';
            $relative = ltrim(Str::after($path, '/storage/'), '/');
            if ($relative !== '' && Storage::disk('public')->exists($relative)) {
                $contents = Storage::disk('public')->get($relative);
            }
        }

        if ($contents === null) {
            $contents = @file_get_contents($url);
        }

        if (! $contents) {
            return null;
        }

        return @imagecreatefromstring($contents) ?: null;
    }

    private function copyBackground($canvas, $bg, int $width, int $height, string $fit, string $position): void
    {
        $srcW = imagesx($bg);
        $srcH = imagesy($bg);
        if ($srcW <= 0 || $srcH <= 0) {
            return;
        }

        if ($fit === 'contain') {
            $scale = min($width / $srcW, $height / $srcH);
        } else {
            $scale = max($width / $srcW, $height / $srcH);
        }

        $dstW = (int) round($srcW * $scale);
        $dstH = (int) round($srcH * $scale);
        $dstX = (int) round(($width - $dstW) / 2);
        $dstY = (int) round(($height - $dstH) / 2);

        if ($position === 'top') {
            $dstY = 0;
        }
        if ($position === 'bottom') {
            $dstY = $height - $dstH;
        }
        if ($position === 'left') {
            $dstX = 0;
        }
        if ($position === 'right') {
            $dstX = $width - $dstW;
        }

        imagecopyresampled($canvas, $bg, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);
    }

    private function drawSpine($image, int $width, int $height): void
    {
        imagefilledrectangle($image, 0, 0, (int) round($width * .14), $height, imagecolorallocatealpha($image, 0, 0, 0, 78));
    }

    private function drawCoverText($image, Book $book, array $designer, int $width, int $height): void
    {
        $scale = $width / self::PREVIEW_BASE_WIDTH;
        $textColor = $this->allocate($image, (string) ($designer['text_color'] ?? '#FFFFFF'));
        $shadowColor = $this->shadowColor($image, (string) ($designer['text_shadow'] ?? 'soft'));
        $fontBold = $this->fontPath((string) ($designer['font_family'] ?? 'display'), true);
        $fontRegular = $this->fontPath((string) ($designer['font_family'] ?? 'display'), false);

        $align = in_array(($designer['text_align'] ?? 'left'), ['left', 'center', 'right'], true) ? (string) $designer['text_align'] : 'left';
        $position = (string) ($designer['text_position'] ?? 'bottom');
        $layout = (string) ($designer['layout_style'] ?? 'bold');
        $textWidth = max(35, min(100, (int) ($designer['text_width'] ?? 88))) / 100;
        $boxW = (int) round($width * $textWidth);
        $side = (int) round(22 * $scale);
        $bottom = (int) round(22 * $scale);
        $top = (int) round(22 * $scale);
        $x = $align === 'center' ? (int) round(($width - $boxW) / 2) : ($align === 'right' ? $width - $boxW - $side : $side);

        $badge = trim((string) ($designer['badge'] ?? ''));
        $title = $this->applyTitleCase(trim((string) ($designer['title'] ?? $book->title ?? 'Book')), (string) ($designer['title_case'] ?? 'as_typed'));
        $subtitle = trim((string) ($designer['subtitle'] ?? $book->subtitle ?? ''));
        $author = trim((string) ($designer['author'] ?? $book->author_name ?? ''));

        $badgeSize = max(6, (int) ($designer['badge_size'] ?? 10)) * $scale;
        $titleSize = max(12, (int) ($designer['title_size'] ?? 34)) * $scale;
        $subtitleSize = max(8, (int) ($designer['subtitle_size'] ?? 15)) * $scale;
        $authorSize = max(8, (int) ($designer['author_size'] ?? 13)) * $scale;

        $entries = [];

        if ($badge !== '') {
            $entries[] = [
                'kind' => 'badge',
                'text' => strtoupper($badge),
                'size' => $badgeSize,
                'font' => $fontBold,
                'gap' => 10 * $scale,
            ];
        }

        foreach ($this->wrapText($title, $fontBold, (int) round($titleSize), $boxW) as $line) {
            $entries[] = [
                'kind' => 'title',
                'text' => $line,
                'size' => $titleSize,
                'font' => $fontBold,
                'gap' => 0,
            ];
        }

        if ($subtitle !== '') {
            $entries[] = ['kind' => 'gap', 'height' => 8 * $scale];
            foreach ($this->wrapText($subtitle, $fontRegular, (int) round($subtitleSize), $boxW) as $line) {
                $entries[] = [
                    'kind' => 'subtitle',
                    'text' => $line,
                    'size' => $subtitleSize,
                    'font' => $fontRegular,
                    'gap' => 0,
                ];
            }
        }

        if ($author !== '') {
            $entries[] = ['kind' => 'gap', 'height' => 16 * $scale];
            $entries[] = [
                'kind' => 'author',
                'text' => strtoupper($author),
                'size' => $authorSize,
                'font' => $fontBold,
                'gap' => 0,
            ];
        }

        $lineHeights = ['badge' => 1.30, 'title' => 1.02, 'subtitle' => 1.22, 'author' => 1.2];
        $blockH = 0;
        foreach ($entries as $entry) {
            if (($entry['kind'] ?? '') === 'gap') {
                $blockH += (float) $entry['height'];
            } else {
                $blockH += ((float) $entry['size']) * ($lineHeights[$entry['kind']] ?? 1.15) + ((float) ($entry['gap'] ?? 0));
            }
        }

        $y = match ($position) {
            'top' => $top,
            'center' => (int) round(($height - $blockH) / 2),
            'split' => $top,
            default => (int) round($height - $bottom - $blockH),
        };
        $y = max($top, min($height - $bottom - 20, $y));

        $panelOpacity = max(0, min(80, (int) ($designer['panel_opacity'] ?? 0)));
        if ($panelOpacity > 0 || $layout === 'boxed') {
            $alpha = $layout === 'boxed'
                ? min(100, max(48, (int) round(127 * (($panelOpacity ?: 38) / 100))))
                : (int) round(127 * ($panelOpacity / 100));
            $pad = (int) round(18 * $scale);
            imagefilledrectangle(
                $image,
                max(0, $x - $pad),
                max(0, (int) round($y - $pad * .7)),
                min($width, $x + $boxW + $pad),
                min($height, (int) round($y + $blockH + $pad * .7)),
                imagecolorallocatealpha($image, 0, 0, 0, $alpha)
            );
        }

        foreach ($entries as $entry) {
            if (($entry['kind'] ?? '') === 'gap') {
                $y += (int) round((float) $entry['height']);
                continue;
            }

            $kind = (string) $entry['kind'];
            $size = (int) round((float) $entry['size']);
            $lineHeight = $lineHeights[$kind] ?? 1.15;

            if ($kind === 'badge') {
                $this->drawBadgeText($image, (string) $entry['text'], $x, $y, $size, $textColor, $shadowColor, $entry['font'], $boxW, $align, $scale);
            } else {
                $this->drawText($image, (string) $entry['text'], $x, (int) round($y + ($size * .92)), $size, $textColor, $shadowColor, $entry['font'], $boxW, $align);
            }

            $y += (int) round($size * $lineHeight + ((float) ($entry['gap'] ?? 0)));
        }
    }

    private function drawBadgeText($image, string $text, int $x, int $y, int $size, $color, $shadow, ?string $font, int $maxWidth, string $align, float $scale): void
    {
        $textW = $this->textWidth($text, $font, $size);
        $padX = (int) round(9 * $scale);
        $padY = (int) round(6 * $scale);
        $badgeW = min($maxWidth, $textW + ($padX * 2));
        $drawX = $align === 'center' ? $x + (int) round(($maxWidth - $badgeW) / 2) : ($align === 'right' ? $x + $maxWidth - $badgeW : $x);
        $drawY = (int) round($y);

        imagefilledrectangle($image, $drawX, $drawY, $drawX + $badgeW, $drawY + $size + ($padY * 2), imagecolorallocatealpha($image, 255, 255, 255, 108));
        imagerectangle($image, $drawX, $drawY, $drawX + $badgeW, $drawY + $size + ($padY * 2), imagecolorallocatealpha($image, 255, 255, 255, 70));
        $this->drawText($image, $text, $drawX + $padX, $drawY + $size + $padY, $size, $color, $shadow, $font, $badgeW - ($padX * 2), 'left');
    }

    private function drawText($image, string $text, int $x, int $y, int $size, $color, $shadow, ?string $font, int $maxWidth, string $align): void
    {
        if ($font && function_exists('imagettftext')) {
            $bbox = imagettfbbox($size, 0, $font, $text);
            $textW = abs(($bbox[2] ?? 0) - ($bbox[0] ?? 0));
            $drawX = $align === 'center' ? $x + (int) round(($maxWidth - $textW) / 2) : ($align === 'right' ? $x + $maxWidth - $textW : $x);

            if ($shadow !== null) {
                imagettftext($image, $size, 0, $drawX + 3, $y + 3, $shadow, $font, $text);
            }

            imagettftext($image, $size, 0, $drawX, $y, $color, $font, $text);
            return;
        }

        imagestring($image, 5, $x, $y, $text, $color);
    }

    private function wrapText(string $text, ?string $font, int $size, int $maxWidth): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $test = trim($line . ' ' . $word);
            if ($line !== '' && $this->textWidth($test, $font, $size) > $maxWidth) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $test;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines ?: [''];
    }

    private function textWidth(string $text, ?string $font, int $size): int
    {
        if ($font && function_exists('imagettfbbox')) {
            $bbox = imagettfbbox($size, 0, $font, $text);
            return abs(($bbox[2] ?? 0) - ($bbox[0] ?? 0));
        }

        return strlen($text) * imagefontwidth(5);
    }

    private function svgLines(string $text, float $maxWidth, float $fontSize): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $line = '';
        $averageChar = max(8, $fontSize * .55);
        $maxChars = max(5, (int) floor($maxWidth / $averageChar));

        foreach ($words as $word) {
            $test = trim($line . ' ' . $word);
            if ($line !== '' && mb_strlen($test) > $maxChars) {
                $lines[] = e($line);
                $line = $word;
            } else {
                $line = $test;
            }
        }

        if ($line !== '') {
            $lines[] = e($line);
        }

        return $lines ?: [''];
    }

    private function applyTitleCase(string $value, string $mode): string
    {
        return match ($mode) {
            'uppercase' => Str::upper($value),
            'title_case' => Str::title($value),
            'lowercase' => Str::lower($value),
            default => $value,
        };
    }

    private function allocate($image, string $hex)
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        return imagecolorallocate($image, $r, $g, $b);
    }

    private function shadowColor($image, string $shadow)
    {
        return match ($shadow) {
            'none', 'off' => null,
            'strong' => imagecolorallocatealpha($image, 0, 0, 0, 28),
            'glow' => imagecolorallocatealpha($image, 255, 255, 255, 72),
            default => imagecolorallocatealpha($image, 0, 0, 0, 46),
        };
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6) {
            $hex = '0B1F4D';
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function fontPath(string $family, bool $bold): ?string
    {
        $family = strtolower(trim($family));

        if ($family === 'serif' || $family === 'elegant') {
            $candidates = $bold ? [
                '/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf',
                '/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf',
            ] : [
                '/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf',
                '/usr/share/fonts/truetype/liberation/LiberationSerif-Regular.ttf',
            ];
        } else {
            $candidates = $bold ? [
                '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
                '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            ] : [
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            ];
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function svgFontFamily(string $family): string
    {
        return match (strtolower(trim($family))) {
            'serif', 'elegant' => 'Georgia, Times New Roman, serif',
            'condensed' => 'Arial Narrow, Arial, Helvetica, sans-serif',
            default => 'Arial, Helvetica, sans-serif',
        };
    }

    private function registerMediaAsset(Book $book, string $path, string $url, string $mime, ?int $size): void
    {
        try {
            MediaAsset::create([
                'app_id' => (int) $book->app_id,
                'type' => 'image',
                'label' => ((string) ($book->title ?: 'Book')) . ' Final Cover',
                'bucket' => 'book_covers',
                'disk' => 'public',
                'path' => $path,
                'url' => $url,
                'mime' => $mime,
                'size' => $size,
                'width' => null,
                'height' => null,
                'tags_json' => [
                    'source' => 'book_cover_renderer',
                    'usage' => 'final_book_cover',
                    'book_id' => (int) $book->id,
                ],
                'is_active' => true,
                'sort_order' => 0,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
