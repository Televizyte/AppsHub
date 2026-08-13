<?php

namespace Tests\Feature;

use Tests\TestCase;

class VideoEngineBladeCompilationTest extends TestCase
{
    public function test_video_engine_blade_templates_compile_to_valid_php(): void
    {
        $compiler = app('blade.compiler');
        $views = [
            resource_path('views/filament/pages/video-engine.blade.php'),
            resource_path('views/filament/pages/partials/video-engine-card.blade.php'),
            resource_path('views/filament/pages/partials/video-engine-thumbnail-picker.blade.php'),
            resource_path('views/filament/pages/partials/video-engine-publishing.blade.php'),
            resource_path('views/filament/pages/partials/video-engine-preview.blade.php'),
        ];

        foreach ($views as $view) {
            $compiler->compile($view);
            $compiled = $compiler->getCompiledPath($view);
            $output = [];
            $exitCode = 1;
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($compiled), $output, $exitCode);

            $this->assertSame(0, $exitCode, $view . PHP_EOL . implode(PHP_EOL, $output));
        }
    }
}
