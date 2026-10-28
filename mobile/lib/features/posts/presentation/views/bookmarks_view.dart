import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/widgets/paged_list_view.dart';
import '../../domain/post.dart';
import '../controllers/post_list_controllers.dart';
import '../widgets/post_card.dart';

class BookmarksView extends GetView<BookmarksController> {
  const BookmarksView({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('المفضلة')),
      body: PagedListView<Post>(
        controller: controller,
        emptyIcon: Icons.favorite_border_rounded,
        emptyTitle: 'مفضلتك فارغة',
        emptyMessage: 'اضغط على القلب في أي منشور لتضيفه إلى المفضلة وتعود إليه من هنا.',
        itemBuilder: (context, post, index) => PostCard(key: ValueKey(post.id), post: post),
      ),
    );
  }
}
