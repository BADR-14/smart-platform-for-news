import '../../../core/utils/json.dart';

class AppNotification {
  const AppNotification({
    required this.id,
    required this.type,
    required this.title,
    required this.body,
    required this.isRead,
    this.meta = const {},
    this.createdAt,
  });

  /// معرّف الإشعار نصّي (UUID).
  final String id;

  /// نوع ثابت من الباك-إند: post_commented, post_reacted, verification_reviewed ...
  final String type;
  final String title;
  final String body;
  final bool isRead;

  /// بيانات إضافية حسب النوع (post_id, company_id ...).
  final Json meta;
  final DateTime? createdAt;

  int? get postId => asIntOrNull(meta['post_id']);

  int? get actorAccountId => asIntOrNull(meta['follower_account_id'] ?? meta['actor_account_id']);

  factory AppNotification.fromJson(Json json) {
    return AppNotification(
      id: asString(json['id']),
      type: asString(json['type']),
      title: asString(json['title'], 'إشعار'),
      body: asString(json['body']),
      isRead: asBool(json['is_read']),
      meta: asJson(json['meta']),
      createdAt: asDateOrNull(json['created_at']),
    );
  }

  AppNotification markedRead() {
    return AppNotification(id: id, type: type, title: title, body: body, isRead: true, meta: meta, createdAt: createdAt);
  }
}
