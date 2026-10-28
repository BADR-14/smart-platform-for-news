import 'package:get/get.dart';

import '../../../../core/controllers/paged_controller.dart';
import '../../../../core/network/paginated.dart';
import '../../domain/post.dart';
import '../../domain/posts_repository.dart';
import '../post_sync_mixin.dart';

/// أساس قوائم المنشورات البسيطة: يبقيها متزامنة مع أي تعديل أو حذف يحدث في شاشة أخرى.
abstract class PostListController extends PagedController<Post> with PostSyncMixin {
  @override
  void onInit() {
    super.onInit();

    listenToPostChanges(
      onUpdated: onPostUpdated,
      onDeleted: (postId) => items.removeWhere((post) => post.id == postId),
      onFeedChanged: onFeedChanged,
    );
  }

  void onPostUpdated(Post updated) => replacePost(items, updated);

  void onFeedChanged() {}
}

/// "منشوراتي": كل منشورات الحساب بكل حالاتها، مع فلترة حسب الحالة.
class MyPostsController extends PostListController {
  MyPostsController(this._repository);

  final PostsRepository _repository;

  /// null = كل الحالات.
  final status = Rxn<PostStatus>();

  @override
  Future<Paginated<Post>> fetchPage(int page) => _repository.mine(page: page, status: status.value);

  void changeStatus(PostStatus? value) {
    if (status.value == value) return;

    status.value = value;
    items.clear();
    refreshList();
  }

  @override
  void onPostUpdated(Post updated) {
    // منشور تغيّرت حالته ولم يعد يطابق الفلتر الحالي يخرج من القائمة
    final filter = status.value;
    if (filter != null && updated.status != filter) {
      items.removeWhere((post) => post.id == updated.id);
    } else {
      super.onPostUpdated(updated);
    }
  }

  // منشور جديد أو تغيّر حالة: نعيد التحميل بهدوء
  @override
  void onFeedChanged() => refreshList(silent: true);
}

/// سجل الأرشيف: منشوراتي التي أرشفتها (لا تظهر للآخرين حتى أعيد نشرها).
class ArchivedPostsController extends PostListController {
  ArchivedPostsController(this._repository);

  final PostsRepository _repository;

  @override
  Future<Paginated<Post>> fetchPage(int page) => _repository.mine(page: page, status: PostStatus.archived);

  @override
  void onPostUpdated(Post updated) {
    // منشور أُعيد نشره يخرج من الأرشيف
    if (updated.status == PostStatus.archived) {
      super.onPostUpdated(updated);
    } else {
      items.removeWhere((post) => post.id == updated.id);
    }
  }

  @override
  void onFeedChanged() => refreshList(silent: true);
}

/// المفضلة: المنشورات التي أضافها المستخدم إلى مفضلته.
class BookmarksController extends PostListController {
  BookmarksController(this._repository);

  final PostsRepository _repository;

  @override
  Future<Paginated<Post>> fetchPage(int page) => _repository.bookmarks(page: page);

  @override
  void onPostUpdated(Post updated) {
    // إلغاء الحفظ يزيل المنشور من القائمة
    if (updated.isBookmarked) {
      super.onPostUpdated(updated);
    } else {
      items.removeWhere((post) => post.id == updated.id);
    }
  }
}
