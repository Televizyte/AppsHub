<?php

namespace App\Filament\Pages;

use App\Models\AppSupportRequest;
use App\Support\ActiveApp;
use App\Support\AdminAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;

class AppSupportRequests extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $navigationLabel = 'App Support Requests';
    protected static ?string $navigationGroup = 'Workspace';
    protected static ?int $navigationSort = 95;
    protected static ?string $slug = 'app-support-requests';
    protected static string $view = 'filament.pages.app-support-requests';

    public string $statusFilter = 'all';
    public string $categoryFilter = 'all';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('app_settings');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('app_settings');
    }

    public function getRequestsProperty()
    {
        if (! Schema::hasTable('app_support_requests')) {
            return collect();
        }

        $appId = (int) (ActiveApp::ensureId() ?? 0);
        $query = AppSupportRequest::query()->where('app_id', $appId)->latest();

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }
        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }

        return $query->limit(250)->get();
    }

    public function updateStatus(int $id, string $status): void
    {
        abort_unless(in_array($status, ['new','open','waiting','escalated','resolved','closed','rejected'], true), 422);
        $appId = (int) (ActiveApp::ensureId() ?? 0);
        $request = AppSupportRequest::query()->where('app_id', $appId)->findOrFail($id);
        $request->status = $status;
        if (in_array($status, ['resolved','closed','rejected'], true)) {
            $request->completed_at = now();
        }
        $request->save();
        Notification::make()->title('Request updated')->success()->send();
    }

    public function markVerified(int $id): void
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);
        $request = AppSupportRequest::query()->where('app_id', $appId)->findOrFail($id);
        $request->verification_status = 'verified';
        $request->verified_at = now();
        $request->save();
        Notification::make()->title('Identity marked verified')->success()->send();
    }
}
