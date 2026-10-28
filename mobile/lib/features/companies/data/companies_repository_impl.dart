import '../../../core/network/api_client.dart';
import '../../../core/network/paginated.dart';
import '../../../core/utils/json.dart';
import '../../posts/domain/post.dart';
import '../../shared/models/account.dart';
import '../domain/companies_repository.dart';
import '../domain/company.dart';

class CompaniesRepositoryImpl implements CompaniesRepository {
  CompaniesRepositoryImpl(this._api);

  final ApiClient _api;

  @override
  Future<Paginated<Company>> list({required int page, String? search, int? categoryId}) async {
    final json = await _api.get('/companies', query: {'page': page, 'search': search?.trim(), 'category_id': categoryId});
    return Paginated.fromJson(json, Company.fromJson);
  }

  @override
  Future<Company> details(int companyId) async {
    final json = await _api.get('/companies/$companyId');
    return Company.fromJson(asJson(json['data']));
  }

  @override
  Future<Paginated<Post>> posts(int companyId, {required int page}) async {
    final json = await _api.get('/companies/$companyId/posts', query: {'page': page});
    return Paginated.fromJson(json, Post.fromJson);
  }

  @override
  Future<({bool isFollowing, int followersCount})> setFollowing(int companyId, {required bool follow}) async {
    final path = '/companies/$companyId/follow';
    final json = follow ? await _api.post(path) : await _api.delete(path);
    final data = asJson(json['data']);

    return (isFollowing: asBool(data['is_following']), followersCount: asInt(data['followers_count']));
  }

  @override
  Future<List<Category>> categories() async {
    final json = await _api.get('/categories', query: {'type': 'company'});
    return asList(json['data'], Category.fromJson);
  }
}
