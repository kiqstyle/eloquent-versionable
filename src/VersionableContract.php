<?php

namespace Kiqstyle\EloquentVersionable;

interface VersionableContract
{
    public static function bootVersionable();

    public function isVersioningEnabled();

    public function setVersioningEnabled(bool $versioningEnabled);

    public function getVersioningModel();

    public function getVersioningTable();

    public function getNextColumn();

    public function now();
}
