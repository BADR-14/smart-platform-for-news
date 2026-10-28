class NotificationPreferences {
  const NotificationPreferences({required this.emailNotifications, required this.loginNotifications});

  /// إرسال نسخة من الإشعارات إلى البريد.
  final bool emailNotifications;

  /// تنبيه بالبريد عند كل تسجيل دخول جديد.
  final bool loginNotifications;
}

abstract interface class SettingsRepository {
  Future<NotificationPreferences> preferences();

  Future<NotificationPreferences> updatePreferences({bool? emailNotifications, bool? loginNotifications});

  Future<void> changePassword({required String current, required String next, required String confirmation});

  /// [password] مطلوبة للحسابات التي لها كلمة مرور (حسابات Google تُحذف بدونها).
  Future<void> deleteAccount({String? password});
}
