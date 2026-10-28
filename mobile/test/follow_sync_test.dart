import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:get/get.dart';
import 'package:smart_news/core/network/paginated.dart';
import 'package:smart_news/core/session/session_service.dart';
import 'package:smart_news/core/storage/token_storage.dart';
import 'package:smart_news/core/widgets/paged_list_view.dart';
import 'package:smart_news/features/companies/domain/companies_repository.dart';
import 'package:smart_news/features/companies/domain/company.dart';
import 'package:smart_news/features/companies/presentation/company_follow_service.dart';
import 'package:smart_news/features/companies/presentation/controllers/companies_controller.dart';
import 'package:smart_news/features/companies/presentation/widgets/company_card.dart';
import 'package:smart_news/features/posts/domain/comment.dart';
import 'package:smart_news/features/posts/domain/post.dart';
import 'package:smart_news/features/posts/domain/posts_repository.dart';
import 'package:smart_news/features/posts/presentation/post_actions.dart';
import 'package:smart_news/features/shared/models/account.dart';

Company _company(int id, {bool following = false}) => Company(
      id: id,
      accountId: id + 100,
      name: 'Company $id',
      isVerified: true,
      postsCount: 2,
      followersCount: 5,
      isFollowing: following,
      isOwner: false,
    );

class _FakeCompanies implements CompaniesRepository {
  @override
  Future<Paginated<Company>> list({required int page, String? search, int? categoryId}) async {
    return Paginated(items: [_company(1), _company(2)], currentPage: 1, lastPage: 1, total: 2);
  }

  @override
  Future<({bool isFollowing, int followersCount})> setFollowing(int companyId, {required bool follow}) async {
    return (isFollowing: follow, followersCount: follow ? 6 : 5);
  }

  @override
  Future<List<Category>> categories() async => const [];

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

class _FakePosts implements PostsRepository {
  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

void main() {
  // يمنع استعمال التخزين الحقيقي في الاختبار
  FlutterSecureStorage.setMockInitialValues({});

  testWidgets('زر المتابعة في قائمة الشركات يتغيّر فور نجاح المتابعة', (tester) async {
    final session = SessionService(TokenStorage());
    await session.start(
      'token',
      const Account(id: 9, name: 'Me', email: 'me@test.com', type: AccountType.user, verificationStatus: 'approved', isVerified: true),
    );
    Get.put(session);

    final repository = _FakeCompanies();
    final actions = Get.put(PostActions(_FakePosts(), session));
    final follows = Get.put(CompanyFollowService(repository, session, actions));
    final controller = Get.put(CompaniesController(repository, session, follows));

    await tester.pumpWidget(
      GetMaterialApp(
        home: Scaffold(
          body: PagedListView<Company>(
            controller: controller,
            emptyTitle: 'empty',
            itemBuilder: (context, company, index) => CompanyCard(key: ValueKey(company.id), company: company),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('متابعة'), findsNWidgets(2));
    expect(find.text('أتابعها'), findsNothing);

    await tester.tap(find.text('متابعة').first);
    await tester.pump();
    await tester.pump(const Duration(seconds: 4));

    expect(controller.items.first.isFollowing, isTrue, reason: 'عنصر القائمة يجب أن يتحدّث');
    expect(find.text('أتابعها'), findsOneWidget, reason: 'شكل الزر يجب أن يتغيّر');
    expect(find.text('متابعة'), findsOneWidget);

    Get.reset();
  });

  // غير مستعمل هنا لكنه يُبقي الاستيرادات المطلوبة لواجهة المستودع
  test('نماذج مساعدة', () {
    expect(Reaction.like.id, 1);
    expect(PostType.event.label, isNotEmpty);
  });
}
