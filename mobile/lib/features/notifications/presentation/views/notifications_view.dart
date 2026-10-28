import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../app/routes.dart';
import '../../../../core/session/session_service.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/paged_list_view.dart';
import '../../../../core/widgets/state_views.dart';
import '../../domain/app_notification.dart';
import '../controllers/notifications_controller.dart';

class NotificationsView extends GetView<NotificationsController> {
  const NotificationsView({super.key});

  @override
  Widget build(BuildContext context) {
    final session = Get.find<SessionService>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('الإشعارات'),
        actions: [
          Obx(
            () => controller.unreadCount.value == 0
                ? const SizedBox.shrink()
                : TextButton(
                    style: TextButton.styleFrom(foregroundColor: AppColors.accent100),
                    onPressed: controller.markAllAsRead,
                    child: const Text('قراءة الكل'),
                  ),
          ),
        ],
      ),
      body: Obx(() {
        if (session.account.value == null) {
          return EmptyView(
            icon: Icons.notifications_none,
            title: 'الإشعارات للمستخدمين المسجّلين',
            message: 'سجّل الدخول لتصلك إشعارات التعليقات والتفاعلات ونتائج التوثيق.',
            action: AppButton(
              label: 'تسجيل الدخول',
              icon: Icons.login,
              expanded: false,
              onPressed: () => Get.toNamed(Routes.login),
            ),
          );
        }

        return PagedListView<AppNotification>(
          controller: controller,
          padding: const EdgeInsets.symmetric(vertical: 8),
          separator: const Divider(indent: 70),
          emptyIcon: Icons.notifications_none,
          emptyTitle: 'لا توجد إشعارات',
          emptyMessage: 'ستظهر هنا التعليقات والتفاعلات على منشوراتك ونتائج طلبات التوثيق.',
          itemBuilder: (context, notification, index) => Dismissible(
            key: ValueKey(notification.id),
            direction: DismissDirection.endToStart,
            background: Container(
              color: AppColors.dangerBg,
              alignment: AlignmentDirectional.centerEnd,
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: const Icon(Icons.delete_outline, color: AppColors.danger),
            ),
            onDismissed: (_) => controller.delete(notification),
            child: _NotificationTile(notification: notification, onTap: () => controller.open(notification)),
          ),
        );
      }),
    );
  }
}

class _NotificationTile extends StatelessWidget {
  const _NotificationTile({required this.notification, required this.onTap});

  final AppNotification notification;
  final VoidCallback onTap;

  (IconData, Color) get _appearance => switch (notification.type) {
        'post_reacted' => (Icons.favorite_border, AppColors.danger),
        'post_commented' || 'comment_reply' => (Icons.mode_comment_outlined, AppColors.info),
        'post_reviewed' => (Icons.star_outline_rounded, AppColors.star),
        'new_follower' => (Icons.person_add_alt_1_outlined, AppColors.success),
        'verification_reviewed' => (Icons.verified_outlined, AppColors.accent600),
        'post_moderated' || 'post_deleted' || 'account_banned' => (Icons.gpp_maybe_outlined, AppColors.warning),
        'login_success' || 'account_created' => (Icons.lock_outline, AppColors.brand600),
        _ => (Icons.notifications_none, AppColors.brand600),
      };

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;
    final (icon, color) = _appearance;
    final unread = !notification.isRead;

    return Material(
      color: unread ? AppColors.accent50 : AppColors.surface,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(color: color.withValues(alpha: 0.12), shape: BoxShape.circle),
                child: Icon(icon, size: 20, color: color),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      notification.title,
                      style: theme.titleSmall?.copyWith(fontWeight: unread ? FontWeight.w700 : FontWeight.w600),
                    ),
                    if (notification.body.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        notification.body,
                        maxLines: 3,
                        overflow: TextOverflow.ellipsis,
                        style: theme.bodyMedium?.copyWith(color: AppColors.inkSoft, height: 1.5),
                      ),
                    ],
                    const SizedBox(height: 4),
                    Text(Formatters.timeAgo(notification.createdAt), style: theme.bodySmall?.copyWith(color: AppColors.muted)),
                  ],
                ),
              ),
              if (unread)
                Container(
                  width: 9,
                  height: 9,
                  margin: const EdgeInsetsDirectional.only(start: 8, top: 6),
                  decoration: const BoxDecoration(color: AppColors.accent500, shape: BoxShape.circle),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
