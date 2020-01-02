<?php

namespace Antriver\LaravelNotificationUtils\Notifications;

use Antriver\LaravelNotificationUtils\Mail\NotificationMail;
use Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification;
use Antriver\LaravelNotificationUtils\Notifications\Channels\CustomDatabaseNotificationChannel;
use Antriver\LaravelSiteUtils\Models\User;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

/**
 * Note we don't use ShouldQueue because all these notifications are generated
 * by queued listeners anyway!
 */
abstract class AbstractLaravelNotification extends Notification
{
    /**
     * @var User
     */
    public $fromUser;

    /**
     * Return the type id of this notification.
     *
     * @return int
     */
    abstract public function getType();

    /**
     * A single line summarising the notification.
     *
     * @return string
     */
    abstract public function getSubject();

    /**
     * Get the notification's delivery channels.
     *
     * @param User $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        // Don't notify ourselves
        $from = $this->getFromUser();
        if ($from && $from->id == $notifiable->id) {
            return [];
        }

        $channels = [];
        $type = $this->getType();
        //$settings = $notifiable->getSettings();

        //if ($settings->notificationOptions & $type) {
        $channels[] = CustomDatabaseNotificationChannel::class;
        //}

        //if ($settings->emailNotificationOptions & $type) {
        $channels[] = 'mail';
        //}

        //if ($settings->pushNotificationOptions & $type) {
        //$channels[] = 'push';
        //}

        return $channels;
    }

    /**
     * Convert the Notification to a model to be stored in the database.
     * This is called by the CustomDatabaseNotificationChannel when "sending" (saving) the notification.
     *
     * @param Notifiable $notifiable
     *
     * @return CustomDatabaseNotification
     */
    public function toCustomDatabaseNotificationModel(Notifiable $notifiable)
    {
        return new CustomDatabaseNotification($this->toArray($notifiable));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param Notifiable $notifiable
     *
     * @return array
     */
    public function toArray($notifiable)
    {
        $fromUser = $this->getFromUser();

        return [
            'type' => $this->getType(),
            'forUserId' => $notifiable->id,
            'fromUserId' => $fromUser ? $fromUser->id : null,
        ];
    }

    /**
     * @return User|null
     */
    protected function getFromUser()
    {
        return $this->fromUser;
    }

    protected function getFromName($capitalize = true)
    {
        $fromUser = $this->getFromUser();

        return $fromUser ? $fromUser->username : ($capitalize ? 'Somebody' : 'somebody');
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return NotificationMail
     */
    public function toMail($notifiable)
    {
        $message = new NotificationMail();

        $message->to($notifiable);
        $message->setRecipient($notifiable);
        $message->subject($this->getSubject());

        return $message;
    }
}
