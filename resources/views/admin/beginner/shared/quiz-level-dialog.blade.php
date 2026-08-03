<dialog id="{{ $dialogId }}" class="dxm-dialog">
    <form method="POST" action="{{ $action }}" class="dxm-form">
        @csrf
        @if (($method ?? 'POST') !== 'POST')
            @method($method)
        @endif

        <div class="dxm-panel-title wide">
            <div>
                <strong>Edit Level / Session</strong>
                <small>Levels are fully custom. Use any name and question pool size.</small>
            </div>
            <button type="button" onclick="this.closest('dialog').close()">×</button>
        </div>

        @include('admin.beginner.shared.quiz-level-fields', ['level' => $level])

        <div class="actions wide">
            <button type="submit" class="dxm-btn primary">Save Level</button>
            <button type="button" class="dxm-btn" onclick="this.closest('dialog').close()">Cancel</button>
        </div>
    </form>
</dialog>
