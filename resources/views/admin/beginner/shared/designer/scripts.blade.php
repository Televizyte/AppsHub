@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                function esc(value){return String(value||'').replace(/[&<>"']/g,function(m){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m];});}
                function fontStack(value){const key=String(value||'system').toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'');if(key==='impact'||key==='impact_bold'||key==='anton')return 'Anton, Impact, Arial Black, sans-serif';if(key==='archivo_black'||key==='arial_black')return 'Archivo Black, Arial Black, Impact, sans-serif';if(key==='bebas'||key==='bebas_neue')return 'Bebas Neue, Impact, sans-serif';if(key==='oswald')return 'Oswald, Arial, sans-serif';if(key==='montserrat')return 'Montserrat, Arial, sans-serif';if(key==='poppins'||key==='inter')return 'Poppins, Inter, Arial, sans-serif';if(key==='playfair'||key==='playfair_display')return 'Playfair Display, Georgia, serif';if(key==='merriweather'||key==='serif'||key==='georgia'||key==='times')return 'Merriweather, Georgia, Times New Roman, serif';if(key==='lora'||key==='garamond')return 'Lora, Garamond, Georgia, serif';if(key==='roboto_slab')return 'Roboto Slab, Georgia, serif';if(key==='courier'||key==='mono')return 'Roboto Mono, Consolas, Monaco, monospace';if(key==='arial')return 'Arial, Helvetica, sans-serif';if(key==='verdana')return 'Verdana, Geneva, sans-serif';if(key==='tahoma')return 'Tahoma, Geneva, sans-serif';if(key==='trebuchet')return 'Trebuchet MS, Arial, sans-serif';return 'Arial, Helvetica, sans-serif';}
                function getField(name){return document.querySelector('[name="'+name+'"]')||document.getElementById(name);}
                function getValue(name,fallback){const field=getField(name);if(!field)return fallback;if(field.type==='checkbox')return field.checked;return field.value||fallback;}
                function currentFormatRadio(){return document.querySelector('input[name="card_format"]:checked');}
                function autoSize(baseSize,text){const scaleMode=getValue('text_scale_mode','auto');if(scaleMode!=='auto')return baseSize;const length=(text||'').length;if(length>420)return Math.max(10,baseSize-12);if(length>300)return Math.max(11,baseSize-9);if(length>220)return Math.max(12,baseSize-6);if(length>150)return Math.max(13,baseSize-3);return baseSize;}
                function syncColorText(colorInput){const textInput=document.querySelector('[data-color-copy="'+colorInput.id+'"]');if(textInput)textInput.value=colorInput.value;}
                function mainTextForKind(kind){if(kind==='scripture')return getValue('verse','Scripture text will appear here.');if(kind==='daily_quote')return getValue('quote','Quote text will appear here.');return getValue('quote_text','Quote text will appear here.');}
                function supportTextForKind(kind){if(kind==='scripture')return getValue('note','');if(kind==='daily_quote')return getValue('source','');return getValue('quote_source','');}
                function refTextForKind(kind){return kind==='scripture'?getValue('ref',''):'';}
                function escapeRegExp(value){return String(value).replace(/[.*+?^${}()|[\]\\]/g,'\\$&');}
                function highlightedHtml(text){
                    let html=esc(text).replace(/\n/g,'<br>');
                    const raw=String(getValue('highlight_phrases','')||'').trim();
                    if(!raw)return html;
                    const phrases=raw.split(/[\r\n,]+/).map(function(v){return v.trim();}).filter(Boolean).sort(function(a,b){return b.length-a.length;}).slice(0,12);
                    const color=getValue('highlight_color','#facc15');
                    const scale=getValue('highlight_scale','1.08');
                    const weight=getValue('highlight_weight','800');
                    phrases.forEach(function(phrase){
                        const safe=esc(phrase);
                        if(!safe)return;
                        const re=new RegExp(escapeRegExp(safe),'gi');
                        html=html.replace(re,function(match){return '<span class="dxm-highlight-text" style="color:'+color+';font-size:'+scale+'em;font-weight:'+weight+';">'+match+'</span>';});
                    });
                    return html;
                }

                function updateSharedDesignerPreview(){
                    document.querySelectorAll('[data-dxm-designer-root]').forEach(function(root){
                        const kind=root.getAttribute('data-designer-kind')||'quote';
                        const card=root.querySelector('[data-dxm-card-preview]');
                        const bg=root.querySelector('[data-dxm-card-bg]');
                        const overlay=root.querySelector('[data-dxm-card-overlay]');
                        const content=root.querySelector('[data-dxm-card-content]');
                        const inner=root.querySelector('[data-dxm-card-inner]');
                        const mark=root.querySelector('[data-dxm-card-mark]');
                        const mainTextBox=root.querySelector('[data-dxm-main-text]');
                        const supportTextBox=root.querySelector('[data-dxm-support-text]');
                        const refTextBox=root.querySelector('[data-dxm-ref-text]');
                        const formatLabel=root.querySelector('[data-dxm-format-label]');
                        const countLabel=root.querySelector('[data-dxm-character-count]');
                        if(!card||!bg||!overlay||!content||!inner||!mainTextBox)return;

                        const format=currentFormatRadio();
                        const ratio=format?format.getAttribute('data-format-ratio'):'4 / 5';
                        const backgroundMode=getValue('background_mode','gradient');
                        const imageUrl=getValue('cover_image_url',getValue('image_url','')).trim();
                        const bgColor=getValue('bg_color','#160042');
                        const bgColor2=getValue('bg_color_2','#e2388a');
                        const textColor=getValue('text_color','#ffffff');
                        const sourceColor=getValue('source_color',getValue('accent_color','#38bdf8'));
                        const overlayStrength=parseInt(getValue('overlay_strength','58'),10)/100;
                        const mainSizeField=document.querySelector('[data-dxm-main-size]');
                        const supportSizeField=document.querySelector('[data-dxm-support-size]');
                        const mainSize=parseInt(mainSizeField?mainSizeField.value:getValue('title_size',getValue('quote_size','24')),10);
                        const supportSize=supportSizeField?supportSizeField.value:getValue('font_size',getValue('source_size','14'));
                        const fontWeight=getValue('font_weight','700');
                        const sourceWeight=getValue('source_weight','700');
                        const textAlign=getValue('text_align','center');
                        const verticalAlign=getValue('vertical_align','center');
                        const cardPaddingX=getValue('card_padding_x',getValue('card_padding','34'));
                        const cardPaddingY=getValue('card_padding_y',getValue('card_padding','34'));
                        const legacyPaddingField=document.getElementById('card_padding');
                        if(legacyPaddingField){legacyPaddingField.value=String(Math.max(parseInt(cardPaddingX||'34',10), parseInt(cardPaddingY||'34',10)));}
                        const contentWidth=getValue('content_width','86');
                        const lineHeight=getValue('line_height','1.35');
                        const textShadow=getValue('text_shadow','soft');
                        const fontFamily=getValue('font_family','system');
                        const showMark=getValue('show_quote_mark',true);
                        const mainText=mainTextForKind(kind);
                        const supportText=supportTextForKind(kind);
                        const refText=refTextForKind(kind);

                        card.style.aspectRatio=ratio;
                        bg.innerHTML='';

                        if(backgroundMode==='image'&&imageUrl!==''){bg.style.background='linear-gradient(135deg,'+bgColor+','+bgColor2+')';bg.innerHTML='<img src="'+esc(imageUrl)+'" alt="">';}
                        else if(backgroundMode==='solid'){bg.style.background=bgColor;}
                        else{bg.style.background='linear-gradient(135deg,'+bgColor+','+bgColor2+')';}

                        overlay.style.background='linear-gradient(to bottom, rgba(0,0,0,0.04), rgba(0,0,0,'+(overlayStrength*0.40)+'), rgba(0,0,0,'+overlayStrength+'))';
                        content.style.color=textColor;
                        content.style.textAlign=textAlign;
                        content.style.justifyContent=verticalAlign==='start'?'flex-start':(verticalAlign==='end'?'flex-end':'center');
                        content.style.alignItems=textAlign==='left'?'flex-start':(textAlign==='right'?'flex-end':'center');
                        content.style.inset=cardPaddingY+'px '+cardPaddingX+'px';
                        inner.style.width=contentWidth+'%';
                        inner.style.fontFamily=fontStack(fontFamily);
                        if(mark)mark.style.display=showMark?'block':'none';

                        mainTextBox.innerHTML=highlightedHtml(mainText);
                        mainTextBox.style.fontSize=autoSize(mainSize,mainText)+'px';
                        mainTextBox.style.fontWeight=fontWeight;
                        mainTextBox.style.lineHeight=lineHeight;
                        mainTextBox.style.textShadow=textShadow==='off'?'none':(textShadow==='strong'?'0 4px 18px rgba(0,0,0,.65)':'0 2px 10px rgba(0,0,0,.35)');

                        if(supportTextBox){supportTextBox.textContent=supportText?supportText:'';supportTextBox.style.display=supportText?'block':'none';supportTextBox.style.fontSize=supportSize+'px';supportTextBox.style.color=sourceColor;supportTextBox.style.fontWeight=sourceWeight;supportTextBox.style.textShadow=mainTextBox.style.textShadow;}
                        if(refTextBox){refTextBox.textContent=refText;refTextBox.style.display=refText?'block':'none';refTextBox.style.fontSize=supportSize+'px';refTextBox.style.color=sourceColor;refTextBox.style.fontWeight=sourceWeight;refTextBox.style.textShadow=mainTextBox.style.textShadow;}

                        document.querySelectorAll('[data-dxm-display]').forEach(function(display){const key=display.getAttribute('data-dxm-display');const source=document.querySelector('[name="'+key+'"], #'+key);if(source)display.textContent=source.value;});
                        if(formatLabel&&format){const label=format.closest('label').querySelector('strong');formatLabel.textContent=label?label.textContent:'Format Preview';}
                        if(countLabel)countLabel.textContent=(mainText||'').length+' characters';

                        ['bg_color','bg_color_2','text_color','source_color','highlight_color','accent_color'].forEach(function(fieldId){const field=document.getElementById(fieldId);if(field)syncColorText(field);});
                    });
                }

                document.querySelectorAll('[data-color-copy]').forEach(function(input){input.addEventListener('input',function(){const colorInput=document.getElementById(input.dataset.colorCopy);if(colorInput&&/^#[0-9A-Fa-f]{6}$/.test(input.value)){colorInput.value=input.value;updateSharedDesignerPreview();}});});
                document.querySelectorAll('[data-dxm-designer-palette]').forEach(function(button){button.addEventListener('click',function(){const bg=document.getElementById('bg_color');const bg2=document.getElementById('bg_color_2');const text=document.getElementById('text_color');const accent=document.getElementById('accent_color');const preset=document.getElementById('style_preset');const backgroundMode=document.getElementById('background_mode');const source=document.getElementById('source_color');const highlight=document.getElementById('highlight_color');if(bg)bg.value=button.dataset.bg;if(bg2)bg2.value=button.dataset.bg2;if(text)text.value=button.dataset.text;if(source&&button.dataset.source)source.value=button.dataset.source;if(highlight&&button.dataset.highlight)highlight.value=button.dataset.highlight;if(accent)accent.value=button.dataset.accent;if(preset)preset.value=button.dataset.preset;if(backgroundMode)backgroundMode.value='gradient';updateSharedDesignerPreview();});});
                document.querySelectorAll('input, select, textarea, [data-dxm-designer-input]').forEach(function(field){field.addEventListener('input',updateSharedDesignerPreview);field.addEventListener('change',updateSharedDesignerPreview);});

                window.dxmUpdateSharedDesignerPreview=updateSharedDesignerPreview;
                updateSharedDesignerPreview();
            });
        </script>
    @endpush
@endonce