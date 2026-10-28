import '../../../core/network/api_client.dart';
import '../../../core/utils/json.dart';
import '../domain/settings_repository.dart';

class SettingsRepositoryImpl implements SettingsRepository {
  SettingsRepositoryImpl(this._api);

  final ApiClient _api;

  @override
  Future<NotificationPreferences> preferences() async {
    return _parse(await _api.get('/notification-settings'));
  }

  @override
  Future<NotificationPreferences> updatePreferences({bool? emailNotifications, bool? loginNotifications}) async {
    final json = await _api.patch('/notification-settings', body: {
      if (emailNotifications != null) 'email_notifications': emailNotifications,
      if (loginNotifications != null) 'login_notifications': loginNotifications,
    });

    return _parse(json);
  }

  @override
  Future<void> changePassword({required String current, required String next, required String confirmation}) async {
    await _api.post('/settings/password', body: {
      'current_password': current,
      'new_password': next,
      'new_password_confirmation': confirmation,
    });
  }

  @override
  Future<void> deleteAccount({String? password}) async {
    await _api.delete('/settings/account', body: {'password': password});
  }

  NotificationPreferences _parse(Json json) {
    final data = asJson(json['data']);

    return NotificationPreferences(
      emailNotifications: asBool(data['email_notifications'], true),
      loginNotifications: asBool(data['login_notifications'], true),
    );
  }
}
