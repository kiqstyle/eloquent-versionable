<?php

namespace Kiqstyle\EloquentVersionable;

use Illuminate\Database\Eloquent\Builder;

interface VersionableContract
{
    public static function bootVersionable(): void;

    public function isVersioningEnabled(): bool;

    public function setVersioningEnabled(bool $versioningEnabled): void;

    public function getVersioningModel(): string;

    public function getVersioningTable(): string;

    public function getNextColumn(): string;

    public function now(): Builder;

    public function updateLastVersion(array $attributes = [], array $options = []): bool;
}
