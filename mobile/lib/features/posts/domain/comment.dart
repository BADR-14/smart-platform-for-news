import '../../../core/utils/json.dart';
import '../../shared/models/account.dart';

class Comment {
  const Comment({
    required this.id,
    required this.content,
    required this.postId,
    required this.repliesCount,
    required this.isOwner,
    required this.canDelete,
    this.parentId,
    this.author,
    this.createdAt,
  });

  final int id;
  final String content;
  final int postId;
  final int? parentId;
  final int repliesCount;
  final Author? author;
  final bool isOwner;

  /// صاحب التعليق أو صاحب المنشور.
  final bool canDelete;
  final DateTime? createdAt;

  factory Comment.fromJson(Json json) {
    final author = asJsonOrNull(json['author']);

    return Comment(
      id: asInt(json['id']),
      content: asString(json['content']),
      postId: asInt(json['post_id']),
      parentId: asIntOrNull(json['parent_id']),
      repliesCount: asInt(json['replies_count']),
      author: author == null ? null : Author.fromJson(author),
      isOwner: asBool(json['is_owner']),
      canDelete: asBool(json['can_delete']),
      createdAt: asDateOrNull(json['created_at']),
    );
  }

  Comment copyWith({int? repliesCount}) {
    return Comment(
      id: id,
      content: content,
      postId: postId,
      parentId: parentId,
      repliesCount: repliesCount ?? this.repliesCount,
      author: author,
      isOwner: isOwner,
      canDelete: canDelete,
      createdAt: createdAt,
    );
  }
}

class Review {
  const Review({required this.id, required this.rating, this.content, this.author, this.postId, this.postTitle, this.createdAt});

  final int id;
  final double rating;
  final String? content;
  final Author? author;

  /// موجودان فقط في قائمة "تقييماتي".
  final int? postId;
  final String? postTitle;
  final DateTime? createdAt;

  factory Review.fromJson(Json json) {
    final author = asJsonOrNull(json['author']);
    final post = asJsonOrNull(json['post']);

    return Review(
      id: asInt(json['id']),
      rating: asDoubleOrNull(json['rating']) ?? 0,
      content: asStringOrNull(json['content']),
      author: author == null ? null : Author.fromJson(author),
      postId: post == null ? null : asIntOrNull(post['id']),
      postTitle: post == null ? null : asStringOrNull(post['title']),
      createdAt: asDateOrNull(json['created_at']),
    );
  }
}

/// أنواع التفاعل — المعرّفات ثابتة في الباك-إند (ReactTypeSeeder).
enum Reaction {
  like(1, '👍', 'إعجاب'),
  love(2, '❤️', 'أحببته'),
  haha(3, '😂', 'مضحك'),
  wow(4, '😮', 'مدهش'),
  sad(5, '😢', 'محزن'),
  angry(6, '😡', 'مزعج');

  const Reaction(this.id, this.emoji, this.label);

  final int id;
  final String emoji;
  final String label;

  static Reaction? byId(int? id) {
    if (id == null) return null;
    for (final reaction in Reaction.values) {
      if (reaction.id == id) return reaction;
    }
    return null;
  }
}
