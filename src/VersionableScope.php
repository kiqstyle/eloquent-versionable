<?php

namespace Kiqstyle\EloquentVersionable;

use Illuminate\Database\Eloquent\Scope;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VersionableScope implements Scope
{
    /**
     * Apply scope on the query.
     */
    public function apply(Builder $builder, Model|Versionable $model): void
    {
        if (! versioningDate()->issetDate() || ($model->isVersioningEnabled() !== true)) {
            return;
        }

        $datetime = versioningDate()->getDate()->format('Y-m-d H:i:s');

        $versionedAt = $model->getVersionedAtColumn();
        $next = $model->getQualifiedNextColumn();
        $builder->where($model->getVersioningTable() . '.' . $versionedAt, '<=', $datetime)
            ->where(fn (Builder $q) => $q->where($next, '>', $datetime)->orWhereNull($next));

        $joins = $builder->getQuery()->joins ?? [];
        foreach ($joins as $join) {
            if (str_contains($join->table, '_versioning')) {
                $builder->where($join->table . '.' . $versionedAt, '<=', $datetime)
                    ->whereNull($join->table . '.' . $model->getDeletedAtColumn())
                    ->where(function (Builder $q) use ($datetime, $join, $model) {
                        $q->where($join->table . '.' . $model->getNextColumn(), '>', $datetime)
                            ->orWhereNull($join->table . '.' . $model->getNextColumn());
                    });
            }
        }
    }
}
