import 'package:get/get.dart';

import '../network/paginated.dart';

/// أساس كل القوائم المقسّمة لصفحات (منشورات، تعليقات، إشعارات ...):
/// تحميل أول صفحة، السحب للتحديث، وتحميل المزيد عند الوصول لآخر القائمة.
///
/// الصنف الوارث يطبّق [fetchPage] فقط.
abstract class PagedController<T> extends GetxController {
  final items = <T>[].obs;

  /// تحميل أول صفحة (يعرض مؤشر تحميل يملأ الشاشة).
  final isLoading = false.obs;

  /// تحميل صفحة إضافية (مؤشر صغير أسفل القائمة).
  final isLoadingMore = false.obs;

  /// خطأ تحميل أول صفحة. أخطاء "تحميل المزيد" لا تمسح ما هو معروض.
  final Rxn<Object> error = Rxn<Object>();

  final total = 0.obs;

  int _page = 0;
  int _lastPage = 1;

  /// رقم يتغير مع كل إعادة تحميل، حتى نتجاهل رد طلب قديم وصل بعد طلب أحدث.
  int _generation = 0;

  bool get hasMore => _page < _lastPage;

  /// هل يبدأ التحميل تلقائياً عند إنشاء المتحكم؟
  bool get loadOnInit => true;

  Future<Paginated<T>> fetchPage(int page);

  @override
  void onInit() {
    super.onInit();
    if (loadOnInit) refreshList();
  }

  /// يعيد التحميل من الصفحة الأولى.
  Future<void> refreshList({bool silent = false}) async {
    final generation = ++_generation;

    if (!silent || items.isEmpty) isLoading.value = true;
    error.value = null;

    try {
      final result = await fetchPage(1);
      if (generation != _generation) return;

      items.assignAll(result.items);
      _apply(result);
    } catch (caught) {
      if (generation != _generation) return;
      if (items.isEmpty) error.value = caught;
    } finally {
      if (generation == _generation) isLoading.value = false;
    }
  }

  Future<void> loadMore() async {
    if (!hasMore || isLoadingMore.value || isLoading.value) return;

    final generation = _generation;
    isLoadingMore.value = true;

    try {
      final result = await fetchPage(_page + 1);
      if (generation != _generation) return;

      items.addAll(result.items);
      _apply(result);
    } catch (_) {
      // يبقى ما هو معروض، والمستخدم يعيد المحاولة بالتمرير مجدداً
    } finally {
      isLoadingMore.value = false;
    }
  }

  /// يُستدعى بعد كل صفحة ناجحة — للأصناف التي تحتاج قيماً إضافية من الرد.
  void onPageLoaded(Paginated<T> page) {}

  void _apply(Paginated<T> result) {
    _page = result.currentPage;
    _lastPage = result.lastPage;
    total.value = result.total;
    onPageLoaded(result);
  }
}
