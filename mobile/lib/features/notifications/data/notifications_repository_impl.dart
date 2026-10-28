import '../../../core/network/api_client.dart';
import '../../../core/network/paginated.dart';
import '../../../core/utils/json.dart';
import '../domain/app_notification.dart';
import '../domain/notifications_repository.dart';

class NotificationsRepositoryImpl implements NotificationsRepository {
  NotificationsRepositoryImpl(this._api);

  final ApiClient _api;

  @override
  Future<Paginated<AppNotification>> list({required int page}) async {
    final json = await _api.get('/notifications', query: {'page': page});
    return Paginated.fromJson(json, AppNotification.fromJson);
  }

  @override
  Future<int> unreadCount() async {
    final json = await _api.get('/notifications/unread-count');
    return asInt(asJson(json['data'])['unread_count']);
  }

  @override
  Future<void> markAsRead(String id) async {
    await _api.patch('/notifications/$id/read');
  }

  @override
  Future<void> markAllAsRead() async {
    await _api.patch('/notifications/read-all');
  }

  @override
  Future<void> delete(String id) async {
    await _api.delete('/notifications/$id');
  }
}
