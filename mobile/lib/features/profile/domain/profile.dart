import '../../../core/utils/json.dart';
import '../../shared/models/account.dart';

/// ملفي الشخصي (يتضمن البيانات الخاصة: البريد والهاتف).
class Profile {
  const Profile({
    required this.id,
    required this.name,
    required this.email,
    required this.type,
    required this.verificationStatus,
    required this.isVerified,
    required this.postsCount,
    required this.followingCount,
    required this.reviewsCount,
    required this.bookmarksCount,
    this.avatar,
    this.phoneNumber,
    this.linkedinUrl,
    this.shortBio,
    this.companyId,
    this.createdAt,
  });

  final int id;
  final String name;
  final String email;
  final String? avatar;
  final AccountType type;
  final String verificationStatus;
  final bool isVerified;

  final String? phoneNumber;
  final String? linkedinUrl;
  final String? shortBio;

  final int postsCount;
  final int followingCount;
  final int reviewsCount;
  final int bookmarksCount;

  /// معرّف الشركة إذا كان الحساب حساب شركة.
  final int? companyId;
  final DateTime? createdAt;

  bool get isCompany => type == AccountType.company;

  factory Profile.fromJson(Json json) {
    final company = asJsonOrNull(json['company']);

    return Profile(
      id: asInt(json['id']),
      name: asString(json['name']),
      email: asString(json['email']),
      avatar: asStringOrNull(json['avatar']),
      type: AccountType.parse(json['type']),
      verificationStatus: asString(json['verification_status'], 'not_submitted'),
      isVerified: asBool(json['is_verified']),
      phoneNumber: asStringOrNull(json['phone_number']),
      linkedinUrl: asStringOrNull(json['linkedin_url']),
      shortBio: asStringOrNull(json['short_bio']),
      postsCount: asInt(json['posts_count']),
      followingCount: asInt(json['following_count']),
      reviewsCount: asInt(json['reviews_count']),
      bookmarksCount: asInt(json['bookmarks_count']),
      companyId: company == null ? null : asIntOrNull(company['id']),
      createdAt: asDateOrNull(json['created_at']),
    );
  }

  Profile withAvatar(String? url) {
    return Profile(
      id: id,
      name: name,
      email: email,
      avatar: url,
      type: type,
      verificationStatus: verificationStatus,
      isVerified: isVerified,
      phoneNumber: phoneNumber,
      linkedinUrl: linkedinUrl,
      shortBio: shortBio,
      postsCount: postsCount,
      followingCount: followingCount,
      reviewsCount: reviewsCount,
      bookmarksCount: bookmarksCount,
      companyId: companyId,
      createdAt: createdAt,
    );
  }
}

/// الملف العام لحساب آخر — بدون بريد أو هاتف.
class PublicProfile {
  const PublicProfile({
    required this.id,
    required this.name,
    required this.type,
    required this.isVerified,
    required this.postsCount,
    this.avatar,
    this.shortBio,
    this.linkedinUrl,
    this.companyId,
    this.createdAt,
  });

  final int id;
  final String name;
  final String? avatar;
  final AccountType type;
  final bool isVerified;
  final String? shortBio;
  final String? linkedinUrl;
  final int? companyId;
  final int postsCount;
  final DateTime? createdAt;

  factory PublicProfile.fromJson(Json json) {
    return PublicProfile(
      id: asInt(json['id']),
      name: asString(json['name'], 'مستخدم'),
      avatar: asStringOrNull(json['avatar']),
      type: AccountType.parse(json['type']),
      isVerified: asBool(json['is_verified']),
      shortBio: asStringOrNull(json['short_bio']),
      linkedinUrl: asStringOrNull(json['linkedin_url']),
      companyId: asIntOrNull(json['company_id']),
      postsCount: asInt(json['posts_count']),
      createdAt: asDateOrNull(json['created_at']),
    );
  }
}
