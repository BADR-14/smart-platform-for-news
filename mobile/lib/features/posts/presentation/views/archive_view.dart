import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/widgets/paged_list_view.dart';
import '../../domain/post.dart';
import '../controllers/post_list_controllers.dart';
import '../widgets/post_card.dart';

/// سجل الأرشيف: المنشورات المؤرشفة، ويمكن إعادة نشر أي منها من قائمة خياراته.
class ArchiveView extends GetView<ArchivedPostsController> {
  const ArchiveView({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('الأرشيف')),
      body: PagedListView<Post>(
        controller: controller,
        emptyIcon: Icons.archive_outlined,
        emptyTitle: 'الأرشيف فارغ',
        emptyMessage: 'المنشورات التي تؤرشفها تختفي من المنصة وتبقى هنا. يمكنك إعادة نشرها في أي وقت من قائمة خيارات المنشور.',
        itemBuilder: (context, post, index) => PostCard(key: ValueKey(post.id), post: post, showStatus: true),
      ),
    );
  }
}
