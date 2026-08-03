<div class="dxm-mode-switcher">
    <button
        type="button"
        wire:click="switchMode('beginner')"
        class="dxm-mode-btn {{ $mode === 'beginner' ? 'active' : '' }}"
    >
        Beginner
    </button>

    <button
        type="button"
        wire:click="switchMode('advanced')"
        class="dxm-mode-btn {{ $mode === 'advanced' ? 'active' : '' }}"
    >
        Advanced
    </button>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('mode-switched', () => {
                window.location.reload();
            });
        });
    </script>
</div>
