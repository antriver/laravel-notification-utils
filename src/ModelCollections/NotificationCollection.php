<?php

namespace Antriver\LaravelNotificationUtils\ModelCollections;

use Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification;
use Carbon\Carbon;

class NotificationCollection
{
    /**
     * @var string
     */
    public $heading;

    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $icon;

    /**
     * @var Carbon
     */
    public $lastAt;

    /**
     * @var CustomDatabaseNotification[]
     */
    public $notifications = [];

    public function __construct($type = null)
    {
        if ($type) {
            $this->setHeading(trans('notifications.headings.'.$type));
            $this->setIcon(trans('notifications.icons.'.$type));
        }
    }

    /**
     * @param CustomDatabaseNotification $notification
     *
     * @return $this
     */
    public function addNotification(CustomDatabaseNotification $notification)
    {
        $this->notifications[] = $notification;

        if ($notification->createdAt > $this->lastAt) {
            $this->lastAt = $notification->createdAt;
        }

        return $this;
    }

    /**
     * @param string $heading
     *
     * @return $this
     */
    public function setHeading($heading)
    {
        $this->heading = $heading;

        return $this;
    }

    /**
     * @param string $icon
     *
     * @return $this
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }
}
