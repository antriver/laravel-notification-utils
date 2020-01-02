## Notifications

See: https://laravel.com/docs/5.4/notifications

A premade Notification template is provided as AbstractLaravelNotification. It provides methods to convert the notification to an email, or a "custom database notification".

### CustomDatabaseNotificationChannel
A Laravel notification channel for sending notifications on that stores in the database. This is a much more flexible approach than using the built-in database channel, as that will just JSON-encode the Notification and store that.
 
### CustomDatabaseNotificationModel 
When a Notification is converted to a model to send via the CustomDatabaseNotificationChannel, it becomes this CustomDatabaseNotificationModel.

#### SingleCustomDatabaseNotificationModel
Represents one single line in the `notifications` table.

#### GroupedCustomDatabaseNotificationModel
Represents one single result from the `notifications` table, likely the result of a `GROUP BY` operation.
Technically there is nothing different between this and SingleCustomDatabaseNotificationModel but 2 classes exist to make it clearer what form is being used.

### CustomDatabaseNotificationRepository
Handles saving and retrieving this CustomDatabaseNotificationModels.

