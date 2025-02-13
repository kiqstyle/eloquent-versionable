<?php

namespace Kiqstyle\EloquentVersionable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class VersionableScope implements Scope
{
    /**
     * Apply scope on the query.
     */
    public function apply(Builder $builder, Model|Versionable $model): void
    {
        if (
            ! versioningDate()->issetDate()
            || ($model->isVersioningEnabled() !== true)
        ) {
            return;
        }

        $datetime = versioningDate()->getDate()->format('Y-m-d H:i:s');

        $updatedAt = $model->getUpdatedAtColumn();
        $next = $model->getQualifiedNxtColumn();
        $updatedAtField = $model->getVersioningTable() . '.' . $updatedAt;
        $nextIsBiggerThanDatetimeOrNextIsNull = fn (Builder $q) => $q->where($next, '>', $datetime)->orWhereNull($next);
        $builder->where($updatedAtField, '<=', $datetime)
            ->where($nextIsBiggerThanDatetimeOrNextIsNull);

        $joins = $builder->getQuery()->joins ?? [];
        foreach ($joins as $join) {
            if (str_contains($join->table, '_versioning')) {
                $table = $join->table;
                $updatedAtField = $table . '.' . $updatedAt;
                $nextField = $table . '.' . $model->getNextColumn();
                $deletedAtField = $table . '.' . $model->getDeletedAtColumn();

                $builder->where($updatedAtField, '<=', $datetime)
                    ->whereNull($deletedAtField)
                    ->where(function (Builder $q) use ($datetime, $nextField): void {
                        $q->where($nextField, '>', $datetime)
                            ->orWhereNull($nextField);
                    });
            }
        }
    }
}
