import 'package:get/get.dart';

import '../../../core/session/session_service.dart';
import '../../../core/utils/feedback.dart';
import '../../posts/presentation/post_actions.dart';
import '../domain/companies_repository.dart';
import '../domain/company.dart';

/// متابعة الشركات من مكان واحد. الشركة قد تكون معروضة في القائمة وفي صفحتها معاً،
/// فبعد كل تغيير تُبَث النسخة المحدّثة عبر [updated] لتتحدث كل الشاشات.
class CompanyFollowService extends GetxService {
  CompanyFollowService(this._repository, this._session, this._postActions);

  final CompaniesRepository _repository;
  final SessionService _session;
  final PostActions _postActions;

  final Rxn<Company> updated = Rxn<Company>();

  /// معرّفات الشركات التي يُنفَّذ طلب متابعتها حالياً (لتعطيل الزر).
  final pending = <int>{}.obs;

  Future<void> toggle(Company company) async {
    if (!_session.requireLogin('سجّل الدخول لتتابع الشركات وتصلك منشوراتها.')) return;
    if (pending.contains(company.id)) return;

    pending.add(company.id);

    try {
      final result = await _repository.setFollowing(company.id, follow: !company.isFollowing);

      updated.value = company.copyWith(isFollowing: result.isFollowing, followersCount: result.followersCount);

      // تبويب "أتابعهم" في الرئيسية يعتمد على قائمة المتابعة
      _postActions.notifyFeedChanged();
      AppFeedback.success(result.isFollowing ? 'أصبحت تتابع ${company.name}' : 'تم إلغاء المتابعة');
    } catch (error) {
      AppFeedback.error(error);
    } finally {
      pending.remove(company.id);
    }
  }
}
