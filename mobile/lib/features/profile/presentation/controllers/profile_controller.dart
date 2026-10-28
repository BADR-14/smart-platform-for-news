import 'dart:io';

import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';

import '../../../../core/session/session_service.dart';
import '../../../../core/utils/feedback.dart';
import '../../../../core/widgets/dialogs.dart';
import '../../../auth/domain/auth_repository.dart';
import '../../../shared/models/account.dart';
import '../../domain/profile.dart';
import '../../domain/profile_repository.dart';

/// تبويب "حسابي": الملف الشخصي، تغيير الصورة، وتسجيل الخروج.
class ProfileController extends GetxController {
  ProfileController(this._repository, this._auth, this._session);

  final ProfileRepository _repository;
  final AuthRepository _auth;
  final SessionService _session;

  final Rxn<Profile> profile = Rxn<Profile>();
  final Rxn<Object> error = Rxn<Object>();
  final isLoading = false.obs;
  final isUploadingAvatar = false.obs;

  Worker? _sessionWorker;
  int? _loadedAccountId;

  @override
  void onInit() {
    super.onInit();

    if (_session.isLoggedIn) load();

    // نعيد التحميل عند تغيّر الحساب نفسه (دخول / خروج) أو حالة توثيقه فقط،
    // لا عند كل تحديث لبياناته — حتى لا ندخل في حلقة مع [_syncSession].
    _sessionWorker = ever(_session.account, (account) {
      if (account == null) {
        _loadedAccountId = null;
        profile.value = null;
        error.value = null;
      } else if (account.id != _loadedAccountId || _verificationChanged(account)) {
        load();
      }
    });
  }

  /// الجلسة تحدّثت من شاشة التوثيق (موافقة جديدة أو تحوّل لحساب شركة) والملف المعروض قديم.
  bool _verificationChanged(Account account) {
    final current = profile.value;
    return current != null && (current.isVerified != account.isVerified || current.type != account.type);
  }

  @override
  void onClose() {
    _sessionWorker?.dispose();
    super.onClose();
  }

  Future<void> load() async {
    final account = _session.account.value;
    if (account == null) return;

    _loadedAccountId = account.id;
    if (profile.value == null) isLoading.value = true;
    error.value = null;

    try {
      setProfile(await _repository.me());
    } catch (caught) {
      if (profile.value == null) error.value = caught;
    } finally {
      isLoading.value = false;
    }
  }

  /// يحدّث الملف المعروض ويُبقي بيانات الجلسة (الاسم، الصورة، حالة التوثيق) مطابقة له.
  void setProfile(Profile fresh) {
    profile.value = fresh;
    _syncSession(fresh);
  }

  void _syncSession(Profile fresh) {
    final account = _session.account.value;
    if (account == null || account.id != fresh.id) return;

    final changed = account.name != fresh.name ||
        account.avatar != fresh.avatar ||
        account.type != fresh.type ||
        account.isVerified != fresh.isVerified ||
        account.verificationStatus != fresh.verificationStatus ||
        account.companyId != fresh.companyId;

    if (!changed) return;

    _session.updateAccount(account.copyWith(
      name: fresh.name,
      avatar: fresh.avatar,
      type: fresh.type,
      isVerified: fresh.isVerified,
      verificationStatus: fresh.verificationStatus,
      companyId: fresh.companyId,
    ));
  }

  Future<void> changeAvatar() async {
    if (isUploadingAvatar.value) return;

    final XFile? picked;
    try {
      picked = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 85, maxWidth: 800);
    } catch (_) {
      AppFeedback.error('تعذّر فتح معرض الصور.');
      return;
    }
    if (picked == null) return;

    isUploadingAvatar.value = true;

    try {
      final url = await _repository.uploadAvatar(File(picked.path));
      final current = profile.value;
      if (current != null) setProfile(current.withAvatar(url));

      AppFeedback.success('تم تحديث الصورة');
    } catch (caught) {
      AppFeedback.error(caught);
    } finally {
      isUploadingAvatar.value = false;
    }
  }

  Future<void> logout() async {
    final confirmed = await AppDialogs.confirm(
      title: 'تسجيل الخروج',
      message: 'هل تريد تسجيل الخروج من حسابك؟',
      confirmLabel: 'خروج',
    );
    if (!confirmed) return;

    try {
      await _auth.logout();
    } catch (_) {
      // حتى لو فشل الطلب (بدون إنترنت) ننهي الجلسة على الجهاز
    }

    await _session.clear();
    AppFeedback.info('تم تسجيل الخروج');
  }
}
