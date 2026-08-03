@php
    $dxmPalettes = [
        ['preset'=>'royal', 'bg'=>'#160042', 'bg2'=>'#e2388a', 'text'=>'#ffffff', 'source'=>'#d8b4fe', 'highlight'=>'#facc15', 'accent'=>'#38bdf8'],
        ['preset'=>'ocean', 'bg'=>'#061b3a', 'bg2'=>'#0ea5e9', 'text'=>'#ffffff', 'source'=>'#bae6fd', 'highlight'=>'#67e8f9', 'accent'=>'#67e8f9'],
        ['preset'=>'fire', 'bg'=>'#450a0a', 'bg2'=>'#f97316', 'text'=>'#fff7ed', 'source'=>'#fed7aa', 'highlight'=>'#fde047', 'accent'=>'#facc15'],
        ['preset'=>'soft', 'bg'=>'#fdf2f8', 'bg2'=>'#dbeafe', 'text'=>'#111827', 'source'=>'#334155', 'highlight'=>'#facc15', 'accent'=>'#2563eb'],
        ['preset'=>'gold', 'bg'=>'#171717', 'bg2'=>'#a16207', 'text'=>'#fef3c7', 'source'=>'#fde68a', 'highlight'=>'#facc15', 'accent'=>'#facc15'],
        ['preset'=>'midnight', 'bg'=>'#020617', 'bg2'=>'#312e81', 'text'=>'#e0e7ff', 'source'=>'#c4b5fd', 'highlight'=>'#38bdf8', 'accent'=>'#a78bfa'],
        ['preset'=>'white-gold', 'bg'=>'#fff7ed', 'bg2'=>'#fef3c7', 'text'=>'#171717', 'source'=>'#854d0e', 'highlight'=>'#ca8a04', 'accent'=>'#ca8a04'],
        ['preset'=>'purple-lilac', 'bg'=>'#2e1065', 'bg2'=>'#c084fc', 'text'=>'#ffffff', 'source'=>'#f5d0fe', 'highlight'=>'#f0abfc', 'accent'=>'#f0abfc'],
        ['preset'=>'green-life', 'bg'=>'#052e16', 'bg2'=>'#22c55e', 'text'=>'#f0fdf4', 'source'=>'#bbf7d0', 'highlight'=>'#fde047', 'accent'=>'#86efac'],
        ['preset'=>'blue-light', 'bg'=>'#dbeafe', 'bg2'=>'#eff6ff', 'text'=>'#0f172a', 'source'=>'#1d4ed8', 'highlight'=>'#2563eb', 'accent'=>'#2563eb'],
        ['preset'=>'rose-black', 'bg'=>'#09090b', 'bg2'=>'#be185d', 'text'=>'#fff1f2', 'source'=>'#fbcfe8', 'highlight'=>'#f9a8d4', 'accent'=>'#ec4899'],
        ['preset'=>'charcoal-cyan', 'bg'=>'#111827', 'bg2'=>'#0e7490', 'text'=>'#f8fafc', 'source'=>'#a5f3fc', 'highlight'=>'#22d3ee', 'accent'=>'#22d3ee'],
    ];
@endphp

<div class="dxm-designer-palette-row">
    @foreach($dxmPalettes as $palette)
        <button
            type="button"
            class="dxm-designer-palette"
            title="{{ Str::headline($palette['preset']) }}"
            data-dxm-designer-palette
            data-preset="{{ $palette['preset'] }}"
            data-bg="{{ $palette['bg'] }}"
            data-bg2="{{ $palette['bg2'] }}"
            data-text="{{ $palette['text'] }}"
            data-source="{{ $palette['source'] }}"
            data-highlight="{{ $palette['highlight'] }}"
            data-accent="{{ $palette['accent'] }}"
            style="background:linear-gradient(135deg, {{ $palette['bg'] }}, {{ $palette['bg2'] }});"
        ></button>
    @endforeach
</div>
