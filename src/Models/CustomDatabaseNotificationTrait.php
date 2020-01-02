<?php

namespace Antriver\LaravelNotificationUtils\Models;

trait CustomDatabaseNotificationTrait
{


    /**
     * Returns the number of notifications that were grouped together together into this one.
     * Populated by the COUNT() on the query in CustomDatabaseNotificationRepository.
     */
    public function getGroupCount(): int
    {
        return isset($this->attributes['groupCount']) ? $this->attributes['groupCount'] : 1;
    }
}
