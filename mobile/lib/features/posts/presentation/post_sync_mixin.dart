import 'package:get/get.dart';

import '../domain/post.dart';
import 'post_actions.dart';

/// يربط أي متحكم يعرض منشورات بتغييرات [PostActions]:
/// عند تعديل منشور أو حذفه في أي شاشة تتحدث نسخة هذا المتحكم تلقائياً.
mixin PostSyncMixin on GetxController {
  final List<Worker> _postWorkers = [];

  void listenToPostChanges({
    required void Function(Post post) onUpdated,
    required void Function(int postId) onDeleted,
    void Function()? onFeedChanged,
  }) {
    final actions = Get.find<PostActions>();

    _postWorkers
      ..add(ever<Post?>(actions.updated, (post) {
        if (post != null) onUpdated(post);
      }))
      ..add(ever<int?>(actions.deleted, (postId) {
        if (postId != null) onDeleted(postId);
      }));

    if (onFeedChanged != null) {
      _postWorkers.add(ever<int>(actions.feedVersion, (_) => onFeedChanged()));
    }
  }

  /// يسجّل أي مستمع إضافي ليُلغى تلقائياً عند إغلاق المتحكم.
  void trackWorker(Worker worker) => _postWorkers.add(worker);

  @override
  void onClose() {
    for (final worker in _postWorkers) {
      worker.dispose();
    }
    super.onClose();
  }
}

/// يستبدل المنشور ذا نفس المعرّف داخل قائمة منشورات.
void replacePost(RxList<Post> items, Post updated) {
  final index = items.indexWhere((post) => post.id == updated.id);
  if (index != -1) items[index] = updated;
}
