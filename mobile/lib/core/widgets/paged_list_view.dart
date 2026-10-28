import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../controllers/paged_controller.dart';
import 'motion.dart';
import 'state_views.dart';

/// قائمة مرتبطة بـ [PagedController]: تعرض حالات التحميل والخطأ والفراغ،
/// وتدعم السحب للتحديث وتحميل المزيد تلقائياً قرب نهاية القائمة.
class PagedListView<T> extends StatelessWidget {
  const PagedListView({
    super.key,
    required this.controller,
    required this.itemBuilder,
    required this.emptyTitle,
    this.emptyMessage,
    this.emptyIcon = Icons.inbox_outlined,
    this.header,
    this.padding = const EdgeInsets.all(14),
    this.separator = const SizedBox(height: 12),
  });

  final PagedController<T> controller;
  final Widget Function(BuildContext context, T item, int index) itemBuilder;
  final String emptyTitle;
  final String? emptyMessage;
  final IconData emptyIcon;

  /// عنصر يظهر أعلى القائمة ويتمرر معها (مثل شريط المنشورات المميزة).
  final Widget? header;
  final EdgeInsetsGeometry padding;
  final Widget separator;

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final items = controller.items;
      final loading = controller.isLoading.value;
      final error = controller.error.value;
      final loadingMore = controller.isLoadingMore.value;

      // حالات تملأ الشاشة: يجب أن تبقى قابلة للسحب حتى يعمل "اسحب للتحديث"
      Widget? fullState;
      if (loading && items.isEmpty) {
        fullState = const SkeletonList();
      } else if (error != null && items.isEmpty) {
        fullState = ErrorView(error: error, onRetry: controller.refreshList);
      } else if (items.isEmpty) {
        fullState = EmptyView(title: emptyTitle, message: emptyMessage, icon: emptyIcon);
      }

      final headerCount = header == null ? 0 : 1;

      return RefreshIndicator(
        onRefresh: () => controller.refreshList(silent: true),
        child: NotificationListener<ScrollNotification>(
          onNotification: (notification) {
            if (notification.metrics.pixels >= notification.metrics.maxScrollExtent - 400) {
              controller.loadMore();
            }
            return false;
          },
          child: fullState != null
              ? CustomScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  slivers: [
                    if (header != null) SliverToBoxAdapter(child: Padding(padding: padding, child: header)),
                    // الهيكل الوهمي يبدأ من الأعلى، وباقي الحالات تتوسط الشاشة
                    if (fullState is SkeletonList)
                      SliverToBoxAdapter(child: fullState)
                    else
                      SliverFillRemaining(hasScrollBody: false, child: fullState),
                  ],
                )
              : ListView.separated(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: padding,
                  itemCount: items.length + headerCount + 1,
                  separatorBuilder: (context, index) => separator,
                  itemBuilder: (context, index) {
                    if (header != null && index == 0) return header!;

                    final itemIndex = index - headerCount;

                    if (itemIndex == items.length) {
                      return loadingMore
                          ? const Padding(
                              padding: EdgeInsets.symmetric(vertical: 12),
                              child: Center(child: SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2.4))),
                            )
                          : const SizedBox(height: 4);
                    }

                    return FadeSlideIn(index: itemIndex, child: itemBuilder(context, items[itemIndex], itemIndex));
                  },
                ),
        ),
      );
    });
  }
}
