<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WatchLink;
use App\Support\ActiveApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BeginnerWatchLinkController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);

        abort_if($appId <= 0, 422, 'No active app selected.');

        $data = $this->validated($request);

        WatchLink::query()->create([
            'app_id' => $appId,
            'title' => $data['title'],
            'type' => $data['type'],
            'url' => $data['url'],
            'is_enabled' => (bool) ($data['is_enabled'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'meta_json' => $this->meta($data),
        ]);

        return $this->back($request, 'Watch link created.');
    }

    public function update(Request $request, WatchLink $watchLink): RedirectResponse
    {
        $this->ensureScoped($watchLink);

        $data = $this->validated($request);

        $watchLink->update([
            'title' => $data['title'],
            'type' => $data['type'],
            'url' => $data['url'],
            'is_enabled' => (bool) ($data['is_enabled'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'meta_json' => $this->meta($data),
        ]);

        return $this->back($request, 'Watch link updated.');
    }

    public function toggle(Request $request, WatchLink $watchLink): RedirectResponse
    {
        $this->ensureScoped($watchLink);

        $watchLink->update([
            'is_enabled' => ! (bool) $watchLink->is_enabled,
        ]);

        return $this->back($request, 'Watch link status updated.');
    }

    public function move(Request $request, WatchLink $watchLink, string $direction): RedirectResponse
    {
        $this->ensureScoped($watchLink);

        $current = (int) ($watchLink->sort_order ?? 0);

        $watchLink->update([
            'sort_order' => $direction === 'up' ? max(0, $current - 1) : $current + 1,
        ]);

        return $this->back($request, 'Watch link order updated.');
    }

    public function delete(Request $request, WatchLink $watchLink): RedirectResponse
    {
        $this->ensureScoped($watchLink);

        $watchLink->delete();

        return $this->back($request, 'Watch link deleted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:80'],
            'url' => ['nullable', 'string', 'max:4000'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'label' => ['nullable', 'string', 'max:80'],
            'player' => ['nullable', 'string', 'max:80'],
            'group' => ['nullable', 'string', 'max:120'],
            'image_url' => ['nullable', 'string', 'max:4000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable'],
        ]);
    }

    protected function meta(array $data): array
    {
        $meta = [];

        foreach (['subtitle', 'label', 'player', 'group', 'image_url'] as $key) {
            $value = trim((string) ($data[$key] ?? ''));

            if ($value !== '') {
                $meta[$key] = $value;
            }
        }

        return $meta;
    }

    protected function ensureScoped(WatchLink $watchLink): void
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);

        abort_if($appId <= 0 || (int) $watchLink->app_id !== $appId, 403);
    }

    protected function back(Request $request, string $message): RedirectResponse
    {
        $tab = (string) $request->input('return_tab', $request->query('tab', 'primary'));

        return redirect()
            ->to(url('/admin/watch-builder') . '?tab=' . urlencode($tab))
            ->with('status', $message);
    }
}
