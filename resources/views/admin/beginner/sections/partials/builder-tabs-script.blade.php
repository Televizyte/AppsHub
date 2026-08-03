<style>
    .dxm-alert{border:1px solid rgba(34,197,94,.35);background:rgba(34,197,94,.12);color:#bbf7d0;padding:12px 14px;border-radius:14px;margin-bottom:14px;font-weight:750;font-size:13px}.dxm-alert--danger{border-color:rgba(248,113,113,.35);background:rgba(248,113,113,.12);color:#fecaca}.dxm-alert span{display:block;margin-top:3px}.builder-tabs{display:flex;gap:8px;overflow:auto;padding:6px;margin-bottom:14px;border:1px solid rgba(255,255,255,.08);background:rgba(2,6,23,.38);border-radius:16px;scrollbar-width:none}.builder-tabs::-webkit-scrollbar{display:none}.builder-tab{border:1px solid rgba(255,255,255,.10);background:rgba(15,23,42,.78);color:rgba(255,255,255,.72);border-radius:999px;padding:9px 13px;font-size:12px;font-weight:850;white-space:nowrap;cursor:pointer}.builder-tab.is-active{border-color:rgba(34,211,238,.48);background:linear-gradient(135deg,rgba(34,211,238,.24),rgba(168,85,247,.15));color:#fff}.builder-panel{display:none}.builder-panel.is-active{display:block}.builder-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.field{min-width:0}.full{grid-column:1/-1}.field label{display:block;margin:0 0 7px;color:rgba(255,255,255,.82);font-size:12px;font-weight:800;line-height:1.25}.field input,.field select{width:100%;min-height:44px;border-radius:14px;border:1px solid rgba(255,255,255,.10);background:rgba(2,6,23,.78);color:#fff;padding:11px 13px;font-size:13px;outline:none}.field small{display:block;margin-top:7px;color:rgba(255,255,255,.52);font-size:11.5px;line-height:1.45}.check{display:flex!important;align-items:center;gap:10px;border:1px solid rgba(255,255,255,.08);background:rgba(2,6,23,.24);border-radius:14px;padding:12px 14px;margin:0!important}.check input{width:auto}.check span{font-size:13px;font-weight:800;color:#fff}.builder-note{margin-top:14px;border:1px solid rgba(34,211,238,.18);background:rgba(34,211,238,.07);border-radius:16px;padding:12px 14px;color:rgba(255,255,255,.68);font-size:12px;line-height:1.45}.action-row{display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;margin-top:18px}.builder-preview{display:grid;gap:14px}.preview-card{min-height:230px;border-radius:26px;padding:22px;background:radial-gradient(circle at top right,rgba(226,56,138,.42),transparent 38%),linear-gradient(135deg,#0B1F4D,#1D5CFF 48%,#3b0764);border:1px solid rgba(255,255,255,.12);display:flex;flex-direction:column;justify-content:flex-end;overflow:hidden}.preview-kicker{align-self:flex-start;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.18);font-size:10.5px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;color:#cffafe}.preview-card h2{margin:34px 0 8px;color:#fff;font-size:24px;line-height:1.08;letter-spacing:-.04em}.preview-card p{margin:0;color:rgba(255,255,255,.78);font-size:12.5px;line-height:1.4}.preview-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}.preview-row span{padding:5px 8px;border-radius:999px;background:rgba(2,6,23,.34);border:1px solid rgba(255,255,255,.12);font-size:10.5px;font-weight:850;color:#fff}.preview-details{border:1px solid rgba(255,255,255,.08);background:rgba(2,6,23,.28);border-radius:18px;padding:14px;display:grid;gap:8px;color:rgba(255,255,255,.68);font-size:12.5px;word-break:break-word}.preview-details strong{color:rgba(255,255,255,.86)}@media(max-width:760px){.builder-grid{grid-template-columns:1fr}.action-row{justify-content:stretch}.action-row .dxm-btn{width:100%}}
</style>
<script>
(function(){
    const tabs=document.querySelectorAll('.builder-tab');
    const panels=document.querySelectorAll('.builder-panel');
    tabs.forEach(tab=>tab.addEventListener('click',()=>{const key=tab.dataset.tab;tabs.forEach(t=>t.classList.toggle('is-active',t===tab));panels.forEach(p=>p.classList.toggle('is-active',p.dataset.panel===key));}));
    const $=id=>document.getElementById(id);
    function selectedText(el){return el&&el.options&&el.selectedIndex>=0?el.options[el.selectedIndex].textContent.trim():'';}
    function clean(v,f='—'){v=(v||'').toString().trim();return v||f;}
    function update(){
        const title=$('title'),subtitle=$('subtitle'),tab=$('tab_key'),template=$('template'),order=$('sort_order'),routeKey=$('route_key'),key=$('key'),bucket=$('source_bucket'),source=$('source_type'),target=$('target_route'),insertTarget=$('insert_target_bucket'),insertAfter=$('insert_after_items');
        if($('previewTitle')) $('previewTitle').textContent=clean(title&&title.value,'Section title preview');
        if($('previewSubtitle')) $('previewSubtitle').textContent=clean(subtitle&&subtitle.value,'Choose a content source and layout to preview the section contract.');
        if($('previewTab')) $('previewTab').textContent=selectedText(tab)||'Home';
        if($('previewTemplate')) $('previewTemplate').textContent=selectedText(template)||'Layout';
        if($('previewOrder')) $('previewOrder').textContent='Order '+clean(order&&order.value,'0');
        if($('previewRouteKey')) $('previewRouteKey').textContent=clean(routeKey&&routeKey.value,'Main tab screen');
        if($('previewKey')) $('previewKey').textContent=clean(key&&key.value,'section_key_here');
        if($('previewBucket')) $('previewBucket').textContent=clean(bucket&&bucket.value,'—');
        if($('previewSource')) $('previewSource').textContent=selectedText(source)||'Manual items';
        if($('previewTargetRoute')) $('previewTargetRoute').textContent=clean(target&&target.value,'—');
        const after=parseInt((insertAfter&&insertAfter.value)||'0',10)||0;
        if($('previewInsertion')) $('previewInsertion').textContent=(insertTarget&&insertTarget.value&&after>0)?('After every '+after+' item(s) in '+insertTarget.value):'No insertion';
    }
    document.querySelectorAll('input,select,textarea').forEach(el=>{el.addEventListener('input',update);el.addEventListener('change',update);});
    update();
})();
</script>
