import 'package:get/get.dart';

import '../../../../core/controllers/paged_controller.dart';
import '../../../../core/network/paginated.dart';
import '../../../posts/domain/comment.dart';
import '../../../posts/domain/post.dart';
import '../../../posts/domain/posts_repository.dart';
import '../../../posts/presentation/controllers/post_list_controllers.dart';
import '../../domain/profile.dart';
import '../../domain/profile_repository.dart';

/// الملف العام لحساب آخر: بياناته + منشوراته المنشورة.
class AccountProfileController extends PostListController {
  AccountProfileController(this._profiles, this._posts, this.accountId);

  final ProfileRepository _profiles;
  final PostsRepository _posts;
  final int accountId;

  final Rxn<PublicProfile> profile = Rxn<PublicProfile>();
  final Rxn<Object> profileError = Rxn<Object>();

  @override
  void onInit() {
    super.onInit();
    loadProfile();
  }

  @override
  Future<Paginated<Post>> fetchPage(int page) => _posts.byAccount(accountId, page: page);

  Future<void> loadProfile() async {
    profileError.value = null;

    try {
      profile.value = await _profiles.publicProfile(accountId);
    } catch (error) {
      profileError.value = error;
    }
  }
}

/// "تقييماتي": التقييمات التي كتبها صاحب الحساب.
class MyReviewsController extends PagedController<Review> {
  MyReviewsController(this._profiles);

  final ProfileRepository _profiles;

  @override
  Future<Paginated<Review>> fetchPage(int page) => _profiles.myReviews(page: page);
}
