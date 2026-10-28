import '../../../core/utils/json.dart';
import '../../shared/models/account.dart';

enum PostType {
  lecture('محاضرة'),
  exhibition('معرض'),
  event('فعالية'),
  course('دورة');

  const PostType(this.label);

  final String label;

  static PostType parse(Object? value) {
    return PostType.values.firstWhere(
      (type) => type.name == value?.toString(),
      orElse: () => PostType.event,
    );
  }
}

enum PostStatus {
  draft('draft', 'مسودة'),
  published('published', 'منشور'),
  underReview('under_review', 'قيد المراجعة'),
  rejected('rejected', 'مرفوض'),
  archived('archived', 'مؤرشف');

  const PostStatus(this.value, this.label);

  /// القيمة كما يرسلها الـ API.
  final String value;
  final String label;

  static PostStatus parse(Object? value) {
    return PostStatus.values.firstWhere(
      (status) => status.value == value?.toString(),
      orElse: () => PostStatus.published,
    );
  }
}

class PostMedia {
  const PostMedia({required this.id, required this.url, required this.isVideo});

  final int id;
  final String url;
  final bool isVideo;

  factory PostMedia.fromJson(Json json) {
    return PostMedia(
      id: asInt(json['id']),
      url: asString(json['url']),
      isVideo: json['type'] == 'video',
    );
  }
}

/// المنشور — كائن ثابت (immutable). أي تغيير (تفاعل، حفظ ...) ينتج نسخة جديدة عبر [copyWith].
class Post {
  const Post({
    required this.id,
    required this.title,
    required this.postType,
    required this.status,
    required this.isOnline,
    required this.viewsCount,
    required this.likesCount,
    required this.commentsCount,
    required this.sharesCount,
    required this.reviewsCount,
    required this.isFeatured,
    required this.isOwner,
    required this.isBookmarked,
    required this.isShared,
    this.description,
    this.moderationNote,
    this.location,
    this.latitude,
    this.longitude,
    this.startDate,
    this.endDate,
    this.category,
    this.tags = const [],
    this.media = const [],
    this.coverUrl,
    this.author,
    this.ratingAvg,
    this.myReaction,
    this.myRating,
    this.createdAt,
  });

  final int id;
  final String title;
  final String? description;
  final PostType postType;
  final PostStatus status;
  final String? moderationNote;

  final bool isOnline;
  final String? location;
  final double? latitude;
  final double? longitude;
  final DateTime? startDate;
  final DateTime? endDate;

  final Category? category;
  final List<String> tags;
  final List<PostMedia> media;
  final String? coverUrl;
  final Author? author;

  final int viewsCount;
  final int likesCount;
  final int commentsCount;
  final int sharesCount;
  final int reviewsCount;
  final double? ratingAvg;

  final bool isFeatured;

  // حالة المستخدم الحالي تجاه المنشور
  final bool isOwner;
  final bool isBookmarked;
  final bool isShared;

  /// معرّف نوع تفاعلي (1..6) أو null إذا لم أتفاعل.
  final int? myReaction;
  final double? myRating;

  final DateTime? createdAt;

  bool get hasCoordinates => latitude != null && longitude != null;

  List<PostMedia> get images => media.where((item) => !item.isVideo).toList();

  factory Post.fromJson(Json json) {
    final category = asJsonOrNull(json['category']);
    final author = asJsonOrNull(json['author']);
    final rawTags = json['tags'];

    return Post(
      id: asInt(json['id']),
      title: asString(json['title']),
      description: asStringOrNull(json['description']),
      postType: PostType.parse(json['post_type']),
      status: PostStatus.parse(json['status']),
      moderationNote: asStringOrNull(json['moderation_note']),
      isOnline: asBool(json['is_online']),
      location: asStringOrNull(json['location']),
      latitude: asDoubleOrNull(json['latitude']),
      longitude: asDoubleOrNull(json['longitude']),
      startDate: asDateOrNull(json['start_date']),
      endDate: asDateOrNull(json['end_date']),
      category: category == null ? null : Category.fromJson(category),
      tags: rawTags is List
          ? rawTags.whereType<Map>().map((tag) => asString(tag['name'])).where((name) => name.isNotEmpty).toList()
          : const [],
      media: asList(json['media'], PostMedia.fromJson),
      coverUrl: asStringOrNull(json['cover_url']),
      author: author == null ? null : Author.fromJson(author),
      viewsCount: asInt(json['views_count']),
      likesCount: asInt(json['likes_count']),
      commentsCount: asInt(json['comments_count']),
      sharesCount: asInt(json['shares_count']),
      reviewsCount: asInt(json['reviews_count']),
      ratingAvg: asDoubleOrNull(json['rating_avg']),
      isFeatured: asBool(json['is_featured']),
      isOwner: asBool(json['is_owner']),
      isBookmarked: asBool(json['is_bookmarked']),
      isShared: asBool(json['is_shared']),
      myReaction: asIntOrNull(json['my_reaction']),
      myRating: asDoubleOrNull(json['my_rating']),
      createdAt: asDateOrNull(json['created_at']),
    );
  }

