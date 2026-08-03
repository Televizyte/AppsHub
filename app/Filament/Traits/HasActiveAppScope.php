<?php

namespace App\Filament\Traits;

use App\Support\ActiveApp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait HasActiveAppScope
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $appId = ActiveApp::id();
        if ($appId) {
            $query->where('app_id', $appId);
        }

        return $query;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $appId = ActiveApp::id();
        if ($appId) {
            $data['app_id'] = $appId;
        }

        return $data;
    }

    protected function resolveRecord($key): Model
    {
        $record = parent::resolveRecord($key);

        $appId = ActiveApp::id();
        if ($appId && isset($record->app_id) && (int) $record->app_id !== (int) $appId) {
            throw new ModelNotFoundException();
        }

        return $record;
    }
}
