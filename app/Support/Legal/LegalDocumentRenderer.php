<?php

namespace App\Support\Legal;

use App\Models\App;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class LegalDocumentRenderer
{
    public static function profile(App $app): array
    {
        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $profile = is_array(data_get($branding, 'legal.profile')) ? data_get($branding, 'legal.profile') : [];

        $supportEmail = trim((string) ($profile['technical_support_email'] ?? data_get($branding, 'support_email') ?? ''));
        $period = max(1, (int) ($profile['account_deletion_period_days'] ?? 30));

        return [
            'operator_name' => trim((string) ($profile['operator_name'] ?? '')),
            'privacy_contact_email' => trim((string) ($profile['privacy_contact_email'] ?? $supportEmail)),
            'technical_support_email' => $supportEmail,
            'technical_support_url' => trim((string) ($profile['technical_support_url'] ?? LegalTemplateRegistry::publicBaseUrl($app).'/support-request')),
            'account_deletion_url' => trim((string) ($profile['account_deletion_url'] ?? LegalTemplateRegistry::publicBaseUrl($app).'/account-deletion')),
            'account_deletion_period_days' => $period,
            'ministry_name' => trim((string) ($profile['ministry_name'] ?? '')),
            'ministry_email' => trim((string) ($profile['ministry_email'] ?? '')),
            'ministry_phone' => trim((string) ($profile['ministry_phone'] ?? '')),
            'ministry_address' => trim((string) ($profile['ministry_address'] ?? '')),
            'ministry_latitude' => trim((string) ($profile['ministry_latitude'] ?? '')),
            'ministry_longitude' => trim((string) ($profile['ministry_longitude'] ?? '')),
            'ministry_contact_url' => trim((string) ($profile['ministry_contact_url'] ?? '')),
            'jurisdiction' => trim((string) ($profile['jurisdiction'] ?? 'Federal Republic of Nigeria')),
            'copyright_owner' => trim((string) ($profile['copyright_owner'] ?? ($profile['operator_name'] ?? ''))),
            'operator_address' => trim((string) ($profile['operator_address'] ?? '')),
            'legal_public_base_url' => trim((string) ($profile['legal_public_base_url'] ?? LegalTemplateRegistry::publicBaseUrl($app))),
        ];
    }

    public static function placeholders(App $app, array $document = []): array
    {
        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $profile = self::profile($app);
        $effectiveDate = trim((string) ($document['effective_date'] ?? ''));
        $lastUpdated = trim((string) ($document['last_updated'] ?? ''));

        return [
            'app_name' => (string) (data_get($branding, 'display_name') ?: $app->name),
            'app_slug' => $app->slug,
            'app_id' => (string) $app->id,
            'operator_name' => $profile['operator_name'],
            'privacy_contact_email' => $profile['privacy_contact_email'],
            'technical_support_email' => $profile['technical_support_email'],
            'technical_support_url' => $profile['technical_support_url'],
            'support_form_url' => LegalTemplateRegistry::publicBaseUrl($app).'/support-request',
            'account_deletion_url' => $profile['account_deletion_url'],
            'account_deletion_period_days' => (string) $profile['account_deletion_period_days'],
            'ministry_name' => $profile['ministry_name'],
            'ministry_email' => $profile['ministry_email'],
            'ministry_phone' => $profile['ministry_phone'],
            'ministry_address' => $profile['ministry_address'],
            'ministry_latitude' => $profile['ministry_latitude'],
            'ministry_longitude' => $profile['ministry_longitude'],
            'ministry_contact_url' => $profile['ministry_contact_url'],
            'jurisdiction' => $profile['jurisdiction'],
            'copyright_owner' => $profile['copyright_owner'],
            'operator_address' => $profile['operator_address'],
            'legal_public_base_url' => $profile['legal_public_base_url'],
            'effective_date' => self::formatDate($effectiveDate),
            'last_updated' => self::formatDate($lastUpdated),
            'privacy_url' => LegalTemplateRegistry::publicUrl($app, 'privacy'),
            'terms_url' => LegalTemplateRegistry::publicUrl($app, 'terms'),
            'support_url' => LegalTemplateRegistry::publicUrl($app, 'support'),
            'community_guidelines_url' => LegalTemplateRegistry::publicUrl($app, 'community'),
            'copyright_url' => LegalTemplateRegistry::publicUrl($app, 'copyright'),
            'content_usage_url' => LegalTemplateRegistry::publicUrl($app, 'content-usage'),
            'disclaimer_url' => LegalTemplateRegistry::publicUrl($app, 'disclaimer'),
            'data_safety_url' => LegalTemplateRegistry::publicUrl($app, 'data-safety'),
        ];
    }

    public static function resolve(App $app, array $document, ?string $content = null): string
    {
        $content ??= (string) ($document['content'] ?? '');
        $values = self::placeholders($app, $document);

        return preg_replace_callback('/\{\{\s*([a-z0-9_\-]+)\s*\}\}/i', function (array $matches) use ($values): string {
            $key = strtolower((string) $matches[1]);
            $value = $values[$key] ?? null;

            return $value === null || $value === '' ? $matches[0] : (string) $value;
        }, $content) ?? $content;
    }

    public static function markdown(App $app, array $document, ?string $content = null): string
    {
        return Str::markdown(self::resolve($app, $document, $content), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public static function unresolved(App $app, array $document, ?string $content = null): array
    {
        $content ??= (string) ($document['content'] ?? '');
        preg_match_all('/\{\{\s*([a-z0-9_\-]+)\s*\}\}/i', $content, $matches);
        $values = self::placeholders($app, $document);

        return collect($matches[1] ?? [])
            ->map(fn ($key) => strtolower((string) $key))
            ->unique()
            ->filter(fn ($key) => ! array_key_exists($key, $values) || trim((string) $values[$key]) === '')
            ->values()
            ->all();
    }

    private static function formatDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('j F Y');
        } catch (\Throwable) {
            return $value;
        }
    }
}
