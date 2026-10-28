import 'package:get/get.dart';

import '../../../../core/network/paginated.dart';
import '../../../posts/domain/post.dart';
import '../../../posts/presentation/controllers/post_list_controllers.dart';
import '../../domain/companies_repository.dart';
import '../../domain/company.dart';
import '../company_follow_service.dart';

/// صفحة الشركة: بياناتها + منشوراتها (القائمة المقسّمة).
class CompanyProfileController extends PostListController {
  CompanyProfileController(this._repository, this._follows, this.companyId);

  final CompaniesRepository _repository;
  final CompanyFollowService _follows;
  final int companyId;

  final Rxn<Company> company = Rxn<Company>();
  final Rxn<Object> companyError = Rxn<Object>();

  @override
  void onInit() {
    super.onInit();

    loadCompany();

    trackWorker(ever<Company?>(_follows.updated, (updated) {
      if (updated != null && updated.id == companyId) company.value = updated;
    }));
  }

  @override
  Future<Paginated<Post>> fetchPage(int page) => _repository.posts(companyId, page: page);

  Future<void> loadCompany() async {
    companyError.value = null;

    try {
      company.value = await _repository.details(companyId);
    } catch (error) {
      companyError.value = error;
    }
  }

  Future<void> refreshAll() async {
    await Future.wait([loadCompany(), refreshList(silent: true)]);
  }

  void toggleFollow() {
    final current = company.value;
    if (current != null) _follows.toggle(current);
  }

  bool get isFollowPending => _follows.pending.contains(companyId);
}
