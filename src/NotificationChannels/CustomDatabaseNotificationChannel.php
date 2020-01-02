<?php

namespace Antriver\LaravelNotificationUtils\Notifications\Channels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Antriver\LaravelNotificationUtils\Notifications\AbstractLaravelNotification;
use Antriver\LaravelNotificationUtils\Repositories\CustomDatabaseNotificationRepository;

/**
 * Used instead of the Laravel's built in database channel, to make things more flexible.
 * e.g. the ability to filter notifications by type.
 * For example, we don't want to JSON encode the notification, and we want to store an action id.
 */
class CustomDatabaseNotificationChannel
{
    /**
     * Send the given notification.
     *
     * @param Notifiable|Model $notifiable
     * @param AbstractLaravelNotification $notification
     */
    public function send(
        Notifiable $notifiable,
        AbstractLaravelNotification $notification
    ) {
        $model = $notification->toCustomDatabaseNotificationModel($notifiable);
        $model->forUserId = $notifiable->getKey();
        app(CustomDatabaseNotificationRepository::class)->persist($model);
    }
}
