<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Support\Legal\LegalDocumentRenderer;
use App\Support\Legal\LegalTemplateRegistry;
use Illuminate\Http\Response;

class LegalDocumentController extends Controller
{
    public function show(string $appSlug, string $documentSlug): Response
    {
        $documentKey = LegalTemplateRegistry::keyFromPublicSlug($documentSlug);
        abort_unless($documentKey !== null, 404);

        $app = App::query()->where('slug', $appSlug)->where('is_active', true)->firstOrFail();
        $document = LegalTemplateRegistry::effectiveDocument($app, $documentKey);

        abort_unless((bool) ($document['published'] ?? false), 404);
        abort_if(trim((string) ($document['content'] ?? '')) === '', 404);

        return response()->view('public.legal-document', [
            'app' => $app,
            'branding' => is_array($app->branding_json) ? $app->branding_json : [],
            'documentKey' => $documentKey,
            'document' => $document,
            'html' => LegalDocumentRenderer::markdown($app, $document),
            'profile' => LegalDocumentRenderer::profile($app),
        ]);
    }
}
