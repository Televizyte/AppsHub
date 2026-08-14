<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
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
            resource_path('views/admin/beginner/content-posts/create.blade.php'),
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

    public function test_view_cache_produces_valid_video_engine_compiled_php(): void
    {
        $this->assertSame(0, Artisan::call('view:clear'));
        $this->assertSame(0, Artisan::call('view:cache'), Artisan::output());

        $compiledViews = glob(storage_path('framework/views/*.php')) ?: [];
        $videoEngineViews = array_values(array_filter(
            $compiledViews,
            fn (string $path): bool => str_contains(
                (string) file_get_contents($path),
                'resources\\views\\filament\\pages\\video-engine.blade.php'
            ) || str_contains(
                (string) file_get_contents($path),
                'resources/views/filament/pages/video-engine.blade.php'
            ),
        ));

        $this->assertNotEmpty($videoEngineViews, 'The cached Video Engine Blade output was not found.');

        foreach ($videoEngineViews as $compiled) {
            $compiledPhp = (string) file_get_contents($compiled);
            $output = [];
            $exitCode = 1;
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($compiled), $output, $exitCode);

            $this->assertStringContainsString(
                "\$focusedEditorRoute = request()->routeIs('admin.video-engine.*');",
                $compiledPhp,
            );
            $this->assertStringNotContainsString('<?php($focusedEditorRoute', $compiledPhp);
            $this->assertSame(0, $exitCode, $compiled . PHP_EOL . implode(PHP_EOL, $output));
        }
    }
}
