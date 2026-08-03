@once
    @push('styles')
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Anton&family=Archivo+Black&family=Bebas+Neue&family=Montserrat:wght@400;600;700;800;900&family=Oswald:wght@400;600;700&family=Poppins:wght@400;600;700;800;900&family=Playfair+Display:wght@400;700;900&family=Merriweather:wght@400;700;900&family=Lora:wght@400;700&family=Roboto+Slab:wght@400;700;900&family=Roboto+Mono:wght@400;700&display=swap');
            .dxm-designer-panel{border:1px solid rgba(255,255,255,.10);background:rgba(2,6,23,.32);border-radius:20px;padding:16px;margin-bottom:14px}
            .dxm-designer-panel h3{margin:0 0 8px;color:#fff;font-size:15px;line-height:1.25}
            .dxm-designer-panel p{margin:0 0 14px;color:rgba(255,255,255,.62);font-size:12.5px;line-height:1.45}
            .dxm-designer-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
            .dxm-designer-full{grid-column:1/-1}
            .dxm-designer-field label{display:block;color:rgba(255,255,255,.82);font-size:12px;font-weight:800;margin-bottom:7px}
            .dxm-designer-field input,.dxm-designer-field select,.dxm-designer-field textarea{width:100%;border-radius:14px;border:1px solid rgba(255,255,255,.10);background:rgba(2,6,23,.78);color:#fff;padding:12px 13px;outline:none;font-size:13px;line-height:1.55}
            .dxm-designer-field textarea{resize:vertical;min-height:180px;white-space:pre-wrap}
            .dxm-designer-field input[type=file]{padding:10px}
            .dxm-designer-field input[type=range]{padding:0;accent-color:#38bdf8}
            .dxm-designer-field input[type=color]{width:54px;min-width:54px;height:44px;padding:4px}
            .dxm-designer-field small{display:block;margin-top:7px;color:rgba(255,255,255,.50);font-size:11.5px;line-height:1.35}
            .dxm-designer-color-row{display:grid;grid-template-columns:58px minmax(0,1fr);gap:10px}
            .dxm-designer-check{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,.78);font-size:12.5px;font-weight:700;min-height:44px}
            .dxm-designer-check input{width:18px;height:18px;padding:0;flex:0 0 auto}
            .dxm-designer-palette-row{display:flex;gap:10px;flex-wrap:wrap}
            .dxm-designer-palette{width:40px;height:40px;border-radius:999px;border:2px solid rgba(255,255,255,.25);cursor:pointer;box-shadow:inset 0 0 0 1px rgba(0,0,0,.15)}
            .dxm-designer-format-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
            .dxm-designer-format-option{display:flex;gap:10px;align-items:center;border:1px solid rgba(255,255,255,.10);background:rgba(2,6,23,.35);border-radius:16px;padding:12px;cursor:pointer}
            .dxm-designer-format-option:hover{border-color:rgba(34,211,238,.42);background:rgba(34,211,238,.08)}
            .dxm-designer-format-option input{width:16px;height:16px;flex:0 0 auto}
            .dxm-designer-format-option strong{display:block;font-size:13px;color:#fff;line-height:1.25}
            .dxm-designer-format-option small{display:block;margin-top:2px;color:rgba(255,255,255,.52);font-size:11px}
            .dxm-designer-preview-toolbar{display:flex;justify-content:space-between;gap:10px;margin-bottom:10px;color:rgba(255,255,255,.60);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em}
            .dxm-designer-card-stage{display:flex;justify-content:center;align-items:flex-start;overflow:auto;padding:10px}
            .dxm-designer-card-preview{position:relative;width:min(100%,390px);aspect-ratio:4/5;border-radius:26px;overflow:hidden;background:#160042;border:1px solid rgba(255,255,255,.10);box-shadow:0 24px 70px rgba(0,0,0,.35)}
            .dxm-designer-card-bg{position:absolute;inset:0;background:linear-gradient(135deg,#160042,#e2388a)}
            .dxm-designer-card-bg img{width:100%;height:100%;object-fit:cover;display:block}
            .dxm-designer-card-overlay{position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,.08),rgba(0,0,0,.35),rgba(0,0,0,.72))}
            .dxm-designer-mark{position:absolute;top:28px;left:28px;font-size:96px;line-height:.8;font-weight:900;color:rgba(255,255,255,.16);z-index:3;pointer-events:none}
            .dxm-designer-card-content{position:absolute;inset:34px;z-index:4;display:flex;flex-direction:column;justify-content:center;align-items:center;color:#fff;text-align:center}
            .dxm-designer-card-inner{width:86%;max-width:100%;font-family:Arial,Helvetica,sans-serif}
            .dxm-designer-main-text{font-size:24px;line-height:1.35;font-weight:700;white-space:pre-wrap;text-shadow:none;overflow-wrap:anywhere}
            .dxm-designer-support-text,.dxm-designer-ref-text{font-size:14px;line-height:1.45;font-weight:700;margin-top:18px;opacity:.9;white-space:pre-wrap}
            .dxm-designer-ref-text{opacity:.94}
            .dxm-designer-preview-note{margin-top:14px;border:1px solid rgba(255,255,255,.08);background:rgba(2,6,23,.30);border-radius:16px;padding:14px;color:rgba(255,255,255,.65);font-size:12.5px;line-height:1.5}
            .dxm-designer-preview-note strong{display:block;color:#fff;margin-bottom:4px}
            .dxm-designer-preview-note code{color:#67e8f9}
            .dxm-designer-danger{border-color:rgba(248,113,113,.34);background:rgba(127,29,29,.12)}
            .dxm-designer-action-row{display:flex;gap:10px;flex-wrap:wrap}
            @media(max-width:760px){.dxm-designer-grid,.dxm-designer-format-list{grid-template-columns:1fr}.dxm-designer-action-row .dxm-btn{width:100%}}
        </style>
    @endpush
@endonce