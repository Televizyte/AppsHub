@php
    $pickerId = $pickerId ?? ('dxmMediaCenter_' . uniqid());
    $inputId = $inputId ?? 'cover_image_url';
    $selectName = $selectName ?? 'media_asset_id';
    $assets = $assets ?? collect();
    $emptyText = $emptyText ?? 'No media assets found for this app yet.';
    $title = $title ?? 'Media Library';
    $subtitle = $subtitle ?? 'Upload, search, preview, and select media for this field.';
    $uploadBucket = $uploadBucket ?? 'media-center';
    $uploadLabel = $uploadLabel ?? 'Media Upload';
    $buttonLabel = $buttonLabel ?? 'Open Media Library';

    $pickerOptions = [];
    $bucketMap = [];

    foreach ($assets as $asset) {
        $assetUrl = $asset->url ?? '';

        if (!$assetUrl && ($asset->path ?? null)) {
            try {
                $assetUrl = \Illuminate\Support\Facades\Storage::disk($asset->disk ?? 'public')->url($asset->path);
            } catch (\Throwable $e) {
                $assetUrl = '';
            }
        }

        if ($assetUrl) {
            $bucket = trim((string) ($asset->bucket ?? 'uncategorized'));
            $bucket = $bucket !== '' ? $bucket : 'uncategorized';
            $bucketMap[$bucket] = $bucket;
            $pickerOptions[] = [
                'id' => $asset->id,
                'url' => $assetUrl,
                'label' => (($asset->bucket ?? null) ? '[' . $asset->bucket . '] ' : '') . (($asset->label ?? null) ?: ('Asset #' . $asset->id)),
                'plain_label' => (($asset->label ?? null) ?: ('Asset #' . $asset->id)),
                'bucket' => $bucket,
                'mime' => $asset->mime ?? '',
                'size' => $asset->size ?? '',
                'width' => $asset->width ?? '',
                'height' => $asset->height ?? '',
            ];
        }
    }

    ksort($bucketMap);
@endphp

