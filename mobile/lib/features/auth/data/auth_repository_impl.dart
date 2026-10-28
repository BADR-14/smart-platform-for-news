import '../../../core/network/api_client.dart';
import '../../../core/utils/json.dart';
import '../../shared/models/account.dart';
import '../domain/auth_repository.dart';

class AuthRepositoryImpl implements AuthRepository {
  AuthRepositoryImpl(this._api);

  final ApiClient _api;

  static const _deviceName = 'mobile-app';

  @override
  Future<void> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _api.post('/register', body: {
      'name': name,
      'email': email,
      'password': password,
      'password_confirmation': passwordConfirmation,
    });
  }

  @override
  Future<AuthResult> verifyOtp({required String email, required String code}) async {
    final json = await _api.post('/verify-otp', body: {'email': email, 'otp_code': code, 'device_name': _deviceName});
    return _parseResult(json);
  }

  @override
  Future<void> resendOtp(String email) async {
    await _api.post('/resend-otp', body: {'email': email});
  }

  @override
  Future<AuthResult> login({required String email, required String password}) async {
    final json = await _api.post('/login', body: {'email': email, 'password': password, 'device_name': _deviceName});
    return _parseResult(json);
  }

  @override
  Future<AuthResult> loginWithGoogle(String idToken) async {
    final json = await _api.post('/auth/google', body: {'id_token': idToken, 'device_name': _deviceName});
    return _parseResult(json);
  }

  @override
  Future<Account> me() async {
    final json = await _api.get('/me');
    return Account.fromJson(asJson(json['data']));
  }

  @override
  Future<void> logout() async {
    await _api.post('/logout');
  }

  @override
  Future<void> sendResetCode(String email) async {
    await _api.post('/password/otp', body: {'email': email});
  }

  @override
  Future<void> verifyResetCode({required String email, required String code}) async {
    await _api.post('/password/otp/verify', body: {'email': email, 'otp_code': code});
  }

  @override
  Future<void> resetPassword({
    required String email,
    required String code,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _api.post('/password/reset', body: {
      'email': email,
      'otp_code': code,
      'password': password,
      'password_confirmation': passwordConfirmation,
    });
  }

  AuthResult _parseResult(Json json) {
    final data = asJson(json['data']);

    return AuthResult(
      token: asString(data['access_token']),
      account: Account.fromJson(asJson(data['account'])),
    );
  }
}
