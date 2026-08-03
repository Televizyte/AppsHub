<?php

namespace App\Support\Publishing;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class PublicationVisibility
{
    /**
     * Apply the public visibility rule to a query.
     *
     * Visible means:
     * - status is one of the accepted public statuses; and
     * - publish_at is null or has already been reached.
     */
    public static function apply(
        EloquentBuilder|QueryBuilder $query,
        array $statuses = ['published', 'publish', 'active'],
        string $statusColumn = 'status',
        string $publishAtColumn = 'publish_at',
    ): EloquentBuilder|QueryBuilder {
        return $query
            ->whereIn($statusColumn, $statuses)
            ->where(function ($visibilityQuery) use ($publishAtColumn): void {
                $visibilityQuery
                    ->whereNull($publishAtColumn)
                    ->orWhere($publishAtColumn, '<=', now());
            });
    }
}
