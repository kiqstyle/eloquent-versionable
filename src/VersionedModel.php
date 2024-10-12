<?php

namespace Kiqstyle\EloquentVersionable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VersionedModel extends Model implements VersionableContract
{
    use SoftDeletes, Versionable;

    const NEXT_COLUMN = 'next';

    const VERSIONED_TABLE = null;

    const VERSIONING_MODEL = null;

    protected $versioningEnabled = true;

    protected $guarded = [];
}
