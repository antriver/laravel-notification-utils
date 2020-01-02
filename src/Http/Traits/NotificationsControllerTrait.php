<?php

namespace Antriver\LaravelNotificationUtils\Http\Traits;

use Illuminate\Http\Request;
use Antriver\LaravelNotificationUtils\ModelCollections\NotificationCollectionFactory;
use Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification;
use Antriver\LaravelNotificationUtils\Repositories\CustomDatabaseNotificationRepository;
use Tmd\LaravelSite\ModelPresenters\Base\ModelPresenterInterface;

trait NotificationsControllerTrait
{
    /**
     * @var ModelPresenterInterface
     */
    protected $presenter = null;

    /**
     * @param Request $request
     * @param CustomDatabaseNotificationRepository $notificationRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(
        Request $request,
        CustomDatabaseNotificationRepository $notificationRepository
    ) {
        $user = $this->getRequestUser($request);

        $notifications = $notificationRepository->getUsersNotifications($user);

        if ($this->presenter) {
            $notifications = $this->presenter->presentArray($notifications);
        }

        return $this->response(
            [
                'notifications' => $notifications,
            ]
        );
    }

    /**
     * @param Request $request
     * @param NotificationCollectionFactory $notificationCollectionFactory
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexByCollection(
        Request $request,
        NotificationCollectionFactory $notificationCollectionFactory
    ) {
        list($count, $collections) = $notificationCollectionFactory->getCollectionsForUser(
            $this->getRequestUser($request)
        );

        return $this->response(
            [
                'count' => $count,
                'collections' => $collections,
            ]
        );
    }

    /**
     * @param Request $request
     * @param CustomDatabaseNotificationRepository $notificationRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAll(Request $request, CustomDatabaseNotificationRepository $notificationRepository)
    {
        $success = false;

        if ($request->input('seen')) {
            $success = !!$notificationRepository->setAllSeen($this->getRequestUser($request));
        }

        return $this->successResponse($success);
    }

    /**
     * @param CustomDatabaseNotification $notification
     * @param Request $request
     * @param CustomDatabaseNotificationRepository $notificationRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(
        CustomDatabaseNotification $notification,
        Request $request,
        CustomDatabaseNotificationRepository $notificationRepository
    ) {
        $this->authorize('update', $notification);

        $success = false;

        if ($request->input('seen')) {
            $success = !!$notificationRepository->setSeen($notification);
        }

        return $this->successResponse($success);
    }

    /**
     * @param CustomDatabaseNotification $notification
     * @param CustomDatabaseNotificationRepository $notificationRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(
        CustomDatabaseNotification $notification,
        CustomDatabaseNotificationRepository $notificationRepository
    ) {
        $this->authorize('destroy', $notification);

        $success = $notificationRepository->remove($notification);

        return $this->successResponse($success);
    }
}
