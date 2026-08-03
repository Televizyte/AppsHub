<x-filament::page>
    <style>
        .dxm-settings-shell{display:grid;gap:18px;position:relative}.dxm-settings-hero{border:1px solid rgba(34,211,238,.18);border-radius:28px;padding:22px;background:radial-gradient(circle at top left,rgba(34,211,238,.18),transparent 36%),radial-gradient(circle at top right,rgba(168,85,247,.14),transparent 34%),linear-gradient(135deg,rgba(2,6,23,.96),rgba(15,23,42,.9));overflow:hidden}.dxm-settings-hero-grid{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center}.dxm-settings-kicker{display:inline-flex;padding:7px 12px;border-radius:999px;border:1px solid rgba(34,211,238,.34);background:rgba(34,211,238,.10);color:rgba(207,250,254,.95);font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.dxm-settings-title{margin:12px 0 0;color:#fff;font-size:clamp(28px,4vw,42px);line-height:1.04;font-weight:950;letter-spacing:-.055em}.dxm-settings-sub{margin:10px 0 0;max-width:900px;color:rgba(255,255,255,.68);font-size:14px;line-height:1.6}.dxm-settings-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}.dxm-settings-btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:9px 13px;border-radius:13px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.055);color:rgba(255,255,255,.82);font-size:12px;font-weight:900;transition:.16s ease;text-decoration:none}.dxm-settings-btn:hover{border-color:rgba(34,211,238,.42);background:rgba(34,211,238,.10);color:#fff}.dxm-settings-btn.primary{border-color:rgba(34,211,238,.38);background:rgba(34,211,238,.14);color:#e0fbff}.dxm-settings-btn.danger{border-color:rgba(248,113,113,.28);background:rgba(248,113,113,.08);color:#fecaca}.dxm-settings-btn.small{min-height:32px;padding:7px 10px;font-size:11px}.dxm-settings-stats{display:grid;grid-template-columns:repeat(2,minmax(125px,1fr));gap:10px;min-width:280px}.dxm-settings-stat{padding:15px;border:1px solid rgba(255,255,255,.10);border-radius:20px;background:rgba(2,6,23,.48)}.dxm-settings-stat strong{display:block;color:#fff;font-size:23px;line-height:1}.dxm-settings-stat span{display:block;margin-top:6px;color:rgba(255,255,255,.58);font-size:11px;font-weight:850}.dxm-settings-note{border:1px dashed rgba(255,255,255,.15);border-radius:20px;padding:15px;color:rgba(255,255,255,.64);background:rgba(2,6,23,.24);line-height:1.6}.dxm-settings-tabs{display:flex;gap:8px;flex-wrap:wrap;position:sticky;top:74px;z-index:5;padding:8px 0;background:rgba(3,7,18,.72);backdrop-filter:blur(12px)}.dxm-settings-tab{border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.045);color:rgba(255,255,255,.76);border-radius:999px;padding:9px 13px;font-size:12px;font-weight:900;cursor:pointer}.dxm-settings-tab.active,.dxm-settings-tab:hover{border-color:rgba(34,211,238,.45);background:rgba(34,211,238,.12);color:#fff}.dxm-settings-panel{display:none}.dxm-settings-panel.active{display:block}.dxm-settings-box{border:1px solid rgba(255,255,255,.09);border-radius:24px;background:rgba(255,255,255,.035);padding:16px;margin-bottom:14px}.dxm-settings-box h2{margin:0;color:#fff;font-size:20px;font-weight:950;letter-spacing:-.03em}.dxm-settings-box p{margin:7px 0 0;color:rgba(255,255,255,.62);font-size:13px;line-height:1.55}.dxm-settings-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:12px}.dxm-field{grid-column:span 6;border:1px solid rgba(255,255,255,.09);border-radius:18px;background:rgba(2,6,23,.32);padding:13px}.dxm-field.third{grid-column:span 4}.dxm-field.quarter{grid-column:span 3}.dxm-field.full{grid-column:1/-1}.dxm-field label{display:block;color:rgba(255,255,255,.86);font-size:12px;font-weight:900;margin-bottom:8px}.dxm-field input,.dxm-field textarea,.dxm-field select{width:100%;border:1px solid rgba(255,255,255,.10);border-radius:14px;background:rgba(15,23,42,.88);color:#fff;padding:11px 12px;font-size:13px;font-weight:700;outline:none}.dxm-field input[type=color]{height:44px;padding:5px}.dxm-field textarea{min-height:130px;resize:vertical;line-height:1.5}.dxm-field small{display:block;margin-top:7px;color:rgba(255,255,255,.48);font-size:11px;font-weight:700;line-height:1.4}.dxm-dynamic-row{border:1px solid rgba(34,211,238,.14);border-radius:24px;background:linear-gradient(135deg,rgba(2,6,23,.58),rgba(15,23,42,.32));padding:14px;margin-bottom:12px}.dxm-row-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px}.dxm-row-title{display:flex;align-items:center;gap:10px;color:#fff;font-weight:950}.dxm-row-badge{font-size:10px;font-weight:950;color:#67e8f9;border:1px solid rgba(34,211,238,.28);padding:4px 8px;border-radius:999px;background:rgba(34,211,238,.08)}.dxm-toggle{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.72);font-size:12px;font-weight:900}.dxm-toggle input{width:18px;height:18px}
        .dxm-graphics-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.dxm-graphic-card{border:1px solid rgba(34,211,238,.16);border-radius:24px;background:linear-gradient(135deg,rgba(2,6,23,.7),rgba(15,23,42,.36));overflow:hidden}.dxm-graphic-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;padding:14px 14px 0}.dxm-graphic-title{color:#fff;font-size:16px;font-weight:950}.dxm-graphic-use{margin-top:4px;color:rgba(255,255,255,.55);font-size:12px;line-height:1.4}.dxm-graphic-badge{border:1px solid rgba(34,211,238,.26);background:rgba(34,211,238,.1);color:#a5f3fc;font-size:10px;font-weight:950;border-radius:999px;padding:5px 8px;text-transform:uppercase}.dxm-graphic-preview{margin:14px;border:1px solid rgba(255,255,255,.1);border-radius:20px;background:radial-gradient(circle at center,rgba(34,211,238,.14),transparent 55%),rgba(2,6,23,.62);height:170px;display:grid;place-items:center;overflow:hidden}.dxm-graphic-preview.logo,.dxm-graphic-preview.app_icon{height:150px}.dxm-graphic-preview.banner{height:130px}.dxm-graphic-preview.splash{height:230px;max-width:250px;margin-left:auto;margin-right:auto}.dxm-graphic-preview img{max-width:100%;max-height:100%;object-fit:contain;display:block}.dxm-graphic-preview.banner img{width:100%;height:100%;object-fit:cover}.dxm-empty-preview{width:100%;height:100%;display:grid;place-items:center;text-align:center;color:rgba(255,255,255,.45);font-size:12px;font-weight:800;padding:20px}.dxm-graphic-controls{padding:0 14px 14px;display:grid;gap:10px}.dxm-upload-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:center}.dxm-upload-row input[type=file]{width:100%;border:1px dashed rgba(255,255,255,.16);border-radius:14px;background:rgba(15,23,42,.72);color:rgba(255,255,255,.78);padding:9px;font-size:12px}.dxm-technical-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.dxm-tech-field{border:1px solid rgba(255,255,255,.08);border-radius:14px;background:rgba(2,6,23,.36);padding:10px}.dxm-tech-field label{display:flex;justify-content:space-between;gap:8px;color:rgba(255,255,255,.58);font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.04em}.dxm-tech-field input{margin-top:7px;width:100%;border:0;background:transparent;color:rgba(255,255,255,.8);font-size:11px;font-weight:750;outline:none}.dxm-preview-layout{display:grid;grid-template-columns:minmax(300px,.9fr) minmax(0,1.1fr);gap:14px}.dxm-phone-preview{border:1px solid rgba(34,211,238,.22);border-radius:34px;background:#020617;padding:12px;max-width:360px;margin:auto;box-shadow:0 25px 60px rgba(0,0,0,.35)}.dxm-phone-screen{border:1px solid rgba(255,255,255,.12);border-radius:26px;min-height:640px;overflow:hidden;background:linear-gradient(180deg,rgba(15,23,42,.98),rgba(2,6,23,.98))}.dxm-phone-banner{height:160px;background:rgba(15,23,42,.9);display:grid;place-items:center;overflow:hidden}.dxm-phone-banner img{width:100%;height:100%;object-fit:cover}.dxm-phone-content{padding:16px}.dxm-app-row{display:flex;gap:12px;align-items:center}.dxm-app-icon{width:68px;height:68px;border-radius:20px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);display:grid;place-items:center;overflow:hidden;color:#67e8f9;font-size:24px;font-weight:950}.dxm-app-icon img{width:100%;height:100%;object-fit:contain}.dxm-app-name{color:#fff;font-size:18px;font-weight:950}.dxm-app-tag{margin-top:3px;color:rgba(255,255,255,.58);font-size:12px}.dxm-phone-card{margin-top:14px;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.045);border-radius:18px;padding:13px;color:rgba(255,255,255,.76);font-size:12px;line-height:1.5}.dxm-splash-mini{border:1px solid rgba(255,255,255,.1);border-radius:22px;background:#020617;min-height:260px;display:grid;place-items:center;text-align:center;overflow:hidden}.dxm-splash-mini img{width:100%;height:100%;object-fit:cover}.dxm-push-preview{border:1px solid rgba(255,255,255,.12);border-radius:18px;background:rgba(15,23,42,.9);padding:12px;display:flex;gap:11px;align-items:flex-start}.dxm-color-row{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}.dxm-color-chip{border:1px solid rgba(255,255,255,.1);border-radius:14px;overflow:hidden;background:rgba(2,6,23,.5)}.dxm-color-swatch{height:52px}.dxm-color-chip span{display:block;padding:8px;color:rgba(255,255,255,.74);font-size:11px;font-weight:850}.dxm-preview-list{display:grid;gap:10px}.dxm-preview-item{border:1px solid rgba(255,255,255,.10);border-radius:16px;background:rgba(2,6,23,.32);padding:12px;color:rgba(255,255,255,.75);font-size:12px;word-break:break-word}.dxm-preview-item strong{display:block;color:#fff;font-size:13px;margin-bottom:4px}
        @media(max-width:1100px){.dxm-graphics-grid,.dxm-preview-layout{grid-template-columns:1fr}.dxm-settings-tabs{top:0}}@media(max-width:980px){.dxm-settings-hero-grid{grid-template-columns:1fr}.dxm-settings-stats{min-width:0}.dxm-field,.dxm-field.third,.dxm-field.quarter{grid-column:1/-1}.dxm-technical-grid{grid-template-columns:1fr}}@media(max-width:640px){.dxm-settings-stats{grid-template-columns:1fr}.dxm-row-head{align-items:flex-start;flex-direction:column}.dxm-upload-row{grid-template-columns:1fr}.dxm-color-row{grid-template-columns:1fr 1fr}}
    

        /* AppsHub compact graphics cleanup: keep graphics visual, but prevent giant vertical cards. */
        .dxm-settings-shell{gap:14px;padding-top:10px;max-width:1180px;margin:0 auto}.dxm-settings-hero{padding:18px;border-radius:22px}.dxm-settings-title{font-size:clamp(24px,3vw,34px)}.dxm-settings-sub{font-size:13px}.dxm-settings-tabs{position:relative!important;top:auto!important;z-index:2;padding:8px 0 10px;background:transparent!important;backdrop-filter:none!important}.dxm-settings-box{padding:14px;border-radius:20px;margin-bottom:12px}.dxm-settings-box h2{font-size:18px}.dxm-settings-box p{font-size:12px}
        .dxm-graphics-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;align-items:start}.dxm-graphic-card{border-radius:20px}.dxm-graphic-head{padding:12px 12px 0}.dxm-graphic-title{font-size:15px}.dxm-graphic-use{font-size:11px;line-height:1.35}.dxm-graphic-badge{font-size:9px;padding:4px 7px}.dxm-graphic-preview{height:116px!important;margin:12px;border-radius:16px}.dxm-graphic-preview.logo,.dxm-graphic-preview.app_icon{height:104px!important;max-width:210px;margin-left:auto;margin-right:auto}.dxm-graphic-preview.banner{height:112px!important}.dxm-graphic-preview.splash{height:148px!important;max-width:210px}.dxm-graphic-preview img{max-width:100%;max-height:100%;object-fit:contain!important}.dxm-graphic-preview.banner img{width:100%;height:100%;object-fit:contain!important;background:rgba(2,6,23,.25)}.dxm-graphic-controls{padding:0 12px 12px;gap:8px}.dxm-upload-row{grid-template-columns:minmax(0,1fr) auto;gap:8px}.dxm-upload-row input[type=file]{padding:7px;font-size:11px}.dxm-technical-grid{grid-template-columns:1fr 1fr;gap:8px}.dxm-tech-field{border-radius:12px;padding:9px;min-width:0}.dxm-tech-field input{font-size:10.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.dxm-tech-field label{font-size:9px}.dxm-settings-btn.small{min-height:29px;padding:6px 9px;font-size:10px}
        .dxm-preview-layout{grid-template-columns:minmax(270px,.82fr) minmax(0,1.18fr);gap:12px;align-items:start}.dxm-phone-preview{max-width:300px;border-radius:28px;padding:10px}.dxm-phone-screen{min-height:420px;border-radius:22px}.dxm-phone-banner{height:112px}.dxm-phone-banner img{object-fit:contain;background:rgba(2,6,23,.35)}.dxm-phone-content{padding:12px}.dxm-app-icon{width:52px;height:52px;border-radius:15px}.dxm-app-name{font-size:16px}.dxm-app-tag{font-size:11px}.dxm-phone-card{margin-top:10px;border-radius:14px;padding:10px;font-size:11px}.dxm-preview-list{gap:9px}.dxm-preview-item{padding:10px;border-radius:14px;font-size:11px}.dxm-push-preview{padding:10px;border-radius:15px}.dxm-splash-mini{min-height:0;height:170px;border-radius:16px}.dxm-splash-mini img{width:100%;height:100%;object-fit:contain;background:#020617}.dxm-color-row{grid-template-columns:repeat(4,minmax(0,1fr));gap:7px}.dxm-color-swatch{height:38px}.dxm-color-chip span{font-size:10px;padding:7px}.dxm-preview-item:has(.dxm-splash-mini){max-height:238px;overflow:hidden}
        @media(max-width:1180px){.dxm-graphics-grid,.dxm-preview-layout{grid-template-columns:1fr}.dxm-phone-preview{max-width:320px}.dxm-settings-shell{max-width:100%}}@media(max-width:760px){.dxm-technical-grid,.dxm-upload-row{grid-template-columns:1fr}.dxm-graphic-preview.logo,.dxm-graphic-preview.app_icon{max-width:180px}.dxm-color-row{grid-template-columns:1fr 1fr}.dxm-settings-shell{padding-top:4px}}

    
        /* AppsHub tight graphics override START */
        .dxm-settings-shell {
            padding-top: 18px !important;
        }

        .dxm-settings-tabs {
            position: relative !important;
            top: auto !important;
            z-index: 1 !important;
            background: transparent !important;
            backdrop-filter: none !important;
            padding: 6px 0 10px !important;
        }

        .dxm-graphics-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 12px !important;
            align-items: start !important;
        }

        .dxm-graphic-card {
            border-radius: 20px !important;
            min-width: 0 !important;
        }

        .dxm-graphic-head {
            padding: 12px 12px 0 !important;
        }

        .dxm-graphic-title {
            font-size: 15px !important;
        }

        .dxm-graphic-use {
            font-size: 11px !important;
            line-height: 1.35 !important;
        }

        .dxm-graphic-preview {
            height: 96px !important;
            margin: 10px 12px !important;
            border-radius: 16px !important;
            max-width: none !important;
        }

        .dxm-graphic-preview.logo,
        .dxm-graphic-preview.app_icon {
            height: 100px !important;
            max-width: 190px !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        .dxm-graphic-preview.banner {
            height: 90px !important;
        }

        .dxm-graphic-preview.splash {
            height: 125px !important;
            max-width: 210px !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        .dxm-graphic-preview img,
        .dxm-graphic-preview.banner img,
        .dxm-graphic-preview.splash img {
            width: 100% !important;
            height: 100% !important;
            object-fit: contain !important;
        }

        .dxm-graphic-controls {
            padding: 0 12px 12px !important;
            gap: 8px !important;
        }

        .dxm-upload-row {
            grid-template-columns: minmax(0, 1fr) auto !important;
            gap: 7px !important;
        }

        .dxm-upload-row input[type=file] {
            padding: 7px !important;
            font-size: 11px !important;
        }

        .dxm-technical-grid {
            grid-template-columns: 1fr 1fr !important;
            gap: 7px !important;
        }

        .dxm-tech-field {
            padding: 9px !important;
            min-height: 76px !important;
        }

        .dxm-tech-field input {
            font-size: 10.5px !important;
            line-height: 1.25 !important;
        }

        .dxm-preview-layout {
            display: grid !important;
            grid-template-columns: minmax(260px, 0.85fr) minmax(0, 1.15fr) !important;
            gap: 12px !important;
            align-items: start !important;
        }

        .dxm-phone-preview {
            max-width: 300px !important;
            border-radius: 28px !important;
            padding: 10px !important;
        }

        .dxm-phone-screen {
            min-height: 440px !important;
            border-radius: 22px !important;
        }

        .dxm-phone-banner {
            height: 92px !important;
        }

        .dxm-phone-banner img {
            object-fit: contain !important;
            background: #020617 !important;
        }

        .dxm-phone-content {
            padding: 12px !important;
        }

        .dxm-app-icon {
            width: 54px !important;
            height: 54px !important;
            border-radius: 16px !important;
        }

        .dxm-app-name {
            font-size: 16px !important;
        }

        .dxm-phone-card {
            margin-top: 10px !important;
            padding: 10px !important;
            font-size: 11px !important;
        }

        .dxm-splash-mini {
            min-height: 145px !important;
            max-height: 170px !important;
            border-radius: 18px !important;
        }

        .dxm-splash-mini img {
            width: 100% !important;
            height: 100% !important;
            object-fit: contain !important;
            background: #020617 !important;
        }

        .dxm-push-preview {
            padding: 10px !important;
            border-radius: 16px !important;
        }

        .dxm-color-swatch {
            height: 38px !important;
        }

        .dxm-preview-item {
            padding: 10px !important;
            font-size: 11px !important;
        }

        @media (max-width: 760px) {
            .dxm-graphics-grid,
            .dxm-preview-layout {
                grid-template-columns: 1fr !important;
            }

            .dxm-technical-grid {
                grid-template-columns: 1fr !important;
            }
        }
        /* AppsHub tight graphics override END */

    
        /* AppsHub image library picker for app graphics START */
        .dxm-library-row {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto auto !important;
            gap: 7px !important;
            align-items: center !important;
            margin-top: 7px !important;
        }

        .dxm-library-row select {
            min-width: 0 !important;
            width: 100% !important;
            min-height: 34px !important;
            border-radius: 12px !important;
            border: 1px solid rgba(148, 163, 184, .20) !important;
            background: rgba(15, 23, 42, .78) !important;
            color: rgba(255, 255, 255, .88) !important;
            padding: 7px 10px !important;
            font-size: 11px !important;
            font-weight: 800 !important;
        }

        .dxm-library-hint {
            margin-top: 5px !important;
            color: rgba(203, 213, 225, .62) !important;
            font-size: 10.5px !important;
            line-height: 1.35 !important;
        }

        @media (max-width: 920px) {
            .dxm-library-row {
                grid-template-columns: 1fr !important;
            }
        }
        /* AppsHub image library picker for app graphics END */

    
        /* AppsHub embedded image picker modal for app graphics START */
        .dxm-picker-card{border:1px solid rgba(255,255,255,.10);border-radius:16px;background:rgba(2,6,23,.34);padding:10px;display:grid;gap:10px}.dxm-picker-top{display:flex;align-items:center;justify-content:space-between;gap:10px}.dxm-picker-copy strong{display:block;color:#fff;font-size:12px}.dxm-picker-copy span{display:block;color:rgba(255,255,255,.52);font-size:10.5px;line-height:1.35;margin-top:2px}.dxm-picker-actions{display:flex;gap:7px;flex-wrap:wrap;justify-content:flex-end}.dxm-picker-selected{display:grid;grid-template-columns:76px minmax(0,1fr);gap:10px;align-items:center;border:1px solid rgba(255,255,255,.08);border-radius:14px;background:rgba(15,23,42,.48);padding:9px}.dxm-picker-thumb{width:76px;height:58px;border-radius:12px;background:rgba(2,6,23,.72);display:grid;place-items:center;overflow:hidden;color:rgba(255,255,255,.45);font-size:10px;font-weight:900;text-align:center}.dxm-picker-thumb img{width:100%;height:100%;object-fit:contain}.dxm-picker-meta strong{display:block;color:#fff;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.dxm-picker-meta span{display:block;margin-top:3px;color:rgba(255,255,255,.52);font-size:10.5px;line-height:1.35;word-break:break-word}.dxm-picker-modal{position:fixed;inset:0;z-index:9999;display:none}.dxm-picker-modal.is-open{display:block}.dxm-picker-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.70);backdrop-filter:blur(5px)}.dxm-picker-dialog{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:min(980px,94vw);max-height:88vh;border:1px solid rgba(34,211,238,.22);border-radius:24px;background:linear-gradient(135deg,rgba(15,23,42,.98),rgba(2,6,23,.98));box-shadow:0 30px 90px rgba(0,0,0,.55);overflow:hidden;display:grid;grid-template-rows:auto minmax(0,1fr) auto}.dxm-picker-modal-head,.dxm-picker-modal-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.08)}.dxm-picker-modal-foot{border-top:1px solid rgba(255,255,255,.08);border-bottom:0;justify-content:flex-end}.dxm-picker-modal-head strong{display:block;color:#fff;font-size:16px}.dxm-picker-modal-head span{display:block;color:rgba(255,255,255,.55);font-size:12px;margin-top:3px}.dxm-picker-close{width:34px;height:34px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#fff;font-size:22px;cursor:pointer}.dxm-picker-body{display:grid;grid-template-columns:250px minmax(0,1fr);gap:14px;padding:14px;overflow:auto}.dxm-picker-side{display:grid;gap:10px;align-content:start}.dxm-picker-large{border:1px solid rgba(255,255,255,.10);border-radius:18px;background:rgba(2,6,23,.50);padding:10px}.dxm-picker-large-preview{height:170px;border-radius:14px;background:rgba(15,23,42,.72);display:grid;place-items:center;overflow:hidden;color:rgba(255,255,255,.45);font-size:12px;font-weight:900;text-align:center}.dxm-picker-large-preview img{width:100%;height:100%;object-fit:contain}.dxm-picker-large strong{display:block;color:#fff;font-size:12px;margin-bottom:8px}.dxm-picker-large small{display:block;color:rgba(255,255,255,.50);font-size:11px;line-height:1.35;margin-top:8px;word-break:break-word}.dxm-picker-search{display:grid;gap:8px;margin-bottom:10px}.dxm-picker-search input{width:100%;border:1px solid rgba(255,255,255,.10);border-radius:14px;background:rgba(15,23,42,.88);color:#fff;padding:10px 12px;font-size:12px;font-weight:800;outline:none}.dxm-picker-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px}.dxm-picker-tile{border:1px solid rgba(255,255,255,.10);border-radius:16px;background:rgba(15,23,42,.62);padding:8px;text-align:left;cursor:pointer;color:#fff;display:grid;gap:7px}.dxm-picker-tile:hover,.dxm-picker-tile.is-selected{border-color:rgba(34,211,238,.60);background:rgba(34,211,238,.10)}.dxm-picker-tile-img{height:92px;border-radius:12px;background:rgba(2,6,23,.72);display:grid;place-items:center;overflow:hidden}.dxm-picker-tile-img img{width:100%;height:100%;object-fit:cover}.dxm-picker-tile strong{font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.dxm-picker-tile small{color:rgba(255,255,255,.48);font-size:10px}.dxm-picker-empty{border:1px dashed rgba(255,255,255,.16);border-radius:16px;padding:18px;text-align:center;color:rgba(255,255,255,.55);font-size:12px;font-weight:800}.dxm-picker-hidden{display:none!important}@media(max-width:760px){.dxm-picker-body{grid-template-columns:1fr}.dxm-picker-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.dxm-picker-dialog{width:96vw;max-height:92vh}.dxm-picker-large-preview{height:130px}}
        /* AppsHub embedded image picker modal for app graphics END */

    </style>

    @php
        $activeName = $activeApp?->name ?? 'Selected App';
        $enabledSupport = collect($supportChannels ?? [])->where('enabled', true)->count();
        $enabledLinks = collect($officialLinks ?? [])->where('enabled', true)->count();
        $enabledCustom = collect($customSettings ?? [])->where('enabled', true)->count();
        $logoUrl = $graphics['logo_url'] ?? '';
        $bannerUrl = $graphics['banner_url'] ?? '';
        $splashUrl = $graphics['splash_url'] ?? '';
        $appIconUrl = $graphics['app_icon_url'] ?? '';
        $graphicCards = [
            'logo' => ['title' => 'App Logo', 'badge' => 'Header + Push', 'url' => $logoUrl, 'path' => $graphics['logo_path'] ?? '', 'asset_id' => $graphics['logo_asset_id'] ?? '', 'help' => 'Used in app header, About page, backend preview, and push notification fallback. Recommended: transparent PNG, square or near-square.'],
            'banner' => ['title' => 'Main Banner', 'badge' => 'Frontend Header', 'url' => $bannerUrl, 'path' => $graphics['banner_path'] ?? '', 'asset_id' => $graphics['banner_asset_id'] ?? '', 'help' => 'Used for wide app branding/header areas. Recommended: wide JPG/PNG, 16:9 or similar.'],
            'splash' => ['title' => 'Splash Screen', 'badge' => 'Launch', 'url' => $splashUrl, 'path' => $graphics['splash_path'] ?? '', 'asset_id' => $graphics['splash_asset_id'] ?? '', 'help' => 'Used by frontend splash/launch experience where supported. Recommended: portrait design.'],
            'app_icon' => ['title' => 'App Icon', 'badge' => 'Identity', 'url' => $appIconUrl, 'path' => $graphics['app_icon_path'] ?? '', 'asset_id' => $graphics['app_icon_asset_id'] ?? '', 'help' => 'Used for app identity and store/build metadata. Recommended: square PNG.'],
        ];
    @endphp

    <form wire:submit.prevent="save" class="dxm-settings-shell" x-data="{ tab: localStorage.getItem('appSettingsTab') || 'profile', selectTab(value){ this.tab = value; localStorage.setItem('appSettingsTab', value); } }">
        <section class="dxm-settings-hero">
            <div class="dxm-settings-hero-grid">
                <div>
                    <div class="dxm-settings-kicker">Dynamic Reusable App Settings Engine</div>
                    <h1 class="dxm-settings-title">{{ $activeName }} Settings</h1>
                    <div class="dxm-settings-sub">
                        Manage public app identity, graphics, theme, store metadata, support channels, official links, and custom frontend settings for the active app.
                        These settings are multi-app ready and are delivered through the AppsHub API.
                    </div>
                    <div class="dxm-settings-actions">
                        <button type="submit" class="dxm-settings-btn primary">Save App Settings</button>
                        <button type="button" class="dxm-settings-btn" wire:click="seedRecommendedDefaults">Prepare Defaults</button>
                        <a class="dxm-settings-btn" href="{{ url('/admin/destination-builder?tab=more') }}">Open More Builder</a>
                        <a class="dxm-settings-btn" href="{{ url('/admin/app-capabilities') }}">App Capabilities</a>
                    </div>
                </div>

                <div class="dxm-settings-stats">
                    <div class="dxm-settings-stat"><strong>{{ $activeApp?->id ?? '—' }}</strong><span>Active App ID</span></div>
                    <div class="dxm-settings-stat"><strong>{{ $logoUrl ? 'Yes' : 'No' }}</strong><span>Logo Ready</span></div>
                    <div class="dxm-settings-stat"><strong>{{ $enabledSupport }}</strong><span>Enabled Support</span></div>
                    <div class="dxm-settings-stat"><strong>{{ $enabledLinks }}</strong><span>Enabled Links</span></div>
                </div>
            </div>
        </section>

        <div class="dxm-settings-note">
            <strong>App graphics rule:</strong> Logo, banner, splash, and app icon are stored separately so push notifications, frontend branding, splash assets, and store graphics do not conflict.
            For uploaded public files, use storage paths like <strong>apps/logos/logo.png</strong>. For external files, use a full <strong>https://</strong> URL.
        </div>

        <div class="dxm-settings-tabs">
            @foreach([
                'profile' => 'Profile',
                'graphics' => 'Graphics',
                'theme' => 'Theme',
                'support' => 'Support Channels',
                'links' => 'Official Links',
                'legal' => 'Legal / Store',
                'custom' => 'Custom Settings',
                'preview' => 'Preview',
            ] as $key => $label)
                <button type="button" class="dxm-settings-tab" :class="tab === '{{ $key }}' ? 'active' : ''" x-on:click="selectTab('{{ $key }}')">{{ $label }}</button>
            @endforeach
        </div>

        <section class="dxm-settings-panel" :class="tab === 'profile' ? 'active' : ''">
            <div class="dxm-settings-box"><h2>Basic Profile</h2><p>This appears in the app header, About area, sharing text, and public app identity.</p></div>
            <div class="dxm-settings-grid">
                <div class="dxm-field"><label>Display Name</label><input type="text" wire:model.defer="profile.display_name" placeholder="Dunamis TV"><small>Public app name shown in frontend areas.</small></div>
                <div class="dxm-field"><label>Tagline</label><input type="text" wire:model.defer="profile.tagline" placeholder="Watch, learn and get inspired"><small>Short subtitle shown under the app name.</small></div>
                <div class="dxm-field full"><label>About App</label><textarea wire:model.defer="profile.about" placeholder="Describe this app..."></textarea></div>
            </div>
        </section>

        <section class="dxm-settings-panel" :class="tab === 'graphics' ? 'active' : ''">
            <div class="dxm-settings-box">
                <h2>App Graphics</h2>
                <p>Upload and preview the real logo, banner, splash screen, and app icon used by the backend preview, Flutter bootstrap API, and push notification card payload. Raw paths are still available as technical details, but the main work area is now visual.</p>
            </div>

            <div class="dxm-graphics-grid">
                @foreach($graphicCards as $assetKey => $card)
                    <div class="dxm-graphic-card" wire:key="app-graphic-card-{{ $assetKey }}">
                        <div class="dxm-graphic-head">
                            <div>
                                <div class="dxm-graphic-title">{{ $card['title'] }}</div>
                                <div class="dxm-graphic-use">{{ $card['help'] }}</div>
                            </div>
                            <div class="dxm-graphic-badge">{{ $card['badge'] }}</div>
                        </div>

                        <div class="dxm-graphic-preview {{ $assetKey }}">
                            @if($card['url'])
                                <img src="{{ $card['url'] }}" alt="{{ $card['title'] }} preview">
                            @else
                                <div class="dxm-empty-preview">No {{ strtolower($card['title']) }} uploaded yet</div>
                            @endif
                        </div>

                        <div class="dxm-graphic-controls">
                            <div class="dxm-upload-row">
                                <input type="file" wire:model="graphicUploads.{{ $assetKey }}" accept="image/*">
                                <button type="button" class="dxm-settings-btn primary small" wire:click="uploadGraphic('{{ $assetKey }}')" wire:loading.attr="disabled" wire:target="graphicUploads.{{ $assetKey }},uploadGraphic('{{ $assetKey }}')">Upload / Replace</button>
                            </div>
                            <div wire:loading wire:target="graphicUploads.{{ $assetKey }},uploadGraphic('{{ $assetKey }}')" style="color:rgba(103,232,249,.9);font-size:12px;font-weight:800;">Processing image...</div>

                            <div class="dxm-picker-card"
                                 x-data="{
                                    open:false,
                                    query:'',
                                    selectedId: '{{ $card['asset_id'] }}',
                                    selectedUrl: @js($card['url'] ?: ''),
                                    selectedLabel: 'Current {{ $card['title'] }}',
                                    choose(id, url, label) { this.selectedId = String(id || ''); this.selectedUrl = url || ''; this.selectedLabel = label || 'Selected image'; },
                                    match(label, bucket, url) { const q = this.query.toLowerCase().trim(); return !q || String(label + ' ' + bucket + ' ' + url).toLowerCase().includes(q); }
                                 }">
                                <div class="dxm-picker-top">
                                    <div class="dxm-picker-copy">
                                        <strong>{{ $card['title'] }} Library</strong>
                                        <span>Pick from the same Media Library picker style used by the Quote Designer.</span>
                                    </div>
                                    <div class="dxm-picker-actions">
                                        <button type="button" class="dxm-settings-btn primary small" x-on:click="open=true">Open Media Library</button>
                                        <button type="button" class="dxm-settings-btn small" x-on:click="choose('', '', 'No image selected')">Clear Pick</button>
                                    </div>
                                </div>

                                <div class="dxm-picker-selected">
                                    <div class="dxm-picker-thumb">
                                        <template x-if="selectedUrl"><img :src="selectedUrl" alt=""></template>
                                        <template x-if="!selectedUrl"><span>No image</span></template>
                                    </div>
                                    <div class="dxm-picker-meta">
                                        <strong x-text="selectedUrl ? selectedLabel : 'No image selected'"></strong>
                                        <span x-text="selectedUrl || 'Select from library, upload new media, or use the upload above.'"></span>
                                    </div>
                                </div>

                                <div class="dxm-picker-modal" :class="open ? 'is-open' : ''" x-cloak>
                                    <div class="dxm-picker-backdrop" x-on:click="open=false"></div>
                                    <div class="dxm-picker-dialog" role="dialog" aria-modal="true">
                                        <div class="dxm-picker-modal-head">
                                            <div>
                                                <strong>{{ $card['title'] }} Media Library</strong>
                                                <span>Choose an existing image for {{ strtolower($card['title']) }}. Upload new images from the card upload area above.</span>
                                            </div>
                                            <button type="button" class="dxm-picker-close" x-on:click="open=false">×</button>
                                        </div>

                                        <div class="dxm-picker-body">
                                            <aside class="dxm-picker-side">
                                                <div class="dxm-picker-large">
                                                    <strong>Selected Preview</strong>
                                                    <div class="dxm-picker-large-preview">
                                                        <template x-if="selectedUrl"><img :src="selectedUrl" alt=""></template>
                                                        <template x-if="!selectedUrl"><span>No image selected</span></template>
                                                    </div>
                                                    <small x-text="selectedUrl || 'Select an image from the grid.'"></small>
                                                </div>
                                            </aside>

                                            <section>
                                                <div class="dxm-picker-search">
                                                    <input type="text" x-model="query" placeholder="Search by label, bucket, URL...">
                                                </div>
                                                <div class="dxm-picker-grid">
                                                    @forelse($mediaLibraryAssets ?? [] as $libraryAsset)
                                                        @php($assetUrl = $libraryAsset['url'] ?? '')
                                                        <button type="button"
                                                                class="dxm-picker-tile"
                                                                :class="String(selectedId) === '{{ $libraryAsset['id'] }}' ? 'is-selected' : ''"
                                                                x-show="match(@js($libraryAsset['label'] ?? ''), @js($libraryAsset['bucket'] ?? ''), @js($assetUrl))"
                                                                x-on:click="choose('{{ $libraryAsset['id'] }}', @js($assetUrl), @js($libraryAsset['label'] ?? ('Asset #' . $libraryAsset['id'])))">
                                                            <span class="dxm-picker-tile-img">
                                                                @if($assetUrl)
                                                                    <img src="{{ $assetUrl }}" alt="" loading="lazy">
                                                                @else
                                                                    <span>No image</span>
                                                                @endif
                                                            </span>
                                                            <strong>{{ $libraryAsset['label'] ?? ('Asset #' . $libraryAsset['id']) }}</strong>
                                                            <small>{{ $libraryAsset['scope'] ?? 'Asset' }} · {{ $libraryAsset['bucket'] ?? 'misc' }} · #{{ $libraryAsset['id'] }}</small>
                                                        </button>
                                                    @empty
                                                        <div class="dxm-picker-empty">No image assets found for this app yet. Upload one from the card upload field first.</div>
                                                    @endforelse
                                                </div>
                                            </section>
                                        </div>

                                        <div class="dxm-picker-modal-foot">
                                            <a class="dxm-settings-btn small" href="{{ url('/admin/media-library?tab=images') }}" target="_blank" rel="noopener">Manage Full Library</a>
                                            <button type="button" class="dxm-settings-btn small" x-on:click="open=false">Cancel</button>
                                            <button type="button" class="dxm-settings-btn primary small"
                                                    x-bind:disabled="!selectedId"
                                                    x-on:click="$wire.applyGraphicFromLibraryId('{{ $assetKey }}', selectedId); open=false">Use Selected Image</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="dxm-technical-grid">
                                <div class="dxm-tech-field">
                                    <label>Public URL <button type="button" class="dxm-settings-btn small" x-on:click="navigator.clipboard?.writeText('{{ $card['url'] }}')">Copy</button></label>
                                    <input type="text" wire:model.defer="graphics.{{ $assetKey }}_url" placeholder="https://...">
                                </div>
                                <div class="dxm-tech-field">
                                    <label>Storage Path</label>
                                    <input type="text" wire:model.defer="graphics.{{ $assetKey }}_path" placeholder="assets/app/{{ $assetKey }}.png">
                                </div>
                                <div class="dxm-tech-field">
                                    <label>Media Asset ID</label>
                                    <input type="text" wire:model.defer="graphics.{{ $assetKey }}_asset_id" placeholder="Optional asset ID">
                                </div>
                                <div class="dxm-tech-field">
                                    <label>Actions</label>
                                    <div class="dxm-settings-actions" style="margin-top:7px">
                                        @if($card['url'] || $card['path'] || $card['asset_id'])
                                            <a class="dxm-settings-btn small" href="{{ $card['url'] ?: '#' }}" target="_blank" rel="noopener">Open</a>
                                            <button type="button" class="dxm-settings-btn danger small" wire:click="clearGraphic('{{ $assetKey }}')">Clear Setting</button>
                                        @else
                                            <span style="color:rgba(255,255,255,.45);font-size:11px;font-weight:800;">No graphic set</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

                <section class="dxm-settings-panel" :class="tab === 'theme' ? 'active' : ''">
            <div class="dxm-settings-box"><h2>Theme & Appearance</h2><p>These values are sent through the bootstrap API so the frontend can render app-specific colors without hardcoding.</p></div>
            <div class="dxm-settings-grid">
                <div class="dxm-field quarter"><label>Primary Color</label><input type="color" wire:model.defer="theme.primary_color"><small>{{ $theme['primary_color'] ?: 'Not set' }}</small></div>
                <div class="dxm-field quarter"><label>Accent Color</label><input type="color" wire:model.defer="theme.accent_color"><small>{{ $theme['accent_color'] ?: 'Not set' }}</small></div>
                <div class="dxm-field quarter"><label>Background Color</label><input type="color" wire:model.defer="theme.background_color"><small>{{ $theme['background_color'] ?: 'Not set' }}</small></div>
                <div class="dxm-field quarter"><label>Text Color</label><input type="color" wire:model.defer="theme.text_color"><small>{{ $theme['text_color'] ?: 'Not set' }}</small></div>
                <div class="dxm-field third"><label>Theme Mode</label><select wire:model.defer="theme.theme_mode"><option value="dark">Dark</option><option value="light">Light</option><option value="system">System</option></select></div>
                <div class="dxm-field third"><label>Font Family</label><input type="text" wire:model.defer="theme.font_family" placeholder="system"></div>
                <div class="dxm-field third"><label>Button Style</label><select wire:model.defer="theme.button_style"><option value="rounded">Rounded</option><option value="pill">Pill</option><option value="square">Square</option><option value="soft">Soft Card</option></select></div>
            </div>
        </section>

        <section class="dxm-settings-panel" :class="tab === 'support' ? 'active' : ''">
            <div class="dxm-settings-box"><h2>Dynamic Support Channels</h2><p>Add church support, technical support, prayer line, testimony line, partnership contact, or any custom channel.</p><div class="dxm-settings-actions"><button type="button" class="dxm-settings-btn primary" wire:click="addSupportChannel">+ Add Support Channel</button></div></div>
            @forelse($supportChannels as $index => $row)
                <div class="dxm-dynamic-row" wire:key="support-channel-{{ $index }}">
                    <div class="dxm-row-head"><div class="dxm-row-title"><span class="dxm-row-badge">SUPPORT {{ $index + 1 }}</span><span>{{ $row['label'] ?: 'Untitled Support Channel' }}</span></div><div class="dxm-settings-actions" style="margin-top:0"><label class="dxm-toggle"><input type="checkbox" wire:model.defer="supportChannels.{{ $index }}.enabled"> Enabled</label><button type="button" class="dxm-settings-btn danger" wire:click="removeSupportChannel({{ $index }})">Delete</button></div></div>
                    <div class="dxm-settings-grid"><div class="dxm-field third"><label>Key</label><input type="text" wire:model.defer="supportChannels.{{ $index }}.key" placeholder="technical_support"></div><div class="dxm-field third"><label>Label</label><input type="text" wire:model.defer="supportChannels.{{ $index }}.label" placeholder="Technical Support"></div><div class="dxm-field third"><label>Type</label><select wire:model.defer="supportChannels.{{ $index }}.type"><option value="email">Email</option><option value="phone">Phone</option><option value="whatsapp">WhatsApp</option><option value="url">URL</option><option value="address">Address</option><option value="text">Text</option></select></div><div class="dxm-field"><label>Value</label><input type="text" wire:model.defer="supportChannels.{{ $index }}.value" placeholder="support@example.com / +234... / https://..."></div><div class="dxm-field"><label>Icon</label><input type="text" wire:model.defer="supportChannels.{{ $index }}.icon" placeholder="support, church, phone, mail"></div><div class="dxm-field full"><label>Description</label><input type="text" wire:model.defer="supportChannels.{{ $index }}.description" placeholder="For app support and technical issues."></div></div>
                </div>
            @empty
                <div class="dxm-settings-box"><p>No support channel yet. Click <strong>Add Support Channel</strong>.</p></div>
            @endforelse
        </section>

        <section class="dxm-settings-panel" :class="tab === 'links' ? 'active' : ''">
            <div class="dxm-settings-box"><h2>Dynamic Official Links</h2><p>Add unlimited links: YouTube, Facebook, Instagram, X, TikTok, Telegram, WhatsApp Channel, website, or any custom platform.</p><div class="dxm-settings-actions"><button type="button" class="dxm-settings-btn primary" wire:click="addOfficialLink">+ Add Official Link</button></div></div>
            @forelse($officialLinks as $index => $row)
                <div class="dxm-dynamic-row" wire:key="official-link-{{ $index }}">
                    <div class="dxm-row-head"><div class="dxm-row-title"><span class="dxm-row-badge">LINK {{ $index + 1 }}</span><span>{{ $row['label'] ?: 'Untitled Link' }}</span></div><div class="dxm-settings-actions" style="margin-top:0"><label class="dxm-toggle"><input type="checkbox" wire:model.defer="officialLinks.{{ $index }}.enabled"> Enabled</label><button type="button" class="dxm-settings-btn danger" wire:click="removeOfficialLink({{ $index }})">Delete</button></div></div>
                    <div class="dxm-settings-grid"><div class="dxm-field third"><label>Key</label><input type="text" wire:model.defer="officialLinks.{{ $index }}.key" placeholder="youtube"></div><div class="dxm-field third"><label>Label</label><input type="text" wire:model.defer="officialLinks.{{ $index }}.label" placeholder="YouTube"></div><div class="dxm-field third"><label>Type</label><select wire:model.defer="officialLinks.{{ $index }}.type"><option value="url">URL</option><option value="deep_link">Deep Link</option><option value="email">Email</option><option value="phone">Phone</option><option value="whatsapp">WhatsApp</option><option value="text">Text</option></select></div><div class="dxm-field"><label>Value</label><input type="text" wire:model.defer="officialLinks.{{ $index }}.value" placeholder="https://..."></div><div class="dxm-field"><label>Icon</label><input type="text" wire:model.defer="officialLinks.{{ $index }}.icon" placeholder="youtube, facebook, link"></div><div class="dxm-field full"><label>Description</label><input type="text" wire:model.defer="officialLinks.{{ $index }}.description" placeholder="Official YouTube channel."></div></div>
                </div>
            @empty
                <div class="dxm-settings-box"><p>No official link yet. Click <strong>Add Official Link</strong>.</p></div>
            @endforelse
        </section>

        <section class="dxm-settings-panel" :class="tab === 'legal' ? 'active' : ''">
            <div class="dxm-settings-box"><h2>Legal & Store</h2><p>These are needed for publishing, policy pages, and the Rate App action.</p></div>
            <div class="dxm-settings-grid"><div class="dxm-field"><label>Privacy Policy URL</label><input type="text" wire:model.defer="legal.privacy_url" placeholder="https://example.com/privacy"></div><div class="dxm-field"><label>Terms of Use URL</label><input type="text" wire:model.defer="legal.terms_url" placeholder="https://example.com/terms"></div><div class="dxm-field full"><label>Play Store URL</label><input type="text" wire:model.defer="store.play_store_url" placeholder="https://play.google.com/store/apps/details?id=..."><small>Add this after Play Store listing is available. The frontend Rate App button will use it automatically.</small></div><div class="dxm-field third"><label>Package Name</label><input type="text" wire:model.defer="store.package_name" placeholder="com.digitxtramedia.dunamistv"></div><div class="dxm-field third"><label>Version Name</label><input type="text" wire:model.defer="store.version_name" placeholder="1.0.0"></div><div class="dxm-field third"><label>Version Code</label><input type="text" wire:model.defer="store.version_code" placeholder="1"></div></div>
        </section>

        <section class="dxm-settings-panel" :class="tab === 'custom' ? 'active' : ''">
            <div class="dxm-settings-box"><h2>Custom Public Settings</h2><p>Add extra app-specific frontend values without needing new database columns or rebuilding the settings page.</p><div class="dxm-settings-actions"><button type="button" class="dxm-settings-btn primary" wire:click="addCustomSetting">+ Add Custom Setting</button></div></div>
            @forelse($customSettings as $index => $row)
                <div class="dxm-dynamic-row" wire:key="custom-setting-{{ $index }}">
                    <div class="dxm-row-head"><div class="dxm-row-title"><span class="dxm-row-badge">CUSTOM {{ $index + 1 }}</span><span>{{ $row['label'] ?: 'Untitled Setting' }}</span></div><div class="dxm-settings-actions" style="margin-top:0"><label class="dxm-toggle"><input type="checkbox" wire:model.defer="customSettings.{{ $index }}.enabled"> Enabled</label><button type="button" class="dxm-settings-btn danger" wire:click="removeCustomSetting({{ $index }})">Delete</button></div></div>
                    <div class="dxm-settings-grid"><div class="dxm-field third"><label>Key</label><input type="text" wire:model.defer="customSettings.{{ $index }}.key" placeholder="welcome_message"></div><div class="dxm-field third"><label>Label</label><input type="text" wire:model.defer="customSettings.{{ $index }}.label" placeholder="Welcome Message"></div><div class="dxm-field third"><label>Group</label><input type="text" wire:model.defer="customSettings.{{ $index }}.group" placeholder="general"></div><div class="dxm-field"><label>Type</label><select wire:model.defer="customSettings.{{ $index }}.type"><option value="text">Text</option><option value="url">URL</option><option value="image_url">Image URL</option><option value="boolean">Boolean</option><option value="number">Number</option><option value="json">JSON</option></select></div><div class="dxm-field"><label>Value</label><input type="text" wire:model.defer="customSettings.{{ $index }}.value" placeholder="Custom value"></div><div class="dxm-field full"><label>Description</label><input type="text" wire:model.defer="customSettings.{{ $index }}.description" placeholder="Describe what this setting controls."></div></div>
                </div>
            @empty
                <div class="dxm-settings-box"><p>No custom setting yet. Click <strong>Add Custom Setting</strong>.</p></div>
            @endforelse
        </section>

        <section class="dxm-settings-panel" :class="tab === 'preview' ? 'active' : ''">
            <div class="dxm-settings-box">
                <h2>Live App Identity Preview</h2>
                <p>This is a visual check of what AppsHub is sending to the Flutter app: logo, banner, splash, app icon, theme colors, and push notification identity.</p>
            </div>

            <div class="dxm-preview-layout">
                <div class="dxm-phone-preview">
                    <div class="dxm-phone-screen">
                        <div class="dxm-phone-banner">
                            @if($bannerUrl)
                                <img src="{{ $bannerUrl }}" alt="Banner preview">
                            @else
                                <div class="dxm-empty-preview">Banner preview</div>
                            @endif
                        </div>
                        <div class="dxm-phone-content">
                            <div class="dxm-app-row">
                                <div class="dxm-app-icon">
                                    @if($logoUrl)
                                        <img src="{{ $logoUrl }}" alt="Logo preview">
                                    @else
                                        {{ strtoupper(substr($activeName, 0, 1)) }}
                                    @endif
                                </div>
                                <div>
                                    <div class="dxm-app-name">{{ $profile['display_name'] ?: $activeName }}</div>
                                    <div class="dxm-app-tag">{{ $profile['tagline'] ?: 'No tagline set yet.' }}</div>
                                </div>
                            </div>

                            <div class="dxm-phone-card">{{ $profile['about'] ?: 'No about text set yet.' }}</div>
                            <div class="dxm-phone-card"><strong>Support / Links</strong><br>{{ $enabledSupport }} support channel(s), {{ $enabledLinks }} official link(s), {{ $enabledCustom }} custom setting(s)</div>
                            <div class="dxm-phone-card"><strong>Package</strong><br>{{ $store['package_name'] ?: 'Not set' }}</div>
                        </div>
                    </div>
                </div>

                <div class="dxm-preview-list">
                    <div class="dxm-preview-item">
                        <strong>Push Notification Card Preview</strong>
                        <div class="dxm-push-preview">
                            <div class="dxm-app-icon" style="width:48px;height:48px;border-radius:14px">
                                @if($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="Push logo preview">
                                @else
                                    {{ strtoupper(substr($activeName, 0, 1)) }}
                                @endif
                            </div>
                            <div>
                                <strong style="margin:0 0 4px">{{ $profile['display_name'] ?: $activeName }}</strong>
                                <div>Sample notification title</div>
                                <div style="color:rgba(255,255,255,.55);font-size:11px;margin-top:3px">This uses the app logo URL as notification identity fallback.</div>
                            </div>
                        </div>
                    </div>

                    <div class="dxm-preview-item">
                        <strong>Splash Screen Preview</strong>
                        <div class="dxm-splash-mini">
                            @if($splashUrl)
                                <img src="{{ $splashUrl }}" alt="Splash preview">
                            @else
                                <div><div class="dxm-app-icon" style="margin:0 auto 10px">@if($logoUrl)<img src="{{ $logoUrl }}" alt="Logo">@else{{ strtoupper(substr($activeName, 0, 1)) }}@endif</div>{{ $profile['display_name'] ?: $activeName }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="dxm-preview-item">
                        <strong>Theme Colors</strong>
                        <div class="dxm-color-row">
                            <div class="dxm-color-chip"><div class="dxm-color-swatch" style="background:{{ $theme['primary_color'] ?: '#280061' }}"></div><span>Primary<br>{{ $theme['primary_color'] ?: 'Not set' }}</span></div>
                            <div class="dxm-color-chip"><div class="dxm-color-swatch" style="background:{{ $theme['accent_color'] ?: '#9e56fc' }}"></div><span>Accent<br>{{ $theme['accent_color'] ?: 'Not set' }}</span></div>
                            <div class="dxm-color-chip"><div class="dxm-color-swatch" style="background:{{ $theme['background_color'] ?: '#020617' }}"></div><span>Background<br>{{ $theme['background_color'] ?: 'Not set' }}</span></div>
                            <div class="dxm-color-chip"><div class="dxm-color-swatch" style="background:{{ $theme['text_color'] ?: '#ffffff' }}"></div><span>Text<br>{{ $theme['text_color'] ?: 'Not set' }}</span></div>
                        </div>
                    </div>

                    <div class="dxm-preview-item"><strong>Logo URL</strong>{{ $logoUrl ?: 'Not set' }}</div>
                    <div class="dxm-preview-item"><strong>Banner URL</strong>{{ $bannerUrl ?: 'Not set' }}</div>
                    <div class="dxm-preview-item"><strong>Splash URL</strong>{{ $splashUrl ?: 'Not set' }}</div>
                    <div class="dxm-preview-item"><strong>App Icon URL</strong>{{ $appIconUrl ?: 'Not set' }}</div>
                    <div class="dxm-preview-item"><strong>Store</strong>Package: {{ $store['package_name'] ?: 'Not set' }}<br>Play Store: {{ $store['play_store_url'] ?: 'Not set' }}</div>
                </div>
            </div>
        </section>

                <div class="dxm-settings-actions">
            <button type="submit" class="dxm-settings-btn primary">Save App Settings</button>
            <a class="dxm-settings-btn" href="{{ url('/admin/destination-builder?tab=more') }}">Return to More Builder</a>
        </div>
    </form>
</x-filament::page>
