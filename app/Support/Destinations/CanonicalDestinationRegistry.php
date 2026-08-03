<?php

namespace App\Support\Destinations;

use App\Models\App;
use App\Support\Legal\LegalTemplateRegistry;

final class CanonicalDestinationRegistry
{
    public static function bootstrapPayload(App $app): array
    {
        $branding = is_array($app->branding_json)
            ? $app->branding_json
            : (json_decode((string) $app->branding_json, true) ?: []);

        $legal = LegalTemplateRegistry::bootstrapPayload($app);
        $documents = is_array($legal['documents'] ?? null) ? $legal['documents'] : [];
        $support = is_array($legal['support'] ?? null) ? $legal['support'] : [];
        $deletion = is_array($legal['account_deletion'] ?? null) ? $legal['account_deletion'] : [];

        $package = trim((string) data_get($branding, 'store.package_name', ''));
        $playStore = trim((string) data_get($branding, 'store.play_store_url', ''));
        if ($playStore === '' && $package !== '') {
            $playStore = 'https://play.google.com/store/apps/details?id='.rawurlencode($package);
        }

        $website = self::firstEnabledValue(
            data_get($branding, 'official_links', []),
            ['website', 'main_website']
        ) ?: trim((string) data_get($branding, 'website_url', ''));

        $privacy = trim((string) data_get($documents, 'privacy.url', data_get($branding, 'legal.privacy_url', '')));
        $terms = trim((string) data_get($documents, 'terms.url', data_get($branding, 'legal.terms_url', '')));
        $supportUrl = trim((string) ($support['url'] ?? ''));
        $supportForm = trim((string) ($support['form_url'] ?? data_get($branding, 'legal.profile.technical_support_url', '')));
        $accountDeletion = trim((string) ($deletion['url'] ?? data_get($branding, 'legal.profile.account_deletion_url', '')));

        return [
            'schema_version' => '1.0',
            'legal' => [
                'privacy' => self::entry('legal.privacy', 'Privacy Policy', 'web', $privacy),
                'terms' => self::entry('legal.terms', 'Terms of Use', 'web', $terms),
                'account_deletion' => self::entry('legal.account_deletion', 'Account Deletion', 'web', $accountDeletion),
                'support' => self::entry('legal.support', 'Support Information', 'web', $supportUrl),
            ],
            'support' => [
                'form' => self::entry('support.form', 'App Support', 'web', $supportForm),
                'ministry' => self::entry('support.ministry', 'Ministry Contact', 'internal_route', '/ministry-contact'),
            ],
            'store' => [
                'rate' => self::entry('store.rate', 'Rate App', 'external', $playStore),
            ],
            'official' => [
                'website' => self::entry('official.website', 'Website', 'web', $website),
            ],
            'app' => [
                'package_name' => $package,
                'version_name' => trim((string) data_get($branding, 'store.version_name', '')),
                'version_code' => trim((string) data_get($branding, 'store.version_code', '')),
            ],
        ];
    }

    private static function entry(string $key, string $label, string $type, string $value): array
    {
        $value = trim($value);

        return [
            'key' => $key,
            'label' => $label,
            'type' => $type,
            'value' => $value,
            'enabled' => $value !== '',
        ];
    }

    private static function firstEnabledValue(mixed $rows, array $keys): string
    {
        if (! is_array($rows)) return '';

        foreach ($rows as $row) {
            if (! is_array($row)) continue;
            if (($row['enabled'] ?? true) === false) continue;
            if (! in_array((string) ($row['key'] ?? ''), $keys, true)) continue;

            $value = trim((string) ($row['value'] ?? $row['url'] ?? ''));
            if ($value !== '') return $value;
        }

        return '';
    }
}
