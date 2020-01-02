<?php

namespace Antriver\LaravelNotificationUtils\Models;

interface CustomDatabaseNotificationInterface
{
    public function getGroupCount(): int;
}
