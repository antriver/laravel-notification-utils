<?php

namespace Antriver\LaravelNotificationUtils\ModelCollections;

use Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification;
use Antriver\LaravelNotificationUtils\Repositories\CustomDatabaseNotificationRepository;
use Antriver\LaravelSiteScaffolding\Models\User;

class NotificationCollectionFactory
{
    /**
     * @var CustomDatabaseNotificationRepository
     */
    private $notificationRepository;

    /**
     * @param CustomDatabaseNotificationRepository $notificationRepository
     */
    public function __construct(CustomDatabaseNotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * TODO: Cache
     *
     * Return an array with 2 items. The first is the count of individual GroupedNotifications.
     * The second is an array of NotificationConnections, with the notifications organised.
     *
     * @param User $user
     *
     * @return array
     */
    public function getCollectionsForUser(User $user)
    {
        $notifications = $this->notificationRepository->getUsersNotifications($user);
        $collections = $this->createNotificationCollections($notifications);

        $this->sortNotificationCollections($collections);

        return [count($notifications), $collections];
    }

    /**
     * @param array $collections
     */
    private function sortNotificationCollections(array &$collections)
    {
        usort(
            $collections,
            function (NotificationCollection $a, NotificationCollection $b) {
                return $a->lastAt < $b->lastAt;
            }
        );
    }

    /**
     * @param CustomDatabaseNotification[] $notifications
     *
     * @return NotificationCollection[]
     */
    private function createNotificationCollections($notifications)
    {
        /** @var NotificationCollection[] $groups */
        $groups = [];

        foreach ($notifications as $notification) {
            $collectionKey = $this->getCollectionKeyForNotification($notification);
            switch ($collectionKey['type']) {
                default:
                    if (!isset($groups[$collectionKey['key']])) {
                        $groups[$collectionKey['key']] =
                            (new NotificationCollection($notification->type))
                                ->setKey($collectionKey['key']);
                    }
                    break;
            }

            $groups[$collectionKey['key']]->addNotification($notification);
        }

        return $groups;
    }

    /**
     * @param CustomDatabaseNotification $notification
     *
     * @return string[]
     */
    public function getCollectionKeyForNotification(CustomDatabaseNotification $notification)
    {
        return [
            'type' => '',
            'key' => 'type-'.$notification->type,
        ];
    }
}
