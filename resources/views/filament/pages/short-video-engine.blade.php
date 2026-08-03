<x-filament-panels::page>
    <style>
        /* AppsHub Short Channel phase 3A polish: duration fallback + final library controls. */
        .sv-shell { display:flex; flex-direction:column; gap:14px; }
        .sv-hero { border:1px solid rgba(148,163,184,.18); border-radius:26px; padding:18px; background:linear-gradient(135deg, rgba(88,28,135,.34), rgba(15,23,42,.96) 48%, rgba(14,165,233,.13)); box-shadow:0 18px 50px rgba(2,6,23,.22); color:#e5e7eb; overflow:hidden; position:relative; }
        .sv-hero:after { content:""; position:absolute; width:210px; height:210px; right:-70px; top:-70px; border-radius:999px; background:radial-gradient(circle, rgba(168,85,247,.32), transparent 68%); pointer-events:none; }
        .sv-hero-top { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; position:relative; z-index:1; }
        .sv-kicker { display:inline-flex; align-items:center; gap:7px; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#c4b5fd; background:rgba(139,92,246,.14); border:1px solid rgba(196,181,253,.20); }
        .sv-title { margin-top:10px; font-size:26px; line-height:1.08; font-weight:900; color:#fff; letter-spacing:-.03em; }
        .sv-desc { margin-top:8px; max-width:760px; color:#cbd5e1; font-size:13px; line-height:1.55; }
        .sv-app-pill { display:inline-flex; flex-direction:column; gap:2px; min-width:180px; padding:10px 12px; border-radius:18px; background:rgba(15,23,42,.74); border:1px solid rgba(148,163,184,.18); text-align:right; }
        .sv-app-pill span { font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:.08em; font-weight:800; }
        .sv-app-pill strong { font-size:13px; color:#fff; }
        .sv-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; position:relative; z-index:1; }
        .sv-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; min-height:34px; padding:8px 12px; border-radius:12px; border:1px solid rgba(148,163,184,.22); background:rgba(15,23,42,.72); color:#e2e8f0; font-size:12px; font-weight:800; text-decoration:none; transition:.18s ease; cursor:pointer; }
        .sv-btn:hover { transform:translateY(-1px); border-color:rgba(196,181,253,.5); color:#fff; }
        .sv-btn.primary { background:linear-gradient(135deg,#7c3aed,#06b6d4); border-color:transparent; color:#fff; }
        .sv-btn.small { min-height:30px; padding:6px 10px; font-size:11px; }
        .sv-tabs { display:flex; flex-wrap:wrap; gap:8px; padding:7px; border-radius:18px; background:rgba(15,23,42,.72); border:1px solid rgba(148,163,184,.16); }
        .sv-tab { border:0; cursor:pointer; border-radius:13px; padding:9px 12px; background:transparent; color:#94a3b8; font-size:12px; font-weight:900; }
        .sv-tab.active { background:linear-gradient(135deg, rgba(124,58,237,.95), rgba(14,165,233,.72)); color:#fff; box-shadow:0 10px 24px rgba(14,165,233,.16); }
        .sv-card { border:1px solid rgba(148,163,184,.16); border-radius:22px; background:rgba(15,23,42,.78); color:#e5e7eb; box-shadow:0 14px 40px rgba(2,6,23,.15); overflow:hidden; }
        .sv-card-pad { padding:14px; }
        .sv-card-head { display:flex; justify-content:space-between; gap:10px; align-items:flex-start; margin-bottom:12px; }
        .sv-card-title { font-size:15px; font-weight:900; color:#fff; }
        .sv-card-note { margin-top:3px; font-size:12px; line-height:1.45; color:#94a3b8; }
        .sv-stats { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:10px; }
        .sv-stat { border:1px solid rgba(148,163,184,.14); border-radius:18px; padding:12px; background:linear-gradient(180deg, rgba(30,41,59,.72), rgba(15,23,42,.88)); min-height:92px; }
        .sv-stat span { font-size:10px; letter-spacing:.08em; text-transform:uppercase; color:#94a3b8; font-weight:900; }
        .sv-stat strong { display:block; margin-top:8px; font-size:24px; line-height:1; color:#fff; font-weight:950; }
        .sv-stat small { display:block; margin-top:7px; color:#a5b4fc; font-size:11px; }
        .sv-two { display:grid; grid-template-columns:minmax(0,1.12fr) minmax(280px,.88fr); gap:14px; align-items:start; }
        .sv-preview-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(280px,360px); gap:14px; align-items:start; }
        .sv-toolbar { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
        .sv-view-toggle { display:inline-flex; gap:6px; padding:4px; border-radius:14px; background:rgba(2,6,23,.42); border:1px solid rgba(148,163,184,.16); }
        .sv-view-toggle button { border:0; cursor:pointer; border-radius:10px; padding:6px 10px; background:transparent; color:#94a3b8; font-size:11px; font-weight:900; }
        .sv-view-toggle button.active { background:linear-gradient(135deg, rgba(124,58,237,.9), rgba(14,165,233,.65)); color:#fff; }
        .sv-filterbar { display:grid; grid-template-columns:minmax(180px,1fr) 150px 160px 150px auto; gap:8px; align-items:end; margin-bottom:12px; }
        .sv-input, .sv-select, .sv-textarea { width:100%; border-radius:13px; border:1px solid rgba(148,163,184,.22); background:rgba(2,6,23,.44) !important; color:#e5e7eb; padding:9px 10px; font-size:12px; outline:none; }
        .sv-select { appearance:none !important; -webkit-appearance:none !important; -moz-appearance:none !important; background-image:none !important; padding-right:30px !important; }
        .sv-select::-ms-expand { display:none; }
        .sv-select-wrap { position:relative; }
        .sv-select-wrap:after { content:"⌄"; position:absolute; right:11px; top:50%; transform:translateY(-53%); color:#94a3b8; font-size:13px; pointer-events:none; line-height:1; }
        .sv-label { display:block; font-size:11px; color:#94a3b8; font-weight:900; margin-bottom:5px; }
        .sv-library { display:grid; gap:10px; }
        .sv-library.is-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
        .sv-library.is-list { grid-template-columns:1fr; }
        .sv-video-card { border:1px solid rgba(148,163,184,.14); border-radius:18px; padding:10px; background:rgba(2,6,23,.33); min-width:0; }
        .sv-video-card.is-grid { display:flex; flex-direction:column; gap:9px; }
        .sv-video-card.is-list { display:grid; grid-template-columns:92px minmax(0,1fr); gap:10px; align-items:start; }
        .sv-thumb { border-radius:15px; background:linear-gradient(135deg, rgba(124,58,237,.25), rgba(14,165,233,.16)); border:1px solid rgba(148,163,184,.14); overflow:hidden; display:flex; align-items:center; justify-content:center; color:#c4b5fd; font-size:26px; font-weight:950; }
        .sv-video-card.is-grid .sv-thumb { width:100%; height:155px; }
        .sv-video-card.is-list .sv-thumb { width:92px; height:122px; }
        .sv-thumb img { width:100%; height:100%; object-fit:cover; }
        .sv-video-title { color:#fff; font-weight:950; font-size:13px; line-height:1.25; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .sv-meta { display:flex; flex-wrap:wrap; gap:5px; margin-top:7px; }
        .sv-badge { display:inline-flex; align-items:center; gap:4px; border-radius:999px; padding:4px 7px; font-size:10px; font-weight:900; border:1px solid rgba(148,163,184,.16); background:rgba(15,23,42,.75); color:#cbd5e1; }
        .sv-badge.published { color:#86efac; border-color:rgba(34,197,94,.22); background:rgba(22,163,74,.1); }
        .sv-badge.draft { color:#fde68a; border-color:rgba(245,158,11,.25); background:rgba(245,158,11,.1); }
        .sv-badge.featured { color:#f0abfc; border-color:rgba(217,70,239,.24); background:rgba(168,85,247,.12); }
        .sv-video-actions { display:flex; gap:6px; flex-wrap:wrap; margin-top:10px; }
        .sv-mini-link, .sv-mini-btn { cursor:pointer; padding:6px 8px; border-radius:10px; border:1px solid rgba(148,163,184,.16); color:#cbd5e1; text-decoration:none; font-size:11px; font-weight:900; background:rgba(15,23,42,.8); }
        .sv-mini-btn.primary { color:#67e8f9; border-color:rgba(6,182,212,.35); background:rgba(8,145,178,.12); }
        .sv-mini-btn.danger { color:#fecaca; border-color:rgba(248,113,113,.3); background:rgba(127,29,29,.2); }
        .sv-category-list { display:grid; gap:10px; }
        .sv-category-list.is-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
        .sv-category-list.is-list { grid-template-columns:1fr; }
        .sv-category-row { border:1px solid rgba(148,163,184,.14); border-radius:18px; padding:12px; background:rgba(2,6,23,.34); display:grid; grid-template-columns:52px minmax(0,1fr); gap:10px; align-items:center; }
        .sv-category-list.is-list .sv-category-row { grid-template-columns:52px minmax(0,1fr) auto; }
        .sv-cat-icon { width:42px; height:42px; flex:0 0 42px; border-radius:14px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,rgba(124,58,237,.44),rgba(14,165,233,.24)); color:#fff; font-weight:950; overflow:hidden; }
        .sv-cat-icon img { width:100%; height:100%; object-fit:cover; }
        .sv-cat-title { color:#fff; font-size:13px; font-weight:950; }
        .sv-cat-note { color:#94a3b8; font-size:11px; margin-top:3px; }
        .sv-category-actions { display:flex; flex-wrap:wrap; gap:6px; justify-content:flex-end; }
        .sv-category-list.is-grid .sv-category-actions { grid-column:1/-1; justify-content:flex-start; }
        .sv-color-dot { width:12px; height:12px; display:inline-flex; border-radius:999px; border:1px solid rgba(255,255,255,.35); }
        .sv-settings-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .sv-setting { border:1px solid rgba(148,163,184,.14); border-radius:18px; padding:12px; background:rgba(2,6,23,.34); }
        .sv-toggle-row { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
        .sv-toggle-row strong { color:#fff; font-size:13px; }
        .sv-toggle-row p { color:#94a3b8; font-size:11px; line-height:1.4; margin-top:3px; }
        .sv-toggle { width:18px; height:18px; accent-color:#8b5cf6; margin-top:2px; }
        .sv-code { white-space:pre-wrap; word-break:break-word; max-height:640px; overflow:auto; border:1px solid rgba(148,163,184,.14); border-radius:18px; background:rgba(2,6,23,.58); color:#dbeafe; padding:12px; font-size:11px; line-height:1.45; }
        .sv-phone { max-width:300px; margin:auto; border-radius:30px; padding:10px; background:#020617; border:1px solid rgba(148,163,184,.2); box-shadow:0 18px 45px rgba(2,6,23,.38); }
        .sv-phone-screen { min-height:470px; border-radius:24px; background:radial-gradient(circle at 30% 10%, rgba(124,58,237,.28), transparent 38%), #0f172a; overflow:hidden; position:relative; }
        .sv-phone-short { height:470px; display:flex; flex-direction:column; justify-content:flex-end; padding:14px; background:linear-gradient(180deg, transparent 20%, rgba(0,0,0,.72)); position:relative; }
        .sv-phone-short img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:.78; }
        .sv-phone-caption { position:relative; z-index:1; color:#fff; }
        .sv-empty { padding:22px; border:1px dashed rgba(148,163,184,.24); border-radius:20px; color:#94a3b8; text-align:center; background:rgba(2,6,23,.24); }
        .sv-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .sv-form-span { grid-column:1/-1; }
        .sv-check-row { display:flex; align-items:center; gap:8px; color:#cbd5e1; font-size:12px; font-weight:800; }
        .sv-editor-panel { border:1px solid rgba(14,165,233,.24); border-radius:22px; background:linear-gradient(180deg,rgba(8,47,73,.34),rgba(15,23,42,.84)); }
        .sv-video-pick-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; max-height:360px; overflow:auto; padding:8px; border:1px solid rgba(148,163,184,.14); border-radius:16px; background:rgba(2,6,23,.24); }
        .sv-video-pick { display:grid; grid-template-columns:44px minmax(0,1fr) auto; gap:8px; align-items:center; padding:8px; border-radius:14px; border:1px solid rgba(148,163,184,.12); background:rgba(15,23,42,.55); }
        .sv-video-pick img { width:44px; height:58px; object-fit:cover; border-radius:10px; }
        .sv-video-pick strong { color:#fff; font-size:12px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .sv-video-pick small { color:#94a3b8; font-size:10px; }
        @media (max-width:1180px){ .sv-stats{grid-template-columns:repeat(3,minmax(0,1fr));} .sv-filterbar{grid-template-columns:1fr 1fr;} .sv-library.is-grid,.sv-category-list.is-grid{grid-template-columns:repeat(2,minmax(0,1fr));} .sv-preview-grid,.sv-two{grid-template-columns:1fr;} }
        @media (max-width:760px){ .sv-hero-top{flex-direction:column;} .sv-app-pill{text-align:left;width:100%;} .sv-stats,.sv-settings-grid,.sv-form-grid,.sv-filterbar,.sv-library.is-grid,.sv-category-list.is-grid{grid-template-columns:1fr;} .sv-video-card.is-list{grid-template-columns:82px minmax(0,1fr);} .sv-video-card.is-list .sv-thumb{width:82px;height:112px;} .sv-category-list.is-list .sv-category-row{grid-template-columns:42px minmax(0,1fr);} .sv-category-actions{grid-column:1/-1;justify-content:flex-start;} }

        /* AppsHub Short Video compact library tightening START */
        .sv-shell { gap:12px !important; }
        .sv-hero { padding:16px 18px !important; border-radius:22px !important; }
        .sv-title { font-size:24px !important; margin-top:8px !important; }
        .sv-desc { font-size:12.5px !important; line-height:1.48 !important; }
        .sv-tabs { padding:6px !important; gap:6px !important; border-radius:16px !important; }
        .sv-tab { padding:8px 11px !important; }
        .sv-card { border-radius:20px !important; }
        .sv-card-pad { padding:13px !important; }
        .sv-stats { gap:9px !important; }
        .sv-stat { min-height:78px !important; padding:11px !important; border-radius:16px !important; }
        .sv-stat strong { font-size:22px !important; margin-top:6px !important; }
        .sv-toolbar { margin-bottom:10px !important; }
        .sv-filterbar { grid-template-columns:minmax(190px,1fr) 150px 150px 130px auto !important; gap:8px !important; }
        .sv-input, .sv-select, .sv-textarea { min-height:38px !important; border-radius:12px !important; padding:8px 10px !important; font-size:11.5px !important; }
        .sv-select { padding-right:28px !important; }
        .sv-select-wrap:after { right:10px !important; font-size:12px !important; opacity:.9 !important; }

        /* Grid view: true short-video cards, compact and vertical. */
        .sv-library.is-grid {
            grid-template-columns:repeat(auto-fill, minmax(178px, 1fr)) !important;
            gap:12px !important;
            align-items:start !important;
        }
        .sv-video-card { border-radius:18px !important; padding:10px !important; }
        .sv-video-card.is-grid { gap:8px !important; min-height:0 !important; }
        .sv-video-card.is-grid .sv-thumb {
            width:100% !important;
            height:auto !important;
            aspect-ratio:9/12.5 !important;
            max-height:240px !important;
            border-radius:16px !important;
        }
        .sv-video-card.is-grid .sv-thumb img { object-fit:cover !important; }
        .sv-video-card.is-grid .sv-video-title { min-height:32px !important; }
        .sv-video-card.is-grid .sv-select-wrap { display:block !important; }
        .sv-video-card.is-grid .sv-select { min-height:36px !important; }
        .sv-video-card.is-grid .sv-video-actions { margin-top:8px !important; }

        /* List view: two-column compact listing on desktop instead of one long full-width row. */
        .sv-library.is-list {
            grid-template-columns:repeat(2, minmax(0, 1fr)) !important;
            gap:10px !important;
            align-items:start !important;
        }
        .sv-video-card.is-list {
            grid-template-columns:76px minmax(0, 1fr) !important;
            gap:10px !important;
            align-items:start !important;
            min-height:142px !important;
        }
        .sv-video-card.is-list .sv-thumb {
            width:76px !important;
            height:112px !important;
            border-radius:14px !important;
        }
        .sv-video-card.is-list .sv-meta { margin-top:5px !important; }
        .sv-video-card.is-list .sv-video-actions { margin-top:7px !important; }
        .sv-video-title { font-size:12.5px !important; }
        .sv-badge { padding:3px 6px !important; font-size:9.5px !important; }
        .sv-label { font-size:10.5px !important; margin-bottom:4px !important; }
        .sv-mini-link, .sv-mini-btn { padding:5px 8px !important; font-size:10.5px !important; border-radius:9px !important; }

        /* Category and feed sections tightened to reduce long-page feeling. */
        .sv-category-list.is-grid { grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)) !important; gap:10px !important; }
        .sv-category-list.is-list { grid-template-columns:repeat(2, minmax(0, 1fr)) !important; gap:10px !important; }
        .sv-category-row { padding:10px !important; border-radius:16px !important; grid-template-columns:46px minmax(0,1fr) !important; }
        .sv-category-list.is-list .sv-category-row { grid-template-columns:46px minmax(0,1fr) auto !important; }
        .sv-cat-icon { width:38px !important; height:38px !important; border-radius:12px !important; }
        .sv-settings-grid { gap:9px !important; }
        .sv-setting { padding:10px !important; border-radius:16px !important; }
        .sv-toggle-row strong { font-size:12.5px !important; }
        .sv-toggle-row p { font-size:10.5px !important; }

        /* Preview stays side-by-side on wider admin screens and is shorter. */
        .sv-preview-grid { grid-template-columns:minmax(0,1.08fr) minmax(260px,330px) !important; gap:12px !important; }
        .sv-code { max-height:520px !important; font-size:10.5px !important; border-radius:16px !important; }
        .sv-phone { max-width:280px !important; padding:8px !important; border-radius:26px !important; }
        .sv-phone-screen { min-height:410px !important; border-radius:22px !important; }
        .sv-phone-short { height:410px !important; padding:12px !important; }

        @media (min-width:1500px){
            .sv-library.is-grid { grid-template-columns:repeat(auto-fill, minmax(188px, 1fr)) !important; }
        }
        @media (max-width:1180px){
            .sv-filterbar { grid-template-columns:1fr 1fr !important; }
            .sv-library.is-grid { grid-template-columns:repeat(auto-fill, minmax(170px, 1fr)) !important; }
            .sv-library.is-list, .sv-category-list.is-list { grid-template-columns:1fr !important; }
            .sv-preview-grid { grid-template-columns:1fr !important; }
        }
        @media (max-width:760px){
            .sv-shell { gap:10px !important; }
            .sv-hero { padding:14px !important; border-radius:20px !important; }
            .sv-title { font-size:21px !important; }
            .sv-filterbar, .sv-settings-grid, .sv-category-list.is-grid, .sv-category-list.is-list { grid-template-columns:1fr !important; }
            .sv-library.is-grid, .sv-library.is-list { grid-template-columns:1fr !important; }
            .sv-video-card.is-grid, .sv-video-card.is-list { grid-template-columns:92px minmax(0,1fr) !important; display:grid !important; }
            .sv-video-card.is-grid .sv-thumb, .sv-video-card.is-list .sv-thumb { width:92px !important; height:122px !important; aspect-ratio:auto !important; }
            .sv-video-pick-list { grid-template-columns:1fr !important; }
            .sv-video-card.is-grid .sv-video-title { min-height:0 !important; }
            .sv-phone { max-width:100% !important; }
        }
        /* AppsHub Short Video compact library tightening END */

        /* AppsHub Short Channel workspace cleanup START */
        body:has(.sv-focus-workspace) .fi-sidebar,
        body:has(.sv-focus-workspace) aside.fi-sidebar,
        body:has(.sv-focus-workspace) .fi-topbar { display:none !important; }
        body:has(.sv-focus-workspace) .fi-main,
        body:has(.sv-focus-workspace) .fi-page,
        body:has(.sv-focus-workspace) main { max-width:none !important; margin-left:0 !important; }
        .sv-focus-workspace { min-height:calc(100vh - 54px); display:grid; grid-template-columns:minmax(0,1fr) minmax(320px,420px); gap:14px; align-items:start; }
        .sv-focus-main { border:1px solid rgba(14,165,233,.28); border-radius:24px; background:linear-gradient(135deg, rgba(8,47,73,.56), rgba(15,23,42,.96)); box-shadow:0 24px 70px rgba(2,6,23,.28); overflow:hidden; }
        .sv-focus-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; padding:16px; border-bottom:1px solid rgba(148,163,184,.12); background:linear-gradient(135deg, rgba(6,182,212,.12), rgba(124,58,237,.08)); }
        .sv-focus-title { color:#fff; font-size:22px; font-weight:950; letter-spacing:-.02em; }
        .sv-focus-sub { margin-top:5px; color:#a7c7df; font-size:12px; line-height:1.45; max-width:720px; }
        .sv-focus-body { padding:14px; }
        .sv-focus-side { position:sticky; top:14px; display:grid; gap:12px; }
        .sv-help-card { border:1px solid rgba(148,163,184,.15); border-radius:22px; background:rgba(15,23,42,.78); padding:14px; color:#cbd5e1; }
        .sv-help-card strong { display:block; color:#fff; font-size:14px; margin-bottom:6px; }
        .sv-help-card p { margin:0; color:#94a3b8; font-size:12px; line-height:1.5; }
        .sv-editor-mini-list { display:grid; gap:8px; max-height:520px; overflow:auto; padding-right:4px; }
        .sv-editor-mini-item { display:flex; gap:8px; align-items:center; padding:8px; border:1px solid rgba(148,163,184,.13); border-radius:14px; background:rgba(2,6,23,.28); }
        .sv-editor-mini-item img { width:44px; height:54px; object-fit:cover; border-radius:10px; }
        .sv-editor-mini-item span { display:block; color:#fff; font-size:11px; font-weight:900; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .sv-editor-mini-item small { display:block; color:#94a3b8; font-size:10px; }
        .sv-category-row { grid-template-columns:1fr !important; gap:10px !important; position:relative; overflow:hidden; }
        .sv-category-row:before { content:""; display:block; height:48px; border-radius:14px; background:linear-gradient(135deg, rgba(14,165,233,.28), rgba(124,58,237,.32), rgba(15,23,42,.58)); border:1px solid rgba(148,163,184,.10); }
        .sv-cat-icon { display:none !important; }
        .sv-category-list.is-list { grid-template-columns:repeat(2,minmax(0,1fr)) !important; }
        .sv-category-list.is-list .sv-category-row { grid-template-columns:1fr !important; }
        .sv-category-actions { justify-content:flex-start !important; }
        .sv-cat-title { font-size:14px !important; }
        .sv-meta { gap:6px !important; }
        .sv-tabs { position:relative !important; }
        @media (max-width:980px){ .sv-focus-workspace{grid-template-columns:1fr;} .sv-focus-side{position:relative;top:auto;} .sv-category-list.is-list{grid-template-columns:1fr !important;} }

        /* AppsHub Short Channel final editor blend + assigned preview START */
        body:has(.sv-focus-workspace),
        body:has(.sv-focus-workspace) .fi-body,
        body:has(.sv-focus-workspace) .fi-main,
        body:has(.sv-focus-workspace) .fi-page,
        body:has(.sv-focus-workspace) main {
            background: radial-gradient(circle at top left, rgba(14, 165, 233, .12), transparent 34%), #07111f !important;
            color: #e5f3ff !important;
        }
        .sv-focus-workspace {
            background: linear-gradient(135deg, rgba(8, 24, 43, .98), rgba(5, 13, 26, .98)) !important;
            border: 1px solid rgba(56, 189, 248, .18) !important;
            border-radius: 24px !important;
            padding: 14px !important;
            box-shadow: 0 24px 80px rgba(0,0,0,.32) !important;
        }
        .sv-focus-main,
        .sv-focus-side,
        .sv-help-card,
        .sv-video-pick,
        .sv-editor-mini-item {
            background: rgba(9, 19, 35, .88) !important;
            border-color: rgba(148, 163, 184, .16) !important;
            box-shadow: none !important;
        }
        .sv-focus-head {
            background: linear-gradient(135deg, rgba(8, 47, 73, .72), rgba(15, 23, 42, .72)) !important;
            border-color: rgba(34, 211, 238, .18) !important;
        }
        .sv-focus-title { letter-spacing: -.02em; }
        .sv-editor-empty {
            border:1px dashed rgba(148,163,184,.25);
            border-radius:16px;
            padding:16px;
            color:#93a9c8;
            background:rgba(15,23,42,.55);
            font-size:12px;
            line-height:1.45;
        }
        /* AppsHub Short Channel final editor blend + assigned preview END */

        /* AppsHub Short Channel workspace cleanup END */

    </style>

    @php
        $stats = $this->stats();
        $categories = $this->categories();
        $channels = $this->channelRows();
        $categoryOptions = $this->categoryOptions();
        $shorts = $this->shortRows();
        $previewPayload = $this->previewPayload();
        $firstShort = $shorts[0] ?? null;
        $tabs = [
            'overview' => 'Overview',
            'library' => 'Library',
            'categories' => 'Categories',
            'short_channels' => 'Short Channels',
            'feed_settings' => 'Feed Settings',
            'preview' => 'Preview',
        ];
    @endphp

    <div class="sv-shell">

        @if($workspaceMode === 'category_editor')
            <section class="sv-focus-workspace">
                <div class="sv-focus-main">
                    <div class="sv-focus-head">
                        <div>
                            <div class="sv-kicker">Short Video Category</div>
                            <div class="sv-focus-title">{{ $editingCategoryKey ? 'Edit Category' : 'Create Category' }}</div>
                            <div class="sv-focus-sub">Focused workspace for category identity. Categories describe the type of short video; Short Channels decide where the videos appear in the app.</div>
                        </div>
                        <button type="button" class="sv-btn" wire:click="closeCategoryEditor">← Back to Categories</button>
                    </div>
                    <div class="sv-focus-body">
                        <div class="sv-form-grid">
                            <div><label class="sv-label">Category Name</label><input class="sv-input" type="text" wire:model.defer="categoryForm.label" placeholder="e.g. Worship"></div>
                            <div><label class="sv-label">Slug / Key</label><input class="sv-input" type="text" wire:model.defer="categoryForm.key" placeholder="Auto from name if empty"></div>
                            <div class="sv-form-span"><label class="sv-label">Description</label><textarea class="sv-textarea" rows="3" wire:model.defer="categoryForm.description" placeholder="Short note for admins/frontend context"></textarea></div>
                            <div><label class="sv-label">Uniform Icon / Label</label><input class="sv-input" type="text" wire:model.defer="categoryForm.icon" placeholder="Optional small label"></div>
                            <div><label class="sv-label">Accent Color</label><input class="sv-input" type="text" wire:model.defer="categoryForm.color" placeholder="#7c3aed"></div>
                            <div class="sv-form-span"><label class="sv-label">Banner / Cover Image URL or Storage Path</label><input class="sv-input" type="text" wire:model.defer="categoryForm.cover_url" placeholder="Optional category banner image"></div>
                            <div><label class="sv-label">Sort Order</label><input class="sv-input" type="number" min="0" wire:model.defer="categoryForm.sort_order"></div>
                            <div style="display:grid;gap:8px;align-content:end;"><label class="sv-check-row"><input type="checkbox" wire:model.defer="categoryForm.is_active"> Active</label><label class="sv-check-row"><input type="checkbox" wire:model.defer="categoryForm.show_on_frontend"> Show on frontend</label></div>
                        </div>
                        <div class="sv-actions" style="margin-top:14px;"><button type="button" class="sv-btn primary" wire:click="saveCategory">{{ $editingCategoryKey ? 'Update Category' : '+ Save Category' }}</button><button type="button" class="sv-btn" wire:click="resetCategoryForm(true)">Reset</button><button type="button" class="sv-btn" wire:click="closeCategoryEditor">Cancel</button></div>
                    </div>
                </div>
                <aside class="sv-focus-side">
                    <div class="sv-help-card"><strong>Category usage</strong><p>Use categories for content type, filtering, and frontend category tabs. Do not use them as frontend placements; use Short Channels for that.</p></div>
                    <div class="sv-help-card"><strong>Visual style</strong><p>Cards now use a consistent gradient banner/placeholder. A real banner image can be added through the cover field later.</p></div>
                    <div class="sv-help-card"><strong>After saving</strong><p>The editor closes and returns to the Categories tab without deleting or moving any short video.</p></div>
                </aside>
            </section>
        @elseif($workspaceMode === 'channel_editor')
            <section class="sv-focus-workspace">
                <div class="sv-focus-main">
                    <div class="sv-focus-head">
                        <div>
                            <div class="sv-kicker">Short Channel</div>
                            <div class="sv-focus-title">{{ $editingChannelKey ? 'Edit Short Channel' : 'Create Short Channel' }}</div>
                            <div class="sv-focus-sub">Focused workspace for a dedicated frontend output feed such as Home Shorts, Inspire Shorts, or Motivation Page Shorts.</div>
                        </div>
                        <button type="button" class="sv-btn" wire:click="closeChannelEditor">← Back to Short Channels</button>
                    </div>
                    <div class="sv-focus-body">
                        <div class="sv-form-grid">
                            <div><label class="sv-label">Channel Name</label><input class="sv-input" type="text" wire:model.defer="channelForm.label" placeholder="e.g. Home Shorts"></div>
                            <div><label class="sv-label">Channel Key</label><input class="sv-input" type="text" wire:model.defer="channelForm.key" placeholder="Auto from name if empty"></div>
                            <div><label class="sv-label">Frontend Placement</label><input class="sv-input" type="text" wire:model.defer="channelForm.placement" placeholder="Home Tab / Inspire Tab / Motivation Page"></div>
                            <div><label class="sv-label">Display Style</label><span class="sv-select-wrap"><select class="sv-select" wire:model.defer="channelForm.display_style">@foreach($this->displayStyleOptions() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></span></div>
                            <div class="sv-form-span"><label class="sv-label">Description</label><textarea class="sv-textarea" rows="3" wire:model.defer="channelForm.description" placeholder="Short note for admins/frontend context"></textarea></div>
                            <div><label class="sv-label">Uniform Icon / Label</label><input class="sv-input" type="text" wire:model.defer="channelForm.icon" placeholder="Optional small label"></div>
                            <div><label class="sv-label">Accent Color</label><input class="sv-input" type="text" wire:model.defer="channelForm.color" placeholder="#06b6d4"></div>
                            <div><label class="sv-label">Sort Order</label><input class="sv-input" type="number" min="0" wire:model.defer="channelForm.sort_order"></div>
                            <div style="display:grid;gap:8px;align-content:end;"><label class="sv-check-row"><input type="checkbox" wire:model.defer="channelForm.is_active"> Active</label><label class="sv-check-row"><input type="checkbox" wire:model.defer="channelForm.show_on_frontend"> Show on frontend</label></div>
                            <div class="sv-form-span">
                                <label class="sv-label">Assign Short Videos to this Channel</label>
                                <div class="sv-card-note" style="margin-bottom:8px;">This does not duplicate files. It only tells the frontend which videos belong to this output feed.</div>
                                <div class="sv-video-pick-list">
                                    @foreach($shorts as $short)
                                        <label class="sv-video-pick">
                                            @if(!empty($short['thumbnail_url']))<img src="{{ $short['thumbnail_url'] }}" alt="">@else<div class="sv-thumb" style="width:44px;height:58px;border-radius:10px;font-size:16px;">▶</div>@endif
                                            <span style="min-width:0;"><strong>{{ $short['title'] ?? 'Untitled Short' }}</strong><small>{{ $short['category_label'] ?? 'General' }} @if(!empty($short['video_duration'])) • {{ $short['video_duration'] }} @endif</small></span>
                                            <input type="checkbox" value="{{ (int) ($short['id'] ?? 0) }}" wire:model.live="channelForm.video_ids">
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="sv-actions" style="margin-top:14px;"><button type="button" class="sv-btn primary" wire:click="saveChannel">{{ $editingChannelKey ? 'Update Short Channel' : '+ Save Short Channel' }}</button><button type="button" class="sv-btn" wire:click="resetChannelForm(true)">Reset</button><button type="button" class="sv-btn" wire:click="closeChannelEditor">Cancel</button></div>
                    </div>
                </div>
                <aside class="sv-focus-side">
                    <div class="sv-help-card"><strong>Short Channel usage</strong><p>Short Channels are frontend outputs. A video can appear in Home Shorts, Inspire Shorts, Motivation Page Shorts, or any custom output without re-uploading it.</p></div>
                    <div class="sv-help-card"><strong>Assigned videos</strong>
                        @php
                            $selectedChannelVideoIds = array_values(array_filter(array_map('intval', $channelForm['video_ids'] ?? [])));
                            $assignedPreviewShorts = array_values(array_filter($shorts, fn ($short) => in_array((int) ($short['id'] ?? 0), $selectedChannelVideoIds, true)));
                        @endphp
                        @if(count($assignedPreviewShorts) > 0)
                            <div class="sv-card-note" style="margin:6px 0 10px;">Showing only videos currently checked on the left.</div>
                            <div class="sv-editor-mini-list">
                                @foreach($assignedPreviewShorts as $short)
                                    <div class="sv-editor-mini-item">
                                        @if(!empty($short['thumbnail_url']))<img src="{{ $short['thumbnail_url'] }}" alt="">@else<div class="sv-thumb" style="width:44px;height:54px;font-size:14px;">▶</div>@endif
                                        <span style="min-width:0;"><span>{{ $short['title'] ?? 'Untitled' }}</span><small>{{ $short['category_label'] ?? 'General' }} @if(!empty($short['video_duration'])) • {{ $short['video_duration'] }} @endif</small></span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="sv-editor-empty">No videos assigned yet. Tick videos on the left and they will appear here.</div>
                        @endif
                    </div>
                    <div class="sv-help-card"><strong>After saving</strong><p>The editor closes and returns to Short Channels. The videos remain in the main library.</p></div>
                </aside>
            </section>
        @else

        <section class="sv-hero">
            <div class="sv-hero-top">
                <div>
                    <div class="sv-kicker">▶ AppsHub Engine • Short Video Feed</div>
                    <div class="sv-title">Short Video Engine</div>
                    <div class="sv-desc">A standalone control center for reels-style clips, categories, Short Channels, feed behavior, and frontend delivery. It builds on the existing <strong>short_videos</strong> bucket so the current app frontend remains safe.</div>
                </div>
                <div class="sv-app-pill"><span>Active App</span><strong>{{ $currentApp?->name ?? 'No active app' }}</strong><span>{{ $currentApp?->slug ?? 'app not selected' }}</span></div>
            </div>
            <div class="sv-actions">
                <a class="sv-btn primary" href="{{ $this->addShortUrl() }}">+ Add Short Video</a>
                <a class="sv-btn" href="{{ $this->libraryUrl() }}">Open Existing Content Library</a>
                <a class="sv-btn" href="{{ $this->mediaLibraryUrl() }}">Open Media Library</a>
                <button type="button" class="sv-btn" wire:click="selectTab('feed_settings')">Feed Settings</button>
            </div>
        </section>

        <div class="sv-tabs">
            @foreach($tabs as $key => $label)
                <button type="button" wire:click="selectTab('{{ $key }}')" class="sv-tab {{ $activeTab === $key ? 'active' : '' }}">{{ $label }}</button>
            @endforeach
        </div>

        @if($activeTab === 'overview')
            <section class="sv-stats">
                <div class="sv-stat"><span>Total Shorts</span><strong>{{ $stats['total'] }}</strong><small>Current app library</small></div>
                <div class="sv-stat"><span>Published</span><strong>{{ $stats['published'] }}</strong><small>Visible-ready items</small></div>
                <div class="sv-stat"><span>Drafts</span><strong>{{ $stats['drafts'] }}</strong><small>Work in progress</small></div>
                <div class="sv-stat"><span>Featured</span><strong>{{ $stats['featured'] }}</strong><small>Priority feed items</small></div>
                <div class="sv-stat"><span>Categories</span><strong>{{ $stats['categories'] }}</strong><small>Detected + defaults</small></div>
                <div class="sv-stat"><span>Short Channels</span><strong>{{ $stats['channels'] ?? count($channels) }}</strong><small>Frontend outputs</small></div>
                <div class="sv-stat"><span>Latest Update</span><strong style="font-size:13px;line-height:1.25">{{ $stats['latest'] }}</strong><small>Last activity</small></div>
            </section>

            <section class="sv-two">
                <div class="sv-card"><div class="sv-card-pad">
                    <div class="sv-toolbar" style="margin-bottom:12px;">
                        <div><div class="sv-card-title">Latest Short Videos</div><div class="sv-card-note">Visual snapshot from the existing short_videos bucket.</div></div>
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <div class="sv-view-toggle"><button type="button" class="{{ $overviewView === 'grid' ? 'active' : '' }}" wire:click="setOverviewView('grid')">Grid</button><button type="button" class="{{ $overviewView === 'list' ? 'active' : '' }}" wire:click="setOverviewView('list')">List</button></div>
                            <button type="button" class="sv-btn small" wire:click="selectTab('library')">View Library</button>
                        </div>
                    </div>
                    @if(count($shorts))
                        <div class="sv-library is-{{ $overviewView }}">
                            @foreach(array_slice($shorts, 0, 6) as $short)
                                @include('filament.pages.partials.short-video-card', ['short' => $short, 'categoryOptions' => $categoryOptions, 'viewMode' => $overviewView])
                            @endforeach
                        </div>
                    @else
                        <div class="sv-empty">No short videos found yet for this app. Use Add Short Video to create the first item.</div>
                    @endif
                </div></div>

                <div class="sv-card"><div class="sv-card-pad">
                    <div class="sv-card-title">Feed Behavior Rule</div>
                    <div class="sv-card-note">Auto-scroll is part of the engine plan and saved per app.</div>
                    <div style="margin-top:12px; display:grid; gap:8px;">
                        <div class="sv-badge">Autoplay: {{ $feedSettings['autoplay'] ? 'On' : 'Off' }}</div>
                        <div class="sv-badge">Loop Current Video: {{ $feedSettings['loop_current_video'] ? 'On' : 'Off' }}</div>
                        <div class="sv-badge featured">Auto Scroll Next: {{ $feedSettings['auto_scroll_next'] ? 'On' : 'Off' }}</div>
                        <div class="sv-badge">Speed: {{ ucfirst($feedSettings['smooth_scroll_speed']) }}</div>
                    </div>
                    <div class="sv-card-note" style="margin-top:14px;">If loop is ON, the frontend should replay the current video. If loop is OFF and auto-scroll is ON, it should smoothly and quickly move to the next short when the current video ends.</div>
                </div></div>
            </section>
        @endif

        @if($activeTab === 'library')
            <section class="sv-card"><div class="sv-card-pad">
                <div class="sv-card-head"><div><div class="sv-card-title">Short Video Library</div><div class="sv-card-note">Grid/List workspace for the current short_videos bucket. The existing editor remains the edit screen. Short Channels will become the dedicated frontend output layer.</div></div><div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;"><button type="button" class="sv-btn" wire:click="syncMissingDurations">Sync Durations</button><a class="sv-btn primary" href="{{ $this->addShortUrl() }}">+ Add Short</a></div></div>

                <div class="sv-filterbar">
                    <div><label class="sv-label">Search</label><input class="sv-input" type="search" placeholder="Search title or caption..." wire:model.live.debounce.450ms="search"></div>
                    <div><label class="sv-label">Status</label><span class="sv-select-wrap"><select class="sv-select" wire:model.live="statusFilter">@foreach($this->statusOptions() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></span></div>
                    <div><label class="sv-label">Category</label><span class="sv-select-wrap"><select class="sv-select" wire:model.live="categoryFilter"><option value="all">All Categories</option>@foreach($categories as $category)<option value="{{ $category['key'] }}">{{ $category['label'] }}</option>@endforeach</select></span></div>
                    <div><label class="sv-label">Featured</label><span class="sv-select-wrap"><select class="sv-select" wire:model.live="featuredFilter"><option value="all">All</option><option value="featured">Featured</option><option value="normal">Normal</option></select></span></div>
                    <button type="button" class="sv-btn" wire:click="resetFilters">Reset</button>
                </div>
                <div class="sv-toolbar" style="margin-bottom:12px;">
                    <div class="sv-card-note">Showing {{ count($shorts) }} item(s)</div>
                    <div class="sv-view-toggle"><button type="button" class="{{ $libraryView === 'grid' ? 'active' : '' }}" wire:click="setLibraryView('grid')">Grid</button><button type="button" class="{{ $libraryView === 'list' ? 'active' : '' }}" wire:click="setLibraryView('list')">List</button></div>
                </div>

                @if(count($shorts))
                    <div class="sv-library is-{{ $libraryView }}">
                        @foreach($shorts as $short)
                            @include('filament.pages.partials.short-video-card', ['short' => $short, 'categoryOptions' => $categoryOptions, 'viewMode' => $libraryView])
                        @endforeach
                    </div>
                @else
                    <div class="sv-empty">No shorts match the selected filters.</div>
                @endif
            </div></section>
        @endif

        @if($activeTab === 'categories')
            @if(false && $showCategoryEditor)
                <section class="sv-card sv-editor-panel"><div class="sv-card-pad">
                    <div class="sv-card-head">
                        <div><div class="sv-card-title">{{ $editingCategoryKey ? 'Edit Short Video Category' : 'Create Short Video Category' }}</div><div class="sv-card-note">Focused editor. Save closes this panel and returns to the category list.</div></div>
                        <button type="button" class="sv-btn" wire:click="closeCategoryEditor">Close Editor</button>
                    </div>
                    <div class="sv-form-grid">
                        <div><label class="sv-label">Category Name</label><input class="sv-input" type="text" wire:model.defer="categoryForm.label" placeholder="e.g. Worship"></div>
                        <div><label class="sv-label">Slug / Key</label><input class="sv-input" type="text" wire:model.defer="categoryForm.key" placeholder="Auto from name if empty"></div>
                        <div class="sv-form-span"><label class="sv-label">Description</label><textarea class="sv-textarea" rows="2" wire:model.defer="categoryForm.description" placeholder="Short note for admins/frontend context"></textarea></div>
                        <div><label class="sv-label">Icon / Emoji</label><input class="sv-input" type="text" wire:model.defer="categoryForm.icon" placeholder="▶"></div>
                        <div><label class="sv-label">Accent Color</label><input class="sv-input" type="text" wire:model.defer="categoryForm.color" placeholder="#7c3aed"></div>
                        <div class="sv-form-span"><label class="sv-label">Cover Image URL / Storage Path</label><input class="sv-input" type="text" wire:model.defer="categoryForm.cover_url" placeholder="Optional category cover image"></div>
                        <div><label class="sv-label">Sort Order</label><input class="sv-input" type="number" min="0" wire:model.defer="categoryForm.sort_order"></div>
                        <div style="display:grid;gap:8px;align-content:end;"><label class="sv-check-row"><input type="checkbox" wire:model.defer="categoryForm.is_active"> Active</label><label class="sv-check-row"><input type="checkbox" wire:model.defer="categoryForm.show_on_frontend"> Show on frontend</label></div>
                    </div>
                    <div class="sv-actions" style="margin-top:12px;"><button type="button" class="sv-btn primary" wire:click="saveCategory">{{ $editingCategoryKey ? 'Update Category' : '+ Save Category' }}</button><button type="button" class="sv-btn" wire:click="resetCategoryForm(true)">Reset</button><button type="button" class="sv-btn" wire:click="closeCategoryEditor">Cancel</button></div>
                </div></section>
            @endif

            <section class="sv-card"><div class="sv-card-pad">
                <div class="sv-card-head">
                    <div><div class="sv-card-title">Managed Categories</div><div class="sv-card-note">Create, edit, order, and control frontend category tabs. The editor only opens when needed.</div></div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;"><button type="button" class="sv-btn primary" wire:click="createCategory">+ Create Category</button><button type="button" class="sv-btn" wire:click="seedDefaultCategories">Prepare Defaults</button></div>
                </div>
                <div class="sv-toolbar" style="margin-bottom:12px;"><div class="sv-card-note">{{ count($categories) }} category item(s)</div><div class="sv-view-toggle"><button type="button" class="{{ $categoryView === 'grid' ? 'active' : '' }}" wire:click="setCategoryView('grid')">Grid</button><button type="button" class="{{ $categoryView === 'list' ? 'active' : '' }}" wire:click="setCategoryView('list')">List</button></div></div>
                <div class="sv-category-list is-{{ $categoryView }}">
                    @foreach($categories as $category)
                        <div class="sv-category-row">
                            <div class="sv-cat-icon" style="background:{{ $category['color'] ?? '#7c3aed' }};">@if(!empty($category['cover_url']))<img src="{{ $this->assetUrl($category['cover_url']) }}" alt="">@elseif(!empty($category['sample_thumbnail']))<img src="{{ $category['sample_thumbnail'] }}" alt="">@else{{ $category['icon'] ?? mb_substr($category['label'], 0, 1) }}@endif</div>
                            <div style="min-width:0;"><div class="sv-cat-title">{{ $category['label'] }}</div><div class="sv-cat-note">{{ $category['count'] }} shorts • {{ $category['published'] }} published • {{ $category['featured'] }} featured</div>@if(!empty($category['description']))<div class="sv-cat-note">{{ $category['description'] }}</div>@endif<div class="sv-meta"><span class="sv-badge"><span class="sv-color-dot" style="background:{{ $category['color'] ?? '#7c3aed' }}"></span>{{ $category['key'] }}</span><span class="sv-badge">{{ ucfirst($category['source'] ?? 'custom') }}</span><span class="sv-badge {{ ($category['is_active'] ?? true) ? 'published' : 'draft' }}">{{ ($category['is_active'] ?? true) ? 'Active' : 'Inactive' }}</span><span class="sv-badge {{ ($category['show_on_frontend'] ?? true) ? 'published' : 'draft' }}">{{ ($category['show_on_frontend'] ?? true) ? 'Frontend' : 'Hidden' }}</span></div></div>
                            <div class="sv-category-actions"><button type="button" class="sv-mini-btn primary" wire:click="editCategory('{{ $category['key'] }}')">Edit</button><button type="button" class="sv-mini-btn" wire:click="toggleCategoryActive('{{ $category['key'] }}')">{{ ($category['is_active'] ?? true) ? 'Disable' : 'Enable' }}</button><button type="button" class="sv-mini-btn danger" wire:click="deleteCategory('{{ $category['key'] }}')">Remove</button></div>
                        </div>
                    @endforeach
                </div>
            </div></section>
        @endif

        @if($activeTab === 'short_channels')
            @if(false && $showChannelEditor)
                <section class="sv-card sv-editor-panel"><div class="sv-card-pad">
                    <div class="sv-card-head">
                        <div><div class="sv-card-title">{{ $editingChannelKey ? 'Edit Short Channel' : 'Create Short Channel' }}</div><div class="sv-card-note">A Short Channel is a dedicated frontend output, for example Home Shorts, Inspire Shorts, or Motivation Page Shorts. Save closes this panel.</div></div>
                        <button type="button" class="sv-btn" wire:click="closeChannelEditor">Close Editor</button>
                    </div>
                    <div class="sv-form-grid">
                        <div><label class="sv-label">Channel Name</label><input class="sv-input" type="text" wire:model.defer="channelForm.label" placeholder="e.g. Home Shorts"></div>
                        <div><label class="sv-label">Channel Key</label><input class="sv-input" type="text" wire:model.defer="channelForm.key" placeholder="Auto from name if empty"></div>
                        <div><label class="sv-label">Frontend Placement</label><input class="sv-input" type="text" wire:model.defer="channelForm.placement" placeholder="Home Tab / Inspire Tab / Motivation Page"></div>
                        <div><label class="sv-label">Display Style</label><span class="sv-select-wrap"><select class="sv-select" wire:model.defer="channelForm.display_style">@foreach($this->displayStyleOptions() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></span></div>
                        <div class="sv-form-span"><label class="sv-label">Description</label><textarea class="sv-textarea" rows="2" wire:model.defer="channelForm.description" placeholder="What this frontend output is used for"></textarea></div>
                        <div><label class="sv-label">Icon / Emoji</label><input class="sv-input" type="text" wire:model.defer="channelForm.icon" placeholder="▶"></div>
                        <div><label class="sv-label">Accent Color</label><input class="sv-input" type="text" wire:model.defer="channelForm.color" placeholder="#06b6d4"></div>
                        <div><label class="sv-label">Sort Order</label><input class="sv-input" type="number" min="0" wire:model.defer="channelForm.sort_order"></div>
                        <div style="display:grid;gap:8px;align-content:end;"><label class="sv-check-row"><input type="checkbox" wire:model.defer="channelForm.is_active"> Active</label><label class="sv-check-row"><input type="checkbox" wire:model.defer="channelForm.show_on_frontend"> Show on frontend</label></div>
                        <div class="sv-form-span">
                            <label class="sv-label">Assign Short Videos to this Channel</label>
                            <div class="sv-card-note" style="margin-bottom:8px;">This does not duplicate files. It only tells the frontend which videos belong to this output feed.</div>
                            <div class="sv-video-pick-list">
                                @foreach($shorts as $short)
                                    <label class="sv-video-pick">
                                        @if(!empty($short['thumbnail_url']))<img src="{{ $short['thumbnail_url'] }}" alt="">@else<div class="sv-thumb" style="width:44px;height:58px;border-radius:10px;font-size:16px;">▶</div>@endif
                                        <span style="min-width:0;"><strong>{{ $short['title'] ?? 'Untitled Short' }}</strong><small>{{ $short['category_label'] ?? 'General' }} @if(!empty($short['video_duration'])) • {{ $short['video_duration'] }} @endif</small></span>
                                        <input type="checkbox" value="{{ (int) ($short['id'] ?? 0) }}" wire:model.live="channelForm.video_ids">
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="sv-actions" style="margin-top:12px;"><button type="button" class="sv-btn primary" wire:click="saveChannel">{{ $editingChannelKey ? 'Update Short Channel' : '+ Save Short Channel' }}</button><button type="button" class="sv-btn" wire:click="resetChannelForm(true)">Reset</button><button type="button" class="sv-btn" wire:click="closeChannelEditor">Cancel</button></div>
                </div></section>
            @endif

            <section class="sv-card"><div class="sv-card-pad">
                <div class="sv-card-head">
                    <div><div class="sv-card-title">Short Channels</div><div class="sv-card-note">Create frontend output feeds. Categories describe videos; Short Channels decide where videos appear in the app.</div></div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;"><button type="button" class="sv-btn primary" wire:click="createChannel">+ Create Short Channel</button><button type="button" class="sv-btn" wire:click="seedDefaultChannels">Prepare Defaults</button></div>
                </div>
                <div class="sv-toolbar" style="margin-bottom:12px;"><div class="sv-card-note">{{ count($channels) }} Short Channel output(s)</div><div class="sv-view-toggle"><button type="button" class="{{ $channelView === 'grid' ? 'active' : '' }}" wire:click="setChannelView('grid')">Grid</button><button type="button" class="{{ $channelView === 'list' ? 'active' : '' }}" wire:click="setChannelView('list')">List</button></div></div>
                <div class="sv-category-list is-{{ $channelView }}">
                    @foreach($channels as $channel)
                        <div class="sv-category-row">
                            <div class="sv-cat-icon" style="background:{{ $channel['color'] ?? '#06b6d4' }};">{{ $channel['icon'] ?? '▶' }}</div>
                            <div style="min-width:0;"><div class="sv-cat-title">{{ $channel['label'] }}</div><div class="sv-cat-note">{{ $channel['placement'] ?? 'Custom Placement' }} • {{ $channel['count'] ?? count($channel['video_ids'] ?? []) }} assigned video(s)</div>@if(!empty($channel['description']))<div class="sv-cat-note">{{ $channel['description'] }}</div>@endif<div class="sv-meta"><span class="sv-badge"><span class="sv-color-dot" style="background:{{ $channel['color'] ?? '#06b6d4' }}"></span>{{ $channel['key'] }}</span><span class="sv-badge">{{ ucwords(str_replace('_',' ', $channel['display_style'] ?? 'vertical_feed')) }}</span><span class="sv-badge {{ ($channel['is_active'] ?? true) ? 'published' : 'draft' }}">{{ ($channel['is_active'] ?? true) ? 'Active' : 'Inactive' }}</span><span class="sv-badge {{ ($channel['show_on_frontend'] ?? true) ? 'published' : 'draft' }}">{{ ($channel['show_on_frontend'] ?? true) ? 'Frontend' : 'Hidden' }}</span></div></div>
                            <div class="sv-category-actions"><button type="button" class="sv-mini-btn primary" wire:click="editChannel('{{ $channel['key'] }}')">Manage Videos</button><button type="button" class="sv-mini-btn" wire:click="toggleChannelActive('{{ $channel['key'] }}')">{{ ($channel['is_active'] ?? true) ? 'Disable' : 'Enable' }}</button><button type="button" class="sv-mini-btn danger" wire:click="deleteChannel('{{ $channel['key'] }}')">Remove</button></div>
                        </div>
                    @endforeach
                </div>
            </div></section>
        @endif

        @if($activeTab === 'feed_settings')
            <section class="sv-card"><div class="sv-card-pad">
                <div class="sv-card-head"><div><div class="sv-card-title">Feed Settings</div><div class="sv-card-note">Saved per active app in app branding settings. Frontend alignment comes after this backend shell is confirmed.</div></div><button type="button" class="sv-btn primary" wire:click="saveFeedSettings">Save Settings</button></div>
                <div class="sv-settings-grid">
                    @foreach(['enabled' => ['Enable Short Video Feed','Allow this app to expose short videos to frontend sections.'],'show_category_tabs' => ['Show Category Tabs','Frontend should display dynamic category tabs.'],'show_search_filter' => ['Show Search / Filter','Allow users to quickly find shorts inside the feed.'],'autoplay' => ['Autoplay Videos','Start current short automatically when it becomes active.'],'loop_current_video' => ['Loop Current Video','Replay the same video after it ends. This overrides auto-scroll.'],'auto_scroll_next' => ['Auto Scroll to Next Short','When a video finishes, smoothly and quickly move to the next short.'],'show_progress_bar' => ['Show Progress Bar','Display a thin playback progress line.'],'show_like_button' => ['Show Like Button','Show like action. Login rule can still protect the action.'],'show_comment_button' => ['Show Comment Button','Show comment action. Login rule can still protect the action.'],'show_share_button' => ['Show Share Button','Show share action.'],'allow_save_favorite' => ['Allow Save / Favorite','Allow signed-in users to save shorts.'],'require_login_for_interactions' => ['Require Login for Interactions','Watching stays public; like/comment/save require login.'],'show_banner_ads' => ['Show Banner Ads','Allow safe banner ads around the shorts experience.'],'show_native_ads' => ['Show Native Ads','Allow native ads after configured intervals.']] as $key => $copy)
                        <label class="sv-setting"><div class="sv-toggle-row"><div><strong>{{ $copy[0] }}</strong><p>{{ $copy[1] }}</p></div><input class="sv-toggle" type="checkbox" wire:model.defer="feedSettings.{{ $key }}"></div></label>
                    @endforeach
                    <div class="sv-setting"><label class="sv-label">Default Feed</label><span class="sv-select-wrap"><select class="sv-select" wire:model.defer="feedSettings.default_feed"><option value="latest">Latest</option><option value="all">All</option><option value="featured">Featured</option><option value="category">Category</option></select></span></div>
                    <div class="sv-setting"><label class="sv-label">Smooth Scroll Speed</label><span class="sv-select-wrap"><select class="sv-select" wire:model.defer="feedSettings.smooth_scroll_speed">@foreach($this->feedSpeedOptions() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></span></div>
                    <div class="sv-setting"><label class="sv-label">Native Ads After Every X Shorts</label><input class="sv-input" type="number" min="1" max="20" wire:model.defer="feedSettings.native_ads_after"></div>
                    <div class="sv-setting"><label class="sv-label">Target Route</label><input class="sv-input" type="text" wire:model.defer="feedSettings.target_route"></div>
                    <div class="sv-setting" style="grid-column:1/-1"><label class="sv-label">Fallback Message</label><textarea class="sv-textarea" rows="2" wire:model.defer="feedSettings.fallback_message"></textarea></div>
                </div>
            </div></section>
        @endif

        @if($activeTab === 'preview')
            <section class="sv-preview-grid">
                <div class="sv-card"><div class="sv-card-pad"><div class="sv-card-title">API-style Preview Payload</div><div class="sv-card-note">Left-side payload view for frontend/API alignment.</div><pre class="sv-code">{{ json_encode($previewPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div></div>
                <div class="sv-card"><div class="sv-card-pad"><div class="sv-card-title">Phone Feed Preview</div><div class="sv-card-note">Right-side visual approximation. Flutter handles the real player behavior.</div><div class="sv-phone" style="margin-top:12px;"><div class="sv-phone-screen"><div class="sv-phone-short">@if(!empty($firstShort['thumbnail_url']))<img src="{{ $firstShort['thumbnail_url'] }}" alt="">@endif<div class="sv-phone-caption"><div class="sv-badge featured">{{ $firstShort['category_label'] ?? 'Shorts' }}</div><h3 style="margin:10px 0 5px;font-size:18px;font-weight:950;">{{ $firstShort['title'] ?? 'Short Video Preview' }}</h3><p style="font-size:12px;color:#cbd5e1;line-height:1.45;">{{ $feedSettings['auto_scroll_next'] ? 'Auto-scroll next is ON.' : 'Auto-scroll next is OFF.' }} {{ $feedSettings['loop_current_video'] ? 'Loop current video is ON.' : 'Loop current video is OFF.' }}</p></div></div></div></div></div></div>
            </section>
        @endif
        @endif
    </div>
</x-filament-panels::page>
