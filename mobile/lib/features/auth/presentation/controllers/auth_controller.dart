import 'package:get/get.dart';
import 'package:google_sign_in/google_sign_in.dart';

import '../../../../app/routes.dart';
import '../../../../core/config/app_config.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/session/session_service.dart';
import '../../../../core/utils/feedback.dart';
import '../../domain/auth_repository.dart';
import '../../../shell/presentation/shell_controller.dart';

/// منطق شاشات المصادقة: الدخول، التسجيل، تأكيد البريد، واستعادة كلمة المرور.
class AuthController extends GetxController {
  AuthController(this._repository, this._session);

  final AuthRepository _repository;
  final SessionService _session;

  final isLoading = false.obs;

  /// أخطاء التحقق القادمة من الخادم لكل حقل (تُعرض تحت الحقل).
  final fieldErrors = <String, String>{}.obs;

  bool _googleInitialized = false;

  // ==================== الدخول ====================

  Future<void> login({required String email, required String password}) async {
    await _run(() async {
      final result = await _repository.login(email: email, password: password);
      await _enter(result);
    }, onError: (error) {
      // حساب غير مؤكد البريد: نرسل رمزاً جديداً وننتقل لشاشة التأكيد
      if (error.code == 'email_unverified') {
        _repository.resendOtp(email).ignore();
        Get.toNamed(Routes.otp, arguments: email);
        AppFeedback.info('أكّد بريدك الإلكتروني أولاً. أرسلنا لك رمزاً جديداً.');
        return true;
      }
      return false;
    });
  }

  Future<void> loginWithGoogle() async {
    await _run(() async {
      final signIn = GoogleSignIn.instance;

      if (!_googleInitialized) {
        await signIn.initialize(serverClientId: AppConfig.googleServerClientId);
        _googleInitialized = true;
      }

      final googleAccount = await signIn.authenticate();
      final idToken = googleAccount.authentication.idToken;

      if (idToken == null) {
        throw const ApiException('تعذّر الحصول على بيانات حساب Google.');
      }

      await _enter(await _repository.loginWithGoogle(idToken));
    });
  }

  // ==================== التسجيل وتأكيد البريد ====================

  Future<void> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _run(() async {
      await _repository.register(
        name: name,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );

      Get.toNamed(Routes.otp, arguments: email);
      AppFeedback.success('أرسلنا رمز التحقق إلى بريدك الإلكتروني.');
    });
  }

  Future<void> verifyOtp({required String email, required String code}) async {
    await _run(() async {
      final result = await _repository.verifyOtp(email: email, code: code);
      await _enter(result);
      AppFeedback.success('تم تأكيد بريدك الإلكتروني.');
    });
  }

  Future<void> resendOtp(String email) async {
    await _run(() async {
      await _repository.resendOtp(email);
      AppFeedback.success('أرسلنا رمزاً جديداً إلى بريدك.');
    });
  }

  // ==================== استعادة كلمة المرور ====================

  Future<void> sendResetCode(String email) async {
    await _run(() async {
      await _repository.sendResetCode(email);
      Get.toNamed(Routes.resetPassword, arguments: email);
      AppFeedback.success('إذا كان البريد مسجلاً فسيصلك رمز لإعادة التعيين.');
    });
  }

  Future<void> resetPassword({
    required String email,
    required String code,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _run(() async {
      await _repository.resetPassword(
        email: email,
        code: code,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );

      Get.offAllNamed(Routes.login);
      AppFeedback.success('تم تغيير كلمة المرور. سجّل الدخول بكلمة المرور الجديدة.');
    });
  }

  // ==================== مشترك ====================

  Future<void> _enter(AuthResult result) async {
    await _session.start(result.token, result.account);
    ShellController.open();
  }

  /// ينفّذ طلباً مع إدارة حالة التحميل وعرض الأخطاء.
  /// [onError] يرجع true إذا عالج الخطأ بنفسه.
  Future<void> _run(Future<void> Function() action, {bool Function(ApiException error)? onError}) async {
    if (isLoading.value) return;

    isLoading.value = true;
    fieldErrors.clear();

    try {
      await action();
    } on ApiException catch (error) {
      if (onError?.call(error) ?? false) return;

      if (error.isValidation) {
        fieldErrors.assignAll(error.fieldErrors.map((key, value) => MapEntry(key, value.first)));
      }

      AppFeedback.error(error);
    } on GoogleSignInException catch (error) {
      // إغلاق نافذة Google من المستخدم ليس خطأً يستحق رسالة
      if (error.code != GoogleSignInExceptionCode.canceled) {
        AppFeedback.error('تعذّر تسجيل الدخول عبر Google.');
      }
    } catch (error) {
      AppFeedback.error(error);
    } finally {
      isLoading.value = false;
    }
  }
}
