<?php

namespace App\Filament\Pages;

use App\Models\App;
use App\Support\ActiveApp;
use App\Support\AdminAccess;
use App\Support\Legal\LegalDocumentRenderer;
use App\Support\Legal\LegalTemplateRegistry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class LegalDocumentEditor extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'legal-compliance/{documentKey}/edit';
    protected static string $view = 'filament.pages.legal-document-editor';

    public ?App $activeApp = null;
    public string $documentKey = 'privacy';
    public array $document = [];

    public static function canAccess(): bool
    {
        return AdminAccess::page('app_settings');
    }

    public function mount(string $documentKey): void
    {
        abort_unless(array_key_exists($documentKey, $this->documentDefaults()), 404);
        $this->documentKey = $documentKey;

        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        $this->activeApp = $activeAppId > 0 ? App::query()->find($activeAppId) : null;
        abort_unless($this->activeApp, 404);

        $branding = is_array($this->activeApp->branding_json) ? $this->activeApp->branding_json : [];
        $stored = data_get($branding, 'legal.documents.' . $documentKey, []);
        $this->document = $this->normalizeDocument(is_array($stored) ? $stored : []);
    }

    public function saveDraft(): void
    {
        $this->persist(false);
    }

    public function publish(): void
    {
        $unresolved = LegalDocumentRenderer::unresolved($this->activeApp, $this->document);
        if ($unresolved !== []) {
            $this->addError('document.content', 'Complete the Legal Profile. Unresolved fields: ' . implode(', ', $unresolved));
            return;
        }

        $this->persist(true);
    }

    public function unpublish(): void
    {
        $this->document['published'] = false;
        $this->persist(false, 'Document unpublished');
    }

    public function publicUrl(): string
    {
        return LegalTemplateRegistry::publicUrl($this->activeApp, $this->documentKey);
    }

    public function getRenderedPreviewHtmlProperty(): string
    {
        $content = trim((string) ($this->document['content'] ?? ''));
        if ($content === '') {
            return '<p>Start writing in the Content tab to preview the document here.</p>';
        }

        return LegalDocumentRenderer::markdown($this->activeApp, $this->document, $content);
    }

    public function getResolvedFieldsProperty(): array
    {
        return LegalDocumentRenderer::placeholders($this->activeApp, $this->document);
    }

    public function getUnresolvedFieldsProperty(): array
    {
        return LegalDocumentRenderer::unresolved($this->activeApp, $this->document);
    }

    protected function persist(bool $publish, string $message = 'Document saved'): void
    {
        $this->validate([
            'document.title' => ['required', 'string', 'max:160'],
            'document.summary' => ['nullable', 'string', 'max:500'],
            'document.effective_date' => ['nullable', 'date_format:Y-m-d'],
            'document.content' => [$publish ? 'required' : 'nullable', 'string'],
        ]);

        $branding = is_array($this->activeApp->branding_json) ? $this->activeApp->branding_json : [];
        $legal = is_array(data_get($branding, 'legal')) ? data_get($branding, 'legal') : [];
        $documents = is_array(data_get($legal, 'documents')) ? data_get($legal, 'documents') : [];

        $this->document['title'] = Str::limit(strip_tags(trim((string) $this->document['title'])), 160, '');
        $this->document['summary'] = Str::limit(strip_tags(trim((string) ($this->document['summary'] ?? ''))), 500, '');
        $this->document['content'] = trim((string) ($this->document['content'] ?? ''));
        $this->document['published'] = $publish;
        $this->document['last_updated'] = now()->toIso8601String();
        $documents[$this->documentKey] = $this->document;

        $baseUrl = url('/' . $this->activeApp->slug);
        $legal['documents'] = $documents;
        $legal['privacy_url'] = !empty(data_get($documents, 'privacy.published')) ? $baseUrl . '/privacy-policy' : (string) data_get($legal, 'privacy_url', '');
        $legal['terms_url'] = !empty(data_get($documents, 'terms.published')) ? $baseUrl . '/terms-of-use' : (string) data_get($legal, 'terms_url', '');
        $legal['support_url'] = !empty(data_get($documents, 'support.published')) ? $baseUrl . '/support' : '';
        $legal['community_guidelines_url'] = !empty(data_get($documents, 'community.published')) ? $baseUrl . '/community-guidelines' : '';
        $legal['copyright_url'] = !empty(data_get($documents, 'copyright.published')) ? $baseUrl . '/copyright-policy' : '';
        $legal['content_usage_url'] = !empty(data_get($documents, 'content-usage.published')) ? $baseUrl . '/content-usage' : '';
        $legal['disclaimer_url'] = !empty(data_get($documents, 'disclaimer.published')) ? $baseUrl . '/disclaimer' : '';
        $legal['data_safety_url'] = !empty(data_get($documents, 'data-safety.published')) ? $baseUrl . '/data-safety' : '';
        $branding['legal'] = $legal;

        $store = is_array(data_get($branding, 'store')) ? data_get($branding, 'store') : [];
        if (!empty(data_get($documents, 'privacy.published'))) {
            $store['privacy_policy_url'] = $baseUrl . '/privacy-policy';
        }
        $branding['store'] = $store;

        $this->activeApp->forceFill(['branding_json' => $branding])->save();
        $this->activeApp->refresh();

        Notification::make()->title($publish ? 'Document published' : $message)->success()->send();
        $this->dispatch('legal-document-saved', key: $this->documentKey);
    }

    protected function normalizeDocument(array $stored): array
    {
        $default = $this->documentDefaults()[$this->documentKey];

        return [
            'title' => trim((string) ($stored['title'] ?? $default['title'])) ?: $default['title'],
            'summary' => trim((string) ($stored['summary'] ?? $default['summary'])) ?: $default['summary'],
            'content' => (string) ($stored['content'] ?? LegalTemplateRegistry::defaultDocument($this->documentKey)['content']),
            'effective_date' => (string) ($stored['effective_date'] ?? ''),
            'last_updated' => (string) ($stored['last_updated'] ?? ''),
            'published' => (bool) ($stored['published'] ?? false),
            'inheritance_mode' => (string) ($stored['inheritance_mode'] ?? 'inherited'),
            'template_version' => (string) ($stored['template_version'] ?? LegalTemplateRegistry::VERSION),
        ];
    }

    protected function documentDefaults(): array
    {
        return collect(LegalTemplateRegistry::definitions())
            ->map(fn (array $definition): array => [
                'title' => $definition['title'],
                'summary' => $definition['summary'],
            ])
            ->all();
    }
}
