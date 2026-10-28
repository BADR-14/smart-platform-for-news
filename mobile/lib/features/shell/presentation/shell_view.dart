import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:get/get.dart';

import '../../companies/presentation/views/companies_view.dart';
import '../../notifications/presentation/controllers/notifications_controller.dart';
import '../../notifications/presentation/views/notifications_view.dart';
import '../../posts/presentation/views/home_view.dart';
import '../../profile/presentation/views/profile_view.dart';
import '../../../core/widgets/motion.dart';
import 'shell_controller.dart';

class ShellView extends GetView<ShellController> {
  const ShellView({super.key});

  // IndexedStack يُبقي حالة كل تبويب (موضع التمرير، نص البحث) عند التنقل بينها
  static const _tabs = [HomeView(), CompaniesView(), NotificationsView(), ProfileView()];

  @override
  Widget build(BuildContext context) {
    final notifications = Get.find<NotificationsController>();

    return Obx(() {
      final index = controller.index.value;

      return PopScope(
        canPop: index == ShellController.homeTab,
        onPopInvokedWithResult: (didPop, _) {
          if (!didPop) controller.backToHome();
        },
        child: Scaffold(
          // التمرير للأسفل يخفي زر "منشور جديد" ليفسح للمحتوى، والتمرير للأعلى يعيده
          body: NotificationListener<UserScrollNotification>(
            onNotification: (notification) {
              if (notification.metrics.axis != Axis.vertical) return false;
              if (notification.direction == ScrollDirection.reverse) controller.fabVisible.value = false;
              if (notification.direction == ScrollDirection.forward) controller.fabVisible.value = true;
              return false;
            },
            child: FadeIndexedStack(index: index, children: _tabs),
          ),
          floatingActionButton: index == ShellController.homeTab
              ? AnimatedSlide(
                  offset: controller.fabVisible.value ? Offset.zero : const Offset(0, 1.6),
                  duration: const Duration(milliseconds: 260),
                  curve: Curves.easeOutCubic,
                  child: AnimatedOpacity(
                    opacity: controller.fabVisible.value ? 1 : 0,
                    duration: const Duration(milliseconds: 200),
                    child: ScaleIn(
                      child: FloatingActionButton(
                        tooltip: 'منشور جديد',
                        onPressed: controller.createPost,
                        child: const Icon(Icons.add),
                      ),
                    ),
                  ),
                )
              : null,
          bottomNavigationBar: NavigationBar(
            selectedIndex: index,
            onDestinationSelected: controller.select,
            destinations: [
              const NavigationDestination(
                icon: Icon(Icons.home_outlined),
                selectedIcon: ScaleIn(child: Icon(Icons.home)),
                label: 'الرئيسية',
              ),
              const NavigationDestination(
                icon: Icon(Icons.apartment_outlined),
                selectedIcon: ScaleIn(child: Icon(Icons.apartment)),
                label: 'الشركات',
              ),
              NavigationDestination(
                icon: _UnreadBadge(count: notifications.unreadCount.value, child: const Icon(Icons.notifications_none)),
                selectedIcon: ScaleIn(
                  child: _UnreadBadge(count: notifications.unreadCount.value, child: const Icon(Icons.notifications)),
                ),
                label: 'الإشعارات',
              ),
              const NavigationDestination(
                icon: Icon(Icons.person_outline),
                selectedIcon: ScaleIn(child: Icon(Icons.person)),
                label: 'حسابي',
              ),
            ],
          ),
        ),
      );
    });
  }
}

class _UnreadBadge extends StatelessWidget {
  const _UnreadBadge({required this.count, required this.child});

  final int count;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Badge(
      isLabelVisible: count > 0,
      label: Text(count > 99 ? '99+' : '$count'),
      child: child,
    );
  }
}
