<?php

namespace App\Support;

class DesignEngineCatalog
{
    public static function canvasPresets(): array
    {
        return [
            'square' => [
                'label' => 'Square Post',
                'width' => 1080,
                'height' => 1080,
                'ratio' => '1:1',
                'note' => 'Quotes, scriptures, social posts.',
                'icon' => 'square',
            ],
            'story' => [
                'label' => 'Story / Reel',
                'width' => 1080,
                'height' => 1920,
                'ratio' => '9:16',
                'note' => 'Status, story, vertical announcement.',
                'icon' => 'story',
            ],
            'landscape' => [
                'label' => 'Landscape Banner',
                'width' => 1280,
                'height' => 720,
                'ratio' => '16:9',
                'note' => 'Video thumbnails, watch banners.',
                'icon' => 'landscape',
            ],
            'portrait' => [
                'label' => 'Portrait Flyer',
                'width' => 1080,
                'height' => 1350,
                'ratio' => '4:5',
                'note' => 'Flyers and event posts.',
                'icon' => 'portrait',
            ],
            'app_banner' => [
                'label' => 'App Banner',
                'width' => 1600,
                'height' => 600,
                'ratio' => '8:3',
                'note' => 'Home banners and section banners.',
                'icon' => 'banner',
            ],
        ];
    }

    public static function designTypes(): array
    {
        return [
            'daily_scripture' => [
                'label' => 'Daily Scripture',
                'note' => 'Verse card with scripture reference.',
                'template_category' => 'scripture',
                'icon' => 'bible',
            ],
            'daily_quote' => [
                'label' => 'Daily Quote',
                'note' => 'Daily inspirational quote card.',
                'template_category' => 'quote',
                'icon' => 'quote',
            ],
            'sod_quote' => [
                'label' => 'SOD Quote',
                'note' => 'Seeds of Destiny quote design.',
                'template_category' => 'quote',
                'icon' => 'seed',
            ],
            'motivation' => [
                'label' => 'Motivational Card',
                'note' => 'Faith-building motivational design.',
                'template_category' => 'quote',
                'icon' => 'spark',
            ],
            'flyer' => [
                'label' => 'Flyer / Poster',
                'note' => 'Event, announcement and campaign flyer.',
                'template_category' => 'flyer',
                'icon' => 'flyer',
            ],
            'banner' => [
                'label' => 'App / Web Banner',
                'note' => 'Reusable app and website banner.',
                'template_category' => 'banner',
                'icon' => 'banner',
            ],
            'push_image' => [
                'label' => 'Push Image Card',
                'note' => 'Image used with notification campaigns.',
                'template_category' => 'notification',
                'icon' => 'notification',
            ],
            'general' => [
                'label' => 'General Design',
                'note' => 'Flexible custom design.',
                'template_category' => 'general',
                'icon' => 'design',
            ],
        ];
    }

