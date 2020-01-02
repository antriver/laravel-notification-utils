<?php

namespace Antriver\LaravelNotificationUtils\Repositories;

use Cache;
use DB;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification;
use Tmd\LaravelRepositories\Base\AbstractRepository;
use Tmd\LaravelSite\Models\User;

class CustomDatabaseNotificationRepository extends AbstractRepository
{
    /**
     * When selecting/updating/deleting, group notifications/perform the action on all notifications where
     * these fields all match.
     *
     * @var array
     */
    protected $groupBy = [
        'forUserId',
        'type',
    ];

    /**
     * Provide a list of notification types here where the fromUserId should be added to the fields to group by
     * if the notification is of one of these types.
     * For example this can be used to un-group message notifications from different users.
     *
     * @var array
     */
    protected $groupByFromUserForTypes = [];

    /**
     * Returns an array of Notifications, which have been grouped together by $this->groupFieldsForSelect.
     *
     * @param User $user
     *
     * @return CustomDatabaseNotification[]
     */
    public function getUsersNotifications(User $user)
    {
        return Cache::rememberForever(
            'user-notifications:'.$user->id,
            function () use ($user) {
                $results = DB::select(
                    "SELECT
                        `n`.*,
                        MAX(`n`.`id`) AS `id`,
                        MAX(`n`.`createdAt`) AS `createdAt`,
                        COUNT(*) AS `groupCount`,
                        GROUP_CONCAT(DISTINCT `n`.`fromUserId` ORDER BY n.id DESC SEPARATOR ',') AS `fromUserIds`
                    FROM
                        `notifications` `n`
                    WHERE
                        `n`.`forUserId` = ?
                    AND
                        `n`.`seenAt` IS NULL
                    GROUP BY
                        ".implode(',', $this->getGroupByFieldsForSelect())."
                    ORDER BY
                        `n`.`id` DESC
                    ",
                    [
                        $user->id,
                    ]
                );

                $modelClass = $this->getModelClass();

                return $modelClass::hydrate($results);
            }
        );
    }

    /**
     * Given a CustomDatabaseNotification which may either be a single row from the DB, or a row that is already
     * the result of a GROUP BY, returns a single CustomDatabaseNotification for the group of notifications
     * considered the same as this one.
     *
     * @param CustomDatabaseNotification $notification
     * @param bool $unseenOnly
     *
     * @return CustomDatabaseNotification
     */
    public function getGroupedNotificationFromSingleNotification(
        CustomDatabaseNotification $notification,
        $unseenOnly = true
    ) {
        $sql = "SELECT
                *,
                COUNT(*) AS `groupCount`,
                GROUP_CONCAT(DISTINCT `n`.`fromUserId` SEPARATOR ',') AS `fromUserIds`
            FROM
                `notifications` `n`
            WHERE 1";

        $match = $this->getMatchSqlForNotification($notification);
        $sql .= $match[0];
        $bindings = $match[1];

        if ($unseenOnly) {
            $sql .= " AND seenAt IS NULL";
        }

        $sql .= " GROUP BY n.forUserId";

        $modelClass = $this->getModelClass();
        $notifications = $modelClass::hydrateRaw($sql, $bindings);

        return $notifications[0];
    }

    public function forgetUsersNotifications($userOrId)
    {
        if ($userOrId instanceof User) {
            $userOrId = $userOrId->id;
        }
        Cache::forget('user-notifications:'.$userOrId);
    }

    /**
     * Marks all notifications within the same group as the given notification as seen.
     *
     * @param CustomDatabaseNotification $notification
     *
     * @return int
     */
    public function setSeen(CustomDatabaseNotification $notification)
    {
        $sql = "UPDATE `notifications` SET `seenAt` = NOW() WHERE 1";

        $match = $this->getMatchSqlForNotification($notification);
        $sql .= $match[0];
        $bindings = $match[1];

        $result = DB::affectingStatement($sql, $bindings);

        $this->forgetUsersNotifications($notification->forUserId);

        return $result;
    }

    /**
     * Marks all notifications for a user as seen.
     *
     * @param User $user
     *
     * @return int
     */
    public function setAllSeen(User $user)
    {
        $sql = "UPDATE `notifications` SET `seenAt` = NOW() WHERE `forUserId` = ?";
        $bindings = [$user->id];

        $result = DB::affectingStatement($sql, $bindings);

        $this->forgetUsersNotifications($user);

        return $result;
    }

    /**
     * Deletes all notifications that are similar to the given notification.
     *
     * @param EloquentModel|CustomDatabaseNotification $notification
     *      (Typehinted as EloquentModel for compatibility with Repository interface)
     *
     * @return bool
     */
    public function remove(EloquentModel $notification)
    {
        $sql = "DELETE FROM `notifications` WHERE 1 ";

        $match = $this->getMatchSqlForNotification($notification);
        $sql .= $match[0];
        $bindings = $match[1];

        $result = DB::affectingStatement($sql, $bindings) > 0;

        $this->forgetUsersNotifications($notification->forUserId);

        return $result;
    }

    public function getGroupByKeyForNotification(CustomDatabaseNotification $notification)
    {
        return implode('-', $this->getGroupByValuesForNotification($notification));
    }

    public function getGroupByValuesForNotification(CustomDatabaseNotification $notification)
    {
        $values = [];

        foreach ($this->groupBy as $field) {
            $values[] = $notification->getAttribute($field);
        }

        $values[] = (in_array($notification->type, $this->groupByFromUserForTypes) ? $notification->fromUserId : null);

        return $values;
    }

    protected function getGroupByFieldsForSelect()
    {
        $return = $this->groupBy;

        if (!empty($this->groupByFromUserForTypes)) {
            $return[] = '(CASE WHEN `type` IN ('.implode(',', $this->groupByFromUserForTypes).') 
            THEN fromUserId ELSE NULL END)';
        }

        return $return;
    }

    /**
     * Returns the SQL to match all notifications similar to the given one (similar is determined by
     * matching $this->updateGroupFields.
     *
     * @param CustomDatabaseNotification $notification
     *
     * @return array
     */
    protected function getMatchSqlForNotification(CustomDatabaseNotification $notification)
    {
        $sql = '';
        $bindings = [];

        foreach ($this->groupBy as $i => $groupField) {
            $value = $notification->{$groupField};
            if ($value === null) {
                $sql .= " AND {$groupField} IS NULL";
            } else {
                $sql .= " AND {$groupField} = ?";
                $bindings[] = $value;
            }
        }

        if (in_array($notification->type, $this->groupByFromUserForTypes)) {
            $sql .= " AND fromUserId = ?";
            $bindings[] = $notification->fromUserId;
        }

        return [$sql, $bindings];
    }

    /**
     * Called when the model is inserted, updated, or deleted.
     * (AFTER the onInsert/onUpdate/onDelete methods are called.)
     *
     * @param EloquentModel|CustomDatabaseNotification $model
     * @param array $dirtyAttributes
     */
    protected function onChange(EloquentModel $model, array $dirtyAttributes = null)
    {
        $this->forgetUsersNotifications($model->forUserId);
    }

    /**
     * Return the fully qualified class name of the Models this repository returns.
     *
     * @return string
     */
    public function getModelClass()
    {
        return CustomDatabaseNotification::class;
    }
}
