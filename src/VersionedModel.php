<?php

namespace Kiqstyle\EloquentVersionable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VersionedModel extends Model implements VersionableContract
{
    use SoftDeletes, Versionable;

    public const NEXT_COLUMN = 'next';

    public const VERSIONED_TABLE = null;

    public const VERSIONING_MODEL = null;

    protected $versioningEnabled = true;

    protected $guarded = [];
}
