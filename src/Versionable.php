<?php

namespace Kiqstyle\EloquentVersionable;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionClass;

trait Versionable
{
    public static function bootVersionable(): void
    {
        static::addGlobalScope(new VersionableScope());

        $callback = function (Model $model) {
            if ($model->isVersioningEnabled() && $model->isDirty()) {
                DB::beginTransaction();
            }
        };
        static::saving($callback);

        static::saved(function (Model $model) {
            if ($model->isVersioningEnabled() && $model->isDirty()) {
                try {
                    app(VersioningPersistence::class)
                        ->createVersionedRecord($model);
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }
        });

        static::updating($callback);

        static::updated(function (Model $model) {
            if ($model->isVersioningEnabled() && $model->isDirty()) {
                try {
                    app(VersioningPersistence::class)
                        ->updateNextColumnOfLastVersionedRegister($model);
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }
        });

        static::deleting(function (Model $model) {
            if ($model->isVersioningEnabled()) {
                DB::beginTransaction();
            }
        });

        static::deleted(function (Model $model) {
            if ($model->isVersioningEnabled()) {
                try {
                    app(VersioningPersistence::class)
                        ->updateNextColumnOfLastVersionedRegister($model);
                    app(VersioningPersistence::class)
                        ->createDeletedVersionedRecord($model);
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }
        });
    }

    public function insert(array $data): void
    {
        foreach ($data as $register) {
            $this->create($register);
        }
    }

    public function getTable(): string
    {
        [$one, $two, $three, $caller] =
            debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        $calledBy = $caller['function'];

        $methods = [
            'save',
            'runSoftDelete',
            'delete',
            'performDeleteOnModel',
            'create',
            'updateOrCreate',
            'addUpdatedAtColumn',
        ];

        if (
            versioningDate()->issetDate()
            && ($this->isVersioningEnabled()
            && ! in_array($calledBy, $methods))
        ) {
            return $this->getVersioningTable();
        }

        return $this->getOriginalTable();
    }

    public function getOriginalTable(): string
    {
        if (! isset($this->table)) {
            return str_replace(
                '\\', '', Str::snake(Str::plural(class_basename($this)))
            );
        }

        return $this->table;
    }

    public function isVersioningEnabled(): bool
    {
        return $this->versioningEnabled;
    }

    public function setVersioningEnabled(bool $versioningEnabled): void
    {
        $this->versioningEnabled = $versioningEnabled;
    }

    public function unsetVersioning(): void
    {
        $this->versioningEnabled = false;
    }

    public function getVersioningModel(): string
    {
        if ($this::VERSIONING_MODEL !== null) {
            return $this::VERSIONING_MODEL;
        }

        return $this->guessVersioningClassName();
    }

    public function getVersioningTable(): string
    {
        if ($this::VERSIONED_TABLE !== null) {
            return $this::VERSIONED_TABLE;
        }

        return $this->getOriginalTable() . '_versioning';
    }

    /**
     * Get the name of the column for applying the scope.
     */
    public function getNextColumn(): string
    {
        return ($this::NEXT_COLUMN !== null) ? $this::NEXT_COLUMN : 'next';
    }

    /**
     * Get the fully qualified column name for applying the scope.
     */
    public function getQualifiedNxtColumn(): string
    {
        return $this->getVersioningTable() . '.' . $this->getNextColumn();
    }

    /**
     * Get the query builder without the scope applied.
     */
    public function now(): Builder
    {
        return with(new static())
            ->newQueryWithoutScope(new VersionableScope());
    }

    public function getQualifiedVersioningKeyName(): string
    {
        return $this->getVersioningTable() . '.' . $this->getKeyName();
    }

    /**
     * Create a new instance of the given model.
     */
    public function newInstance($attributes = [], $exists = false)
    {
        // This method just provides a convenient way for us to generate fresh
        // model instances of this current model. It is particularly useful
        // during the hydration of new objects via the Eloquent query
        // builder instances.
        $model = new static((array) $attributes);

        $model->exists = $exists;

        $model->setConnection(
            $this->getConnectionName()
        );

        $model->setTable($this->getOriginalTable());

        return $model;
    }

    private function guessVersioningClassName(): string
    {
        $class = new ReflectionClass(get_class($this));
        return $class->getNamespaceName()
            . '\\Versioning\\'
            . $class->getShortName()
            . 'Versioning';
    }
}
