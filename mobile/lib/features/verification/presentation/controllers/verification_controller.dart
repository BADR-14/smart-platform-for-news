import 'package:get/get.dart';

import '../../../../core/session/session_service.dart';
import '../../../auth/domain/auth_repository.dart';
import '../../domain/verification_repository.dart';
import '../../domain/verification_status.dart';

/// شاشة حالة التوثيق: تعرض حالة طلب المستخدم وطلب الشركة وما يمكن تقديمه.
class VerificationController extends GetxController {
  VerificationController(this._repository, this._auth, this._session);

  final VerificationRepository _repository;
  final AuthRepository _auth;
  final SessionService _session;

  final Rxn<VerificationStatus> status = Rxn<VerificationStatus>();
  final Rxn<Object> error = Rxn<Object>();
  final isLoading = false.obs;

  @override
  void onInit() {
    super.onInit();
    load();
  }

  Future<void> load() async {
    if (status.value == null) isLoading.value = true;
    error.value = null;

    try {
      await applyStatus(await _repository.status());
    } catch (caught) {
      if (status.value == null) error.value = caught;
    } finally {
      isLoading.value = false;
    }
  }

  /// يُستدعى أيضاً بعد تقديم طلب جديد (الخادم يرجع الحالة المحدّثة).
  Future<void> applyStatus(VerificationStatus fresh) async {
    status.value = fresh;
    await _syncSession(fresh);
  }

  /// إذا تغيّرت حالة التوثيق أو نوع الحساب (موافقة تمت أثناء فتح التطبيق)
  /// نحدّث بيانات الجلسة حتى تُفتح صلاحية النشر فوراً.
  Future<void> _syncSession(VerificationStatus fresh) async {
    final account = _session.account.value;
    if (account == null) return;

    final changed = account.isVerified != fresh.isVerified ||
        account.verificationStatus != fresh.status.value ||
        account.type != fresh.accountType;

    if (!changed) return;

    try {
      _session.updateAccount(await _auth.me());
    } catch (_) {
      // تُحدَّث الجلسة عند فتح التطبيق مجدداً
    }
  }
}
