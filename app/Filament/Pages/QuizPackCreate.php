<?php

namespace App\Filament\Pages;

use App\Models\App;
use App\Support\ActiveApp;
use App\Support\AdminAccess;
use Filament\Pages\Page;

class QuizPackCreate extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static ?string $navigationLabel = 'Create Quiz Pack';
    protected static ?string $slug = 'quiz-center/packs/create';
    protected static string $view = 'filament.pages.quiz-pack-create';

    public ?App $currentApp = null;
    public string $selectedType = 'custom';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('quiz_center');
    }

    public function mount(): void
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);
        $this->currentApp = $appId > 0 ? App::query()->find($appId) : null;

        $type = strtolower(trim((string) request()->query('type', 'custom')));
        $allowed = ['bible', 'article', 'daily', 'sod', 'general', 'custom'];
        $this->selectedType = in_array($type, $allowed, true) ? $type : 'custom';
    }
}
