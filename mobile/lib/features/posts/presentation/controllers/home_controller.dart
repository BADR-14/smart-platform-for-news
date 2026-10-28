import 'package:get/get.dart';

import '../../../../core/controllers/paged_controller.dart';
import '../../../../core/network/paginated.dart';
import '../../../../core/session/session_service.dart';
import '../../../shared/models/account.dart';
import '../../domain/post.dart';
import '../../domain/posts_repository.dart';
import '../post_sync_mixin.dart';

enum HomeTab { all, following }

/// الصفحة الرئيسية: المنشورات المميزة أعلى الصفحة، ثم كل المنشورات والمشاركات،
/// مع بحث وفلاتر، وتبويب لمنشورات الشركات التي أتابعها.
class HomeController extends PagedController<FeedItem> with PostSyncMixin {
  HomeController(this._repository, this._session);

  final PostsRepository _repository;
  final SessionService _session;

  final tab = HomeTab.all.obs;
  final filters = const PostFilters().obs;
  final featured = <Post>[].obs;
  final categories = <Category>[].obs;

  @override
  void onInit() {
    super.onInit();

    loadFeatured();
    loadCategories();

    listenToPostChanges(
      onUpdated: _replace,
      onDeleted: _remove,
      onFeedChanged: () => refreshList(silent: true),
    );

    // تسجيل الدخول أو الخروج يغيّر حالة المشاهد (تفاعلاتي، محفوظاتي) فنعيد التحميل
    trackWorker(ever(_session.account, (_) {
      if (!_session.isLoggedIn) tab.value = HomeTab.all;
      refreshAll();
    }));
  }

  @override
  Future<Paginated<FeedItem>> fetchPage(int page) async {
    if (tab.value == HomeTab.following) {
      final result = await _repository.following(page: page);

      return Paginated<FeedItem>(
        items: result.items.map((post) => FeedItem(post: post)).toList(),
        currentPage: result.currentPage,
        lastPage: result.lastPage,
        total: result.total,
      );
    }

    return _repository.feed(page: page, filters: filters.value);
  }

  Future<void> refreshAll() async {
    await Future.wait([refreshList(silent: true), loadFeatured()]);
  }

  Future<void> loadFeatured() async {
    try {
      featured.assignAll(await _repository.featured());
    } catch (_) {
      // شريط المميز اختياري: فشله لا يمنع عرض الصفحة
    }
  }

  Future<void> loadCategories() async {
    try {
      categories.assignAll(await _repository.categories());
    } catch (_) {
      // الفلاتر تعمل بدون التصنيفات
    }
  }

  void changeTab(HomeTab value) {
    if (value == HomeTab.following && !_session.requireLogin('سجّل الدخول لترى منشورات الشركات التي تتابعها.')) {
      return;
    }

    if (tab.value == value) return;

    tab.value = value;
    items.clear();
    refreshList();
  }

  void search(String keyword) {
    if ((filters.value.keyword ?? '') == keyword.trim()) return;

    filters.value = filters.value.withKeyword(keyword.trim());
    refreshList();
  }

  void applyFilters(PostFilters value) {
    filters.value = PostFilters(
      keyword: filters.value.keyword,
      categoryId: value.categoryId,
      postType: value.postType,
      isOnline: value.isOnline,
      minRating: value.minRating,
    );
    refreshList();
  }

  /// شريط المميز يظهر فقط في العرض الافتراضي (بدون بحث أو فلاتر).
  bool get showFeatured => tab.value == HomeTab.all && filters.value.isEmpty && featured.isNotEmpty;

  void _replace(Post updated) {
    for (var index = 0; index < items.length; index++) {
      if (items[index].post.id == updated.id) {
        items[index] = items[index].withPost(updated);
      }
    }

    replacePost(featured, updated);
  }

  void _remove(int postId) {
    items.removeWhere((item) => item.post.id == postId);
    featured.removeWhere((post) => post.id == postId);
  }
}