  /// [clearReaction] لأن null في [myReaction] تعني "لا تغيّر".
  Post copyWith({
    int? likesCount,
    int? commentsCount,
    int? sharesCount,
    int? viewsCount,
    int? reviewsCount,
    double? ratingAvg,
    bool? isBookmarked,
    bool? isShared,
    int? myReaction,
    bool clearReaction = false,
    double? myRating,
    PostStatus? status,
  }) {
    return Post(
      id: id,
      title: title,
      description: description,
      postType: postType,
      status: status ?? this.status,
      moderationNote: moderationNote,
      isOnline: isOnline,
      location: location,
      latitude: latitude,
      longitude: longitude,
      startDate: startDate,
      endDate: endDate,
      category: category,
      tags: tags,
      media: media,
      coverUrl: coverUrl,
      author: author,
      viewsCount: viewsCount ?? this.viewsCount,
      likesCount: likesCount ?? this.likesCount,
      commentsCount: commentsCount ?? this.commentsCount,
      sharesCount: sharesCount ?? this.sharesCount,
      reviewsCount: reviewsCount ?? this.reviewsCount,
      ratingAvg: ratingAvg ?? this.ratingAvg,
      isFeatured: isFeatured,
      isOwner: isOwner,
      isBookmarked: isBookmarked ?? this.isBookmarked,
      isShared: isShared ?? this.isShared,
      myReaction: clearReaction ? null : (myReaction ?? this.myReaction),
      myRating: myRating ?? this.myRating,
      createdAt: createdAt,
    );
  }
}

/// عنصر في الصفحة الرئيسية: منشور أصلي، أو مشاركة لمنشور مع نص من المشارِك.
class FeedItem {
  const FeedItem({required this.post, this.shareId, this.sharedBy, this.shareText, this.sharedAt, this.isMyShare = false});

  final Post post;

  /// null = منشور أصلي وليس مشاركة.
  final int? shareId;
  final Author? sharedBy;
  final String? shareText;
  final DateTime? sharedAt;
  final bool isMyShare;

  bool get isShare => shareId != null;

  /// مفتاح فريد للقوائم (نفس المنشور قد يظهر أصلياً ومشارَكاً).
  String get key => isShare ? 'share-$shareId' : 'post-${post.id}';

  FeedItem withPost(Post updated) {
    return FeedItem(
      post: updated,
      shareId: shareId,
      sharedBy: sharedBy,
      shareText: shareText,
      sharedAt: sharedAt,
      isMyShare: isMyShare,
    );
  }

  /// يرجع null إذا كانت المشاركة تشير لمنشور لم يعد متاحاً.
  static FeedItem? fromJson(Json json) {
    if (json['type'] != 'share') {
      return FeedItem(post: Post.fromJson(json));
    }

    final post = asJsonOrNull(json['post']);
    if (post == null) return null;

    final sharedBy = asJsonOrNull(json['shared_by']);

    return FeedItem(
      post: Post.fromJson(post),
      shareId: asInt(json['id']),
      sharedBy: sharedBy == null ? null : Author.fromJson(sharedBy),
      shareText: asStringOrNull(json['share_text']),
      sharedAt: asDateOrNull(json['created_at']),
      isMyShare: asBool(json['is_owner']),
    );
  }
}
