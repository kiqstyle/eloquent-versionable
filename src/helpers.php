<?php

use Kiqstyle\EloquentVersionable\VersioningDate;

if (! function_exists('versioningDate')) {
    function versioningDate(): VersioningDate
    {
        return app('versioningDate');
    }
}