    public static function starterTemplates(): array
    {
        return [
            'scripture_elegant' => [
                'title' => 'Elegant Scripture',
                'type' => 'daily_scripture',
                'preset' => 'square',
                'tone' => 'purple',
                'description' => 'Clean Bible verse design with centered verse and reference.',
                'canvas' => [
                    'width' => 1080,
                    'height' => 1080,
                    'background_type' => 'gradient',
                    'background_color' => '#280061',
                    'gradient_from' => '#280061',
                    'gradient_to' => '#9e56fc',
                    'background_image' => '',
                    'overlay_opacity' => 0,
                ],
                'layers' => [
                    [
                        'id' => 'verse_text',
                        'type' => 'text',
                        'role' => 'main',
                        'label' => 'Verse Text',
                        'text' => 'For I know the thoughts that I think toward you...',
                        'x' => 90,
                        'y' => 310,
                        'width' => 900,
                        'height' => 260,
                        'font_size' => 54,
                        'font_weight' => 800,
                        'color' => '#ffffff',
                        'align' => 'center',
                        'line_height' => 1.22,
                        'shadow' => 'soft',
                        'opacity' => 1,
                        'rotation' => 0,
                    ],
                    [
                        'id' => 'reference',
                        'type' => 'text',
                        'role' => 'reference',
                        'label' => 'Reference',
                        'text' => 'Jeremiah 29:11',
                        'x' => 110,
                        'y' => 660,
                        'width' => 860,
                        'height' => 80,
                        'font_size' => 30,
                        'font_weight' => 700,
                        'color' => '#fce7ff',
                        'align' => 'center',
                        'line_height' => 1.2,
                        'shadow' => 'off',
                        'opacity' => 1,
                        'rotation' => 0,
                    ],
                ],
            ],
            'quote_bold_gradient' => [
                'title' => 'Bold Gradient Quote',
                'type' => 'daily_quote',
                'preset' => 'square',
                'tone' => 'pink',
                'description' => 'Bold centered quote with source line.',
                'canvas' => [
                    'width' => 1080,
                    'height' => 1080,
                    'background_type' => 'gradient',
                    'background_color' => '#0b0f2a',
                    'gradient_from' => '#0b0f2a',
                    'gradient_to' => '#e4007c',
                    'background_image' => '',
                    'overlay_opacity' => 0,
                ],
                'layers' => [
                    [
                        'id' => 'quote_text',
                        'type' => 'text',
                        'role' => 'main',
                        'label' => 'Quote Text',
                        'text' => 'A life left to chance has no chance.',
                        'x' => 90,
                        'y' => 330,
                        'width' => 900,
                        'height' => 220,
                        'font_size' => 62,
                        'font_weight' => 900,
                        'color' => '#ffffff',
                        'align' => 'center',
                        'line_height' => 1.12,
                        'shadow' => 'soft',
                        'opacity' => 1,
                        'rotation' => 0,
                    ],
                    [
                        'id' => 'source',
                        'type' => 'text',
                        'role' => 'source',
                        'label' => 'Source',
                        'text' => 'Seeds of Destiny',
                        'x' => 130,
                        'y' => 650,
                        'width' => 820,
                        'height' => 70,
                        'font_size' => 26,
                        'font_weight' => 700,
                        'color' => '#ffe4f4',
                        'align' => 'center',
                        'line_height' => 1.2,
                        'shadow' => 'off',
                        'opacity' => 1,
                        'rotation' => 0,
                    ],
                ],
            ],
            'flyer_ministry_event' => [
                'title' => 'Ministry Event Flyer',
                'type' => 'flyer',
                'preset' => 'portrait',
                'tone' => 'cyan',
                'description' => 'Event title, date, speaker and call-to-action layout.',
                'canvas' => [
                    'width' => 1080,
                    'height' => 1350,
                    'background_type' => 'solid',
                    'background_color' => '#0b1020',
                    'gradient_from' => '#0b1020',
                    'gradient_to' => '#1a1f5a',
                    'background_image' => '',
                    'overlay_opacity' => 0,
                ],
                'layers' => [
                    [
                        'id' => 'event_title',
                        'type' => 'text',
                        'role' => 'headline',
                        'label' => 'Event Title',
                        'text' => 'Night of Worship',
                        'x' => 90,
                        'y' => 150,
                        'width' => 900,
                        'height' => 150,
                        'font_size' => 76,
                        'font_weight' => 900,
                        'color' => '#ffffff',
                        'align' => 'center',
                        'line_height' => 1.04,
                        'shadow' => 'strong',
                        'opacity' => 1,
                        'rotation' => 0,
                    ],
                    [
                        'id' => 'event_details',
                        'type' => 'text',
                        'role' => 'body',
                        'label' => 'Event Details',
                        'text' => 'Friday • 5:00 PM • Glory Dome',
                        'x' => 120,
                        'y' => 1040,
                        'width' => 840,
                        'height' => 90,
                        'font_size' => 34,
                        'font_weight' => 700,
                        'color' => '#dff9ff',
                        'align' => 'center',
                        'line_height' => 1.2,
                        'shadow' => 'soft',
                        'opacity' => 1,
                        'rotation' => 0,
                    ],
                ],
            ],
        ];
    }

    public static function defaultCanvas(string $preset = 'square'): array
    {
        $presetData = self::canvasPresets()[$preset] ?? self::canvasPresets()['square'];

        return [
            'width' => $presetData['width'],
            'height' => $presetData['height'],
            'preset' => $preset,
            'background_type' => 'gradient',
            'background_color' => '#0b0f2a',
            'gradient_from' => '#0b0f2a',
            'gradient_to' => '#9e56fc',
            'background_image' => '',
            'overlay_opacity' => 0,
        ];
    }
}
