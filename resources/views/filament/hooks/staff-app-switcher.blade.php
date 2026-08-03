@php
    use App\Models\App;
    use App\Support\ActiveApp;
    use App\Support\AdminAccess;

    $isSuperAdmin = AdminAccess::super();
    $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
    $allowedAppIds = AdminAccess::allowedAppIds();
    $assignedApps = (! $isSuperAdmin && $allowedAppIds !== [])
        ? App::query()->whereIn('id', $allowedAppIds)->where('is_active', true)->orderBy('name')->get(['id','name','slug'])
        : collect();
@endphp

@if (! $isSuperAdmin && $assignedApps->isNotEmpty())
    <div class="dxm-sidebar-app-switcher">
        <details id="dxm-staff-assigned-apps-details" data-dxm-persist="staff-assigned-apps" open>
            <summary>
                <span>Assigned Apps</span>
                <b>{{ $assignedApps->count() }}</b>
            </summary>
            <div class="dxm-sidebar-app-list">
                @foreach ($assignedApps as $assignedApp)
                    <a
                        class="dxm-sidebar-app-link {{ (int) $assignedApp->id === $activeAppId ? 'is-active' : '' }}"
                        href="{{ route('admin.switch-active-app', ['id' => $assignedApp->id]) }}"
                    >
                        <strong>{{ $assignedApp->name }}</strong>
                        <small>{{ $assignedApp->slug ?? ('app-' . $assignedApp->id) }}</small>
                    </a>
                @endforeach
            </div>
        </details>
    </div>

    <style>
        /* AppsHub staff assigned-app sidebar switcher. */
        .dxm-sidebar-app-switcher{
            margin:8px 10px 12px;
            padding:10px;
            border:1px solid rgba(34,211,238,.16);
            border-radius:16px;
            background:linear-gradient(145deg,rgba(2,8,23,.96),rgba(15,23,42,.94));
        }
        .dxm-sidebar-app-switcher summary{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:8px;
            cursor:pointer;
            list-style:none;
            color:#e5f9ff;
            font-size:12px;
            font-weight:900;
        }
        .dxm-sidebar-app-switcher summary::-webkit-details-marker{display:none;}
        .dxm-sidebar-app-switcher summary span{text-transform:uppercase;letter-spacing:.06em;font-size:10px;color:#93c5fd;}
        .dxm-sidebar-app-switcher summary b{
            display:inline-grid;
            place-items:center;
            min-width:22px;
            height:22px;
            border-radius:999px;
            background:rgba(34,211,238,.14);
            color:#67e8f9;
            font-size:11px;
        }
        .dxm-sidebar-app-list{display:grid;gap:7px;margin-top:9px;}
        .dxm-sidebar-app-link{
            display:block;
            padding:9px 10px;
            border:1px solid rgba(148,163,184,.14);
            border-radius:13px;
            background:rgba(15,23,42,.76);
            text-decoration:none;
            transition:.16s ease;
        }
        .dxm-sidebar-app-link:hover{border-color:rgba(34,211,238,.42);background:rgba(8,47,73,.56);}
        .dxm-sidebar-app-link.is-active{border-color:rgba(34,211,238,.70);background:linear-gradient(135deg,rgba(8,47,73,.80),rgba(30,41,59,.92));}
        .dxm-sidebar-app-link strong{display:block;min-width:0;overflow:hidden;text-overflow:ellipsis;color:#fff;font-size:12px;line-height:1.2;}
        .dxm-sidebar-app-link small{display:block;margin-top:2px;min-width:0;overflow:hidden;text-overflow:ellipsis;color:#8fb8d4;font-size:10px;text-transform:uppercase;letter-spacing:.04em;}
        .dxm-sidebar-app-switcher details:not([open]) .dxm-sidebar-app-list{display:none;}
        .dxm-sidebar-app-switcher details:not([open]){padding-bottom:0;}
        .dxm-sidebar-app-switcher details:not([open]) summary{margin-bottom:0;}
    </style>

    <script>
        (() => {
            const storageKey = 'appshub_staff_assigned_apps_open';
            const details = document.getElementById('dxm-staff-assigned-apps-details');
            if (! details) return;

            const saved = localStorage.getItem(storageKey);
            if (saved === '0') {
                details.removeAttribute('open');
            } else if (saved === '1') {
                details.setAttribute('open', 'open');
            }

            details.addEventListener('toggle', () => {
                localStorage.setItem(storageKey, details.open ? '1' : '0');
            });
        })();
    </script>
@endif
