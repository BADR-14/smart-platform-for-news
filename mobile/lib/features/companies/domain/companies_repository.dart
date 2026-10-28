import '../../../core/network/paginated.dart';
import '../../posts/domain/post.dart';
import '../../shared/models/account.dart';
import 'company.dart';

abstract interface class CompaniesRepository {
  /// الشركات الموافق عليها فقط.
  Future<Paginated<Company>> list({required int page, String? search, int? categoryId});

  Future<Company> details(int companyId);

  Future<Paginated<Post>> posts(int companyId, {required int page});

  /// يرجع حالة المتابعة وعدد المتابعين بعد التغيير.
  Future<({bool isFollowing, int followersCount})> setFollowing(int companyId, {required bool follow});

  /// تصنيفات الشركات (للفلترة ولنموذج طلب إنشاء شركة).
  Future<List<Category>> categories();
}
