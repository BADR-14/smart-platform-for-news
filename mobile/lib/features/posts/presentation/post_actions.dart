import 'package:get/get.dart';

import '../../../core/session/session_service.dart';
import '../../../core/utils/feedback.dart';
import '../../../core/widgets/dialogs.dart';
import '../domain/comment.dart';
import '../domain/post.dart';
import '../domain/posts_repository.dart';

/// الإجراءات على المنشور (تفاعل، حفظ، مشاركة، حذف ...) من مكان واحد.
///
/// المنشور نفسه قد يكون معروضاً في أكثر من شاشة (الرئيسية، صفحة الشركة، المحفوظات،
/// التفاصيل). بعد أي تغيير يُبَث المنشور المحدَّث عبر [updated] أو معرّفه عبر [deleted]
/// فتحدّث كل الشاشات نسختها دون أن تعرف واحدتها عن الأخرى.
class PostActions extends GetxService {
  PostActions(this._repository, this._session);

  final PostsRepository _repository;
  final SessionService _session;

  /// آخر منشور تغيّر (تفاعل، حفظ، تعليق ...).
  final Rxn<Post> updated = Rxn<Post>();

  /// معرّف آخر منشور حُذف.
  final RxnInt deleted = RxnInt();

  /// يتغير كلما وجب إعادة تحميل الصفحة الرئيسية (منشور جديد، مشاركة جديدة ...).
  final feedVersion = 0.obs;

  void publish(Post post) => updated.value = post;

  void notifyFeedChanged() => feedVersion.value++;

  /// نفس التفاعل مرة ثانية يلغيه، وتفاعل مختلف يستبدله.
  Future<void> react(Post post, Reaction reaction) async {
    if (!_session.requireLogin()) return;

    // تحديث فوري للواجهة، ثم التصحيح بقيم الخادم (أو التراجع عند الفشل)
    final removing = post.myReaction == reaction.id;
    final hadReaction = post.myReaction != null;

    publish(post.copyWith(
      myReaction: reaction.id,
      clearReaction: removing,
      likesCount: removing ? (post.likesCount > 0 ? post.likesCount - 1 : 0) : (hadReaction ? post.likesCount : post.likesCount + 1),
    ));

    try {
      final result = await _repository.react(post.id, reaction.id);

      publish(post.copyWith(
        likesCount: result.likesCount,
        myReaction: result.myReaction,
        clearReaction: result.myReaction == null,
      ));
    } catch (error) {
      publish(post);
      AppFeedback.error(error);
    }
  }

  Future<void> toggleBookmark(Post post) async {
    if (!_session.requireLogin('سجّل الدخول لتضيف المنشورات إلى مفضلتك وتعود إليها لاحقاً.')) return;

    publish(post.copyWith(isBookmarked: !post.isBookmarked));

    try {
      final saved = await _repository.toggleBookmark(post.id);
      publish(post.copyWith(isBookmarked: saved));
      AppFeedback.success(saved ? 'أُضيف إلى المفضلة' : 'أُزيل من المفضلة');
    } catch (error) {
      publish(post);
      AppFeedback.error(error);
    }
  }

  /// يرجع true إذا تمت المشاركة.
  Future<bool> share(Post post, {String? text}) async {
    try {
      await _repository.share(post.id, text: text);

      publish(post.copyWith(isShared: true, sharesCount: post.sharesCount + 1));
      notifyFeedChanged();
      AppFeedback.success('تمت مشاركة المنشور');
      return true;
    } catch (error) {
      AppFeedback.error(error);
      return false;
    }
  }

  Future<void> deleteShare(FeedItem item) async {
    final shareId = item.shareId;
    if (shareId == null) return;

    final confirmed = await AppDialogs.confirm(
      title: 'حذف المشاركة',
      message: 'هل تريد حذف مشاركتك لهذا المنشور؟',
      confirmLabel: 'حذف',
      destructive: true,
    );
    if (!confirmed) return;

    try {
      await _repository.deleteShare(shareId);

      publish(item.post.copyWith(isShared: false, sharesCount: item.post.sharesCount > 0 ? item.post.sharesCount - 1 : 0));
      notifyFeedChanged();
      AppFeedback.success('تم حذف المشاركة');
    } catch (error) {
      AppFeedback.error(error);
    }
  }

  /// يرجع true إذا حُذف المنشور.
  Future<bool> delete(Post post) async {
    final confirmed = await AppDialogs.confirm(
      title: 'حذف المنشور',
      message: 'سيُحذف "${post.title}" نهائياً مع تعليقاته وتفاعلاته.',
      confirmLabel: 'حذف',
      destructive: true,
    );
    if (!confirmed) return false;

    try {
      await _repository.delete(post.id);
      deleted.value = post.id;
      AppFeedback.success('تم حذف المنشور');
      return true;
    } catch (error) {
      AppFeedback.error(error);
      return false;
    }
  }

  /// نشر مسودة أو أرشفة منشور.
  Future<void> changeStatus(Post post, String action) async {
    try {
      publish(await _repository.changeStatus(post.id, action));
      notifyFeedChanged();
      AppFeedback.success(switch (action) {
        'archive' => 'تمت أرشفة المنشور. تجده في الأرشيف من ملفك الشخصي',
        _ when post.status == PostStatus.archived => 'أُعيد المنشور من الأرشيف ونُشر',
        _ => 'تم نشر المنشور',
      });
    } catch (error) {
      AppFeedback.error(error);
    }
  }

  /// يرجع true إذا أُرسل البلاغ.
  Future<bool> report({required String type, required int id, required String reason}) async {
    try {
      await _repository.report(type: type, id: id, reason: reason);
      AppFeedback.success('تم إرسال البلاغ وستراجعه الإدارة');
      return true;
    } catch (error) {
      AppFeedback.error(error);
      return false;
    }
  }
}
