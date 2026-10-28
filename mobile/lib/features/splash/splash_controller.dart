import 'dart:async';

import 'package:get/get.dart';

import '../../app/routes.dart';
import '../../core/network/api_exception.dart';
import '../../core/session/session_service.dart';
import '../auth/domain/auth_repository.dart';

/// شاشة البداية: إن وُجد توكن محفوظ نتحقق منه ونجلب الحساب، ثم ندخل التطبيق.
/// الزائر (بدون توكن) يدخل مباشرة للتصفح.
class SplashController extends GetxController {
  SplashController(this._auth, this._session);

  /// أول تشغيل بعد التثبيت: تُعرض حركة الشعار كاملة. في كل تشغيل بعده تُعرض
  /// نسخة سريعة حتى لا ينتظر المستخدم خمس ثوانٍ كلما فتح التطبيق. تُضبط في main.dart.
  static bool firstLaunch = true;

  /// مدة حركة تركيب الشعار نفسها.
  static Duration get animationDuration => Duration(milliseconds: firstLaunch ? 4400 : 1500);

  /// أقل مدة تبقى فيها الشاشة: الحركة + لحظة يُرى فيها الشعار مكتملاً.
  static Duration get introDuration => Duration(milliseconds: firstLaunch ? 5200 : 1750);

  final AuthRepository _auth;
  final SessionService _session;

  /// تعذّر الوصول للخادم (التوكن موجود لكن لم نستطع التحقق منه).
  final failed = false.obs;

  Completer<void> _intro = Completer<void>();

  /// نقرة على الشاشة تتخطّى حركة الشعار (المستخدم لا يُجبَر على الانتظار).
  void skipIntro() {
    if (!_intro.isCompleted) _intro.complete();
  }

  // onReady وليس onInit: التنقل غير مسموح قبل اكتمال بناء أول إطار
  @override
  void onReady() {
    super.onReady();
    start();
  }

  Future<void> start() async {
    failed.value = false;

    // الحركة والتحقق من الجلسة يجريان معاً، وننتظر الأطول منهما (أو نقرة تخطٍّ)
    _intro = Completer<void>();
    Timer(introDuration, skipIntro);
    final intro = _intro.future;

    if (!_session.hasStoredToken) {
      await intro;
      Get.offAllNamed(Routes.shell);
      return;
    }

    try {
      final account = await _auth.me();
      await intro;

      _session.updateAccount(account);
      Get.offAllNamed(Routes.shell);
    } on ApiException catch (error) {
      // توكن منتهٍ: ApiClient أنهى الجلسة وانتقل لشاشة الدخول
      if (!error.isUnauthorized) failed.value = true;
    } catch (_) {
      failed.value = true;
    }
  }
}
