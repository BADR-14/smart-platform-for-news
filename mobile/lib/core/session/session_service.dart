import 'package:get/get.dart';

import '../../app/routes.dart';
import '../../features/shared/models/account.dart';
import '../storage/token_storage.dart';
import '../widgets/dialogs.dart';

/// مصدر الحقيقة الوحيد لحالة الدخول في التطبيق.
///
/// التوكن يبقى في الذاكرة (ليقرأه [ApiClient] دون انتظار) ويُحفظ مشفّراً على الجهاز.
/// الزائر (بدون حساب) يستطيع التصفح، وكل إجراء يحتاج حساباً يمر عبر [requireLogin].
class SessionService extends GetxService {
  SessionService(this._tokenStorage);

  final TokenStorage _tokenStorage;

  final Rxn<Account> account = Rxn<Account>();

  String? _token;

  String? get token => _token;

  bool get isLoggedIn => _token != null && account.value != null;

  /// يُستدعى مرة واحدة عند تشغيل التطبيق لقراءة التوكن المحفوظ.
  Future<void> restoreToken() async {
    try {
      _token = await _tokenStorage.read();
    } catch (_) {
      // تعذّرت قراءة التخزين المشفّر (نادر بعد استعادة نسخة احتياطية): نبدأ كزائر
      _token = null;
    }
  }

  bool get hasStoredToken => _token != null && _token!.isNotEmpty;

  Future<void> start(String token, Account loggedIn) async {
    _token = token;
    account.value = loggedIn;
    await _tokenStorage.write(token);
  }

  void updateAccount(Account updated) => account.value = updated;

  Future<void> clear() async {
    _token = null;
    account.value = null;
    await _tokenStorage.clear();
  }

  /// يُستدعى من [ApiClient] عند رد 401: التوكن انتهى أو أُلغي.
  void handleUnauthorized() {
    if (_token == null) return;

    clear();

    if (Get.currentRoute != Routes.login) {
      Get.offAllNamed(Routes.login);
      Get.snackbar('انتهت الجلسة', 'سجّل الدخول مجدداً للمتابعة.', snackPosition: SnackPosition.BOTTOM);
    }
  }

  /// يتحقق أن المستخدم مسجّل قبل إجراء يحتاج حساباً، وإلا يعرض دعوة لتسجيل الدخول.
  /// يرجع true إذا كان مسجلاً.
  bool requireLogin([String message = 'سجّل الدخول لتتمكن من التفاعل مع المنشورات.']) {
    if (isLoggedIn) return true;

    AppDialogs.confirm(
      title: 'تسجيل الدخول مطلوب',
      message: message,
      confirmLabel: 'تسجيل الدخول',
      cancelLabel: 'لاحقاً',
    ).then((login) {
      if (login) Get.toNamed(Routes.login);
    });

    return false;
  }
}
