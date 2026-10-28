import '../../shared/models/account.dart';

class AuthResult {
  const AuthResult({required this.token, required this.account});

  final String token;
  final Account account;
}

/// عقد المصادقة — المتحكمات تعتمد على هذه الواجهة وليس على Dio مباشرة.
abstract interface class AuthRepository {
  /// ينشئ الحساب ويرسل رمز التحقق إلى البريد. لا يسجّل الدخول.
  Future<void> register({required String name, required String email, required String password, required String passwordConfirmation});

  /// تأكيد البريد بالرمز ثم تسجيل الدخول مباشرة.
  Future<AuthResult> verifyOtp({required String email, required String code});

  Future<void> resendOtp(String email);

  Future<AuthResult> login({required String email, required String password});

  Future<AuthResult> loginWithGoogle(String idToken);

  Future<Account> me();

  Future<void> logout();

  // ---- استعادة كلمة المرور برمز يُرسل للبريد ----

  Future<void> sendResetCode(String email);

  Future<void> verifyResetCode({required String email, required String code});

  Future<void> resetPassword({required String email, required String code, required String password, required String passwordConfirmation});
}
