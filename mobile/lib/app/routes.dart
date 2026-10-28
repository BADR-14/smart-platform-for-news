/// أسماء مسارات التطبيق. الصفحات وربطها بالـ Bindings في app_pages.dart
abstract final class Routes {
  static const splash = '/';

  // المصادقة
  static const login = '/login';
  static const register = '/register';
  static const otp = '/otp';
  static const forgotPassword = '/forgot-password';
  static const resetPassword = '/reset-password';

  // الهيكل الرئيسي (الشريط السفلي)
  static const shell = '/home';

  // المنشورات
  static const postDetails = '/posts/details';
  static const postForm = '/posts/form';
  static const myPosts = '/posts/mine';
  static const bookmarks = '/bookmarks';
  static const archive = '/posts/archive';

  // الشركات والحسابات
  static const companyProfile = '/companies/profile';
  static const accountProfile = '/accounts/profile';

  // حسابي
  static const editProfile = '/profile/edit';
  static const myReviews = '/profile/reviews';
  static const verification = '/verification';
  static const userVerification = '/verification/user';
  static const companyVerification = '/verification/company';
  static const settings = '/settings';
  static const changePassword = '/settings/password';
}
