<?php

namespace Database\Seeders;

use App\Models\IconPreset;
use App\Support\Icons\SvgIconRegistry;
use Illuminate\Database\Seeder;

class IconPresetSeeder extends Seeder
{
    public function run(): void
    {
        $map = SvgIconRegistry::allMap();

        $tabs = ['home','watch','inspire','explore','more'];
        $content = ['motivation','wordification','articles','highlights','inside_dunamis','sod'];

        $sort = 10;

        foreach ($map as $key => $svg) {
            $group = 'General';

            if (in_array($key, $tabs, true)) $group = 'Tabs';
            elseif (in_array($key, $content, true)) $group = 'Content';
            else $group = 'Tools';

            IconPreset::updateOrCreate(
                ['key' => $key],
                [
                    'label' => SvgIconRegistry::label($key),
                    'group' => $group,
                    'svg' => $svg,
                    'is_active' => true,
                    'sort_order' => $sort,
                ]
            );

            $sort += 10;
        }
    }
}
