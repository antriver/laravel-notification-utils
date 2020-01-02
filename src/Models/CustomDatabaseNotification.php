<?php

namespace Antriver\LaravelNotificationUtils\Models;

use Auth;
use Lang;
use Antriver\LaravelNotificationUtils\Types\NotificationTypes;
use Tmd\LaravelSite\Libraries\LanguageHelpers;
use Tmd\LaravelSite\Models\Base\AbstractModel;
use Tmd\LaravelSite\Models\Interfaces\BelongsToUserInterface;
use Tmd\LaravelSite\Models\Traits\BelongsToUserTrait;
use Tmd\LaravelSite\Models\Traits\CreatedAtWithoutUpdatedAtTrait;
use Tmd\LaravelSite\Models\User;
use Tmd\LaravelSite\Repositories\UserRepository;

/**
 * Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification
 *
 * @property int $id
 * @property int $type
 * @property int $forUserId
 * @property int|null $fromUserId
 * @property string|null $text
 * @property \Carbon\Carbon $createdAt
 * @property string|null $seenAt
 * @method static \Illuminate\Database\Eloquent\Builder|\Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification
 *     unseen()
 * @method static \Illuminate\Database\Eloquent\Builder|\Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification
 *     whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification
 *     whereForUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification
 *     whereFromUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification
 *     whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification
 *     whereSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification
 *     whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Antriver\LaravelNotificationUtils\Models\CustomDatabaseNotification
 *     whereType($value)
 * @mixin \Eloquent
 */
class CustomDatabaseNotification extends AbstractModel implements BelongsToUserInterface
{
    use BelongsToUserTrait;
    use CreatedAtWithoutUpdatedAtTrait;

    protected $table = 'notifications';

    /**
     * @var int[]
     */
    public $fromUserIds;

    /**
     * Array of Users the notification is from.
     * (Must be populated by some external source)
     *
     * @var User[]
     */
    protected $fromUsers;

    public function getTypeName()
    {
        return NotificationTypes::getName($this->type);
    }

    public function __toString()
    {
        return $this->getText();
    }

    public function scopeUnseen($query)
    {
        return $query->whereNull('seenAt');
    }

    public function toArray()
    {
        $array = parent::toArray();

        $array['action'] = $this->getTypeName();
        $array['groupCount'] = $this->getGroupCount();

        return $array;
    }

    public function getUserId()
    {
        return $this->forUserId;
    }

    /**
     * Returns an array of userIDs that this grouped notification is from.
     * The fromUserIds value should be populated by a GROUP_CONCAT in the query that loaded this notification.
     *
     * @return array
     */
    public function getFromUserIds()
    {
        if (!is_null($this->fromUserIds)) {
            return $this->fromUserIds;
        }

        if (isset($this->attributes['fromUserIds'])) {
            $this->fromUserIds = explode(',', $this->attributes['fromUserIds']);
        } elseif ($this->fromUserId) {
            $this->fromUserIds = [$this->fromUserId];
        } else {
            $this->fromUserIds = [];
        }

        return $this->fromUserIds;
    }

    /**
     * Returns the number of notifications that were grouped together together into this one.
     * Populated by the COUNT() on the query in CustomDatabaseNotificationRepository.
     */
    public function getGroupCount()
    {
        return isset($this->attributes['groupCount']) ? $this->attributes['groupCount'] : 1;
    }

    /**
     * @return User[]
     */
    public function getFromUsers()
    {
        return $this->fromUsers;
    }

    /**
     * @param User[] $fromUsers
     */
    public function setFromUsers($fromUsers)
    {
        $this->fromUsers = $fromUsers;
    }
}
