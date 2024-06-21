<?php

namespace Kiqstyle\EloquentVersionable;

use Carbon\Carbon;

/**
 * @property string date
 */
class VersioningDate
{
    private ?Carbon $date;

    public function setDate(null|string|Carbon $date = null): self
    {
        if (is_null($date)) {
            $date = Carbon::now();
        }

        if (is_string($date)) {
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $date);
        }

        $this->date = $date;

        return $this;
    }

    public function getDate(): Carbon
    {
        return $this->date;
    }

    public function unsetDate(): void
    {
        $this->date = null;
    }

    public function issetDate(): bool
    {
        return (bool) $this->date;
    }
}
