<?php

namespace App\Livewire\Admin;

use App\Models\App;
use App\Support\ActiveApp;
use Livewire\Component;

class ActiveAppSwitcher extends Component
{
    public int $appId = 0;

    public function mount(): void
    {
        $this->appId = (int) (ActiveApp::ensureId() ?? 0);
    }

    public function updatedAppId($value): void
    {
        $id = is_numeric($value) ? (int) $value : 0;

        if ($id <= 0) {
            $this->appId = (int) (ActiveApp::ensureId() ?? 0);
            $this->dispatch('app-switched');
            return;
        }

        $app = App::query()
            ->whereKey($id)
            ->where('is_active', 1)
            ->first();

        if (! $app) {
            $this->appId = (int) (ActiveApp::ensureId() ?? 0);
            $this->dispatch('app-switched');
            return;
        }

        ActiveApp::set($app->id);

        $this->appId = (int) (ActiveApp::ensureId() ?? $app->id);

        $this->dispatch('app-switched');
    }

    public function render()
    {
        $apps = App::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_active'])
            ->toArray();

        $currentApp = App::query()
            ->whereKey(ActiveApp::ensureId())
            ->first(['id', 'name', 'slug']);

        return view('livewire.admin.active-app-switcher', [
            'apps' => $apps,
            'currentApp' => $currentApp,
        ]);
    }
}
