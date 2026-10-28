import 'package:get/get.dart';

import '../../../../core/controllers/paged_controller.dart';
import '../../../../core/network/paginated.dart';
import '../../../../core/session/session_service.dart';
import '../../../shared/models/account.dart';
import '../../domain/companies_repository.dart';
import '../../domain/company.dart';
import '../company_follow_service.dart';

/// تبويب الشركات: قائمة الشركات الموثّقة مع بحث وفلترة حسب التصنيف.
class CompaniesController extends PagedController<Company> {
  CompaniesController(this._repository, this._session, this._follows);

  final CompaniesRepository _repository;
  final SessionService _session;
  final CompanyFollowService _follows;

  final search = ''.obs;
  final categoryId = RxnInt();
  final categories = <Category>[].obs;

  final List<Worker> _workers = [];

  @override
  void onInit() {
    super.onInit();

    _loadCategories();

    _workers
      ..add(ever<Company?>(_follows.updated, (company) {
        if (company == null) return;

        final index = items.indexWhere((item) => item.id == company.id);
        if (index != -1) items[index] = company;
      }))
      // حالة "أتابعها" تختلف بين زائر ومستخدم مسجّل
      ..add(ever(_session.account, (_) => refreshList(silent: true)));
  }

  @override
  void onClose() {
    for (final worker in _workers) {
      worker.dispose();
    }
    super.onClose();
  }

  @override
  Future<Paginated<Company>> fetchPage(int page) {
    return _repository.list(page: page, search: search.value, categoryId: categoryId.value);
  }

  Future<void> _loadCategories() async {
    try {
      categories.assignAll(await _repository.categories());
    } catch (_) {
      // القائمة تعمل بدون فلتر التصنيفات
    }
  }

  void applySearch(String value) {
    final keyword = value.trim();
    if (search.value == keyword) return;

    search.value = keyword;
    refreshList();
  }

  void selectCategory(int? id) {
    if (categoryId.value == id) return;

    categoryId.value = id;
    refreshList();
  }
}
