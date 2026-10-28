/// إعدادات تُحدَّد وقت البناء عبر `--dart-define` (لا يوجد عنوان مكتوب داخل الكود).
///
/// أمثلة:
/// ```
/// flutter run                                              # محاكي أندرويد (10.0.2.2)
/// flutter run --dart-define=API_URL=http://192.168.1.5:8000/api   # جهاز حقيقي
/// ```
abstract final class AppConfig {
  /// عنوان الـ API. الافتراضي يعمل على محاكي أندرويد:
  /// 10.0.2.2 هو عنوان الحاسوب المضيف من داخل المحاكي.
  static const String apiUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'http://10.0.2.2:8000/api',
  );

  /// الـ Web Client ID من Google Cloud (يجب أن يكون مضافاً أيضاً في
  /// GOOGLE_ALLOWED_CLIENT_IDS بالباك-إند). فارغ = زر Google مخفي.
  static const String googleServerClientId = String.fromEnvironment(
    'GOOGLE_SERVER_CLIENT_ID',
  );

  static bool get googleSignInEnabled => googleServerClientId.isNotEmpty;

  static const String appName = 'Smart Platform for News';
}
