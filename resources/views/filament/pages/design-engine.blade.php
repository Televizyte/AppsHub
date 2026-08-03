<x-filament::page>
    <style>
        :root{--dxm-bg:#060914;--dxm-panel:#0d1328;--dxm-panel2:#111a33;--dxm-border:rgba(255,255,255,.10);--dxm-text:#f8fbff;--dxm-muted:rgba(255,255,255,.63);--dxm-cyan:#22d3ee;--dxm-pink:#ec4899;--dxm-purple:#8b5cf6}
        .dxm-studio-scroll{width:100%;height:calc(100vh - 118px);min-height:700px;overflow:auto;border-radius:24px}.dxm-studio{height:100%;min-height:700px;min-width:1180px;border:1px solid rgba(34,211,238,.18);border-radius:24px;overflow:hidden;background:linear-gradient(135deg,rgba(2,6,23,.98),rgba(12,18,38,.96));display:grid;grid-template-rows:64px 1fr;color:var(--dxm-text)}
        .dxm-top{display:flex;align-items:center;gap:12px;padding:10px 14px;border-bottom:1px solid var(--dxm-border);background:rgba(255,255,255,.035);min-width:0}.dxm-mode-links{display:flex;align-items:center;gap:8px;overflow:auto;max-width:340px;padding-bottom:2px}.dxm-mode-pill{display:inline-flex;align-items:center;gap:6px;white-space:nowrap;border:1px solid rgba(34,211,238,.18);background:rgba(34,211,238,.06);color:#dffbff;border-radius:999px;padding:8px 10px;font-size:11px;font-weight:950;text-decoration:none}.dxm-mode-pill:hover{background:rgba(34,211,238,.13);border-color:rgba(34,211,238,.42);color:#fff}
        .dxm-brand{display:flex;align-items:center;gap:10px;min-width:220px}.dxm-brand-icon{width:40px;height:40px;border-radius:14px;background:linear-gradient(135deg,var(--dxm-cyan),var(--dxm-purple),var(--dxm-pink));display:grid;place-items:center;font-weight:950}.dxm-brand h1{font-size:17px;font-weight:950;margin:0}.dxm-brand p{font-size:11px;color:var(--dxm-muted);margin:1px 0 0;font-weight:700}
        .dxm-top-actions{margin-left:auto;display:flex;gap:8px;align-items:center;overflow:auto;max-width:360px;padding-bottom:2px}.dxm-btn{border:1px solid var(--dxm-border);background:rgba(255,255,255,.055);color:#fff;border-radius:13px;padding:9px 12px;font-size:12px;font-weight:900;cursor:pointer;text-decoration:none}.dxm-btn:hover,.dxm-btn.primary{border-color:rgba(34,211,238,.42);background:rgba(34,211,238,.13)}.dxm-input{border:1px solid var(--dxm-border);background:#080d1f;color:#fff;border-radius:12px;padding:9px 10px;font-size:12px;font-weight:800;outline:none}
        .dxm-workspace{min-height:0;display:grid;grid-template-columns:76px 310px minmax(360px,1fr) 330px;background:#070b18}
        .dxm-tools{border-right:1px solid var(--dxm-border);background:#081022;padding:12px 8px;display:flex;flex-direction:column;gap:10px;align-items:center}.dxm-tool{width:50px;height:50px;border-radius:16px;border:1px solid var(--dxm-border);background:rgba(255,255,255,.045);color:#fff;display:grid;place-items:center;font-size:21px;cursor:pointer;position:relative}.dxm-tool:hover,.dxm-tool.active{background:rgba(34,211,238,.14);border-color:rgba(34,211,238,.45);box-shadow:inset 3px 0 0 var(--dxm-cyan)}.dxm-tool span{position:absolute;left:58px;white-space:nowrap;background:#111827;color:#fff;padding:6px 9px;border-radius:8px;font-size:11px;font-weight:900;opacity:0;pointer-events:none;z-index:20}.dxm-tool:hover span{opacity:1}
        .dxm-left{min-height:0;border-right:1px solid var(--dxm-border);background:rgba(255,255,255,.025);display:grid;grid-template-rows:auto 1fr}.dxm-panel-title{padding:14px 14px 10px;border-bottom:1px solid var(--dxm-border)}.dxm-panel-title h2{font-size:15px;font-weight:950;margin:0}.dxm-panel-title p{font-size:11px;color:var(--dxm-muted);font-weight:700;margin:4px 0 0}.dxm-panel-body{overflow:auto;padding:12px}
        .dxm-card{border:1px solid var(--dxm-border);background:rgba(255,255,255,.045);border-radius:16px;padding:12px;margin-bottom:10px;cursor:pointer}.dxm-card:hover{border-color:rgba(34,211,238,.42);background:rgba(34,211,238,.08)}.dxm-card strong{display:block;font-size:13px}.dxm-card small{display:block;color:var(--dxm-muted);margin-top:5px;font-weight:700;line-height:1.35}.dxm-grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px}.dxm-mini{border:1px solid var(--dxm-border);background:rgba(255,255,255,.045);border-radius:14px;padding:10px;cursor:pointer;text-align:center;font-size:12px;font-weight:900}.dxm-mini:hover{border-color:rgba(34,211,238,.42);background:rgba(34,211,238,.08)}
        .dxm-center{min-width:0;min-height:0;display:grid;grid-template-rows:46px 1fr;background:radial-gradient(circle at center,rgba(34,211,238,.06),transparent 48%)}.dxm-canvas-bar{display:flex;align-items:center;justify-content:center;gap:8px;border-bottom:1px solid var(--dxm-border);background:rgba(255,255,255,.02);padding:6px}.dxm-canvas-wrap{min-height:0;overflow:auto;display:grid;place-items:center;padding:22px}.dxm-canvas-shell{position:relative;box-shadow:0 28px 70px rgba(0,0,0,.42);border-radius:8px;overflow:hidden;background:white}.dxm-canvas-shell canvas{display:block}
        .dxm-right{min-height:0;border-left:1px solid var(--dxm-border);background:rgba(255,255,255,.025);display:grid;grid-template-rows:auto auto 1fr}.dxm-tabs{display:flex;gap:6px;padding:10px;border-bottom:1px solid var(--dxm-border);overflow:auto}.dxm-tab{border:1px solid var(--dxm-border);background:rgba(255,255,255,.04);color:#fff;border-radius:999px;padding:7px 10px;font-size:11px;font-weight:950;cursor:pointer}.dxm-tab.active{border-color:rgba(34,211,238,.48);background:rgba(34,211,238,.12)}
        .dxm-inspector{overflow:auto;padding:12px}.dxm-field{margin-bottom:11px}.dxm-field label{display:block;font-size:11px;font-weight:950;margin-bottom:6px;color:rgba(255,255,255,.86)}.dxm-field input,.dxm-field select,.dxm-field textarea{width:100%;border:1px solid var(--dxm-border);background:#080d1f;color:#fff;border-radius:12px;padding:9px 10px;font-size:12px;font-weight:800;outline:none}.dxm-field textarea{min-height:70px;resize:vertical}.dxm-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}.dxm-layer{display:flex;align-items:center;gap:8px;border:1px solid var(--dxm-border);background:rgba(255,255,255,.04);border-radius:13px;padding:9px;margin-bottom:8px;cursor:pointer}.dxm-layer.active{border-color:rgba(34,211,238,.55);background:rgba(34,211,238,.10)}.dxm-layer-name{font-size:12px;font-weight:900}.dxm-layer small{display:block;color:var(--dxm-muted);font-size:10px;font-weight:700}
        .dxm-hidden{display:none!important}.dxm-note{font-size:11px;line-height:1.45;color:var(--dxm-muted);font-weight:700;border:1px dashed var(--dxm-border);border-radius:14px;padding:10px;margin-top:10px}
        @media(max-width:1180px){.dxm-studio-scroll{overflow:auto}.dxm-studio{min-width:1180px}.dxm-top-actions{max-width:300px}.dxm-mode-links{max-width:280px}}
        @media(max-width:760px){.dxm-studio-scroll{height:calc(100vh - 98px);min-height:680px;overflow:auto;-webkit-overflow-scrolling:touch}.dxm-studio{min-width:1180px}.dxm-tool span{display:none}}
    </style>

    <div class="dxm-studio-scroll">
    <div
        class="dxm-studio"
        x-data="dxmFabricStudio({
            saveDesign: async (payload) => {
                @this.set('designTitle', payload.title);
                @this.set('designType', payload.type);
                @this.set('status', payload.status);
                @this.set('isTemplate', payload.isTemplate);
                @this.set('fabricJson', JSON.stringify(payload.fabric));
                @this.set('previewJson', JSON.stringify(payload.preview));
                await @this.call('saveStudioDesign');
            }
        })"
        x-init="boot()"
    >
        <div class="dxm-top">
            <div class="dxm-brand">
                <div class="dxm-brand-icon">✦</div>
                <div>
                    <h1>DXM Design Studio</h1>
                    <p>Advanced studio. Use Quick Designer for fast/simple cards.</p>
                </div>
            </div>

            <div class="dxm-mode-links" title="Quick designers are for fast quote/scripture work. Advanced studio is for rich layered designs.">
                <a class="dxm-mode-pill" href="{{ route('admin.beginner.daily.edit', ['kind' => 'quote']) }}">⚡ Quick Quote</a>
                <a class="dxm-mode-pill" href="{{ route('admin.beginner.daily.edit', ['kind' => 'scripture']) }}">📖 Quick Scripture</a>
                <a class="dxm-mode-pill" href="{{ route('admin.beginner.quote-designer.create') }}">✦ Quick Card</a>
            </div>

            <input class="dxm-input" x-model="project.title" placeholder="Design title" style="width:210px">
            <select class="dxm-input" x-model="project.type" style="width:150px">
                <option value="daily_quote">Daily Quote</option>
                <option value="daily_scripture">Daily Scripture</option>
                <option value="flyer">Flyer</option>
                <option value="banner">Banner</option>
                <option value="social_post">Social Post</option>
                <option value="general">General</option>
            </select>

            <div class="dxm-top-actions">
                <button type="button" class="dxm-btn" @click="newDesign()">+ Blank</button>
                <button type="button" class="dxm-btn" @click="activeTool='templates'">Templates</button>
                <a class="dxm-btn" href="{{ url('/admin/media-library') }}">Assets</a>
                <button type="button" class="dxm-btn primary" @click="save()">💾 Save</button>
            </div>
        </div>

        <div class="dxm-workspace">
            <aside class="dxm-tools">
                <button type="button" class="dxm-tool" :class="{active: activeTool==='select'}" @click="activeTool='select'" title="Select / Move">↖<span>Select / Move</span></button>
                <button type="button" class="dxm-tool" :class="{active: activeTool==='text'}" @click="activeTool='text'" title="Text">T<span>Text</span></button>
                <button type="button" class="dxm-tool" :class="{active: activeTool==='image'}" @click="activeTool='image'" title="Images">▧<span>Images</span></button>
                <button type="button" class="dxm-tool" :class="{active: activeTool==='shapes'}" @click="activeTool='shapes'" title="Shapes">◯<span>Shapes</span></button>
                <button type="button" class="dxm-tool" :class="{active: activeTool==='icons'}" @click="activeTool='icons'" title="Icons">☆<span>Icons</span></button>
                <button type="button" class="dxm-tool" :class="{active: activeTool==='background'}" @click="activeTool='background'" title="Background">▣<span>Background</span></button>
                <button type="button" class="dxm-tool" :class="{active: activeTool==='templates'}" @click="activeTool='templates'" title="Templates">▤<span>Templates</span></button>
                <button type="button" class="dxm-tool" :class="{active: rightTab==='layers'}" @click="rightTab='layers'" title="Layers">▱<span>Layers</span></button>
            </aside>

            <aside class="dxm-left">
                <div class="dxm-panel-title">
                    <h2 x-text="toolTitle()"></h2>
                    <p x-text="toolSubtitle()"></p>
                </div>

                <div class="dxm-panel-body">
                    <div x-show="activeTool==='templates'">
                        <div class="dxm-note" style="margin-top:0;margin-bottom:10px">Use Quick Designer for fast daily quote/scripture updates. Use this Advanced Studio when the design needs layers, images, shapes, and manual positioning.</div>
                        <div class="dxm-card" @click="loadTemplate('scripture')"><strong>Elegant Scripture</strong><small>Clean verse card with reference and soft background.</small></div>
                        <div class="dxm-card" @click="loadTemplate('quote')"><strong>Bold Gradient Quote</strong><small>Strong centered quote layout with source line.</small></div>
                        <div class="dxm-card" @click="loadTemplate('flyer')"><strong>Ministry Event Flyer</strong><small>Flyer layout with headline, date and call-to-action.</small></div>
                    </div>

                    <div x-show="activeTool==='text'">
                        <div class="dxm-grid2">
                            <button class="dxm-mini" type="button" @click="addText('Heading')">Heading</button>
                            <button class="dxm-mini" type="button" @click="addText('Body text')">Body</button>
                            <button class="dxm-mini" type="button" @click="addText('Scripture Reference')">Reference</button>
                            <button class="dxm-mini" type="button" @click="addText('Source')">Source</button>
                        </div>
                        <div class="dxm-note">After adding text, drag it directly on the canvas. Use the right panel to change color, size, alignment and font weight.</div>
                    </div>

                    <div x-show="activeTool==='image'">
                        <div class="dxm-field"><label>Image URL</label><input x-model="assetUrl" placeholder="https://...png or jpg"></div>
                        <button class="dxm-btn primary" type="button" @click="addImageFromUrl()">Add Image</button>
                        <a class="dxm-btn" href="{{ url('/admin/media-library') }}" style="margin-left:6px">Open Library</a>
                        <div class="dxm-note">Media Library picker connection comes next. For now, paste an image URL or open the library.</div>
                    </div>

                    <div x-show="activeTool==='shapes'">
                        <div class="dxm-grid2">
                            <button class="dxm-mini" type="button" @click="addRect(false)">Filled Box</button>
                            <button class="dxm-mini" type="button" @click="addRect(true)">Outline Box</button>
                            <button class="dxm-mini" type="button" @click="addCircle(false)">Filled Circle</button>
                            <button class="dxm-mini" type="button" @click="addCircle(true)">Outline Circle</button>
                            <button class="dxm-mini" type="button" @click="addLine()">Line</button>
                            <button class="dxm-mini" type="button" @click="addOverlay()">Overlay</button>
                        </div>
                    </div>

                    <div x-show="activeTool==='icons'">
                        <div class="dxm-grid2">
                            <button class="dxm-mini" type="button" @click="addIcon('✦')">Star</button>
                            <button class="dxm-mini" type="button" @click="addIcon('♥')">Heart</button>
                            <button class="dxm-mini" type="button" @click="addIcon('✚')">Cross</button>
                            <button class="dxm-mini" type="button" @click="addIcon('➜')">Arrow</button>
                        </div>
                        <a class="dxm-btn" href="{{ url('/admin/icon-library') }}" style="margin-top:10px;display:inline-flex">Open Icon Library</a>
                    </div>

                    <div x-show="activeTool==='background'">
                        <div class="dxm-field"><label>Background Mode</label><select x-model="bg.mode" @change="applyBackground()"><option value="solid">Solid</option><option value="gradient">Gradient</option></select></div>
                        <div class="dxm-row">
                            <div class="dxm-field"><label>Color 1</label><input type="color" x-model="bg.color1" @input="applyBackground()"></div>
                            <div class="dxm-field"><label>Color 2</label><input type="color" x-model="bg.color2" @input="applyBackground()"></div>
                        </div>
                        <div class="dxm-grid2">
                            <button class="dxm-mini" type="button" @click="setCanvasSize(1080,1080)">Square</button>
                            <button class="dxm-mini" type="button" @click="setCanvasSize(1080,1920)">Story</button>
                            <button class="dxm-mini" type="button" @click="setCanvasSize(1600,900)">Banner</button>
                            <button class="dxm-mini" type="button" @click="setCanvasSize(1080,1350)">Portrait</button>
                        </div>
                    </div>

                    <div x-show="activeTool==='select'">
                        <div class="dxm-note">Click any object on the canvas, drag it freely, resize from the handles, then edit properties in the right inspector.</div>
                    </div>
                </div>
            </aside>

            <main class="dxm-center">
                <div class="dxm-canvas-bar">
                    <button class="dxm-btn" type="button" @click="zoomOut()">−</button>
                    <span style="font-size:12px;font-weight:950;color:var(--dxm-muted)" x-text="Math.round(zoom*100)+'%'"></span>
                    <button class="dxm-btn" type="button" @click="zoomIn()">+</button>
                    <button class="dxm-btn" type="button" @click="fitZoom()">Fit</button>
                    <button class="dxm-btn" type="button" @click="deleteSelected()">Delete</button>
                    <button class="dxm-btn" type="button" @click="duplicateSelected()">Duplicate</button>
                </div>
                <div class="dxm-canvas-wrap">
                    <div class="dxm-canvas-shell" :style="'width:'+displayWidth+'px;height:'+displayHeight+'px'">
                        <canvas id="dxmFabricCanvas"></canvas>
                    </div>
                </div>
            </main>

            <aside class="dxm-right">
                <div class="dxm-panel-title">
                    <h2>Inspector</h2>
                    <p x-text="selected ? 'Editing selected object' : 'Select an object on the canvas'"></p>
                </div>

                <div class="dxm-tabs">
                    <button class="dxm-tab" :class="{active:rightTab==='object'}" @click="rightTab='object'">Object</button>
                    <button class="dxm-tab" :class="{active:rightTab==='style'}" @click="rightTab='style'">Style</button>
                    <button class="dxm-tab" :class="{active:rightTab==='layers'}" @click="rightTab='layers'">Layers</button>
                    <button class="dxm-tab" :class="{active:rightTab==='recent'}" @click="rightTab='recent'">Recent</button>
                </div>

                <div class="dxm-inspector">
                    <div x-show="rightTab==='object'">
                        <template x-if="selected">
                            <div>
                                <div class="dxm-field"><label>Text / Label</label><textarea x-model="props.text" @input="updateSelected()"></textarea></div>
                                <div class="dxm-row">
                                    <div class="dxm-field"><label>X</label><input type="number" x-model.number="props.left" @input="updateSelected()"></div>
                                    <div class="dxm-field"><label>Y</label><input type="number" x-model.number="props.top" @input="updateSelected()"></div>
                                </div>
                                <div class="dxm-row">
                                    <div class="dxm-field"><label>Width</label><input type="number" x-model.number="props.width" @input="updateSelected()"></div>
                                    <div class="dxm-field"><label>Height</label><input type="number" x-model.number="props.height" @input="updateSelected()"></div>
                                </div>
                                <div class="dxm-field"><label>Angle</label><input type="range" min="-180" max="180" x-model.number="props.angle" @input="updateSelected()"></div>
                            </div>
                        </template>
                        <div class="dxm-note" x-show="!selected">Select a text, image, shape or line to edit its properties.</div>
                    </div>

                    <div x-show="rightTab==='style'">
                        <template x-if="selected">
                            <div>
                                <div class="dxm-row">
                                    <div class="dxm-field"><label>Fill / Text Color</label><input type="color" x-model="props.fill" @input="updateSelected()"></div>
                                    <div class="dxm-field"><label>Stroke</label><input type="color" x-model="props.stroke" @input="updateSelected()"></div>
                                </div>
                                <div class="dxm-row">
                                    <div class="dxm-field"><label>Stroke Width</label><input type="number" min="0" max="30" x-model.number="props.strokeWidth" @input="updateSelected()"></div>
                                    <div class="dxm-field"><label>Opacity</label><input type="range" min="0" max="1" step=".05" x-model.number="props.opacity" @input="updateSelected()"></div>
                                </div>
                                <div class="dxm-row">
                                    <div class="dxm-field"><label>Font Size</label><input type="number" min="8" max="220" x-model.number="props.fontSize" @input="updateSelected()"></div>
                                    <div class="dxm-field"><label>Weight</label><select x-model="props.fontWeight" @change="updateSelected()"><option value="400">Normal</option><option value="600">Semi Bold</option><option value="700">Bold</option><option value="800">Extra Bold</option><option value="900">Black</option></select></div>
                                </div>
                                <div class="dxm-field"><label>Align</label><select x-model="props.textAlign" @change="updateSelected()"><option value="left">Left</option><option value="center">Center</option><option value="right">Right</option></select></div>
                                <div class="dxm-field"><label>Shadow</label><select x-model="props.shadowMode" @change="updateSelected()"><option value="off">Off</option><option value="soft">Soft</option><option value="strong">Strong</option></select></div>
                            </div>
                        </template>
                    </div>

                    <div x-show="rightTab==='layers'">
                        <template x-for="(layer,index) in layerList" :key="layer.id || index">
                            <div class="dxm-layer" :class="{active: layer.active}" @click="selectLayer(index)">
                                <div>▱</div>
                                <div>
                                    <div class="dxm-layer-name" x-text="layer.name"></div>
                                    <small x-text="layer.type"></small>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="rightTab==='recent'">
                        @forelse($recentDesigns as $design)
                            <div class="dxm-layer">
                                <div>💾</div>
                                <div>
                                    <div class="dxm-layer-name">{{ $design['title'] }}</div>
                                    <small>{{ $design['type'] }} • {{ $design['layers'] }} layers • {{ $design['created_at'] }}</small>
                                </div>
                            </div>
                        @empty
                            <div class="dxm-note">No saved designs yet.</div>
                        @endforelse
                    </div>
                </div>
            </aside>
        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fabric@5.3.0/dist/fabric.min.js"></script>
    <script>
        function dxmFabricStudio(config){
            return {
                canvas:null,
                zoom:.52,
                displayWidth:562,
                displayHeight:562,
                activeTool:'templates',
                rightTab:'object',
                selected:null,
                layerList:[],
                assetUrl:'',
                project:{title:'Untitled Design',type:'daily_quote',status:'draft',isTemplate:false},
                bg:{mode:'gradient',color1:'#280061',color2:'#e4007c'},
                props:{},
                boot(){
                    this.canvas=new fabric.Canvas('dxmFabricCanvas',{width:1080,height:1080,preserveObjectStacking:true,selection:true});
                    this.canvas.on('selection:created',()=>this.syncSelection());
                    this.canvas.on('selection:updated',()=>this.syncSelection());
                    this.canvas.on('selection:cleared',()=>this.syncSelection());
                    this.canvas.on('object:modified',()=>{this.syncSelection();this.refreshLayers();});
                    this.canvas.on('object:moving',()=>this.syncSelection());
                    this.canvas.on('object:scaling',()=>this.syncSelection());
                    this.loadTemplate('quote');
                    this.fitZoom();
                },
                toolTitle(){return {select:'Select / Move',text:'Text Tools',image:'Images',shapes:'Shapes',icons:'Icons',background:'Background',templates:'Templates'}[this.activeTool] || 'Tools'},
                toolSubtitle(){return {select:'Drag objects freely on canvas.',text:'Add and style text layers.',image:'Add PNG/JPG assets.',shapes:'Filled, outline and line shapes.',icons:'Add clean symbol/icon layers.',background:'Canvas size and background.',templates:'Start from a preset.'}[this.activeTool] || ''},
                fitZoom(){
                    const maxW=window.innerWidth>1300?620:520;
                    this.zoom=Math.min(maxW/this.canvas.getWidth(), .62);
                    this.applyZoom();
                },
                zoomIn(){this.zoom=Math.min(1.2,this.zoom+.08);this.applyZoom()},
                zoomOut(){this.zoom=Math.max(.22,this.zoom-.08);this.applyZoom()},
                applyZoom(){
                    this.displayWidth=Math.round(this.canvas.getWidth()*this.zoom);
                    this.displayHeight=Math.round(this.canvas.getHeight()*this.zoom);
                    this.canvas.setZoom(this.zoom);
                    this.canvas.setDimensions({width:this.displayWidth,height:this.displayHeight},{cssOnly:true});
                    this.canvas.renderAll();
                },
                setCanvasSize(w,h){
                    this.canvas.setWidth(w);this.canvas.setHeight(h);this.applyBackground();this.fitZoom();
                },
                newDesign(){
                    this.project.title='Untitled Design';this.project.type='general';
                    this.canvas.clear();this.setCanvasSize(1080,1080);this.bg={mode:'gradient',color1:'#280061',color2:'#e4007c'};this.applyBackground();this.refreshLayers();
                },
                loadTemplate(key){
                    this.canvas.clear();
                    this.setCanvasSize(key==='flyer'?1080:1080,key==='flyer'?1350:1080);
                    if(key==='scripture'){
                        this.project.title='Elegant Scripture';this.project.type='daily_scripture';this.bg={mode:'solid',color1:'#10172a',color2:'#10172a'};this.applyBackground();
                        this.addText('For I know the thoughts that I think toward you...', {top:360,fontSize:56,width:820});
                        this.addText('Jeremiah 29:11', {top:650,fontSize:28,width:820});
                    } else if(key==='flyer'){
                        this.project.title='Ministry Event Flyer';this.project.type='flyer';this.bg={mode:'gradient',color1:'#07111f',color2:'#4c1d95'};this.applyBackground();
                        this.addText('Special Worship Night', {top:260,fontSize:72,width:850});
                        this.addText('Friday • 6:00 PM', {top:520,fontSize:36,width:850});
                        this.addRect(false,{top:760,left:270,width:540,height:120,fill:'#e4007c'});
                        this.addText('Join Us Live', {top:790,fontSize:42,width:540});
                    } else {
                        this.project.title='Bold Gradient Quote';this.project.type='daily_quote';this.bg={mode:'gradient',color1:'#280061',color2:'#e4007c'};this.applyBackground();
                        this.addText('A life left to chance has no chance.', {top:410,fontSize:62,width:820});
                        this.addText('Seeds of Destiny', {top:650,fontSize:26,width:820});
                    }
                    this.refreshLayers();
                },
                applyBackground(){
                    const bg=this.bg.mode==='solid'?this.bg.color1:new fabric.Gradient({type:'linear',gradientUnits:'pixels',coords:{x1:0,y1:0,x2:this.canvas.getWidth(),y2:this.canvas.getHeight()},colorStops:[{offset:0,color:this.bg.color1},{offset:1,color:this.bg.color2}]});
                    this.canvas.setBackgroundColor(bg,()=>this.canvas.renderAll());
                },
                addText(text, opts={}){
                    const item=new fabric.Textbox(text,{left:opts.left||130,top:opts.top||260,width:opts.width||820,fontSize:opts.fontSize||56,fill:opts.fill||'#ffffff',fontWeight:opts.fontWeight||800,textAlign:opts.textAlign||'center',fontFamily:'Arial',objectCaching:false});
                    item.set('name', text.length>22 ? text.substring(0,22)+'...' : text);
                    this.canvas.add(item);this.canvas.setActiveObject(item);this.syncSelection();this.refreshLayers();
                },
                addImageFromUrl(){
                    const url=(this.assetUrl||'').trim();
                    if(!url){alert('Paste image URL first.');return;}
                    fabric.Image.fromURL(url,(img)=>{
                        img.set({left:160,top:160,scaleX:.45,scaleY:.45,name:'Image'});
                        this.canvas.add(img);this.canvas.setActiveObject(img);this.syncSelection();this.refreshLayers();
                    },{crossOrigin:'anonymous'});
                },
                addRect(outline=false,opts={}){
                    const r=new fabric.Rect({left:opts.left||210,top:opts.top||240,width:opts.width||360,height:opts.height||210,fill:outline?'rgba(0,0,0,0)':(opts.fill||'#22d3ee'),stroke:opts.stroke||'#ffffff',strokeWidth:outline?5:0,rx:24,ry:24,opacity:.9,name:outline?'Outline Box':'Filled Box'});
                    this.canvas.add(r);this.canvas.setActiveObject(r);this.syncSelection();this.refreshLayers();
                },
                addCircle(outline=false){
                    const c=new fabric.Circle({left:260,top:250,radius:110,fill:outline?'rgba(0,0,0,0)':'#8b5cf6',stroke:'#ffffff',strokeWidth:outline?5:0,opacity:.9,name:outline?'Outline Circle':'Filled Circle'});
                    this.canvas.add(c);this.canvas.setActiveObject(c);this.syncSelection();this.refreshLayers();
                },
                addLine(){
                    const l=new fabric.Line([180,400,760,400],{stroke:'#ffffff',strokeWidth:8,name:'Line',strokeLineCap:'round'});
                    this.canvas.add(l);this.canvas.setActiveObject(l);this.syncSelection();this.refreshLayers();
                },
                addOverlay(){
                    const r=new fabric.Rect({left:120,top:280,width:840,height:360,fill:'rgba(0,0,0,.30)',stroke:'rgba(255,255,255,.18)',strokeWidth:1,rx:30,ry:30,name:'Overlay'});
                    this.canvas.add(r);this.canvas.setActiveObject(r);this.syncSelection();this.refreshLayers();
                },
                addIcon(icon){
                    const t=new fabric.Text(icon,{left:470,top:240,fontSize:120,fill:'#ffffff',fontWeight:900,name:'Icon '+icon});
                    this.canvas.add(t);this.canvas.setActiveObject(t);this.syncSelection();this.refreshLayers();
                },
                syncSelection(){
                    const obj=this.canvas.getActiveObject();
                    this.selected=obj||null;
                    if(!obj){this.props={};this.refreshLayers();return;}
                    this.props={text:obj.text||obj.name||'',left:Math.round(obj.left||0),top:Math.round(obj.top||0),width:Math.round((obj.width||0)*(obj.scaleX||1)),height:Math.round((obj.height||0)*(obj.scaleY||1)),angle:Math.round(obj.angle||0),fill:obj.fill && typeof obj.fill==='string' && obj.fill.startsWith('#')?obj.fill:'#ffffff',stroke:obj.stroke && typeof obj.stroke==='string'?obj.stroke:'#ffffff',strokeWidth:obj.strokeWidth||0,opacity:obj.opacity??1,fontSize:obj.fontSize||32,fontWeight:String(obj.fontWeight||700),textAlign:obj.textAlign||'center',shadowMode:obj.shadow?'soft':'off'};
                    this.refreshLayers();
                },
                updateSelected(){
                    const obj=this.canvas.getActiveObject(); if(!obj)return;
                    obj.set({left:Number(this.props.left)||0,top:Number(this.props.top)||0,angle:Number(this.props.angle)||0,opacity:Number(this.props.opacity)});
                    if(obj.type==='textbox' || obj.type==='text'){obj.set({text:this.props.text||'',fill:this.props.fill,fontSize:Number(this.props.fontSize)||32,fontWeight:this.props.fontWeight,textAlign:this.props.textAlign});}
                    else {obj.set({fill:this.props.fill,stroke:this.props.stroke,strokeWidth:Number(this.props.strokeWidth)||0});}
                    if(this.props.width && obj.width){obj.scaleX=Number(this.props.width)/obj.width;}
                    if(this.props.height && obj.height){obj.scaleY=Number(this.props.height)/obj.height;}
                    if(this.props.shadowMode==='off'){obj.set('shadow',null)}else{obj.set('shadow',new fabric.Shadow({color:'rgba(0,0,0,.35)',blur:this.props.shadowMode==='strong'?18:10,offsetX:0,offsetY:this.props.shadowMode==='strong'?5:3}))}
                    obj.setCoords();this.canvas.renderAll();this.refreshLayers();
                },
                deleteSelected(){const obj=this.canvas.getActiveObject();if(obj){this.canvas.remove(obj);this.syncSelection();}},
                duplicateSelected(){const obj=this.canvas.getActiveObject();if(!obj)return;obj.clone((copy)=>{copy.set({left:(obj.left||0)+35,top:(obj.top||0)+35,name:(obj.name||'Layer')+' Copy'});this.canvas.add(copy);this.canvas.setActiveObject(copy);this.syncSelection();this.refreshLayers();});},
                refreshLayers(){
                    const active=this.canvas.getActiveObject();
                    this.layerList=this.canvas.getObjects().map((o,i)=>({id:o.__uid||i,name:o.name||o.text||o.type,type:o.type,active:o===active})).reverse();
                },
                selectLayer(reverseIndex){
                    const objects=this.canvas.getObjects(); const obj=objects[objects.length-1-reverseIndex]; if(obj){this.canvas.setActiveObject(obj);this.canvas.renderAll();this.syncSelection();}
                },
                save(){
                    const fabricJson=this.canvas.toJSON(['name']);
                    const payload={title:this.project.title,type:this.project.type,status:this.project.status,isTemplate:this.project.isTemplate,fabric:{...fabricJson,width:this.canvas.getWidth(),height:this.canvas.getHeight(),background:this.bg},preview:{zoom:this.zoom,objects:this.canvas.getObjects().length}};
                    config.saveDesign(payload);
                }
            }
        }
    </script>
</x-filament::page>
