@php
    $activeApp = \App\Models\App::query()->find((int) (\App\Support\ActiveApp::ensureId() ?? 0));
    $allowedAppIds = \App\Support\AdminAccess::allowedAppIds();
    $switcherApps = \App\Models\App::query()
        ->whereIn('id', $allowedAppIds)
        ->where('is_active', true)
        ->orderBy('name')
        ->get();
    $returnUrl = request()->getRequestUri();
@endphp

@if ($activeApp && $switcherApps->count() > 0)
    <div class="dxm-top-active-app-switcher" x-data="{ open:false }" x-on:click.outside="open=false">
        <button type="button" class="dxm-top-active-app-pill" x-on:click="open=!open" title="Switch active app">
            <span>Active App</span>
            <strong>{{ $activeApp->name }}</strong>
            <b>⌄</b>
        </button>

        <div class="dxm-top-active-app-menu" x-cloak x-show="open" x-transition.opacity.scale.origin.top>
            @foreach ($switcherApps as $app)
                <a
                    href="{{ route('admin.switch-active-app', ['id' => $app->id, 'return' => $returnUrl]) }}"
                    class="{{ (int) $activeApp->id === (int) $app->id ? 'active' : '' }}"
                >
                    <span class="dxm-top-app-logo">
                        @if (! empty($app->logo_url))
                            <img src="{{ $app->logo_url }}" alt="{{ $app->name }}">
                        @else
                            {{ strtoupper(substr((string) $app->name, 0, 1)) }}
                        @endif
                    </span>
                    <span>
                        <strong>{{ $app->name }}</strong>
                        <small>{{ strtoupper((string) $app->slug) }}</small>
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <style>
        /* AppsHub compact Active App dropdown switcher: clean topbar control, no workspace plank. */
        .dxm-top-active-app-switcher{position:relative;display:inline-flex;align-items:center;z-index:80}
        .dxm-top-active-app-pill{display:inline-flex;align-items:center;gap:8px;max-width:360px;height:34px;padding:0 12px;border:1px solid rgba(34,211,238,.22);border-radius:999px;background:rgba(15,23,42,.72);color:#dff7ff;white-space:nowrap;overflow:hidden;cursor:pointer}
        .dxm-top-active-app-pill:hover{border-color:rgba(34,211,238,.44);background:rgba(8,47,73,.55)}
        .dxm-top-active-app-pill span{font-size:10px;line-height:1;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;font-weight:900}
        .dxm-top-active-app-pill strong{min-width:0;overflow:hidden;text-overflow:ellipsis;font-size:13px;color:#fff;font-weight:950}
        .dxm-top-active-app-pill b{font-size:12px;color:#67e8f9;font-weight:950}
        .dxm-top-active-app-menu{position:absolute;top:42px;left:0;width:min(320px,calc(100vw - 34px));padding:8px;border:1px solid rgba(34,211,238,.20);border-radius:18px;background:linear-gradient(145deg,rgba(2,6,23,.98),rgba(15,23,42,.96));box-shadow:0 22px 58px rgba(0,0,0,.40);display:grid;gap:6px}
        .dxm-top-active-app-menu a{display:grid;grid-template-columns:38px minmax(0,1fr);gap:10px;align-items:center;border:1px solid rgba(148,163,184,.10);border-radius:14px;padding:8px;text-decoration:none;background:rgba(15,23,42,.48)}
        .dxm-top-active-app-menu a:hover,.dxm-top-active-app-menu a.active{border-color:rgba(34,211,238,.42);background:rgba(8,145,178,.16)}
        .dxm-top-app-logo{width:38px;height:38px;border-radius:12px;display:grid;place-items:center;background:rgba(255,255,255,.07);overflow:hidden;color:#fff;font-weight:950}
        .dxm-top-app-logo img{width:100%;height:100%;object-fit:cover}
        .dxm-top-active-app-menu strong{display:block;color:#fff;font-size:12px;font-weight:950;line-height:1.1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .dxm-top-active-app-menu small{display:block;margin-top:4px;color:#7dd3fc;font-size:10px;font-weight:850;letter-spacing:.04em;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        @media(max-width:860px){.dxm-top-active-app-pill span{display:none}.dxm-top-active-app-pill{max-width:190px}.dxm-top-active-app-menu{right:0;left:auto}}
    </style>
@endif
