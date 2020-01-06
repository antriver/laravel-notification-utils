<?php

namespace Antriver\LaravelNotificationUtils\Interfaces;

interface NotifiableInterface
{
    public function notify($instance);

    public function getKey();
}
