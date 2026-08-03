<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSection;
use App\Models\BuilderTemplate;
use App\Support\ActiveApp;
use App\Support\BuilderTemplateApplyService;
use App\Support\BuilderTemplateCaptureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TemplateApplyController extends Controller
{
    public function apply(Request $request, BuilderTemplate $builderTemplate, BuilderTemplateApplyService $service): RedirectResponse
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        abort_unless($activeAppId > 0, 404);

        $validated = $request->validate([
            'target_tab' => ['nullable', 'in:home,watch,inspire,explore,more'],
        ]);

        $targetTab = $validated['target_tab'] ?? null;

        try {
            $result = $service->apply($builderTemplate, $activeAppId, $targetTab);

            return redirect('/admin/destination-builder?tab=' . urlencode((string) ($result['tab'] ?? 'home')))
                ->with('status', 'Template applied. Created '
                    . (int) $result['created_sections']
                    . ' section(s) and '
                    . (int) $result['created_items']
                    . ' item(s).');
        } catch (\Throwable $e) {
            return redirect('/admin/template-library?tab=' . urlencode((string) $builderTemplate->category))
                ->with('status', 'Template apply failed: ' . $e->getMessage());
        }
    }

    public function saveSection(Request $request, AppSection $appSection, BuilderTemplateCaptureService $service): RedirectResponse
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        abort_unless($activeAppId > 0, 404);
        abort_unless((int) $appSection->app_id === $activeAppId, 404);

        $validated = $request->validate([
            'template_title' => ['nullable', 'string', 'max:160'],
            'template_category' => ['nullable', 'in:sections,cards,widgets,forms,pages'],
        ]);

        try {
            $template = $service->captureSection(
                $appSection,
                $validated['template_title'] ?? null,
                $validated['template_category'] ?? 'sections'
            );

            return redirect('/admin/template-library?tab=' . urlencode((string) $template->category))
                ->with('status', 'Section saved as template: ' . $template->title);
        } catch (\Throwable $e) {
            return redirect('/admin/destination-builder?tab=' . urlencode((string) $appSection->tab_key) . '#section-' . $appSection->id)
                ->with('status', 'Save template failed: ' . $e->getMessage());
        }
    }

    public function saveTab(Request $request, string $tab, BuilderTemplateCaptureService $service): RedirectResponse
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        abort_unless($activeAppId > 0, 404);

        $validated = $request->validate([
            'template_title' => ['nullable', 'string', 'max:160'],
        ]);

        try {
            $template = $service->captureTab(
                $activeAppId,
                $tab,
                $validated['template_title'] ?? null
            );

            return redirect('/admin/template-library?tab=pages')
                ->with('status', 'Page saved as template: ' . $template->title);
        } catch (\Throwable $e) {
            return redirect('/admin/destination-builder?tab=' . urlencode($tab))
                ->with('status', 'Save page template failed: ' . $e->getMessage());
        }
    }
}
