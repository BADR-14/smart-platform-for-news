import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../app/routes.dart';
import '../../../../core/widgets/paged_list_view.dart';
import '../../domain/post.dart';
import '../controllers/post_list_controllers.dart';
import '../widgets/post_card.dart';

class MyPostsView extends GetView<MyPostsController> {
  const MyPostsView({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('منشوراتي'),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(52),
          child: SizedBox(
            height: 52,
            child: Obx(() {
              final selected = controller.status.value;

              return ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
                children: [
                  _StatusChip(label: 'الكل', selected: selected == null, onTap: () => controller.changeStatus(null)),
                  for (final status in PostStatus.values)
                    _StatusChip(
                      label: status.label,
                      selected: selected == status,
                      onTap: () => controller.changeStatus(status),
                    ),
                ],
              );
            }),
          ),
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => Get.toNamed(Routes.postForm),
        icon: const Icon(Icons.add),
        label: const Text('منشور جديد'),
      ),
      body: PagedListView<Post>(
        controller: controller,
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 90),
        emptyIcon: Icons.article_outlined,
        emptyTitle: 'لا توجد منشورات',
        emptyMessage: 'المنشورات التي تنشئها تظهر هنا بكل حالاتها.',
        itemBuilder: (context, post, index) => PostCard(key: ValueKey(post.id), post: post, showStatus: true),
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.label, required this.selected, required this.onTap});

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsetsDirectional.only(end: 8),
      child: ChoiceChip(label: Text(label), selected: selected, onSelected: (_) => onTap()),
    );
  }
}
