import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// توكن الدخول يُحفظ مشفّراً (Keystore على أندرويد / Keychain على iOS)
/// وليس في SharedPreferences كنص عادي.
class TokenStorage {
  TokenStorage([FlutterSecureStorage? storage])
      : _storage = storage ?? const FlutterSecureStorage();

  static const _key = 'auth_token';
  static const _introKey = 'intro_seen';

  final FlutterSecureStorage _storage;

  Future<String?> read() => _storage.read(key: _key);

  Future<void> write(String token) => _storage.write(key: _key, value: token);

  Future<void> clear() => _storage.delete(key: _key);

  /// هل شاهد المستخدم حركة البداية الكاملة من قبل؟
  Future<bool> introSeen() async => await _storage.read(key: _introKey) == '1';

  Future<void> markIntroSeen() => _storage.write(key: _introKey, value: '1');
}
