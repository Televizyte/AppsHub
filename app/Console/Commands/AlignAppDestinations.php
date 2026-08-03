<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Support\Legal\LegalTemplateRegistry;
use Illuminate\Console\Command;

class AlignAppDestinations extends Command
{
    protected $signature = 'app:align-destinations
        {appSlug}
        {--version-name= : Store-facing version name}
        {--version-code= : Store-facing version code}
        {--package= : Android package name}';

    protected $description = 'Synchronize legacy App Settings fields with authoritative legal and store destinations.';

    public function handle(): int
    {
        $app = App::query()->where('slug', (string) $this->argument('appSlug'))->first();
        if (! $app) {
            $this->error('App not found.');
            return self::FAILURE;
        }

        $branding = is_array($app->branding_json)
            ? $app->branding_json
            : (json_decode((string) $app->branding_json, true) ?: []);

        $legalPayload = LegalTemplateRegistry::bootstrapPayload($app);
        $docs = is_array($legalPayload['documents'] ?? null) ? $legalPayload['documents'] : [];
        $support = is_array($legalPayload['support'] ?? null) ? $legalPayload['support'] : [];
        $deletion = is_array($legalPayload['account_deletion'] ?? null) ? $legalPayload['account_deletion'] : [];

        $package = trim((string) ($this->option('package') ?: data_get($branding, 'store.package_name', '')));
        $playStore = $package === '' ? trim((string) data_get($branding, 'store.play_store_url', ''))
            : 'https://play.google.com/store/apps/details?id='.rawurlencode($package);

        data_set($branding, 'legal.privacy_url', trim((string) data_get($docs, 'privacy.url', '')));
        data_set($branding, 'legal.terms_url', trim((string) data_get($docs, 'terms.url', '')));
        data_set($branding, 'legal.support_url', trim((string) ($support['url'] ?? '')));
        data_set($branding, 'legal.support_form_url', trim((string) ($support['form_url'] ?? '')));
        data_set($branding, 'legal.account_deletion_url', trim((string) ($deletion['url'] ?? '')));

        if ($package !== '') data_set($branding, 'store.package_name', $package);
        if ($playStore !== '') data_set($branding, 'store.play_store_url', $playStore);
        if ($this->option('version-name')) data_set($branding, 'store.version_name', trim((string) $this->option('version-name')));
        if ($this->option('version-code')) data_set($branding, 'store.version_code', trim((string) $this->option('version-code')));
        data_set($branding, 'store.privacy_policy_url', (string) data_get($branding, 'legal.privacy_url', ''));

        $supportChannels = is_array(data_get($branding, 'support_channels')) ? data_get($branding, 'support_channels') : [];
        $supportChannels = $this->upsert($supportChannels, [
            'key' => 'app_support', 'label' => 'App Support', 'type' => 'url',
            'value' => (string) ($support['form_url'] ?? ''), 'description' => 'Technical support, account assistance, privacy requests and app complaints.',
            'icon' => 'support', 'enabled' => true,
        ]);
        $supportChannels = $this->upsert($supportChannels, [
            'key' => 'account_deletion', 'label' => 'Account Deletion', 'type' => 'url',
            'value' => (string) ($deletion['url'] ?? ''), 'description' => 'Request deletion of an account and associated eligible data.',
            'icon' => 'account-delete', 'enabled' => true,
        ]);
        data_set($branding, 'support_channels', $supportChannels);

        $app->branding_json = $branding;
        $app->save();

        $this->info('Destination alignment completed for '.$app->slug.'.');
        $this->line('Privacy: '.data_get($branding, 'legal.privacy_url', ''));
        $this->line('Terms: '.data_get($branding, 'legal.terms_url', ''));
        $this->line('Play Store: '.data_get($branding, 'store.play_store_url', ''));
        return self::SUCCESS;
    }

    private function upsert(array $rows, array $replacement): array
    {
        $found = false;
        foreach ($rows as $index => $row) {
            if (is_array($row) && (string) ($row['key'] ?? '') === $replacement['key']) {
                $rows[$index] = array_merge($row, $replacement);
                $found = true;
                break;
            }
        }
        if (! $found) $rows[] = $replacement;
        return array_values($rows);
    }
}
