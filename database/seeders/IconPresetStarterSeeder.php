<?php

namespace Database\Seeders;

use App\Models\IconPreset;
use Illuminate\Database\Seeder;

class IconPresetStarterSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->icons() as $icon) {
            IconPreset::query()->updateOrCreate(
                ['key' => $icon['key']],
                $icon
            );
        }
    }

    private function icons(): array
    {
        return [
            [
                'key' => 'quote_creator',
                'label' => 'Quote Creator',
                'group' => 'Tools',
                'svg' => <<<'SVG'
<svg viewBox="0 0 24 24"><path d="M7 7h5v5H9.8c0 1.5.6 2.6 2.2 3.4l-1.3 2.3C7.6 16.4 6 14 6 10.5V7Zm9 0h5v5h-2.2c0 1.5.6 2.6 2.2 3.4l-1.3 2.3C16.6 16.4 15 14 15 10.5V7Z"/></svg>
SVG,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'key' => 'notes',
                'label' => 'Notes',
                'group' => 'Tools',
                'svg' => <<<'SVG'
<svg viewBox="0 0 24 24"><path d="M6 3h9l3 3v15H6V3Zm8 1.8V7h2.2L14 4.8ZM8 10h8v1.7H8V10Zm0 4h8v1.7H8V14Zm0 4h5v1.7H8V18Z"/></svg>
SVG,
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'key' => 'bible',
                'label' => 'Bible',
                'group' => 'Tools',
                'svg' => <<<'SVG'
<svg viewBox="0 0 24 24"><path d="M6 3h11a2 2 0 0 1 2 2v16H7a2 2 0 0 1-2-2V5a2 2 0 0 1 1-1.73V3Zm1 2v14h10V5H7Zm4 3h2v2h2v2h-2v4h-2v-4H9v-2h2V8Z"/></svg>
SVG,
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'key' => 'books',
                'label' => 'Books',
                'group' => 'Tools',
                'svg' => <<<'SVG'
<svg viewBox="0 0 24 24"><path d="M5 4h5v16H5V4Zm7 0h5v16h-5V4Zm7 2h2v14h-2V6ZM7 7v2h1V7H7Zm7 0v2h1V7h-1Z"/></svg>
SVG,
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'key' => 'watch',
                'label' => 'Watch',
                'group' => 'Navigation',
                'svg' => <<<'SVG'
<svg viewBox="0 0 24 24"><path d="M4 5h16v11H4V5Zm2 2v7h12V7H6Zm4 11h4v2h-4v-2Zm1-9 5 2.5-5 2.5V9Z"/></svg>
SVG,
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'key' => 'short_videos',
                'label' => 'Short Videos',
                'group' => 'Tools',
                'svg' => <<<'SVG'
<svg viewBox="0 0 24 24"><path d="M8 3h8l2 4H6l2-4Zm-2 6h12v12H6V9Zm5 3v6l5-3-5-3Z"/></svg>
SVG,
                'is_active' => true,
                'sort_order' => 60,
            ],
            [
                'key' => 'home',
                'label' => 'Home',
                'group' => 'Navigation',
                'svg' => <<<'SVG'
<svg viewBox="0 0 24 24"><path d="M3 11 12 3l9 8-1.4 1.5L18 11.1V21h-5v-6h-2v6H6v-9.9l-1.6 1.4L3 11Z"/></svg>
SVG,
                'is_active' => true,
                'sort_order' => 70,
            ],
            [
                'key' => 'inspire',
                'label' => 'Inspire',
                'group' => 'Navigation',
                'svg' => <<<'SVG'
<svg viewBox="0 0 24 24"><path d="M12 2 14.8 8 21 9l-4.5 4.4L17.6 20 12 16.8 6.4 20l1.1-6.6L3 9l6.2-1L12 2Z"/></svg>
SVG,
                'is_active' => true,
                'sort_order' => 80,
            ],
            [
                'key' => 'explore',
                'label' => 'Explore',
                'group' => 'Navigation',
                'svg' => <<<'SVG'
<svg viewBox="0 0 24 24"><path d="M11 2h2v4h-2V2Zm0 16h2v4h-2v-4ZM2 11h4v2H2v-2Zm16 0h4v2h-4v-2ZM5.6 4.2 8.4 7 7 8.4 4.2 5.6l1.4-1.4Zm11 11.4 2.8 2.8-1.4 1.4-2.8-2.8 1.4-1.4Zm2.8-10L16.6 8.4 15.2 7 18 4.2l1.4 1.4ZM8.4 16.6 5.6 19.4 4.2 18l2.8-2.8 1.4 1.4ZM12 8a4 4 0 1 1 0 8 4 4 0 0 1 0-8Z"/></svg>
SVG,
                'is_active' => true,
                'sort_order' => 90,
            ],
            [
                'key' => 'notification',
                'label' => 'Notification',
                'group' => 'Engines',
                'svg' => <<<'SVG'
<svg viewBox="0 0 24 24"><path d="M12 22a2.5 2.5 0 0 0 2.4-2h-4.8A2.5 2.5 0 0 0 12 22Zm7-5-2-2v-5a5 5 0 0 0-4-4.9V3h-2v2.1A5 5 0 0 0 7 10v5l-2 2v1h14v-1Z"/></svg>
SVG,
                'is_active' => true,
                'sort_order' => 100,
            ],
            [
                'key' => 'ads',
                'label' => 'Ads',
                'group' => 'Engines',
                'svg' => <<<'SVG'
<svg viewBox="0 0 24 24"><path d="M4 5h16v14H4V5Zm2 2v10h12V7H6Zm2 7 2.2-5h2L15 14h-2l-.4-1H10l-.4 1H8Zm2.6-2.6H12l-.7-1.8-.7 1.8Z"/></svg>
SVG,
                'is_active' => true,
                'sort_order' => 110,
            ],
            [
                'key' => 'media',
                'label' => 'Media',
                'group' => 'Engines',
                'svg' => <<<'SVG'
<svg viewBox="0 0 24 24"><path d="M4 5h16v14H4V5Zm2 2v8l3.2-3.2 2.3 2.3 3.3-4.1L18 14V7H6Zm0 10h12v-1l-3.1-3.5-3.2 4-2.4-2.4L6 17Z"/></svg>
SVG,
                'is_active' => true,
                'sort_order' => 120,
            ],
        ];
    }
}
