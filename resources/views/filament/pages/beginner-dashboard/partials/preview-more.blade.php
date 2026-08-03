@php($tabKey = 'more')
@php($tabSections = $previewSectionsByTab->get($tabKey, collect()))

<div class="dxm-phone-tab-panel {{ $activeTab === $tabKey ? 'is-active' : '' }}" data-dxm-preview-panel="{{ $tabKey }}">
    <div style="height:14px;"></div>

    @forelse ($tabSections as $section)
        @include('filament.pages.beginner-dashboard.partials.preview-section', ['section' => $section])
    @empty
        <div class="dxm-empty" style="margin:14px;">No preview yet.</div>
    @endforelse
</div>
