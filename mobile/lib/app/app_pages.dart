import 'package:get/get.dart';

import '../features/auth/presentation/views/forgot_password_view.dart';
import '../features/auth/presentation/views/login_view.dart';
import '../features/auth/presentation/views/otp_view.dart';
import '../features/auth/presentation/views/register_view.dart';
import '../features/auth/presentation/views/reset_password_view.dart';
import '../features/companies/presentation/controllers/companies_controller.dart';
import '../features/companies/presentation/controllers/company_profile_controller.dart';
import '../features/companies/presentation/views/company_profile_view.dart';
import '../features/notifications/presentation/controllers/notifications_controller.dart';
import '../features/posts/domain/post.dart';
import '../features/posts/presentation/controllers/home_controller.dart';
import '../features/posts/presentation/controllers/post_details_controller.dart';
import '../features/posts/presentation/controllers/post_form_controller.dart';
import '../features/posts/presentation/controllers/post_list_controllers.dart';
import '../features/posts/presentation/views/archive_view.dart';
import '../features/posts/presentation/views/bookmarks_view.dart';
import '../features/posts/presentation/views/my_posts_view.dart';
import '../features/posts/presentation/views/post_details_view.dart';
import '../features/posts/presentation/views/post_form_view.dart';
import '../features/profile/presentation/controllers/account_profile_controller.dart';
import '../features/profile/presentation/controllers/edit_profile_controller.dart';
import '../features/profile/presentation/controllers/profile_controller.dart';
import '../features/profile/presentation/views/account_profile_view.dart';
import '../features/profile/presentation/views/edit_profile_view.dart';
import '../features/profile/presentation/views/my_reviews_view.dart';
import '../features/settings/presentation/controllers/settings_controller.dart';
import '../features/settings/presentation/views/change_password_view.dart';
import '../features/settings/presentation/views/settings_view.dart';
import '../features/shell/presentation/shell_controller.dart';
import '../features/shell/presentation/shell_view.dart';
import '../features/splash/splash_controller.dart';
import '../features/splash/splash_view.dart';
import '../features/verification/presentation/controllers/verification_controller.dart';
import '../features/verification/presentation/controllers/verification_form_controllers.dart';
import '../features/verification/presentation/views/company_verification_view.dart';
import '../features/verification/presentation/views/user_verification_view.dart';
import '../features/verification/presentation/views/verification_view.dart';
import 'routes.dart';

