import '../../../core/utils/json.dart';
import '../../shared/models/account.dart';

/// الشركة كما تظهر في قائمة الشركات وصفحة الشركة.
class Company {
  const Company({
    required this.id,
    required this.accountId,
    required this.name,
    required this.isVerified,
    required this.postsCount,
    required this.followersCount,
    required this.isFollowing,
    required this.isOwner,
    this.logo,
    this.description,
    this.phone,
    this.address,
    this.websiteUrl,
    this.category,
    this.createdAt,
  });

  final int id;
  final int accountId;
  final String name;
  final String? logo;
  final String? description;
  final String? phone;
  final String? address;
  final String? websiteUrl;
  final Category? category;
  final bool isVerified;
  final int postsCount;
  final int followersCount;
  final bool isFollowing;
  final bool isOwner;
  final DateTime? createdAt;

  factory Company.fromJson(Json json) {
    final category = asJsonOrNull(json['category']);

    return Company(
      id: asInt(json['id']),
      accountId: asInt(json['account_id']),
      name: asString(json['name'], 'شركة'),
      logo: asStringOrNull(json['logo']),
      description: asStringOrNull(json['description']),
      phone: asStringOrNull(json['phone']),
      address: asStringOrNull(json['address']),
      websiteUrl: asStringOrNull(json['website_url']),
      category: category == null ? null : Category.fromJson(category),
      isVerified: asBool(json['is_verified']),
      postsCount: asInt(json['posts_count']),
      followersCount: asInt(json['followers_count']),
      isFollowing: asBool(json['is_following']),
      isOwner: asBool(json['is_owner']),
      createdAt: asDateOrNull(json['created_at']),
    );
  }

  Company copyWith({bool? isFollowing, int? followersCount}) {
    return Company(
      id: id,
      accountId: accountId,
      name: name,
      logo: logo,
      description: description,
      phone: phone,
      address: address,
      websiteUrl: websiteUrl,
      category: category,
      isVerified: isVerified,
      postsCount: postsCount,
      followersCount: followersCount ?? this.followersCount,
      isFollowing: isFollowing ?? this.isFollowing,
      isOwner: isOwner,
      createdAt: createdAt,
    );
  }
}
