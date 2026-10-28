import 'package:get/get.dart';

import '../../core/network/api_client.dart';
import '../../core/services/push_service.dart';
import '../../core/session/session_service.dart';
import '../../features/auth/data/auth_repository_impl.dart';
import '../../features/auth/domain/auth_repository.dart';
import '../../features/auth/presentation/controllers/auth_controller.dart';
import '../../features/companies/data/companies_repository_impl.dart';
import '../../features/companies/domain/companies_repository.dart';
import '../../features/companies/presentation/company_follow_service.dart';
import '../../features/notifications/data/notifications_repository_impl.dart';
import '../../features/notifications/domain/notifications_repository.dart';
import '../../features/posts/data/posts_repository_impl.dart';
import '../../features/posts/domain/posts_repository.dart';
import '../../features/posts/presentation/post_actions.dart';
import '../../features/profile/data/profile_repository_impl.dart';
import '../../features/profile/domain/profile_repository.dart';
import '../../features/settings/data/settings_repository_impl.dart';
import '../../features/settings/domain/settings_repository.dart';
import '../../features/verification/data/verification_repository_impl.dart';
import '../../features/verification/domain/verification_repository.dart';

/// الاعتماديات التي تعيش طوال عمر التطبيق: عميل الـ API، المستودعات، والخدمات المشتركة.
///
/// المستودعات تُسجَّل بواجهاتها المجردة (Repository pattern): المتحكمات تطلب
/// `PostsRepository` ولا تعرف شيئاً عن Dio، فيمكن استبدال التطبيق في الاختبارات.
/// [SessionService] يُسجَّل في main.dart قبل تشغيل التطبيق.
class InitialBinding extends Bindings {
  @override
  void dependencies() {
    final session = Get.find<SessionService>();

    final api = Get.put(
      ApiClient(tokenProvider: () => session.token, onUnauthorized: session.handleUnauthorized),
      permanent: true,
    );

    // ---- المستودعات ----
    final auth = Get.put<AuthRepository>(AuthRepositoryImpl(api), permanent: true);
    final posts = Get.put<PostsRepository>(PostsRepositoryImpl(api), permanent: true);
    final companies = Get.put<CompaniesRepository>(CompaniesRepositoryImpl(api), permanent: true);

    Get
      ..put<ProfileRepository>(ProfileRepositoryImpl(api), permanent: true)
      ..put<VerificationRepository>(VerificationRepositoryImpl(api), permanent: true)
      ..put<NotificationsRepository>(NotificationsRepositoryImpl(api), permanent: true)
      ..put<SettingsRepository>(SettingsRepositoryImpl(api), permanent: true);

    // ---- خدمات مشتركة بين الشاشات ----
    final postActions = Get.put(PostActions(posts, session), permanent: true);
    Get.put(CompanyFollowService(companies, session, postActions), permanent: true);

    // إشعارات Push: التهيئة تجري في الخلفية ولا تؤخّر فتح التطبيق
    Get.put(PushService(api, session), permanent: true).start();

    // شاشات المصادقة تتشارك متحكماً واحداً، يُنشأ عند الحاجة ويُعاد إنشاؤه إن حُذف
    Get.lazyPut(() => AuthController(auth, session), fenix: true);
  }
}
