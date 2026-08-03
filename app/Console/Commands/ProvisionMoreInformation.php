<?php

namespace App\Console\Commands;

use App\Models\App;
use Illuminate\Console\Command;

class ProvisionMoreInformation extends Command
{
    protected $signature = 'app:provision-more-information {appSlug=dunamis-tv}';
    protected $description = 'Provision reusable More information-page configuration for an AppsHub app.';

    public function handle(): int
    {
        $app = App::query()->where('slug', $this->argument('appSlug'))->firstOrFail();
        $branding = is_array($app->branding_json) ? $app->branding_json : (json_decode((string) $app->branding_json, true) ?: []);
        data_set($branding, 'more_pages.schema_version', '1.0');
        data_set($branding, 'more_pages.technical_support.enabled', data_get($branding, 'more_pages.technical_support.enabled', true));
        data_set($branding, 'more_pages.ministry.enabled', data_get($branding, 'more_pages.ministry.enabled', true));
        data_set($branding, 'more_pages.about_app.enabled', data_get($branding, 'more_pages.about_app.enabled', true));
        data_set($branding, 'more_pages.legal_index.enabled', data_get($branding, 'more_pages.legal_index.enabled', true));
        data_set($branding, 'more_pages.build_with_dxm.enabled', data_get($branding, 'more_pages.build_with_dxm.enabled', true));
        data_set($branding, 'more_pages.build_with_dxm.public_email', data_get($branding, 'more_pages.build_with_dxm.public_email', 'apps.dxm@gmail.com'));
        data_set($branding, 'more_pages.build_with_dxm.phone_enabled', false);
        data_set($branding, 'more_pages.build_with_dxm.whatsapp_enabled', false);
        $app->branding_json = $branding;
        $app->save();
        $this->info('More information pages provisioned for '.$app->slug.'.');
        return self::SUCCESS;
    }
}
