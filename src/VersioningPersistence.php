<?php

namespace Kiqstyle\EloquentVersionable;

use Illuminate\Database\Eloquent\Model;

class VersioningPersistence
{
    public static function createVersionedRecord(Model $model)
    {
        self::getVersionedModel($model)->create($model->getAttributes());
    }

    public static function updateNextColumnOfLastVersionedRegister(Model $model)
    {
        $lastVersioned = self::getVersionedModel($model)
            ->withoutGlobalScopes()
            ->where('id', $model->id)
            ->orderBy('_id', 'desc')
            ->first();

        $lastVersioned->timestamps = false;

        $lastVersioned->update(['next' => $model->{$model->getUpdatedAtColumn()}]);
    }

    public static function createDeletedVersionedRecord(Model $model)
    {
        $versionedInstance = self::getVersionedModel($model);
        $versionedInstance->fill($model->getAttributes());
        $versionedInstance->{$versionedInstance->getUpdatedAtColumn()} = $model->{$model->getUpdatedAtColumn()};
        $versionedInstance->{$versionedInstance->getDeletedAtColumn()} = $model->{$model->getUpdatedAtColumn()};
        $versionedInstance->save();
    }

    private static function getVersionedModel(Model $model)
    {
        $versionedClassName = $model->getVersioningModel();
        return new $versionedClassName;
    }
}
