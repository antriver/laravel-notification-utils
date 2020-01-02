# Laravel Notification Utils

See: https://laravel.com/docs/5.8/notifications

A bunch of stuff to help with using Laravel's built in notification function.

## CustomDatabaseNotification
An alternative channel for storing notifications in the database. Laravel provides a built-in 'database' channel but it simply JSON encodes everything. This channel instead lets you have a custom table structure and model for the notification.

### CustomDatabaseNotificationChannel
A Laravel notification channel for sending notifications on that stores in the database.
 
### CustomDatabaseNotificationModel 
When a Notification is converted to a model to send via the CustomDatabaseNotificationChannel, it becomes this CustomDatabaseNotificationModel.

#### SingleCustomDatabaseNotificationModel
Represents one single line in the `notifications` table.

#### GroupedCustomDatabaseNotificationModel
Represents one single result from the `notifications` table, likely the result of a `GROUP BY` operation.
Technically there is nothing different between this and SingleCustomDatabaseNotificationModel but 2 classes exist to make it clearer what form is being used.

### CustomDatabaseNotificationRepository
Handles saving and retrieving this CustomDatabaseNotificationModels.
