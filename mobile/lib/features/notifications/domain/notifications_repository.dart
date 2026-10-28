import '../../../core/network/paginated.dart';
import 'app_notification.dart';

abstract interface class NotificationsRepository {
  /// الصفحة تتضمن `unread_count` ضمن [Paginated.extra].
  Future<Paginated<AppNotification>> list({required int page});

  Future<int> unreadCount();

  Future<void> markAsRead(String id);

  Future<void> markAllAsRead();

  Future<void> delete(String id);
}
