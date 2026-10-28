import 'package:get/get.dart';

import '../../../app/routes.dart';
import '../../../core/services/push_service.dart';
import '../../../core/session/session_service.dart';
import '../../../core/widgets/dialogs.dart';
import '../../notifications/presentation/controllers/notifications_controller.dart';
import '../../profile/presentation/controllers/profile_controller.dart';

/// الهيكل الرئيسي: التبويب الحالي في الشريط السفلي + زر إنشاء منشور.
class ShellController extends GetxController {
  ShellController(this._session, this._notifications, this._profile);

  static const homeTab = 0;
  static const companiesTab = 1;
  static const notificationsTab = 2;
  static const profileTab = 3;

  final SessionService _session;
  final NotificationsController _notifications;
  final ProfileController _profile;

  final index = homeTab.obs;

  /// زر "منشور جديد" يختفي أثناء التمرير للأسفل ويعود عند التمرير للأعلى.
  final fabVisible = true.obs;

  /// يدخل إلى الهيكل الرئيسي (بعد تسجيل الدخول، التصفح كزائر ...).
  ///
  /// إن كان الهيكل مفتوحاً في الخلف (زائر فتح شاشة الدخول فوقه) نعود إليه كما هو،
  /// وتبويباته تحدّث نفسها لأنها تراقب حالة الجلسة. لا نستبدله بنسخة جديدة منه:
  /// استبدال صفحة بنفسها يجعل GetX يغلق متحكماتها بينما الصفحة الجديدة ما زالت تستعملها،
  /// فتتوقف المزامنة بين الشاشات (مثل زر المتابعة الذي لا يتغيّر).
  static void open() {
    if (Get.isRegistered<ShellController>()) {
      Get.until((route) => route.settings.name == Routes.shell);
    } else {
      Get.offAllNamed(Routes.shell);
    }
  }

  Worker? _pushWorker;

  @override
  void onInit() {
    super.onInit();

    // فتح التطبيق بالضغط على إشعار ينقل إلى تبويب الإشعارات
    if (Get.isRegistered<PushService>()) {
      final push = Get.find<PushService>();
      if (push.opened.value > 0 && _session.isLoggedIn) index.value = notificationsTab;
      _pushWorker = ever(push.opened, (_) {
        if (_session.isLoggedIn) select(notificationsTab);
      });
    }
  }

  @override
  void onClose() {
    _pushWorker?.dispose();
    super.onClose();
  }

  void select(int tab) {
    // فتح التبويب يحدّث بياناته بهدوء (عدّاد الإشعارات، إحصاءات الحساب)
    if (tab == notificationsTab) _notifications.onTabOpened();
    if (tab == profileTab && tab != index.value) _profile.load();

    index.value = tab;
  }

  /// النشر متاح للحسابات الموثّقة فقط (مستخدم موثّق أو شركة موافق عليها).
  Future<void> createPost() async {
    if (!_session.requireLogin('سجّل الدخول ووثّق حسابك لتتمكن من نشر المحاضرات والفعاليات.')) return;

    if (_session.account.value?.canPublish ?? false) {
      Get.toNamed(Routes.postForm);
      return;
    }

    final goVerify = await AppDialogs.confirm(
      title: 'النشر للحسابات الموثّقة',
      message: 'لتتمكن من النشر يجب توثيق حسابك أولاً، كمستخدم أو كشركة. التوثيق يستغرق دقائق.',
      confirmLabel: 'توثيق الحساب',
    );

    if (goVerify) Get.toNamed(Routes.verification);
  }

  /// زر الرجوع من تبويب آخر يعيد إلى الرئيسية بدل إغلاق التطبيق.
  void backToHome() => index.value = homeTab;
}
