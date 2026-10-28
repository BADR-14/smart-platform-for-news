import 'dart:io';

import '../../../core/network/paginated.dart';
import '../../posts/domain/comment.dart';
import 'profile.dart';

abstract interface class ProfileRepository {
  Future<Profile> me();

  /// الحقول الفارغة تُرسل كـ null لتُمسح قيمتها.
  Future<Profile> update({required String name, String? phoneNumber, String? linkedinUrl, String? shortBio});

  /// يرجع رابط الصورة الجديدة.
  Future<String?> uploadAvatar(File image);

  Future<PublicProfile> publicProfile(int accountId);

  /// التقييمات التي كتبتها.
  Future<Paginated<Review>> myReviews({required int page});
}
