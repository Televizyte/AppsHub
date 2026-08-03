<?php

namespace App\Filament\Resources\Base;

use App\Support\ActiveApp;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

abstract class ActiveAppScopedResource extends Resource
{
    /**
     * The foreign key column name used across app-scoped tables.
     */
    protected static string $appForeignKey = 'app_id';

    /**
     * Scope ALL list/table queries by the currently active app,
     * but only if the model/table actually has the app_id column.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $model = static::getModel();
        $instance = new $model();

        $table = $instance->getTable();
        $key = static::$appForeignKey;

        if (Schema::hasColumn($table, $key)) {
            $activeAppId = ActiveApp::get();

            // Fail closed if there is no valid active app.
            if (!$activeAppId) {
                return $query->whereRaw('1 = 0');
            }

            $query->where($table . '.' . $key, (int) $activeAppId);
        }

        return $query;
    }

    /**
     * Public helper for CreateRecord pages:
     * force the record into the currently active app when the table supports app scoping.
     */
    public static function applyActiveAppToCreateData(array $data): array
    {
        $model = static::getModel();
        $instance = new $model();

        $table = $instance->getTable();
        $key = static::$appForeignKey;

        if (Schema::hasColumn($table, $key)) {
            $activeAppId = ActiveApp::get();

            if ($activeAppId) {
                $data[$key] = (int) $activeAppId;
            } else {
                // Defensive fallback: never write an invalid app id.
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * Public helper for EditRecord pages:
     * never allow app_id/app foreign key to be changed through form submission.
     */
    public static function lockAppKeyOnSave(array $data): array
    {
        $model = static::getModel();
        $instance = new $model();

        $table = $instance->getTable();
        $key = static::$appForeignKey;

        if (Schema::hasColumn($table, $key)) {
            unset($data[$key]);
        }

        return $data;
    }

    /**
     * Backward-safe internal aliases in case any existing code relies on these hooks here.
     */
    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        return static::applyActiveAppToCreateData($data);
    }

    protected static function mutateFormDataBeforeSave(array $data): array
    {
        return static::lockAppKeyOnSave($data);
    }
}
