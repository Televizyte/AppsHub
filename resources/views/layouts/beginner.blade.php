<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Beginner Workspace')</title>

    <style>
        :root{
            --bg:#070b12;
            --bg-2:#020617;
            --panel:rgba(255,255,255,.045);
            --border:rgba(255,255,255,.10);
            --text:rgba(255,255,255,.94);
            --muted:rgba(255,255,255,.66);
            --soft:rgba(255,255,255,.48);
            --accent:rgba(34,211,238,.22);
            --accent-strong:#22d3ee;
            --accent-border:rgba(34,211,238,.42);
            --danger:rgba(248,113,113,.22);
            --danger-border:rgba(248,113,113,.42);
            --radius:18px;
            --shadow:0 24px 70px rgba(0,0,0,.36);
        }

        *{box-sizing:border-box}
        html,body{min-height:100%}

        body{
            margin:0;
            font-family:Arial,Helvetica,sans-serif;
            background:
                radial-gradient(circle at top left,rgba(34,211,238,.12),transparent 34%),
                radial-gradient(circle at top right,rgba(168,85,247,.10),transparent 32%),
                var(--bg);
            color:var(--text);
            overflow:hidden;
        }

        a{color:inherit;text-decoration:none}

        input,select,textarea{
            width:100%;
            padding:12px 13px;
            border-radius:12px;
            border:1px solid var(--border);
            background:#020617;
            color:#e2e8f0;
            font-size:14px;
            outline:none;
        }

        input:focus,select:focus,textarea:focus{
            border-color:var(--accent-border);
            box-shadow:0 0 0 2px rgba(34,211,238,.18);
            background:#020617;
            color:#fff;
        }

        select option{background:#020617;color:#e2e8f0}

        select{
            appearance:none;
            -webkit-appearance:none;
            -moz-appearance:none;
            background-image:
                linear-gradient(45deg,transparent 50%,rgba(255,255,255,.65) 50%),
                linear-gradient(135deg,rgba(255,255,255,.65) 50%,transparent 50%);
            background-position:calc(100% - 18px) 52%,calc(100% - 13px) 52%;
            background-size:5px 5px,5px 5px;
            background-repeat:no-repeat;
            padding-right:36px;
        }

        input:-webkit-autofill{
            -webkit-box-shadow:0 0 0 1000px #020617 inset!important;
            -webkit-text-fill-color:#e2e8f0!important;
        }

        label{
            display:block;
            font-size:12px;
            line-height:1.3;
            font-weight:800;
            color:#fff;
            margin-bottom:7px;
        }

        small,.dxm-help{
            display:block;
            color:var(--soft);
            margin-top:7px;
            font-size:11.5px;
            line-height:1.45;
        }

        .dxm-shell{
            height:100vh;
            max-width:1560px;
            margin:0 auto;
            padding:14px;
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .dxm-topbar{
            display:grid;
            grid-template-columns:minmax(0,1fr) auto;
            align-items:center;
            gap:14px;
            padding:12px 14px;
            border:1px solid var(--border);
            border-radius:var(--radius);
            background:linear-gradient(135deg,rgba(8,145,178,.16),rgba(15,23,42,.92));
            box-shadow:var(--shadow);
            flex:0 0 auto;
        }

        .dxm-topbar__left{
            min-width:0;
            display:flex;
            align-items:center;
            gap:12px;
        }

        .dxm-title-wrap{min-width:0}

        .dxm-eyebrow{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:5px 9px;
            border:1px solid var(--accent-border);
            background:rgba(34,211,238,.10);
            color:rgba(207,250,254,.92);
            border-radius:999px;
            font-size:10.5px;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.08em;
            white-space:nowrap;
        }

        .dxm-topbar h1{
            margin:0;
            font-size:21px;
            line-height:1.15;
            letter-spacing:-.03em;
        }

        .dxm-topbar p{
            margin:4px 0 0;
            color:var(--muted);
            font-size:12.5px;
            line-height:1.4;
            max-width:900px;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .dxm-topbar__actions{
            display:flex;
            align-items:center;
            justify-content:flex-end;
            gap:8px;
            flex-wrap:wrap;
        }

        .dxm-btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            min-height:38px;
            padding:9px 13px;
            border-radius:12px;
            border:1px solid var(--border);
            background:#020617;
            color:#fff;
            cursor:pointer;
            font-size:12.5px;
            font-weight:850;
            white-space:nowrap;
        }

        .dxm-btn:hover{
            border-color:var(--accent-border);
            background:rgba(15,23,42,.95);
        }

        .dxm-btn--primary{
            border-color:rgba(34,211,238,.48);
            background:linear-gradient(135deg,rgba(34,211,238,.28),rgba(59,130,246,.20));
        }

        .dxm-studio-dock{
            flex:0 0 auto;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            padding:8px;
            border:1px solid var(--border);
            border-radius:16px;
            background:rgba(2,6,23,.58);
            backdrop-filter:blur(16px);
        }

        .dxm-dock-tabs{
            display:flex;
            align-items:center;
            gap:7px;
            overflow:auto;
            scrollbar-width:none;
        }

        .dxm-dock-tabs::-webkit-scrollbar{display:none}

        .dxm-dock-tab{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:34px;
            padding:8px 12px;
            border-radius:999px;
            border:1px solid rgba(255,255,255,.08);
            background:rgba(255,255,255,.035);
            color:rgba(255,255,255,.76);
            font-size:11.5px;
            font-weight:900;
            white-space:nowrap;
            cursor:pointer;
        }

        .dxm-dock-tab.is-active,.dxm-dock-tab:hover{
            color:#fff;
            border-color:var(--accent-border);
            background:rgba(34,211,238,.12);
        }

        .dxm-dock-actions{
            display:flex;
            align-items:center;
            gap:8px;
            flex:0 0 auto;
        }

        .dxm-workspace{
            flex:1 1 auto;
            min-height:0;
            display:grid;
            grid-template-columns:minmax(0,1.08fr) minmax(340px,.92fr);
            gap:10px;
        }

        .dxm-panel{
            min-height:0;
            border:1px solid var(--border);
            border-radius:var(--radius);
            background:linear-gradient(180deg,rgba(255,255,255,.052),rgba(255,255,255,.028));
            overflow:hidden;
            box-shadow:0 16px 50px rgba(0,0,0,.22);
            display:flex;
            flex-direction:column;
        }

        .dxm-panel__header{
            flex:0 0 auto;
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:14px;
            padding:10px 13px;
            border-bottom:1px solid var(--border);
            background:rgba(2,6,23,.42);
        }

        .dxm-panel__header h2{
            margin:0;
            font-size:14px;
            line-height:1.25;
        }

        .dxm-panel__header p{
            margin:4px 0 0;
            color:var(--muted);
            font-size:12px;
            line-height:1.4;
        }

        .dxm-panel__body{
            flex:1 1 auto;
            min-height:0;
            overflow:auto;
            padding:13px;
            scrollbar-width:thin;
            scrollbar-color:rgba(34,211,238,.42) rgba(2,6,23,.45);
        }

        .dxm-panel__body::-webkit-scrollbar,
        .dxm-editor-surface::-webkit-scrollbar,
        .dxm-preview-scroll::-webkit-scrollbar{width:8px;height:8px}

        .dxm-panel__body::-webkit-scrollbar-thumb,
        .dxm-editor-surface::-webkit-scrollbar-thumb,
        .dxm-preview-scroll::-webkit-scrollbar-thumb{background:rgba(34,211,238,.35);border-radius:20px}

        .dxm-panel__body::-webkit-scrollbar-track,
        .dxm-editor-surface::-webkit-scrollbar-track,
        .dxm-preview-scroll::-webkit-scrollbar-track{background:rgba(2,6,23,.35)}

        .dxm-grid{display:grid;grid-template-columns:repeat(12,1fr);gap:11px}
        .dxm-col-12{grid-column:span 12}
        .dxm-col-6{grid-column:span 6}
        .dxm-col-4{grid-column:span 4}

        .dxm-tab-panel{display:none;animation:dxmFade .16s ease-out}
        .dxm-tab-panel.is-active{display:block}
        @keyframes dxmFade{from{opacity:.35;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}

        .dxm-section-card{border:1px solid var(--border);border-radius:18px;background:rgba(2,6,23,.32);padding:14px;margin-bottom:13px}
        .dxm-section-card h3{margin:0 0 10px;font-size:14px;color:#fff}
        .dxm-section-card p{margin:0 0 12px;color:var(--muted);font-size:12.5px;line-height:1.45}

        .dxm-submit{position:sticky;bottom:-13px;z-index:20;margin:14px -13px -13px;padding:11px 13px;display:flex;justify-content:flex-end;gap:10px;border-top:1px solid var(--border);background:linear-gradient(180deg,rgba(2,6,23,.74),rgba(2,6,23,.96));backdrop-filter:blur(14px)}

        .dxm-alert{border:1px solid var(--border);background:rgba(2,6,23,.44);border-radius:18px;padding:14px;margin-bottom:14px;color:#fff}
        .dxm-alert--danger{border-color:var(--danger-border);background:rgba(127,29,29,.18)}
        .dxm-alert ul{margin:8px 0 0;padding-left:18px}

        .context-box{border:1px solid rgba(255,255,255,.10);background:rgba(2,6,23,.38);border-radius:18px;padding:14px;margin-bottom:14px;color:#fff;display:flex;justify-content:space-between;gap:12px;align-items:center}
        .context-eyebrow{display:block;color:rgba(255,255,255,.55);font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.07em;margin-bottom:4px}
        .context-box small{color:rgba(255,255,255,.58)}

        .dxm-checkbox{min-height:44px;display:flex;gap:10px;align-items:center;color:rgba(255,255,255,.78);font-weight:800}
        .dxm-checkbox input{width:18px;height:18px;padding:0;flex:0 0 auto}

        .dxm-editor-wrap{border:1px solid var(--border);border-radius:16px;background:rgba(2,6,23,.42);overflow:hidden;width:100%}
        .dxm-editor-top{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 11px;border-bottom:1px solid var(--border);background:rgba(15,23,42,.70)}
        .dxm-editor-title strong{display:block;font-size:13px;color:#fff}
        .dxm-editor-title span{display:block;color:var(--soft);font-size:11.5px;margin-top:3px}
        .dxm-editor-toolbar{display:flex;align-items:center;justify-content:flex-end;gap:6px;flex-wrap:wrap}
        .dxm-tool{min-width:34px;height:34px;border-radius:10px;border:1px solid var(--border);background:#020617;color:#fff;font-size:12px;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;padding:0 9px}
        .dxm-tool:hover{border-color:var(--accent-border);background:rgba(34,211,238,.10)}
        .dxm-tool svg{width:17px;height:17px;display:block;pointer-events:none}
        .dxm-tool[title]{position:relative}

        .dxm-editor-surface{height:430px;overflow:auto;padding:18px;outline:none;color:#e5e7eb;font-size:15.5px;line-height:1.75;background:linear-gradient(180deg,rgba(255,255,255,.025),rgba(255,255,255,.012)),#020617}
        .dxm-editor-surface:empty:before{content:attr(data-placeholder);color:rgba(255,255,255,.36)}
        .dxm-editor-hidden{display:none}

        .dxm-editor-surface h1,.dxm-preview-body h1{font-size:28px;line-height:1.2;margin:0 0 14px}
        .dxm-editor-surface h2,.dxm-preview-body h2{font-size:22px;line-height:1.25;margin:0 0 12px}
        .dxm-editor-surface h3,.dxm-preview-body h3{font-size:18px;line-height:1.3;margin:0 0 10px}
        .dxm-editor-surface p,.dxm-preview-body p{margin:0 0 13px}
        .dxm-editor-surface blockquote,.dxm-preview-body blockquote{margin:12px 0;padding:12px 14px;border-left:3px solid var(--accent-strong);background:rgba(34,211,238,.08);border-radius:12px;color:rgba(255,255,255,.88)}
        .dxm-editor-surface ul,.dxm-editor-surface ol,.dxm-preview-body ul,.dxm-preview-body ol{padding-left:22px;margin:10px 0 14px}
        .dxm-editor-surface a,.dxm-preview-body a{color:#67e8f9;text-decoration:underline}

        .dxm-preview{display:flex;flex-direction:column;gap:11px;min-height:100%}
        .dxm-preview-scroll{overflow:auto;min-height:0}
        .dxm-preview__cover{min-height:150px;border:1px solid var(--border);border-radius:16px;overflow:hidden;background:linear-gradient(135deg,rgba(34,211,238,.10),rgba(168,85,247,.08)),rgba(2,6,23,.45);display:flex;align-items:center;justify-content:center}
        .dxm-preview__cover img{width:100%;height:190px;object-fit:cover;display:block}
        .dxm-preview__card{border:1px solid var(--border);border-radius:16px;padding:14px;background:rgba(2,6,23,.38)}
        .dxm-preview__title{font-size:20px;font-weight:900;line-height:1.22;letter-spacing:-.02em;color:#fff}
        .dxm-preview__subtitle{margin-top:7px;color:var(--muted);font-size:13px;line-height:1.5}
        .dxm-preview__meta{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-top:11px}
        .dxm-tag{display:inline-flex;align-items:center;min-height:27px;padding:6px 9px;border-radius:999px;border:1px solid var(--border);background:rgba(255,255,255,.045);color:rgba(255,255,255,.78);font-size:10.5px;font-weight:900;text-transform:uppercase;letter-spacing:.05em}
        .dxm-preview-body{color:rgba(255,255,255,.84);line-height:1.72;font-size:14px;overflow-wrap:anywhere}
        .dxm-preview-body img{max-width:100%;height:auto;border-radius:14px;margin:10px 0}
        .dxm-preview__empty{color:rgba(255,255,255,.45);font-size:13px;line-height:1.45;text-align:center;padding:18px}

        @media(max-width:900px){body{overflow:auto}.dxm-shell{height:auto;min-height:100vh}.dxm-workspace{grid-template-columns:1fr}.dxm-panel{min-height:560px}}
        @media(max-width:760px){.dxm-shell{padding:10px}.dxm-topbar{grid-template-columns:1fr}.dxm-topbar__left{align-items:flex-start;flex-direction:column;gap:8px}.dxm-topbar__actions,.dxm-btn{width:100%}.dxm-topbar h1{font-size:20px}.dxm-topbar p{white-space:normal}.dxm-studio-dock{align-items:stretch;flex-direction:column}.dxm-dock-actions{width:100%}.dxm-dock-actions .dxm-btn{width:100%}.dxm-grid{grid-template-columns:1fr}.dxm-col-12,.dxm-col-6,.dxm-col-4{grid-column:span 1}.context-box{display:block}.context-box .dxm-btn{margin-top:12px;width:100%;text-align:center}.dxm-editor-top{align-items:flex-start;flex-direction:column}.dxm-editor-toolbar{justify-content:flex-start}.dxm-editor-surface{height:340px}.dxm-submit{flex-direction:column-reverse}}
    </style>

    @stack('styles')
</head>

<body>
<div class="dxm-shell">
    <header class="dxm-topbar">
        <div class="dxm-topbar__left">
            @hasSection('eyebrow')
                <div class="dxm-eyebrow">@yield('eyebrow')</div>
            @endif

            <div class="dxm-title-wrap">
                <h1>@yield('page_title', 'Beginner Workspace')</h1>
                <p>@yield('page_description')</p>
            </div>
        </div>

        <div class="dxm-topbar__actions">
            @hasSection('advanced_url')
                <a href="@yield('advanced_url')" class="dxm-btn">Advanced Editor</a>
            @endif

            @hasSection('back_url')
                <a href="@yield('back_url')" class="dxm-btn">← Back</a>
            @endif
        </div>
    </header>

    @hasSection('studio_tabs')
        <nav class="dxm-studio-dock" aria-label="Studio tabs">
            <div class="dxm-dock-tabs">
                @yield('studio_tabs')
            </div>

            <div class="dxm-dock-actions">
                @yield('dock_actions')
            </div>
        </nav>
    @endif

    <main class="dxm-workspace">
        <section class="dxm-panel dxm-form-panel">
            <div class="dxm-panel__header">
                <div>
                    <h2>@yield('form_title', 'Content Details')</h2>
                    <p>@yield('form_description', 'Fill the important content fields safely.')</p>
                </div>
            </div>

            <div class="dxm-panel__body">
                @yield('form')
            </div>
        </section>

        <section class="dxm-panel dxm-preview-panel">
            <div class="dxm-panel__header">
                <div>
                    <h2>Live Preview</h2>
                    <p>@yield('preview_description', 'Preview updates as you work.')</p>
                </div>
            </div>

            <div class="dxm-panel__body dxm-preview-scroll">
                @yield('preview')
            </div>
        </section>
    </main>
</div>

<script>
    (function () {
        function safeText(value, fallback) {
            value = (value || '').toString().trim();
            return value.length ? value : fallback;
        }

        function normalizeImageUrl(value) {
            value = (value || '').toString().trim();
            if (!value.length) return '';
            if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/')) return value;
            return '/storage/' + value.replace(/^public\//, '').replace(/^storage\//, '');
        }

        function enableGlobalSpellcheck() {
            document.documentElement.setAttribute('spellcheck', 'true');
            document.body.setAttribute('spellcheck', 'true');

            const selectors = [
                'input[type="text"]',
                'input[type="search"]',
                'input[type="email"]',
                'input:not([type])',
                'textarea',
                '[contenteditable="true"]',
                '[data-dxm-rich-editor]'
            ];

            document.querySelectorAll(selectors.join(',')).forEach(function (field) {
                const type = (field.getAttribute('type') || '').toLowerCase();
                const name = (field.getAttribute('name') || '').toLowerCase();
                const id = (field.getAttribute('id') || '').toLowerCase();

                const skip = ['url','image_url','cover_image_url','api','token','password','color','hex','slug'].some(function (keyword) {
                    return type.includes(keyword) || name.includes(keyword) || id.includes(keyword);
                });

                if (skip) {
                    field.setAttribute('spellcheck', 'false');
                    return;
                }

                field.setAttribute('spellcheck', 'true');
                field.setAttribute('lang', field.getAttribute('lang') || 'en');
                field.setAttribute('autocomplete', field.getAttribute('autocomplete') || 'off');

                if (field.matches('[contenteditable="true"], [data-dxm-rich-editor]')) {
                    field.setAttribute('role', 'textbox');
                    field.setAttribute('aria-multiline', 'true');
                    field.setAttribute('inputmode', 'text');
                }
            });
        }

        function setPreviewCover(value) {
    const coverBox = document.querySelector('[data-live="content-cover"]');
    if (!coverBox) return;

    const imageUrl = normalizeImageUrl(value);

    if (!imageUrl) {
        coverBox.innerHTML = '<div class="dxm-preview__empty">No cover image selected.</div>';
        return;
    }

    coverBox.innerHTML = `
        <img src="${imageUrl}" alt="Cover"
        onerror="this.parentElement.innerHTML='<div class=dxm-preview__empty>Image failed to load</div>'">
    `;
}

        function syncPreview() {
            const title = document.getElementById('title');
            const subtitle = document.getElementById('subtitle');
            const status = document.getElementById('status');
            const bucket = document.getElementById('bucket');
            const cover = document.getElementById('cover_image_url');
            const hiddenBody = document.getElementById('body_html');

            const liveTitle = document.querySelector('[data-live="content-title"]');
            const liveSubtitle = document.querySelector('[data-live="content-subtitle"]');
            const liveStatus = document.querySelector('[data-live="content-status"]');
            const liveChannel = document.querySelector('[data-live="content-channel"]');
            const liveBody = document.querySelector('[data-live="content-body"]');

            if (liveTitle && title) liveTitle.textContent = safeText(title.value, 'Content title preview');
            if (liveSubtitle && subtitle) liveSubtitle.textContent = subtitle.value || '';

            if (liveStatus && status) {
                const selected = status.options[status.selectedIndex];
                liveStatus.textContent = selected ? selected.text : safeText(status.value, 'Draft');
            }

            if (liveChannel && bucket) {
                const selected = bucket.options[bucket.selectedIndex];
                liveChannel.textContent = selected ? selected.text : safeText(bucket.value, 'Channel');
            }

            if (cover) setPreviewCover(cover.value);

            if (liveBody && hiddenBody) {
                const content = hiddenBody.value.trim();
                liveBody.innerHTML = content.length ? content : '<div class="dxm-preview__empty">No body content yet.</div>';
            }
        }

        function initRichEditor() {
            const editor = document.querySelector('[data-dxm-rich-editor]');
            const hidden = document.getElementById('body_html');

            if (!editor || !hidden) return;

            editor.setAttribute('spellcheck', 'true');
            editor.setAttribute('lang', editor.getAttribute('lang') || 'en');
            editor.setAttribute('role', 'textbox');
            editor.setAttribute('aria-multiline', 'true');
            editor.setAttribute('inputmode', 'text');
            editor.innerHTML = hidden.value || '';

            const syncHidden = function () {
                hidden.value = editor.innerHTML.trim();
                syncPreview();
            };

            editor.addEventListener('input', syncHidden);
            editor.addEventListener('blur', syncHidden);
            editor.addEventListener('keyup', syncHidden);
            editor.addEventListener('paste', function () {
                setTimeout(syncHidden, 10);
            });

            document.querySelectorAll('[data-dxm-command]').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();

                    const command = button.getAttribute('data-dxm-command');
                    const value = button.getAttribute('data-dxm-value') || null;

                    editor.focus();

                    if (command === 'createLink') {
                        const url = window.prompt('Paste the link URL');
                        if (url) document.execCommand('createLink', false, url);
                    } else if (command === 'insertImage') {
                        const url = window.prompt('Paste the image URL');
                        if (url) document.execCommand('insertImage', false, url);
                    } else if (command === 'formatBlock') {
                        document.execCommand('formatBlock', false, value);
                    } else {
                        document.execCommand(command, false, value);
                    }

                    syncHidden();
                    enableGlobalSpellcheck();
                });
            });

            syncHidden();
        }

        function initTabs() {
            const tabs = document.querySelectorAll('[data-dxm-tab]');
            const panels = document.querySelectorAll('[data-dxm-tab-panel]');

            if (!tabs.length || !panels.length) return;

            function activate(tabName) {
                tabs.forEach(function (tab) {
                    tab.classList.toggle('is-active', tab.getAttribute('data-dxm-tab') === tabName);
                });

                panels.forEach(function (panel) {
                    panel.classList.toggle('is-active', panel.getAttribute('data-dxm-tab-panel') === tabName);
                });

                setTimeout(enableGlobalSpellcheck, 10);
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    activate(tab.getAttribute('data-dxm-tab'));
                });
            });

            activate('content');
        }

        document.addEventListener('DOMContentLoaded', function () {
            enableGlobalSpellcheck();

            document.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.addEventListener('input', syncPreview);
                field.addEventListener('change', syncPreview);
            });

            document.addEventListener('input', function (event) {
                if (event.target && event.target.matches('[contenteditable="true"], [data-dxm-rich-editor]')) {
                    syncPreview();
                }
            });

            initRichEditor();
            initTabs();
            syncPreview();

            setTimeout(enableGlobalSpellcheck, 250);
        });
    })();
</script>

@stack('scripts')
</body>
</html>

