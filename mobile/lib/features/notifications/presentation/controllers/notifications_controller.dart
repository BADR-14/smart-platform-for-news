import 'package:get/get.dart';

import '../../../../app/routes.dart';
import '../../../../core/controllers/paged_controller.dart';
import '../../../../core/network/paginated.dart';
import '../../../../core/services/push_service.dart';
import '../../../../core/session/session_service.dart';
import '../../../../core/utils/feedback.dart';
import '../../../../core/utils/json.dart';
import '../../domain/app_notification.dart';
import '../../domain/notifications_repository.dart';

/// تبويب الإشعارات + عدّاد غير المقروء الظاهر على الشريط السفلي.
class NotificationsController extends PagedController<AppNotification> {
  NotificationsController(this._repository, this._session);

  final NotificationsRepository _repository;
  final SessionService _session;

  final unreadCount = 0.obs;

  Worker? _sessionWorker;
  Worker? _pushWorker;
  int? _accountId;

  // الزائر ليس له إشعارات
  @override
  bool get loadOnInit => _session.isLoggedIn;

  @override
  void onInit() {
    _accountId = _session.account.value?.id;
    super.onInit();

    // وصل إشعار Push والتطبيق مفتوح: نحدّث القائمة والعدّاد بهدوء
    if (Get.isRegistered<PushService>()) {
      _pushWorker = ever(Get.find<PushService>().received, (_) => onTabOpened());
    }

    _sessionWorker = ever(_session.account, (account) {
      if (account?.id == _accountId) return;
      _accountId = account?.id;

      if (account == null) {
        items.clear();
        unreadCount.value = 0;
      } else {
        refreshList();
      }
    });
  }

  @override
  void onClose() {
    _pushWorker?.dispose();
    _sessionWorker?.dispose();
    super.onClose();
  }

  @override
  Future<Paginated<AppNotification>> fetchPage(int page) => _repository.list(page: page);

  @override
  void onPageLoaded(Paginated<AppNotification> page) {
    unreadCount.value = asInt(page.extra['unread_count'], unreadCount.value);
  }

  /// يُستدعى عند فتح تبويب الإشعارات: تحديث هادئ للقائمة والعدّاد.
  void onTabOpened() {
    if (_session.isLoggedIn) refreshList(silent: true);
  }

  /// الضغط على إشعار: يُعلَّم كمقروء ثم يُفتح ما يشير إليه.
  Future<void> open(AppNotification notification) async {
    if (!notification.isRead) markAsRead(notification);

    final postId = notification.postId;

    switch (notification.type) {
      case 'verification_reviewed':
        Get.toNamed(Routes.verification);
      case 'new_follower':
        final follower = notification.actorAccountId;
        if (follower != null) Get.toNamed(Routes.accountProfile, arguments: follower);
      case 'post_deleted':
      case 'account_banned':
        // لا توجد صفحة تُفتح: المنشور حُذف
        break;
      default:
        if (postId != null) Get.toNamed(Routes.postDetails, arguments: postId);
    }
  }

  Future<void> markAsRead(AppNotification notification) async {
    _replace(notification.markedRead());
    if (unreadCount.value > 0) unreadCount.value--;

    try {
      await _repository.markAsRead(notification.id);
    } catch (_) {
      // تبقى مقروءة محلياً، وتُصحَّح الحالة عند التحديث التالي
    }
  }

  Future<void> markAllAsRead() async {
    if (unreadCount.value == 0) return;

    try {
      await _repository.markAllAsRead();

      items.assignAll(items.map((item) => item.isRead ? item : item.markedRead()).toList());
      unreadCount.value = 0;
    } catch (error) {
      AppFeedback.error(error);
    }
  }

  Future<void> delete(AppNotification notification) async {
    final index = items.indexWhere((item) => item.id == notification.id);
    if (index == -1) return;

    // حذف فوري من الواجهة، وإعادة العنصر إن فشل الطلب
    items.removeAt(index);
    if (!notification.isRead && unreadCount.value > 0) unreadCount.value--;

    try {
      await _repository.delete(notification.id);
    } catch (error) {
      items.insert(index > items.length ? items.length : index, notification);
      if (!notification.isRead) unreadCount.value++;
      AppFeedback.error(error);
    }
  }

  void _replace(AppNotification updated) {
    final index = items.indexWhere((item) => item.id == updated.id);
    if (index != -1) items[index] = updated;
  }
}
