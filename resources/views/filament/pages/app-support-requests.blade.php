<x-filament-panels::page>
<style>
.asr-grid{display:grid;gap:14px}.asr-toolbar{display:flex;gap:10px;flex-wrap:wrap}.asr-toolbar select{background:#111827;color:#e5e7eb;border:1px solid #374151;border-radius:9px;padding:8px 10px}.asr-card{background:#0f172a;border:1px solid #26334a;border-radius:16px;padding:16px}.asr-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}.asr-ref{font-weight:900;color:#67e8f9}.asr-meta{color:#94a3b8;font-size:12px;margin-top:5px}.asr-msg{white-space:pre-wrap;color:#dbeafe;margin:12px 0}.asr-actions{display:flex;gap:8px;flex-wrap:wrap}.asr-actions button{border:1px solid #475569;background:#1e293b;color:#fff;border-radius:8px;padding:7px 10px;font-size:12px;font-weight:800;cursor:pointer}.asr-due{color:#fca5a5;font-weight:800}.asr-empty{padding:24px;text-align:center;color:#94a3b8;border:1px dashed #475569;border-radius:14px}
</style>
<div class="asr-grid">
    <div class="asr-toolbar">
        <select wire:model.live="statusFilter"><option value="all">All statuses</option><option value="new">New</option><option value="open">Open</option><option value="waiting">Waiting</option><option value="escalated">Escalated</option><option value="resolved">Resolved</option><option value="closed">Closed</option><option value="rejected">Rejected</option></select>
        <select wire:model.live="categoryFilter"><option value="all">All categories</option><option value="technical">Technical</option><option value="account">Account</option><option value="privacy">Privacy</option><option value="account_deletion">Account deletion</option><option value="content">Content report</option><option value="copyright">Copyright</option><option value="advertising">Advertising</option><option value="ministry">Ministry</option><option value="general">General</option><option value="other">Other</option></select>
    </div>
    @forelse($this->requests as $request)
        <article class="asr-card">
            <div class="asr-head"><div><div class="asr-ref">{{ $request->reference }}</div><div class="asr-meta">{{ strtoupper($request->category) }} · {{ $request->email }} · {{ $request->created_at?->format('d M Y H:i') }}</div></div><div><strong>{{ strtoupper($request->status) }}</strong>@if($request->due_at)<div class="asr-meta {{ $request->due_at->isPast() && !$request->completed_at ? 'asr-due' : '' }}">Due {{ $request->due_at->format('d M Y') }}</div>@endif</div></div>
            <h3>{{ $request->subject }}</h3><div class="asr-msg">{{ $request->message }}</div>
            <div class="asr-meta">Verification: {{ $request->verification_status }} · Source: {{ $request->source }} · App version: {{ $request->app_version ?: 'not supplied' }}</div>
            <div class="asr-actions" style="margin-top:12px">
                @if($request->verification_status === 'required')<button wire:click="markVerified({{ $request->id }})">Mark verified</button>@endif
                @foreach(['open','waiting','escalated','resolved','closed','rejected'] as $status)<button wire:click="updateStatus({{ $request->id }},'{{ $status }}')">{{ ucfirst($status) }}</button>@endforeach
            </div>
        </article>
    @empty<div class="asr-empty">No support requests for the active app.</div>@endforelse
</div>
</x-filament-panels::page>
