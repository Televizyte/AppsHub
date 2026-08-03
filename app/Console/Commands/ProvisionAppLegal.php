<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Support\Legal\LegalTemplateRegistry;
use Illuminate\Console\Command;

class ProvisionAppLegal extends Command
{
    protected $signature = 'legal:provision
        {appSlug : AppsHub app slug}
        {--publish : Publish all inherited legal documents}
        {--public-host=https://appshub.digitxtramedia.com : Public AppsHub host}
        {--operator= : Technical operator name}
        {--support-email= : App-user support email}
        {--privacy-email= : Privacy/data-request email}
        {--jurisdiction=Federal Republic of Nigeria : Governing jurisdiction}
        {--deletion-days=30 : Account deletion processing period}
        {--ministry-name= : Ministry name}
        {--ministry-email= : Ministry email}
        {--ministry-phone= : Ministry phone}
        {--ministry-address= : Ministry address}
        {--ministry-url= : Optional ministry contact URL}
        {--copyright-owner= : Copyright owner}';

    protected $description = 'Provision or refresh reusable AppsHub legal templates for an app';

    public function handle(): int
    {
        $app = App::query()->where('slug', $this->argument('appSlug'))->first();
        if (! $app) {
            $this->error('App not found.');
            return self::FAILURE;
        }

        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $legal = is_array(data_get($branding, 'legal')) ? data_get($branding, 'legal') : [];
        $existingProfile = is_array(data_get($legal, 'profile')) ? data_get($legal, 'profile') : [];
        $baseUrl = rtrim((string) $this->option('public-host'), '/').'/'.$app->slug;

        $profile = array_merge($existingProfile, array_filter([
            'operator_name' => trim((string) $this->option('operator')),
            'technical_support_email' => trim((string) $this->option('support-email')),
            'privacy_contact_email' => trim((string) ($this->option('privacy-email') ?: $this->option('support-email'))),
            'technical_support_url' => $baseUrl.'/support-request',
            'account_deletion_url' => $baseUrl.'/account-deletion',
            'account_deletion_period_days' => max(1, (int) $this->option('deletion-days')),
            'legal_public_base_url' => $baseUrl,
            'jurisdiction' => trim((string) $this->option('jurisdiction')),
            'ministry_name' => trim((string) $this->option('ministry-name')),
            'ministry_email' => trim((string) $this->option('ministry-email')),
            'ministry_phone' => trim((string) $this->option('ministry-phone')),
            'ministry_address' => trim((string) $this->option('ministry-address')),
            'ministry_contact_url' => trim((string) $this->option('ministry-url')),
            'copyright_owner' => trim((string) ($this->option('copyright-owner') ?: $this->option('operator'))),
        ], fn ($value) => $value !== null && $value !== ''));

        $documents = is_array(data_get($legal, 'documents')) ? data_get($legal, 'documents') : [];
        foreach (LegalTemplateRegistry::definitions() as $key => $definition) {
            $stored = is_array($documents[$key] ?? null) ? $documents[$key] : [];
            $default = LegalTemplateRegistry::defaultDocument($key);
            $mode = (string) ($stored['inheritance_mode'] ?? 'inherited');

            if ($mode === 'pinned' && $stored !== []) {
                continue;
            }

            $documents[$key] = array_merge($default, $stored, [
                'title' => $stored['title'] ?? $default['title'],
                'summary' => $stored['summary'] ?? $default['summary'],
                'content' => $mode === 'customized' && trim((string) ($stored['content'] ?? '')) !== ''
                    ? $stored['content']
                    : $default['content'],
                'effective_date' => $stored['effective_date'] ?? now()->toDateString(),
                'last_updated' => now()->toIso8601String(),
                'published' => $this->option('publish') ? true : (bool) ($stored['published'] ?? false),
                'inheritance_mode' => $mode,
                'template_version' => LegalTemplateRegistry::VERSION,
            ]);
        }

        $legal['profile'] = $profile;
        $legal['documents'] = $documents;
        foreach (LegalTemplateRegistry::definitions() as $key => $definition) {
            $mapKey = match ($key) {
                'privacy' => 'privacy_url',
                'terms' => 'terms_url',
                'support' => 'support_url',
                'community' => 'community_guidelines_url',
                'copyright' => 'copyright_url',
                'content-usage' => 'content_usage_url',
                'disclaimer' => 'disclaimer_url',
                'data-safety' => 'data_safety_url',
            };
            $legal[$mapKey] = $baseUrl.'/'.$definition['slug'];
        }
        $legal['account_deletion_url'] = $baseUrl.'/account-deletion';
        $legal['support_form_url'] = $baseUrl.'/support-request';
        $legal['template_version'] = LegalTemplateRegistry::VERSION;

        $branding['legal'] = $legal;
        $branding['support_email'] = $profile['technical_support_email'] ?? data_get($branding, 'support_email');
        $store = is_array(data_get($branding, 'store')) ? data_get($branding, 'store') : [];
        $store['privacy_policy_url'] = $baseUrl.'/privacy-policy';
        $branding['store'] = $store;

        $app->forceFill(['branding_json' => $branding])->save();

        $this->info('Legal engine provisioned for '.$app->name.'.');
        $this->line('Public base: '.$baseUrl);
        $this->line('Template version: '.LegalTemplateRegistry::VERSION);
        $this->line('Documents: '.count($documents));
        $this->line('Published: '.($this->option('publish') ? 'yes' : 'preserved'));

        return self::SUCCESS;
    }
}
