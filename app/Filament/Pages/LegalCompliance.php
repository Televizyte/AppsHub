<?php

namespace App\Filament\Pages;

use App\Models\App;
use App\Support\ActiveApp;
use App\Support\AdminAccess;
use App\Support\Legal\LegalDocumentRenderer;
use App\Support\Legal\LegalTemplateRegistry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class LegalCompliance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Legal & Compliance';
    protected static ?string $navigationGroup = 'Workspace';
    protected static ?int $navigationSort = 94;
    protected static ?string $slug = 'legal-compliance';
    protected static string $view = 'filament.pages.legal-compliance';

    public ?App $activeApp = null;
    public array $legalDocuments = [];
    public array $legalProfile = [];
    public string $selectedTab = 'overview';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('app_settings');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('app_settings');
    }

    public function mount(): void
    {
        $this->loadDocuments();
    }

    public function loadDocuments(): void
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        $this->activeApp = $activeAppId > 0 ? App::query()->find($activeAppId) : null;

        if (! $this->activeApp) {
            $this->legalDocuments = $this->normalizeLegalDocuments([]);
            $this->legalProfile = [];
            return;
        }

        $branding = is_array($this->activeApp->branding_json) ? $this->activeApp->branding_json : [];
        $this->legalDocuments = $this->normalizeLegalDocuments(data_get($branding, 'legal.documents', []));
        $this->legalProfile = LegalDocumentRenderer::profile($this->activeApp);
    }

    public function selectTab(string $key): void
    {
        if ($key === 'overview' || $key === 'profile' || array_key_exists($key, $this->legalDocumentDefaults())) {
            $this->selectedTab = $key;
        }
    }

    public function saveLegalProfile(): void
    {
        abort_unless($this->activeApp, 404);

        $this->validate([
            'legalProfile.operator_name' => ['required', 'string', 'max:160'],
            'legalProfile.privacy_contact_email' => ['required', 'email', 'max:190'],
            'legalProfile.technical_support_email' => ['required', 'email', 'max:190'],
            'legalProfile.technical_support_url' => ['nullable', 'url', 'max:500'],
            'legalProfile.account_deletion_url' => ['required', 'url', 'max:500'],
            'legalProfile.account_deletion_period_days' => ['required', 'integer', 'min:1', 'max:365'],
            'legalProfile.ministry_name' => ['nullable', 'string', 'max:190'],
            'legalProfile.ministry_email' => ['nullable', 'email', 'max:190'],
            'legalProfile.ministry_phone' => ['nullable', 'string', 'max:80'],
            'legalProfile.ministry_address' => ['nullable', 'string', 'max:500'],
            'legalProfile.ministry_latitude' => ['nullable', 'string', 'max:40'],
            'legalProfile.ministry_longitude' => ['nullable', 'string', 'max:40'],
            'legalProfile.ministry_contact_url' => ['nullable', 'url', 'max:500'],
            'legalProfile.legal_public_base_url' => ['required', 'url', 'max:500'],
            'legalProfile.jurisdiction' => ['required', 'string', 'max:120'],
            'legalProfile.copyright_owner' => ['nullable', 'string', 'max:160'],
            'legalProfile.operator_address' => ['nullable', 'string', 'max:500'],
        ]);

        $branding = is_array($this->activeApp->branding_json) ? $this->activeApp->branding_json : [];
        $legal = is_array(data_get($branding, 'legal')) ? data_get($branding, 'legal') : [];
        $legal['profile'] = collect($this->legalProfile)
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->all();
        $branding['legal'] = $legal;
        $branding['support_email'] = $this->legalProfile['technical_support_email'];

        $this->activeApp->forceFill(['branding_json' => $branding])->save();
        $this->activeApp->refresh();
        $this->legalProfile = LegalDocumentRenderer::profile($this->activeApp);

        Notification::make()->title('Legal profile saved')->success()->send();
    }

    public function editorUrl(string $key): string
    {
        return LegalDocumentEditor::getUrl(['documentKey' => $key]);
    }

    public function publicUrl(string $key): string
    {
        return $this->activeApp ? LegalTemplateRegistry::publicUrl($this->activeApp, $key) : '';
    }

    public function getPublishedCountProperty(): int
    {
        return collect($this->legalDocuments)->filter(fn (array $document): bool => (bool) ($document['published'] ?? false))->count();
    }

    public function getCompletionPercentageProperty(): int
    {
        $total = max(1, count($this->legalDocuments));
        return (int) round(($this->publishedCount / $total) * 100);
    }

    public function getLegalProfileIssuesProperty(): array
    {
        $required = [
            'operator_name' => 'Operator name',
            'privacy_contact_email' => 'Privacy contact email',
            'technical_support_email' => 'Technical support email',
            'account_deletion_url' => 'Account deletion URL',
            'jurisdiction' => 'Jurisdiction',
        ];

        return collect($required)
            ->filter(fn ($label, $key) => trim((string) ($this->legalProfile[$key] ?? '')) === '')
            ->values()
            ->all();
    }

    public function getReadinessIssuesProperty(): array
    {
        $issues = collect($this->legalProfileIssues)->map(fn ($item) => 'Legal Profile: ' . $item . ' missing')->all();

        foreach ($this->legalDocuments as $key => $document) {
            if (trim((string) ($document['content'] ?? '')) === '') {
                $issues[] = ($document['title'] ?? $key) . ': content missing';
            }
            if (empty($document['effective_date'])) {
                $issues[] = ($document['title'] ?? $key) . ': effective date missing';
            }
        }

        return array_slice($issues, 0, 8);
    }

    protected function legalDocumentDefaults(): array
    {
        return collect(LegalTemplateRegistry::definitions())
            ->map(fn (array $definition): array => [
                'title' => $definition['title'],
                'summary' => $definition['summary'],
            ])
            ->all();
    }

    protected function normalizeLegalDocuments(mixed $documents): array
    {
        $documents = is_array($documents) ? $documents : [];
        $normalized = [];

        foreach ($this->legalDocumentDefaults() as $key => $default) {
            $stored = is_array($documents[$key] ?? null) ? $documents[$key] : [];
            $normalized[$key] = [
                'title' => trim((string) ($stored['title'] ?? $default['title'])) ?: $default['title'],
                'summary' => trim((string) ($stored['summary'] ?? $default['summary'])) ?: $default['summary'],
                'content' => (string) ($stored['content'] ?? ''),
                'effective_date' => (string) ($stored['effective_date'] ?? ''),
                'last_updated' => (string) ($stored['last_updated'] ?? ''),
                'published' => (bool) ($stored['published'] ?? false),
            ];
        }

        return $normalized;
    }
}
