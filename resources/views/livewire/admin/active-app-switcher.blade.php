<div class="dxm-app-switcher-shell">
    <div class="dxm-app-switcher-labels">
        <div class="dxm-app-switcher-kicker">Current App</div>

        <div class="dxm-app-switcher-current">
            {{ $currentApp?->name ?? 'No active app' }}
        </div>

        @if ($currentApp?->slug)
            <div class="dxm-app-switcher-slug">
                {{ $currentApp->slug }}
            </div>
        @endif
    </div>

    <div class="dxm-app-switcher-control">
        <select
            wire:model.live="appId"
            wire:change.stop.prevent
            class="dxm-app-select"
        >
            @foreach ($apps as $app)
                <option value="{{ (int) $app['id'] }}">
                    {{ $app['name'] }}
                </option>
            @endforeach
        </select>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('app-switched', () => {
                window.location.reload();
            });
        });
    </script>
</div>
