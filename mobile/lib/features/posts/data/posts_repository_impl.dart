import 'package:dio/dio.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/paginated.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/utils/json.dart';
import '../../shared/models/account.dart';
import '../domain/comment.dart';
import '../domain/post.dart';
import '../domain/posts_repository.dart';

class PostsRepositoryImpl implements PostsRepository {
  PostsRepositoryImpl(this._api);

  final ApiClient _api;

  // ==================== القراءة ====================

  @override
  Future<Paginated<FeedItem>> feed({required int page, PostFilters filters = const PostFilters()}) async {
    final json = await _api.get('/posts', query: {'page': page, ...filters.toQuery()});
    return Paginated.fromJsonNullable(json, FeedItem.fromJson);
  }

  @override
  Future<List<Post>> featured() async {
    final json = await _api.get('/posts/featured');
    return asList(json['data'], Post.fromJson);
  }

  @override
  Future<Paginated<Post>> following({required int page}) async {
    final json = await _api.get('/feed', query: {'page': page});
    return Paginated.fromJson(json, Post.fromJson);
  }

  @override
  Future<Post> details(int postId) async {
    final json = await _api.get('/posts/$postId');
    return Post.fromJson(asJson(json['data']));
  }

  @override
  Future<Paginated<Post>> byAccount(int accountId, {required int page}) async {
    final json = await _api.get('/accounts/$accountId/posts', query: {'page': page});
    return Paginated.fromJson(json, Post.fromJson);
  }

  @override
  Future<Paginated<Post>> mine({required int page, PostStatus? status}) async {
    final json = await _api.get('/posts/my-posts', query: {'page': page, 'status': status?.value});
    return Paginated.fromJson(json, Post.fromJson);
  }

  @override
  Future<Paginated<Post>> bookmarks({required int page}) async {
    final json = await _api.get('/profile/bookmarks', query: {'page': page});
    return Paginated.fromJson(json, Post.fromJson);
  }

  @override
  Future<List<Category>> categories({String type = 'post'}) async {
    final json = await _api.get('/categories', query: {'type': type});
    return asList(json['data'], Category.fromJson);
  }

  // ==================== الكتابة ====================

  @override
  Future<Post> create(PostDraft draft) async {
    final json = await _api.upload('/posts', await _toFormData(draft, isUpdate: false));
    return Post.fromJson(asJson(json['data']));
  }

  @override
  Future<Post> update(int postId, PostDraft draft) async {
    final json = await _api.upload('/posts/$postId', await _toFormData(draft, isUpdate: true));
    return Post.fromJson(asJson(json['data']));
  }

  @override
  Future<Post> changeStatus(int postId, String action) async {
    final json = await _api.patch('/posts/$postId', body: {'action': action});
    return Post.fromJson(asJson(json['data']));
  }

  @override
  Future<void> delete(int postId) async {
    await _api.delete('/posts/$postId');
  }

  @override
  Future<void> recordView(int postId) async {
    await _api.post('/posts/$postId/view');
  }

  // ==================== التفاعلات ====================

  @override
  Future<({int likesCount, int? myReaction})> react(int postId, int reactTypeId) async {
    final json = await _api.post('/posts/$postId/react', body: {'react_type_id': reactTypeId});
    final data = asJson(json['data']);

    return (likesCount: asInt(data['likes_count']), myReaction: asIntOrNull(data['my_reaction']));
  }

  @override
  Future<bool> toggleBookmark(int postId) async {
    final json = await _api.post('/posts/$postId/bookmark');
    return asBool(asJson(json['data'])['is_bookmarked']);
  }

  @override
  Future<void> share(int postId, {String? text}) async {
    await _api.post('/post-shares/$postId', body: {'share_text': text});
  }

  @override
  Future<void> deleteShare(int shareId) async {
    await _api.delete('/post-shares/$shareId');
  }

  @override
  Future<void> report({required String type, required int id, required String reason}) async {
    await _api.post('/reports', body: {'reportable_type': type, 'reportable_id': id, 'reason': reason});
  }

  // ==================== التعليقات ====================

  @override
  Future<Paginated<Comment>> comments(int postId, {required int page}) async {
    final json = await _api.get('/posts/$postId/comments', query: {'page': page});
    return Paginated.fromJson(json, Comment.fromJson);
  }

  @override
  Future<Paginated<Comment>> replies(int postId, int commentId, {required int page}) async {
    final json = await _api.get('/posts/$postId/comments/$commentId/replies', query: {'page': page});
    return Paginated.fromJson(json, Comment.fromJson);
  }

  @override
  Future<Comment> addComment(int postId, String content, {int? parentId}) async {
    final path = parentId == null ? '/posts/$postId/comments' : '/posts/$postId/comments/$parentId';
    final json = await _api.post(path, body: {'content': content});
    return Comment.fromJson(asJson(json['data']));
  }

  @override
  Future<void> deleteComment(int postId, int commentId) async {
    await _api.delete('/posts/$postId/comments/$commentId');
  }

  // ==================== التقييمات ====================

  @override
  Future<Paginated<Review>> reviews(int postId, {required int page}) async {
    final json = await _api.get('/posts/$postId/reviews', query: {'page': page});
    return Paginated.fromJson(json, Review.fromJson);
  }

  @override
  Future<void> submitReview(int postId, {required int rating, String? content}) async {
    await _api.post('/posts/$postId/reviews', body: {'rating': rating, 'content': content});
  }

  // ==================== داخلي ====================

  /// المنشور يُرسل كـ multipart لأنه قد يحتوي ملفات.
  Future<FormData> _toFormData(PostDraft draft, {required bool isUpdate}) async {
    final fields = <String, dynamic>{
      // PHP لا يقرأ ملفات طلب PATCH، فنرسل POST مع _method=PATCH
      if (isUpdate) '_method': 'PATCH',
      'title': draft.title,
      'description': draft.description,
      'post_type': draft.postType.name,
      'category_id': draft.categoryId,
      'is_online': draft.isOnline ? '1' : '0',
      if (draft.startDate != null) 'start_date': Formatters.apiDate(draft.startDate!),
      if (draft.endDate != null) 'end_date': Formatters.apiDate(draft.endDate!),
      if (draft.action != null) 'action': draft.action,
    };

    if (!draft.isOnline) {
      fields['location'] = draft.location ?? '';
      fields['latitude'] = draft.latitude;
      fields['longitude'] = draft.longitude;
    }

    // مصفوفة فارغة تُرسل كحقل فارغ حتى تُمسح الوسوم عند التعديل
    if (draft.tags.isEmpty) {
      if (isUpdate) fields['tags'] = '';
    } else {
      fields['tags[]'] = draft.tags;
    }

    if (draft.removedMediaIds.isNotEmpty) {
      fields['remove_media_ids[]'] = draft.removedMediaIds;
    }

    if (draft.newMedia.isNotEmpty) {
      fields['media[]'] = [
        for (final file in draft.newMedia)
          await MultipartFile.fromFile(file.path, filename: file.uri.pathSegments.last),
      ];
    }

    return FormData.fromMap(fields);
  }
}
