<aside class="be-card be-card-cyan" style="position:sticky;top:96px;align-self:start">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
        <div>
            <h2 class="be-card-title" style="font-size:17px">Selected Scriptures</h2>
            <p class="be-card-sub">{{ count($selectedVerses) }} selected for batch actions</p>
        </div>
        @if (count($selectedVerses) > 0)
            <button type="button" wire:click="clearTray" class="be-btn be-btn-danger" style="padding:8px 10px;font-size:12px">Clear</button>
        @endif
    </div>

    <div style="margin-top:14px;max-height:620px;overflow:auto;display:grid;gap:10px;padding-right:3px">
        @forelse ($selectedVerses as $row)
            <article class="be-verse" style="padding:12px;border-radius:17px">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px">
                    <div style="min-width:0">
                        <div class="be-ref" style="font-size:13px">{{ $row['reference'] ?? 'Reference' }}</div>
                        <div class="be-meta">{{ strtoupper($row['translation_key'] ?? '') }} • {{ $row['topic'] ?? 'Bible Engine' }}</div>
                        <p class="be-text" style="font-size:12px;line-height:1.6;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden">{{ $row['text'] ?? '' }}</p>
                    </div>
                    <button type="button" wire:click="removeVerseFromTray({{ (int) ($row['id'] ?? 0) }})" class="be-btn be-btn-danger" style="padding:5px 9px;border-radius:10px">×</button>
                </div>
            </article>
        @empty
            <div class="be-empty" style="padding:24px;font-size:13px">Select one or many scriptures from the picker.</div>
        @endforelse
    </div>

    <div class="be-muted-box" style="margin-top:14px">
        Use selected scriptures to create collections, daily scripture batches, and later book-builder scripture packs.
    </div>
</aside>