<div class="dxm-media-center"
     id="{{ $pickerId }}"
     data-target-input="{{ $inputId }}"
     data-upload-bucket="{{ $uploadBucket }}"
     data-upload-label="{{ $uploadLabel }}"
     data-upload-url="{{ route('admin.beginner.media-center.upload') }}">
    <input type="hidden" name="{{ $selectName }}" data-media-asset-id value="">

    <div class="dxm-media-center__inline">
        <div class="dxm-media-center__copy">
            <strong>{{ $title }}</strong>
            <span>{{ $subtitle }}</span>
        </div>
        <div class="dxm-media-center__actions">
            <button type="button" class="dxm-media-center__button" data-media-open>{{ $buttonLabel }}</button>
            <button type="button" class="dxm-media-center__button dxm-media-center__button--ghost" data-media-clear>Clear</button>
        </div>
    </div>

    <div class="dxm-media-center__selected" data-selected-preview>
        <div class="dxm-media-center__selected-thumb" data-selected-thumb><span>No image</span></div>
        <div class="dxm-media-center__selected-meta">
            <strong data-selected-title>No image selected</strong>
            <span data-selected-url>Select from library, upload new media, or paste URL manually.</span>
        </div>
    </div>

    <div class="dxm-media-center__modal" data-media-modal aria-hidden="true">
        <div class="dxm-media-center__backdrop" data-media-close></div>
        <div class="dxm-media-center__dialog" role="dialog" aria-modal="true" aria-label="{{ $title }}">
            <div class="dxm-media-center__modal-head">
                <div>
                    <strong>{{ $title }}</strong>
                    <span>Choose an existing image or upload a new one for the active app.</span>
                </div>
                <button type="button" class="dxm-media-center__close" data-media-close>×</button>
            </div>

            <div class="dxm-media-center__modal-body">
                <aside class="dxm-media-center__side">
                    <div class="dxm-media-center__upload-card">
                        <strong>Upload New Image</strong>
                        <span>JPG, PNG, WEBP. Maximum 8MB.</span>

                        {{-- IMPORTANT: This is intentionally NOT a form. MediaCenter is used inside parent save forms. --}}
                        <div data-media-upload-form>
                            <input type="hidden" data-media-csrf value="{{ csrf_token() }}">
                            <input type="hidden" data-media-bucket value="{{ $uploadBucket }}">
                            <input type="hidden" data-media-label value="{{ $uploadLabel }}">

                            <label class="dxm-media-center__drop">
                                <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-media-upload-input>
                                <span data-upload-placeholder>Click to choose image</span>
                            </label>

                            <button type="button" class="dxm-media-center__upload-btn" data-media-upload-button>Upload & Use</button>
                        </div>

                        <div class="dxm-media-center__upload-status" data-upload-status></div>
                    </div>

                    <div class="dxm-media-center__preview-card">
                        <strong>Selected Preview</strong>
                        <div class="dxm-media-center__large-preview" data-large-preview><span>No image selected</span></div>
                        <div class="dxm-media-center__large-meta">
                            <span data-large-title>—</span>
                            <small data-large-details>—</small>
                        </div>
                    </div>
                </aside>

                <section class="dxm-media-center__library">
                    <div class="dxm-media-center__filters">
                        <input type="text" data-media-search placeholder="Search by label, bucket, URL, size...">
                        <div class="dxm-media-center__bucket-row" data-bucket-row>
                            <button type="button" class="is-active" data-bucket-filter="all">All</button>
                            <button type="button" data-bucket-filter="recent">Recent</button>
                            @foreach($bucketMap as $bucket)
                                <button type="button" data-bucket-filter="{{ strtolower($bucket) }}">{{ ucwords(str_replace(['-', '_'], ' ', $bucket)) }}</button>
                            @endforeach
                        </div>
                    </div>

                    <div class="dxm-media-center__grid" data-media-grid>
                        @forelse($pickerOptions as $asset)
                            <button type="button"
                                    class="dxm-media-center__tile"
                                    data-media-tile
                                    data-asset-id="{{ $asset['id'] }}"
                                    data-image-url="{{ $asset['url'] }}"
                                    data-label="{{ $asset['plain_label'] }}"
                                    data-bucket="{{ strtolower($asset['bucket']) }}"
                                    data-width="{{ $asset['width'] }}"
                                    data-height="{{ $asset['height'] }}"
                                    data-size="{{ $asset['size'] }}"
                                    data-search="{{ strtolower($asset['label'] . ' ' . $asset['bucket'] . ' ' . $asset['url'] . ' ' . $asset['mime']) }}">
                                <span class="dxm-media-center__tile-img" style="background-image:url('{{ $asset['url'] }}')">
                                    <img src="{{ $asset['url'] }}" alt="" loading="lazy">
                                </span>
                                <span class="dxm-media-center__tile-copy">
                                    <strong>{{ $asset['plain_label'] }}</strong>
                                    <small>{{ $asset['bucket'] }}</small>
                                </span>
                            </button>
                        @empty
                            <div class="dxm-media-center__empty" data-media-empty>{{ $emptyText }}</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="dxm-media-center__modal-foot">
                <button type="button" class="dxm-media-center__button dxm-media-center__button--ghost" data-copy-url>Copy URL</button>
                <button type="button" class="dxm-media-center__button dxm-media-center__button--ghost" data-media-close>Cancel</button>
                <button type="button" class="dxm-media-center__button dxm-media-center__button--primary" data-use-selected>Use Selected Image</button>
            </div>
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>
            .dxm-media-center{border:1px solid rgba(255,255,255,.10);border-radius:18px;background:rgba(2,6,23,.28);padding:12px}.dxm-media-center__inline{display:flex;align-items:center;justify-content:space-between;gap:12px}.dxm-media-center__copy strong{display:block;color:#fff;font-size:13px}.dxm-media-center__copy span{display:block;color:rgba(255,255,255,.54);font-size:11.5px;line-height:1.4;margin-top:3px}.dxm-media-center__actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}.dxm-media-center__button{border:1px solid rgba(34,211,238,.36);background:rgba(34,211,238,.13);color:#fff;border-radius:12px;min-height:38px;padding:9px 12px;font-size:12px;font-weight:900;cursor:pointer}.dxm-media-center__button:hover{border-color:rgba(34,211,238,.70);background:rgba(34,211,238,.20)}.dxm-media-center__button--primary{background:linear-gradient(135deg,rgba(34,211,238,.34),rgba(59,130,246,.24));border-color:rgba(34,211,238,.58)}.dxm-media-center__button--ghost{background:rgba(255,255,255,.045);border-color:rgba(255,255,255,.10)}.dxm-media-center__selected{display:grid;grid-template-columns:92px minmax(0,1fr);gap:12px;margin-top:12px;padding:10px;border:1px solid rgba(255,255,255,.08);border-radius:16px;background:rgba(255,255,255,.035)}.dxm-media-center__selected-thumb{width:92px;height:62px;border-radius:12px;background:#020617;overflow:hidden;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.45);font-size:10px;border:1px solid rgba(255,255,255,.08)}.dxm-media-center__selected-thumb img{max-width:100%!important;max-height:100%!important;width:auto!important;height:auto!important;object-fit:contain!important;display:block!important}.dxm-media-center__selected-meta{min-width:0;align-self:center}.dxm-media-center__selected-meta strong{display:block;color:#fff;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.dxm-media-center__selected-meta span{display:block;margin-top:4px;color:rgba(255,255,255,.50);font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
            .dxm-media-center__modal{position:fixed;inset:0;z-index:9999;display:none}.dxm-media-center__modal.is-open{display:block}.dxm-media-center__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.72);backdrop-filter:blur(10px)}.dxm-media-center__dialog{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:min(1180px,calc(100vw - 24px));height:min(780px,calc(100vh - 24px));border:1px solid rgba(255,255,255,.12);border-radius:24px;background:linear-gradient(180deg,#0f172a,#020617);box-shadow:0 28px 100px rgba(0,0,0,.58);display:flex;flex-direction:column;overflow:hidden}.dxm-media-center__modal-head,.dxm-media-center__modal-foot{flex:0 0 auto;padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.10);display:flex;align-items:center;justify-content:space-between;gap:12px}.dxm-media-center__modal-foot{border-bottom:0;border-top:1px solid rgba(255,255,255,.10);justify-content:flex-end;background:rgba(2,6,23,.72)}.dxm-media-center__modal-head strong{display:block;color:#fff;font-size:16px}.dxm-media-center__modal-head span{display:block;color:rgba(255,255,255,.58);font-size:12px;margin-top:4px}.dxm-media-center__close{width:38px;height:38px;border-radius:999px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#fff;font-size:24px;line-height:1;cursor:pointer}.dxm-media-center__modal-body{flex:1 1 auto;min-height:0;display:grid;grid-template-columns:300px minmax(0,1fr)}.dxm-media-center__side{min-height:0;overflow:auto;padding:14px;border-right:1px solid rgba(255,255,255,.10);background:rgba(2,6,23,.44)}.dxm-media-center__library{min-height:0;display:flex;flex-direction:column;padding:14px;overflow:hidden}.dxm-media-center__upload-card,.dxm-media-center__preview-card{border:1px solid rgba(255,255,255,.10);border-radius:18px;background:rgba(255,255,255,.045);padding:12px;margin-bottom:12px}.dxm-media-center__upload-card strong,.dxm-media-center__preview-card strong{display:block;color:#fff;font-size:13px}.dxm-media-center__upload-card span{display:block;color:rgba(255,255,255,.52);font-size:11.5px;line-height:1.4;margin-top:4px}.dxm-media-center__drop{display:block;margin-top:12px;border:1px dashed rgba(34,211,238,.34);border-radius:16px;background:rgba(34,211,238,.07);padding:16px;text-align:center;color:rgba(255,255,255,.72);font-size:12px;font-weight:800;cursor:pointer}.dxm-media-center__drop input{display:none}.dxm-media-center__upload-btn{width:100%;margin-top:10px;border:1px solid rgba(34,211,238,.42);background:rgba(34,211,238,.14);color:#fff;border-radius:12px;min-height:40px;font-size:12px;font-weight:900;cursor:pointer}.dxm-media-center__upload-status{margin-top:10px;color:rgba(255,255,255,.60);font-size:11.5px;line-height:1.4}
            .dxm-media-center__large-preview{width:100%;height:190px;border-radius:16px;background:#020617;display:flex;align-items:center;justify-content:center;overflow:hidden;color:rgba(255,255,255,.45);font-size:12px;border:1px solid rgba(255,255,255,.08)}.dxm-media-center__large-preview img{max-width:100%!important;max-height:100%!important;width:auto!important;height:auto!important;object-fit:contain!important;display:block!important}.dxm-media-center__large-meta span{display:block;margin-top:10px;color:#fff;font-size:12px;font-weight:900;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.dxm-media-center__large-meta small{display:block;margin-top:4px;color:rgba(255,255,255,.50);font-size:11px;line-height:1.4;word-break:break-word}.dxm-media-center__filters{flex:0 0 auto}.dxm-media-center__filters input{width:100%;border-radius:14px;border:1px solid rgba(255,255,255,.10);background:#020617;color:#fff;padding:12px 13px;outline:none}.dxm-media-center__bucket-row{display:flex;gap:8px;overflow:auto;scrollbar-width:none;margin-top:10px;padding-bottom:2px}.dxm-media-center__bucket-row::-webkit-scrollbar{display:none}.dxm-media-center__bucket-row button{border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.045);color:rgba(255,255,255,.68);border-radius:999px;min-height:32px;padding:7px 11px;font-size:11px;font-weight:900;white-space:nowrap;cursor:pointer}.dxm-media-center__bucket-row button.is-active{border-color:rgba(34,211,238,.55);background:rgba(34,211,238,.14);color:#fff}.dxm-media-center__grid{flex:1 1 auto;min-height:0;overflow:auto;display:grid!important;grid-template-columns:repeat(auto-fill,minmax(170px,1fr))!important;gap:14px!important;margin-top:14px;padding-right:6px;align-content:start!important}.dxm-media-center__tile{min-width:0!important;height:auto!important;min-height:168px!important;border:1px solid rgba(255,255,255,.10)!important;border-radius:16px!important;background:rgba(255,255,255,.045)!important;padding:8px!important;color:#fff!important;text-align:left!important;cursor:pointer!important;overflow:hidden!important;display:flex!important;flex-direction:column!important;gap:8px!important;align-items:stretch!important;justify-content:flex-start!important}.dxm-media-center__tile:hover,.dxm-media-center__tile.is-selected{border-color:rgba(34,211,238,.72)!important;box-shadow:0 0 0 2px rgba(34,211,238,.16)!important}.dxm-media-center__tile.is-hidden{display:none!important}.dxm-media-center__tile-img{width:100%!important;height:112px!important;min-height:112px!important;border-radius:12px!important;background-color:#020617!important;background-repeat:no-repeat!important;background-position:center center!important;background-size:contain!important;display:flex!important;align-items:center!important;justify-content:center!important;overflow:hidden!important;border:1px solid rgba(255,255,255,.08)!important}.dxm-media-center__tile-img img{max-width:100%!important;max-height:100%!important;width:auto!important;height:auto!important;object-fit:contain!important;display:block!important}.dxm-media-center__tile-copy{display:block!important;min-width:0!important}.dxm-media-center__tile-copy strong{display:block!important;color:#fff!important;font-size:11.5px!important;line-height:1.25!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important}.dxm-media-center__tile-copy small{display:block!important;color:rgba(255,255,255,.50)!important;font-size:10.5px!important;margin-top:3px!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important}.dxm-media-center__empty{grid-column:1/-1;border:1px dashed rgba(255,255,255,.18);border-radius:18px;padding:20px;text-align:center;color:rgba(255,255,255,.54)}
            @media(max-width:980px){.dxm-media-center__modal-body{grid-template-columns:1fr}.dxm-media-center__side{display:grid;grid-template-columns:1fr 1fr;gap:12px;border-right:0;border-bottom:1px solid rgba(255,255,255,.10)}.dxm-media-center__upload-card,.dxm-media-center__preview-card{margin-bottom:0}.dxm-media-center__grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr))!important}}@media(max-width:720px){.dxm-media-center__inline{display:block}.dxm-media-center__actions{margin-top:10px;justify-content:stretch}.dxm-media-center__button{width:100%}.dxm-media-center__dialog{width:calc(100vw - 10px);height:calc(100vh - 10px);border-radius:18px}.dxm-media-center__side{grid-template-columns:1fr}.dxm-media-center__grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}.dxm-media-center__modal-foot{display:grid;grid-template-columns:1fr}}
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                function esc(value){return String(value||'').replace(/[&<>"']/g,function(m){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m];});}
                function csrfToken(uploadBox){const local=uploadBox?uploadBox.querySelector('[data-media-csrf]'):null;if(local&&local.value)return local.value;const token=document.querySelector('input[name="_token"]');if(token)return token.value;const meta=document.querySelector('meta[name="csrf-token"]');return meta?meta.getAttribute('content'):'';}
                document.querySelectorAll('.dxm-media-center').forEach(function(picker){
                    const targetInput=document.getElementById(picker.getAttribute('data-target-input'));const hiddenAssetInput=picker.querySelector('[data-media-asset-id]');const modal=picker.querySelector('[data-media-modal]');const openButtons=picker.querySelectorAll('[data-media-open]');const closeButtons=picker.querySelectorAll('[data-media-close]');const clearButton=picker.querySelector('[data-media-clear]');const search=picker.querySelector('[data-media-search]');const bucketButtons=picker.querySelectorAll('[data-bucket-filter]');const grid=picker.querySelector('[data-media-grid]');const uploadBox=picker.querySelector('[data-media-upload-form]');const uploadButton=picker.querySelector('[data-media-upload-button]');const uploadInput=picker.querySelector('[data-media-upload-input]');const uploadPlaceholder=picker.querySelector('[data-upload-placeholder]');const uploadStatus=picker.querySelector('[data-upload-status]');const useSelectedButton=picker.querySelector('[data-use-selected]');const copyUrlButton=picker.querySelector('[data-copy-url]');const selectedThumb=picker.querySelector('[data-selected-thumb]');const selectedTitle=picker.querySelector('[data-selected-title]');const selectedUrl=picker.querySelector('[data-selected-url]');const largePreview=picker.querySelector('[data-large-preview]');const largeTitle=picker.querySelector('[data-large-title]');const largeDetails=picker.querySelector('[data-large-details]');
                    let selected={id:'',url:targetInput?(targetInput.value||'').trim():'',label:'',bucket:'',width:'',height:'',size:''};let activeBucket='all';
                    function tiles(){return Array.from(picker.querySelectorAll('[data-media-tile]'));}
                    function openModal(){modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');document.body.style.overflow='hidden';}
                    function closeModal(){modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');document.body.style.overflow='';}
                    function formatBytes(value){const size=parseInt(value||'0',10);if(!size)return '';if(size<1024)return size+' B';if(size<1024*1024)return Math.round(size/1024)+' KB';return(size/1024/1024).toFixed(1)+' MB';}
                    function normalizedLabel(){if(selected.label)return selected.label;if(selected.url)return 'Selected Image';return 'No image selected';}
                    function setTarget(url,assetId){if(targetInput){targetInput.value=url||'';targetInput.dispatchEvent(new Event('input',{bubbles:true}));targetInput.dispatchEvent(new Event('change',{bubbles:true}));}if(hiddenAssetInput){hiddenAssetInput.value=assetId||'';hiddenAssetInput.dispatchEvent(new Event('input',{bubbles:true}));hiddenAssetInput.dispatchEvent(new Event('change',{bubbles:true}));}}
                    function imageHtml(url){return '<img src="'+esc(url)+'" alt="" onerror="this.style.display=\'none\'">';}
                    function updateSelectedPreview(){const url=selected.url||'';if(url){selectedThumb.innerHTML=imageHtml(url);largePreview.innerHTML=imageHtml(url);selectedTitle.textContent=normalizedLabel();selectedUrl.textContent=url;largeTitle.textContent=normalizedLabel();const detailParts=[];if(selected.bucket)detailParts.push('Bucket: '+selected.bucket);if(selected.width&&selected.height)detailParts.push(selected.width+'×'+selected.height);const bytes=formatBytes(selected.size);if(bytes)detailParts.push(bytes);largeDetails.textContent=detailParts.length?detailParts.join(' • '):url;}else{selectedThumb.innerHTML='<span>No image</span>';largePreview.innerHTML='<span>No image selected</span>';selectedTitle.textContent='No image selected';selectedUrl.textContent='Select from library, upload new media, or paste URL manually.';largeTitle.textContent='—';largeDetails.textContent='—';}tiles().forEach(function(tile){tile.classList.toggle('is-selected',selected.id&&tile.getAttribute('data-asset-id')===String(selected.id));});}
                    function selectAsset(data,applyNow){selected={id:data.id||'',url:data.url||'',label:data.label||'Selected Image',bucket:data.bucket||'',width:data.width||'',height:data.height||'',size:data.size||''};updateSelectedPreview();if(applyNow)setTarget(selected.url,selected.id);}
                    function selectTile(tile,applyNow){selectAsset({id:tile.getAttribute('data-asset-id')||'',url:tile.getAttribute('data-image-url')||'',label:tile.getAttribute('data-label')||'',bucket:tile.getAttribute('data-bucket')||'',width:tile.getAttribute('data-width')||'',height:tile.getAttribute('data-height')||'',size:tile.getAttribute('data-size')||''},applyNow);}
                    function applyFilters(){const query=search?search.value.toLowerCase().trim():'';tiles().forEach(function(tile,index){const haystack=tile.getAttribute('data-search')||'';const bucket=tile.getAttribute('data-bucket')||'';const matchesSearch=!query||haystack.includes(query);const matchesBucket=activeBucket==='all'||(activeBucket==='recent'&&index<24)||bucket===activeBucket;tile.classList.toggle('is-hidden',!(matchesSearch&&matchesBucket));});}
                    function addTile(asset){if(!grid||!asset||!asset.url)return;const empty=grid.querySelector('[data-media-empty]');if(empty)empty.remove();const tile=document.createElement('button');tile.type='button';tile.className='dxm-media-center__tile';tile.setAttribute('data-media-tile','');tile.setAttribute('data-asset-id',asset.id||'');tile.setAttribute('data-image-url',asset.url||'');tile.setAttribute('data-label',asset.label||'Uploaded Image');tile.setAttribute('data-bucket',(asset.bucket||'media-center').toLowerCase());tile.setAttribute('data-width',asset.width||'');tile.setAttribute('data-height',asset.height||'');tile.setAttribute('data-size',asset.size||'');tile.setAttribute('data-search',String((asset.label||'')+' '+(asset.bucket||'')+' '+(asset.url||'')).toLowerCase());tile.innerHTML='<span class="dxm-media-center__tile-img" style="background-image:url(\''+esc(asset.url)+'\')">'+imageHtml(asset.url)+'</span><span class="dxm-media-center__tile-copy"><strong>'+esc(asset.label||'Uploaded Image')+'</strong><small>'+esc(asset.bucket||'media-center')+'</small></span>';tile.addEventListener('click',function(){selectTile(tile,false);});grid.prepend(tile);selectTile(tile,true);applyFilters();}
                    function uploadAndUse(){const uploadUrl=picker.getAttribute('data-upload-url');const file=uploadInput&&uploadInput.files?uploadInput.files[0]:null;if(!file){uploadStatus.textContent='Choose an image first.';return;}const formData=new FormData();formData.append('image_file',file);formData.append('bucket',uploadBox.querySelector('[data-media-bucket]')?.value||picker.getAttribute('data-upload-bucket')||'media-center');formData.append('label',uploadBox.querySelector('[data-media-label]')?.value||picker.getAttribute('data-upload-label')||'Media Upload');uploadStatus.textContent='Uploading...';if(uploadButton){uploadButton.disabled=true;uploadButton.textContent='Uploading...';}fetch(uploadUrl,{method:'POST',headers:{'X-CSRF-TOKEN':csrfToken(uploadBox),'Accept':'application/json'},body:formData}).then(function(response){return response.json().then(function(data){if(!response.ok)throw new Error(data.message||'Upload failed.');return data;});}).then(function(data){if(!data.ok||!data.asset)throw new Error(data.message||'Upload failed.');uploadStatus.textContent='Uploaded successfully.';if(uploadInput)uploadInput.value='';if(uploadPlaceholder)uploadPlaceholder.textContent='Click to choose image';addTile(data.asset);}).catch(function(error){uploadStatus.textContent=error.message||'Upload failed.';}).finally(function(){if(uploadButton){uploadButton.disabled=false;uploadButton.textContent='Upload & Use';}});}
                    openButtons.forEach(function(button){button.addEventListener('click',openModal);});closeButtons.forEach(function(button){button.addEventListener('click',closeModal);});document.addEventListener('keydown',function(event){if(event.key==='Escape'&&modal.classList.contains('is-open'))closeModal();});tiles().forEach(function(tile){tile.addEventListener('click',function(){selectTile(tile,false);});});if(search)search.addEventListener('input',applyFilters);bucketButtons.forEach(function(button){button.addEventListener('click',function(){activeBucket=button.getAttribute('data-bucket-filter')||'all';bucketButtons.forEach(function(b){b.classList.toggle('is-active',b===button);});applyFilters();});});if(useSelectedButton)useSelectedButton.addEventListener('click',function(){if(selected.url)setTarget(selected.url,selected.id);closeModal();});if(copyUrlButton)copyUrlButton.addEventListener('click',function(){if(!selected.url)return;if(navigator.clipboard)navigator.clipboard.writeText(selected.url);});if(clearButton)clearButton.addEventListener('click',function(){selected={id:'',url:'',label:'',bucket:'',width:'',height:'',size:''};setTarget('','');updateSelectedPreview();});if(targetInput)targetInput.addEventListener('input',function(){const manual=(targetInput.value||'').trim();if(!manual){selected={id:'',url:'',label:'',bucket:'',width:'',height:'',size:''};updateSelectedPreview();return;}if(manual===selected.url)return;selected={id:'',url:manual,label:'Manual URL / Path',bucket:'manual',width:'',height:'',size:''};updateSelectedPreview();});if(uploadInput&&uploadPlaceholder)uploadInput.addEventListener('change',function(){const file=uploadInput.files&&uploadInput.files[0];uploadPlaceholder.textContent=file?file.name:'Click to choose image';});if(uploadButton)uploadButton.addEventListener('click',uploadAndUse);if(selected.url){selected.label='Current Image';}else{selected={id:'',url:'',label:'',bucket:'',width:'',height:'',size:''};}updateSelectedPreview();applyFilters();
                });
            });
        </script>
    @endpush
@endonce

@once
    @push('styles')
        <style>
            @media (max-width: 700px) {
                .dxm-media-center__modal,
                .dxm-media-center__dialog,
                [data-media-modal],
                [data-media-dialog] {
                    width: 96vw !important;
                    max-width: 96vw !important;
                    left: 2vw !important;
                    right: 2vw !important;
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                }

                .dxm-media-center__grid,
                [data-media-grid] {
                    display: grid !important;
                    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                    gap: 10px !important;
                }

                .dxm-media-center__thumb,
                .dxm-media-center__asset,
                [data-media-card] {
                    min-height: 130px !important;
                }

                .dxm-media-center__thumb img,
                .dxm-media-center__asset img,
                [data-media-card] img {
                    width: 100% !important;
                    height: 118px !important;
                    object-fit: cover !important;
                }
            }
        </style>
    @endpush
@endonce
