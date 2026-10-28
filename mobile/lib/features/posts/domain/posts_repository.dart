import 'dart:io';

import '../../../core/network/paginated.dart';
import '../../shared/models/account.dart';
import 'comment.dart';
import 'post.dart';

/// فلاتر الصفحة الرئيسية.
class PostFilters {
  const PostFilters({this.keyword, this.categoryId, this.postType, this.isOnline, this.minRating});

  final String? keyword;
  final int? categoryId;
  final PostType? postType;
  final bool? isOnline;
  final int? minRating;

  bool get isEmpty =>
      (keyword == null || keyword!.trim().isEmpty) &&
      categoryId == null &&
      postType == null &&
      isOnline == null &&
      minRating == null;

  /// عدد الفلاتر المفعّلة (عدا البحث) — يظهر كشارة على زر الفلترة.
  int get activeCount => [categoryId, postType, isOnline, minRating].where((value) => value != null).length;

  Map<String, dynamic> toQuery() => {
        'keyword': keyword?.trim(),
        'category_id': categoryId,
        'post_type': postType?.name,
        'is_online': isOnline,
        'rating': minRating,
      };

  PostFilters withKeyword(String? value) => PostFilters(
        keyword: value,
        categoryId: categoryId,
        postType: postType,
        isOnline: isOnline,
        minRating: minRating,
      );
}

/// بيانات إنشاء أو تعديل منشور.
class PostDraft {
  const PostDraft({
    required this.title,
    required this.description,
    required this.postType,
    required this.categoryId,
    required this.isOnline,
    required this.tags,
    this.location,
    this.latitude,
    this.longitude,
    this.startDate,
    this.endDate,
    this.newMedia = const [],
    this.removedMediaIds = const [],
    this.action,
  });

  final String title;
  final String description;
  final PostType postType;
  final int categoryId;
  final bool isOnline;
  final String? location;
  final double? latitude;
  final double? longitude;
  final DateTime? startDate;
  final DateTime? endDate;
  final List<String> tags;
  final List<File> newMedia;
  final List<int> removedMediaIds;

  /// publish | draft | archive — أو null عند التعديل لإبقاء الحالة كما هي.
  final String? action;
}

abstract interface class PostsRepository {
  // ---- القراءة ----
  Future<Paginated<FeedItem>> feed({required int page, PostFilters filters});

  Future<List<Post>> featured();

  /// منشورات الشركات التي أتابعها.
  Future<Paginated<Post>> following({required int page});

  Future<Post> details(int postId);

  Future<Paginated<Post>> byAccount(int accountId, {required int page});

  Future<Paginated<Post>> mine({required int page, PostStatus? status});

  Future<Paginated<Post>> bookmarks({required int page});

  Future<List<Category>> categories({String type});

  // ---- الكتابة ----
  Future<Post> create(PostDraft draft);

  Future<Post> update(int postId, PostDraft draft);

  Future<Post> changeStatus(int postId, String action);

  Future<void> delete(int postId);

  Future<void> recordView(int postId);

  // ---- التفاعلات ----

  /// يرجع عدد التفاعلات الجديد ونوع تفاعلي (null إذا أُلغي).
  Future<({int likesCount, int? myReaction})> react(int postId, int reactTypeId);

  /// يرجع true إذا أصبح المنشور محفوظاً.
  Future<bool> toggleBookmark(int postId);

  Future<void> share(int postId, {String? text});

  Future<void> deleteShare(int shareId);

  Future<void> report({required String type, required int id, required String reason});

  // ---- التعليقات ----
  Future<Paginated<Comment>> comments(int postId, {required int page});

  Future<Paginated<Comment>> replies(int postId, int commentId, {required int page});

  Future<Comment> addComment(int postId, String content, {int? parentId});

  Future<void> deleteComment(int postId, int commentId);

  // ---- التقييمات ----
  Future<Paginated<Review>> reviews(int postId, {required int page});

  Future<void> submitReview(int postId, {required int rating, String? content});
}
