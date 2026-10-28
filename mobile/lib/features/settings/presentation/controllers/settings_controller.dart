import 'package:get/get.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/session/session_service.dart';
import '../../../../core/utils/feedback.dart';
import '../../../../core/utils/navigation.dart';
import '../../domain/settings_repository.dart';
import '../../../shell/presentation/shell_controller.dart';

class SettingsController extends GetxController {
  SettingsController(this._repository, this._session);

  final SettingsRepository _repository;
  final SessionService _session;

  final Rxn<NotificationPreferences> preferences = Rxn<NotificationPreferences>();
  final isBusy = false.obs;
  final fieldErrors = <String, String>{}.obs;

  @override
  void onInit() {
    super.onInit();
    _loadPreferences();
  }

  Future<void> _loadPreferences() async {
    try {
      preferences.value = await _repository.preferences();
    } catch (error) {
      AppFeedback.error(error);
    }
  }

  Future<void> setEmailNotifications(bool value) => _updatePreferences(email: value);

  Future<void> setLoginNotifications(bool value) => _updatePreferences(login: value);

  Future<void> _updatePreferences({bool? email, bool? login}) async {
    final previous = preferences.value;
    if (previous == null) return;

    // تحديث فوري للمفتاح، والتراجع إن فشل الطلب
    preferences.value = NotificationPreferences(
      emailNotifications: email ?? previous.emailNotifications,
      loginNotifications: login ?? previous.loginNotifications,
    );

    try {
      preferences.value = await _repository.updatePreferences(emailNotifications: email, loginNotifications: login);
    } catch (error) {
      preferences.value = previous;
      AppFeedback.error(error);
    }
  }

  Future<void> changePassword({required String current, required String next, required String confirmation}) async {
    await _run(() async {
      await _repository.changePassword(current: current, next: next, confirmation: confirmation);

      AppNav.back<void>();
      AppFeedback.success('تم تغيير كلمة المرور');
    });
  }

  /// يرجع true إذا حُذف الحساب.
  Future<bool> deleteAccount(String password) async {
    var deleted = false;

    await _run(() async {
      await _repository.deleteAccount(password: password.isEmpty ? null : password);
      deleted = true;

      await _session.clear();
      ShellController.open();
      AppFeedback.info('تم حذف حسابك');
    });

    return deleted;
  }

  Future<void> _run(Future<void> Function() action) async {
    if (isBusy.value) return;

    isBusy.value = true;
    fieldErrors.clear();

    try {
      await action();
    } on ApiException catch (error) {
      if (error.isValidation) {
        fieldErrors.assignAll(error.fieldErrors.map((key, value) => MapEntry(key, value.first)));
      }
      AppFeedback.error(error);
    } catch (error) {
      AppFeedback.error(error);
    } finally {
      isBusy.value = false;
    }
  }
}
