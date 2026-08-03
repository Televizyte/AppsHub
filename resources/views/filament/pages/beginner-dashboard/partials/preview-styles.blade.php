<style>
    .dxm-studio{display:flex;flex-direction:column;gap:18px}
    .dxm-hero{border:1px solid rgba(148,163,184,.18);background:linear-gradient(135deg,rgba(15,23,42,.95),rgba(2,6,23,.98));border-radius:24px;padding:22px}
    .dxm-title{margin:0;color:#fff;font-size:28px;font-weight:900}
    .dxm-sub{margin-top:8px;color:rgba(226,232,240,.7)}
    .dxm-tabs{display:flex;gap:10px;overflow:auto;padding:10px;border:1px solid rgba(148,163,184,.16);background:rgba(15,23,42,.72);border-radius:18px}
    .dxm-tab{color:rgba(226,232,240,.78);text-decoration:none;padding:10px 16px;border-radius:999px;font-weight:800;white-space:nowrap}
    .dxm-tab.active{color:#fff;background:rgba(34,211,238,.18);border:1px solid rgba(34,211,238,.38)}
    .dxm-toolbar{display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-top:-2px}
    .dxm-btn{display:inline-flex;text-decoration:none;align-items:center;justify-content:center;padding:10px 14px;border-radius:13px;border:1px solid rgba(148,163,184,.18);color:#fff;background:rgba(255,255,255,.05);font-weight:800;font-size:13px;gap:6px;cursor:pointer}
    .dxm-btn.primary{background:rgba(34,211,238,.18);border-color:rgba(34,211,238,.38)}
    .dxm-sections{display:flex;flex-direction:column;gap:16px}
    .dxm-section{border:1px solid rgba(148,163,184,.16);background:rgba(15,23,42,.72);border-radius:22px;overflow:hidden}
    .dxm-section-head{display:flex;justify-content:space-between;gap:14px;padding:16px;border-bottom:1px solid rgba(148,163,184,.14)}
    .dxm-section-title{margin:0;color:#fff;font-size:17px;font-weight:900}
    .dxm-meta{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
    .dxm-pill{font-size:12px;font-weight:700;color:rgba(226,232,240,.72);border:1px solid rgba(148,163,184,.14);background:rgba(255,255,255,.06);padding:5px 9px;border-radius:999px}
    .dxm-items{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;padding:16px}
    .dxm-card{border:1px solid rgba(148,163,184,.14);background:rgba(255,255,255,.04);border-radius:18px;overflow:hidden;display:flex;flex-direction:column;min-height:185px}
    .dxm-card-img{height:92px;background:rgba(255,255,255,.05)}
    .dxm-card-img img{width:100%;height:100%;object-fit:cover}
    .dxm-card-body{padding:12px;display:flex;flex-direction:column;gap:8px;flex:1}
    .dxm-card-title{color:#fff;font-weight:900;font-size:14px;line-height:1.35;white-space:pre-line}
    .dxm-card-sub{color:rgba(226,232,240,.58);font-size:12px;line-height:1.45;white-space:pre-line}
    .dxm-empty{border:1px dashed rgba(148,163,184,.24);background:rgba(255,255,255,.03);border-radius:20px;padding:22px;color:rgba(226,232,240,.68);text-align:center}
    .dxm-preview-rail{position:fixed;right:0;top:42%;z-index:80;display:flex;align-items:center}
    .dxm-preview-handle{writing-mode:vertical-rl;transform:rotate(180deg);border:1px solid rgba(34,211,238,.45);border-right:0;background:linear-gradient(180deg,rgba(34,211,238,.95),rgba(168,85,247,.95));color:#fff;border-radius:0 14px 14px 0;padding:14px 9px;font-size:13px;font-weight:900;letter-spacing:.5px;box-shadow:0 14px 34px rgba(0,0,0,.32);cursor:pointer}
    .dxm-preview-backdrop{position:fixed;inset:0;background:rgba(2,6,23,.62);backdrop-filter:blur(5px);z-index:90;opacity:0;pointer-events:none;transition:.22s ease}
    .dxm-preview-backdrop.is-open{opacity:1;pointer-events:auto}
    .dxm-preview-drawer{position:fixed;top:0;right:0;height:100vh;width:min(440px,94vw);z-index:100;background:linear-gradient(180deg,#020617,#050816);border-left:1px solid rgba(148,163,184,.2);box-shadow:-30px 0 70px rgba(0,0,0,.45);transform:translateX(102%);transition:transform .24s ease;display:flex;flex-direction:column}
    .dxm-preview-drawer.is-open{transform:translateX(0)}
    .dxm-preview-drawer-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-bottom:1px solid rgba(148,163,184,.14);background:rgba(15,23,42,.82)}
    .dxm-preview-drawer-title{color:#fff;font-weight:900;font-size:16px}
    .dxm-preview-drawer-sub{color:rgba(226,232,240,.58);font-size:11px;margin-top:2px}
    .dxm-preview-drawer-actions{display:flex;gap:8px;align-items:center}
    .dxm-preview-icon-btn{width:38px;height:38px;border-radius:12px;border:1px solid rgba(148,163,184,.18);background:rgba(255,255,255,.06);color:#fff;font-weight:900;cursor:pointer}
    .dxm-preview-drawer-body{flex:1;overflow:auto;padding:18px;display:flex;justify-content:center}
    .dxm-phone-shell{width:360px;height:760px;max-height:calc(100vh - 120px);border-radius:38px;background:#020617;border:1px solid rgba(148,163,184,.28);padding:12px;box-shadow:0 30px 70px rgba(0,0,0,.42)}
    .dxm-phone-screen{height:100%;border-radius:30px;overflow:hidden;background:#08091b;border:1px solid rgba(148,163,184,.15);display:flex;flex-direction:column}
    .dxm-phone-topbar{height:54px;display:flex;align-items:center;justify-content:space-between;padding:0 14px;background:linear-gradient(90deg,#3b1b8f,#c00491);color:#fff;flex:0 0 auto}
    .dxm-phone-topbar-title{font-size:16px;font-weight:900}
    .dxm-phone-topbar-icons{display:flex;gap:8px;font-size:18px}
    .dxm-phone-content{flex:1;overflow:auto;padding:14px 0 18px}
    .dxm-phone-bottom{height:58px;display:grid;grid-template-columns:repeat(5,1fr);gap:2px;background:#050816;border-top:1px solid rgba(148,163,184,.12);flex:0 0 auto}
    .dxm-phone-nav-item{border:0;background:transparent;display:flex;flex-direction:column;align-items:center;justify-content:center;color:rgba(226,232,240,.55);font-size:9px;font-weight:800;gap:3px;cursor:pointer;text-decoration:none}
    .dxm-phone-nav-item.is-active{color:#ff4fc3}
    .dxm-phone-nav-dot{font-size:15px;line-height:1}
    .dxm-phone-tab-panel{display:none}
    .dxm-phone-tab-panel.is-active{display:block}
    .dxm-mobile-section{margin-bottom:18px}
    .dxm-mobile-section-head{padding:0 14px 12px}
    .dxm-mobile-section-title{color:#fff;font-weight:900;font-size:16px;margin-bottom:5px}
    .dxm-mobile-section-sub{color:rgba(226,232,240,.68);font-size:12.5px;line-height:1.35;font-weight:600}
    .dxm-mobile-items{display:grid;gap:12px;padding:0 14px}
    .dxm-mobile-items--horizontal_scroll,.dxm-mobile-items--carousel{display:flex;overflow-x:auto;padding-bottom:4px}
    .dxm-mobile-items--horizontal_scroll .dxm-mobile-card,.dxm-mobile-items--carousel .dxm-mobile-card{min-width:310px;max-width:310px}
    .dxm-mobile-items--grid.dxm-mobile-items--cols-1{grid-template-columns:1fr}
    .dxm-mobile-items--grid.dxm-mobile-items--cols-2,.dxm-mobile-items--icon_grid.dxm-mobile-items--cols-2,.dxm-mobile-items--tool_grid.dxm-mobile-items--cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
    .dxm-mobile-items--grid.dxm-mobile-items--cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
    .dxm-mobile-items--grid.dxm-mobile-items--cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
    .dxm-mobile-items--vertical_list,.dxm-mobile-items--video_feed,.dxm-mobile-items--compact{grid-template-columns:1fr}
    .dxm-mobile-card{border:1px solid rgba(255,255,255,.08);border-radius:22px;overflow:hidden;background:rgba(255,255,255,.055);height:170px;text-align:left;cursor:pointer;width:100%;padding:0;position:relative;box-shadow:0 10px 20px rgba(0,0,0,.18)}
    .dxm-mobile-card-img{position:absolute;inset:0;background:linear-gradient(135deg,#0b1f4d,#1d5cff,#e2388a)}
    .dxm-mobile-card-img img{width:100%;height:100%;object-fit:cover}
    .dxm-mobile-card-overlay{position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.08),rgba(0,0,0,.20),rgba(0,0,0,.76))}
    .dxm-mobile-card-watermark{position:absolute;right:-8px;bottom:-10px;color:rgba(255,255,255,.12);font-size:82px;font-weight:900}
    .dxm-mobile-card-body{position:absolute;inset:0;padding:14px;display:flex;flex-direction:column}
    .dxm-mobile-card-top{display:flex;align-items:center;justify-content:space-between}
    .dxm-mobile-badge{border-radius:999px;padding:6px 10px;font-size:10px;font-weight:900;color:#fff;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.18)}
    .dxm-mobile-card-icon{color:#fff;font-size:22px;font-weight:900}
    .dxm-mobile-card-spacer{flex:1}
    .dxm-mobile-card-title{color:#fff;font-size:18px;font-weight:900;line-height:1.08;white-space:pre-line}
    .dxm-mobile-card-sub{color:rgba(255,255,255,.88);font-size:12.5px;line-height:1.25;margin-top:6px;font-weight:600;white-space:pre-line}
    .dxm-mobile-daily-frame{margin:0 14px;border-radius:28px;overflow:hidden;border:1px solid rgba(34,211,238,.35);background:#07132c}
    .dxm-mobile-daily-card{min-height:360px;border:0;border-radius:0;display:flex;align-items:center;justify-content:center;text-align:center;position:relative;overflow:hidden;width:100%;cursor:pointer;background-size:cover;background-position:center;padding:var(--dxm-card-padding,34px)}
    .dxm-mobile-daily-inner{width:var(--dxm-content-width,86%);margin:0 auto;white-space:pre-line}
    .dxm-mobile-daily-ref{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;font-size:11px;color:rgba(255,255,255,.9);font-weight:800;margin-top:12px;white-space:pre-line}
    .dxm-mobile-daily-actions{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;padding:14px;background:#07132c}
    .dxm-mobile-daily-action{border:1px solid rgba(34,211,238,.35);border-radius:18px;background:#111a35;color:#fff;font-weight:900;font-size:11px;padding:12px 8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer}
    .dxm-mobile-daily-action-light{background:#fff;color:#08091b;border-color:#fff}
    .dxm-ad-card{margin:0 14px;height:90px;border-radius:18px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.055);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.68);font-weight:800;font-size:12px}
    .dxm-preview-main.is-hidden{display:none}
    .dxm-preview-detail{display:none}
    .dxm-preview-detail.is-active{display:block;padding:0 14px}
    .dxm-preview-back-row{display:flex;align-items:center;gap:8px;margin-bottom:12px}
    .dxm-preview-back-btn{border:1px solid rgba(148,163,184,.2);background:rgba(255,255,255,.06);color:#fff;border-radius:999px;padding:8px 11px;font-size:12px;font-weight:900;cursor:pointer}
    .dxm-preview-detail-card{border:1px solid rgba(148,163,184,.16);border-radius:22px;background:rgba(255,255,255,.045);overflow:hidden}
    .dxm-preview-detail-image{height:180px;background:linear-gradient(135deg,#0b1f4d,#1d5cff,#e2388a);display:flex;align-items:center;justify-content:center;color:rgba(226,232,240,.6);font-size:12px}
    .dxm-preview-detail-image img{width:100%;height:100%;object-fit:cover}
    .dxm-preview-detail-body{padding:15px}
    .dxm-preview-detail-title{font-size:18px;font-weight:900;color:#fff;line-height:1.25;white-space:pre-line}
    .dxm-preview-detail-sub{font-size:12px;color:rgba(226,232,240,.62);line-height:1.45;margin-top:8px;white-space:pre-line}
    .dxm-preview-player{height:205px;background:#020617;border-bottom:1px solid rgba(148,163,184,.14);display:flex;align-items:center;justify-content:center;position:relative}
    .dxm-preview-play{width:64px;height:64px;border-radius:999px;background:linear-gradient(135deg,#22d3ee,#ec4899);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:28px}
    .dxm-preview-player-label{position:absolute;left:12px;bottom:12px;right:12px;color:rgba(226,232,240,.75);font-size:11px;line-height:1.35;word-break:break-all}
    .dxm-preview-action-pill{display:inline-flex;margin-top:12px;border-radius:999px;padding:7px 10px;font-size:11px;font-weight:900;color:#a5f3fc;background:rgba(34,211,238,.12);border:1px solid rgba(34,211,238,.24)}
</style>
