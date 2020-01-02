<?php

namespace Antriver\LaravelNotificationUtils\ModelPresenters\Traits;

use Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification;
use Antriver\LaravelNotificationUtils\Repositories\CustomDatabaseNotificationRepository;
use Antriver\LaravelSiteUtils\Libraries\LanguageHelpers;
use Antriver\LaravelSiteUtils\Models\User;
use Antriver\LaravelSiteUtils\Repositories\UserRepository;
use Lang;

/**
 * Provides some base methods that can be used for converting a CustomDatabaseNotification to something
 * to export via an API.
 */
trait CustomDatabaseNotificationPresenterTrait
{
    /**
     * @var User
     */
    protected $currentUser;

    /**
     * @var CustomDatabaseNotificationRepository
     */
    protected $notificationRepository;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    protected function notificationToArray(CustomDatabaseNotification $notification)
    {
        $array = $notification->toArray();

        // Set some properties that might be needed depending on the notification type

        $array['groupingId'] = $this->notificationRepository->getGroupByKeyForNotification($notification);

        $array['icon'] = $this->getIcon($notification);
        $array['imageUrl'] = $this->getImageUrl($notification);
        $array['text'] = $this->getText($notification);
        $array['url'] = $this->getUrl($notification);

        return $array;
    }

    /**
     * Returns a FontAwesome icon name for this notification.
     *
     * @param CustomDatabaseNotification $notification
     *
     * @return null|string
     */
    protected function getIcon(CustomDatabaseNotification $notification)
    {
        return Lang::get('notifications.icons.'.$notification->type);
    }

    /**
     * Return the full URL to an image to be displayed for this notification.
     *
     * @param CustomDatabaseNotification $notification
     *
     * @return null|string
     */
    protected function getImageUrl(CustomDatabaseNotification $notification)
    {
        return null;
    }

    protected function getUrl(CustomDatabaseNotification $notification)
    {
        return Lang::get(
            'notifications.urls.'.$notification->type,
            $this->getUrlParameters($notification)
        );
    }

    /**
     * Return an array of parameters that may be needed in the getUrl strings.
     *
     * @param CustomDatabaseNotification $notification
     *
     * @return array
     */
    protected function getUrlParameters(CustomDatabaseNotification $notification)
    {
        $parameters = $notification->getAttributes();

        if ($fromUsers = $this->getFromUsers($notification)) {
            $parameters['fromUsername'] = $fromUsers[0]->getUrlUsername();
        }

        if ($this->currentUser) {
            /** @var User $user */
            $parameters['currentUsername'] = $this->currentUser->getUrlUsername();
        }

        return $parameters;
    }

    /**
     * Returns the text of this notification.
     *
     * @param CustomDatabaseNotification $notification
     *
     * @return string
     */
    protected function getText(CustomDatabaseNotification $notification)
    {
        return trans_choice(
            'notifications.types.'.$notification->type,
            $notification->getGroupCount(),
            $this->getTextParameters($notification)
        );
    }

    /**
     * Return an array of parameters that may be needed in the getText strings.
     *
     * @param CustomDatabaseNotification $notification
     *
     * @return array
     */
    protected function getTextParameters(CustomDatabaseNotification $notification)
    {
        $parameters = [
            'usernames' => $this->getFromUsernamesString($notification),
        ];

        return $parameters;
    }

    /**
     * Returns an array of Users the notification is from.
     * Sets it on the notification model when loaded for the first time.
     *
     * @param CustomDatabaseNotification $notification
     * @param int $limit
     *
     * @return User[]
     */
    protected function getFromUsers(CustomDatabaseNotification $notification, $limit = null)
    {
        $fromUserIds = $notification->getFromUserIds();
        if (empty($fromUserIds)) {
            return [];
        }

        if (!is_null($users = $notification->getFromUsers())) {
            return $users;
        }

        $users = [];

        $i = 0;
        while ($i < count($fromUserIds) && ($limit === null || $i < $limit)) {
            $userId = $fromUserIds[$i];
            $users[] = $this->userRepository->find($userId);
            ++$i;
        }

        $notification->setFromUsers($users);

        return $users;
    }

    /**
     * Returns an array of usernames the notification is from.
     *
     * @param CustomDatabaseNotification $notification
     * @param int $limit
     *
     * @return \string[]
     */
    protected function getFromUsernames(CustomDatabaseNotification $notification, $limit = null)
    {
        $fromUsers = $this->getFromUsers($notification, $limit);

        $usernames = array_map(
            function (User $user) {
                return $user->username;
            },
            $fromUsers
        );

        return $usernames;
    }

    /**
     * Get the list of usernames for the fromUserIds to display in the notification.
     *
     * Number of users:
     * 1: nameA
     * 2: nameA and nameB
     * 3: nameA, nameB and nameC
     * 4+: nameA, nameB, and 2+ others
     *
     * @param CustomDatabaseNotification $notification
     *
     * @return null|string
     */
    protected function getFromUsernamesString(CustomDatabaseNotification $notification)
    {
        if ($userIds = $notification->getFromUserIds()) {
            $userIdCount = count($userIds);

            $showUsernameCount = $userIdCount > 3 ? 2 : 3;
            $usernames = $this->getFromUsernames($notification, $showUsernameCount);

            if ($userIdCount <= 3) {
                $usernameStr = LanguageHelpers::naturalLanguageImplode($usernames);
            } else {
                $usernameStr = implode(', ', $usernames);
                $remaining = $userIdCount - $showUsernameCount;
                $usernameStr .= ', '.Lang::get('notifications.and-others', ['count' => $remaining]);
            }

            return $usernameStr;
        }

        return null;
    }
}
