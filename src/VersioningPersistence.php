<?php

namespace Kiqstyle\EloquentVersionable;

use Illuminate\Database\Eloquent\Model;

class VersioningPersistence
{
    public function createVersionedRecord(Model $model): void
    {
        $attributes = $model->getAttributes();
        $versionedModel = self::getVersionedModel($model);
        $attributes[$versionedModel->getVersionedAtColumn()] = $model->{$model->getUpdatedAtColumn()};
        $versionedModel->create($attributes);
    }

    public function updateNextColumnOfLastVersionedRegister(Model $model): void
    {
        $lastVersioned = self::getVersionedModel($model)
            ->withoutGlobalScopes()
            ->where('id', $model->id)
            ->orderBy('versioned_at', 'desc')
            ->orderBy('_id', 'desc')
            ->first();

        $lastVersioned->timestamps = false;

        // @todo será que aqui vai versioned_at?
        $lastVersioned->update(['next' => $model->{$model->getUpdatedAtColumn()}]);
    }

    public function createDeletedVersionedRecord(Model $model): void
    {
        $versionedInstance = self::getVersionedModel($model);
        $versionedInstance->fill($model->getAttributes());
        $versionedInstance->{$versionedInstance->getVersionedAtColumn()} = $model->{$model->getUpdatedAtColumn()};
        $versionedInstance->{$versionedInstance->getDeletedAtColumn()} = $model->{$model->getUpdatedAtColumn()};
        $versionedInstance->save();
    }

    private static function getVersionedModel(Versionable|Model $model): Model|VersionedModel
    {
        $versionedClassName = $model->getVersioningModel();
        return new $versionedClassName;
    }
}