/// جدول الصفحات: كل مسار مع الـ Binding الذي ينشئ متحكماته.
/// المتحكم يُحذف تلقائياً عند إغلاق صفحته (إدارة الذاكرة في GetX).
///
/// `Get.find()` بدون نوع يستنتج النوع من معامل الباني (المستودعات والخدمات
/// مسجّلة في InitialBinding).
abstract final class AppPages {
  static final List<GetPage<dynamic>> pages = [
    GetPage(
      name: Routes.splash,
      page: () => const SplashView(),
      binding: BindingsBuilder(() {
        Get.put(SplashController(Get.find(), Get.find()));
      }),
    ),

    // ==================== المصادقة ====================
    GetPage(name: Routes.login, page: () => const LoginView()),
    GetPage(name: Routes.register, page: () => const RegisterView()),
    GetPage(name: Routes.otp, page: () => const OtpView()),
    GetPage(name: Routes.forgotPassword, page: () => const ForgotPasswordView()),
    GetPage(name: Routes.resetPassword, page: () => const ResetPasswordView()),

    // ==================== الهيكل الرئيسي ====================
    GetPage(
      name: Routes.shell,
      page: () => const ShellView(),
      // الدخول من شاشة البداية بتلاشٍ هادئ بدل الانزلاق
      transition: Transition.fadeIn,
      transitionDuration: const Duration(milliseconds: 450),
      binding: BindingsBuilder(() {
        final notifications = Get.put(NotificationsController(Get.find(), Get.find()));

        final profile = Get.put(ProfileController(Get.find(), Get.find(), Get.find()));

        Get
          ..put(HomeController(Get.find(), Get.find()))
          ..put(CompaniesController(Get.find(), Get.find(), Get.find()))
          ..put(ShellController(Get.find(), notifications, profile));
      }),
    ),

    // ==================== المنشورات ====================
    GetPage(
      name: Routes.postDetails,
      page: () => const PostDetailsView(),
      binding: BindingsBuilder(() {
        // متحكم لكل منشور، موسوم بمعرّفه (يمكن فتح أكثر من صفحة تفاصيل فوق بعض)
        final postId = Get.arguments as int;
        Get.put(PostDetailsController(Get.find(), Get.find(), Get.find(), postId), tag: '$postId');
      }),
    ),
    GetPage(
      name: Routes.postForm,
      page: () => const PostFormView(),
      binding: BindingsBuilder(() {
        // arguments = المنشور عند التعديل، أو null لمنشور جديد
        final editing = Get.arguments as Post?;
        Get.put(PostFormController(Get.find(), Get.find(), editing: editing));
      }),
    ),
    GetPage(
      name: Routes.myPosts,
      page: () => const MyPostsView(),
      binding: BindingsBuilder(() {
        Get.put(MyPostsController(Get.find()));
      }),
    ),
    GetPage(
      name: Routes.archive,
      page: () => const ArchiveView(),
      binding: BindingsBuilder(() {
        Get.put(ArchivedPostsController(Get.find()));
      }),
    ),
    GetPage(
      name: Routes.bookmarks,
      page: () => const BookmarksView(),
      binding: BindingsBuilder(() {
        Get.put(BookmarksController(Get.find()));
      }),
    ),

    // ==================== الشركات والحسابات ====================
    GetPage(
      name: Routes.companyProfile,
      page: () => const CompanyProfileView(),
      binding: BindingsBuilder(() {
        final companyId = Get.arguments as int;
        Get.put(CompanyProfileController(Get.find(), Get.find(), companyId), tag: '$companyId');
      }),
    ),
    GetPage(
      name: Routes.accountProfile,
      page: () => const AccountProfileView(),
      binding: BindingsBuilder(() {
        final accountId = Get.arguments as int;
        Get.put(AccountProfileController(Get.find(), Get.find(), accountId), tag: '$accountId');
      }),
    ),

    // ==================== حسابي ====================
    GetPage(
      name: Routes.editProfile,
      page: () => const EditProfileView(),
      binding: BindingsBuilder(() {
        Get.put(EditProfileController(Get.find(), Get.find()));
      }),
    ),
    GetPage(
      name: Routes.myReviews,
      page: () => const MyReviewsView(),
      binding: BindingsBuilder(() {
        Get.put(MyReviewsController(Get.find()));
      }),
    ),
    GetPage(
      name: Routes.verification,
      page: () => const VerificationView(),
      binding: BindingsBuilder(() {
        Get.put(VerificationController(Get.find(), Get.find(), Get.find()));
      }),
    ),
    GetPage(
      name: Routes.userVerification,
      page: () => const UserVerificationView(),
      binding: BindingsBuilder(() {
        Get.put(UserVerificationController(Get.find(), Get.find()));
      }),
    ),
    GetPage(
      name: Routes.companyVerification,
      page: () => const CompanyVerificationView(),
      binding: BindingsBuilder(() {
        Get.put(CompanyVerificationController(Get.find(), Get.find(), Get.find()));
      }),
    ),
    GetPage(
      name: Routes.settings,
      page: () => const SettingsView(),
      binding: BindingsBuilder(() {
        Get.put(SettingsController(Get.find(), Get.find()));
      }),
    ),
    // تُفتح من صفحة الإعدادات وتستخدم متحكمها
    GetPage(name: Routes.changePassword, page: () => const ChangePasswordView()),
  ];
}
