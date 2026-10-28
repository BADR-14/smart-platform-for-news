import 'package:get/get.dart';

import '../../../../app/routes.dart';
import '../../../../core/controllers/paged_controller.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/network/paginated.dart';
import '../../../../core/session/session_service.dart';
import '../../../../core/utils/feedback.dart';
import '../../../../core/utils/navigation.dart';
import '../../../../core/widgets/dialogs.dart';
import '../../domain/comment.dart';
import '../../domain/post.dart';
import '../../domain/posts_repository.dart';
import '../post_actions.dart';
import '../post_sync_mixin.dart';

/// صفحة تفاصيل المنشور: المنشور نفسه + تعليقاته (القائمة المقسّمة) + الردود + التقييم.
class PostDetailsController extends PagedController<Comment> with PostSyncMixin {
  PostDetailsController(this._repository, this._session, this._actions, this.postId);

  final PostsRepository _repository;
  final SessionService _session;
  final PostActions _actions;
  final int postId;

  final Rxn<Post> post = Rxn<Post>();
  final Rxn<Object> postError = Rxn<Object>();

  /// التعليق الذي يرد عليه المستخدم حالياً (null = تعليق جديد على المنشور).
  final Rxn<Comment> replyingTo = Rxn<Comment>();
  final isSending = false.obs;

  /// الردود المحمّلة لكل تعليق: معرّف التعليق ⟵ ردوده.
  final replies = <int, List<Comment>>{}.obs;
  final loadingReplies = <int>{}.obs;

  @override
  void onInit() {
    super.onInit();

    loadPost();

    listenToPostChanges(
      onUpdated: (updated) {
        if (updated.id == postId) post.value = updated;
      },
      onDeleted: (deletedId) {
        if (deletedId != postId || isClosed) return;

        // إن كانت هذه الصفحة هي الظاهرة نغلقها، وإن كانت تحت صفحة أخرى نكتفي بتعليمها كمحذوفة
        if (Get.currentRoute == Routes.postDetails && Get.arguments == postId) {
          AppNav.back<void>();
        } else {
          post.value = null;
          postError.value = const ApiException('تم حذف هذا المنشور.');
        }
      },
    );
  }

  @override
  Future<Paginated<Comment>> fetchPage(int page) => _repository.comments(postId, page: page);

  Future<void> loadPost() async {
    postError.value = null;

    try {
      post.value = await _repository.details(postId);

      // تسجيل المشاهدة لا يؤثر على عرض الصفحة إن فشل
      _repository.recordView(postId).ignore();
    } catch (error) {
      postError.value = error;
    }
  }

  Future<void> refreshAll() async {
    await Future.wait([loadPost(), refreshList(silent: true)]);
  }

  // ==================== التعليقات ====================

  void startReply(Comment comment) {
    if (_session.requireLogin('سجّل الدخول لتتمكن من التعليق.')) replyingTo.value = comment;
  }

  void cancelReply() => replyingTo.value = null;

  /// يرجع true إذا أُرسل التعليق (لتفريغ حقل الإدخال).
  Future<bool> sendComment(String content) async {
    final text = content.trim();
    if (text.isEmpty || isSending.value) return false;
    if (!_session.requireLogin('سجّل الدخول لتتمكن من التعليق.')) return false;

    isSending.value = true;

    try {
      final parent = replyingTo.value;
      final created = await _repository.addComment(postId, text, parentId: parent?.id);

      if (parent == null) {
        items.insert(0, created);
      } else {
        // الرد يُلحق بالتعليق الأصلي (مستوى واحد من الردود)
        final rootId = parent.parentId ?? parent.id;

        replies[rootId] = [...?replies[rootId], created];
        _updateRepliesCount(rootId, 1);
        replyingTo.value = null;
      }

      _bumpCommentsCount(1);
      return true;
    } catch (error) {
      AppFeedback.error(error);
      return false;
    } finally {
      isSending.value = false;
    }
  }

  Future<void> toggleReplies(Comment comment) async {
    if (replies.containsKey(comment.id)) {
      replies.remove(comment.id);
      return;
    }

    loadingReplies.add(comment.id);

    try {
      final page = await _repository.replies(postId, comment.id, page: 1);
      replies[comment.id] = page.items;
    } catch (error) {
      AppFeedback.error(error);
    } finally {
      loadingReplies.remove(comment.id);
    }
  }

  Future<void> deleteComment(Comment comment) async {
    final confirmed = await AppDialogs.confirm(
      title: 'حذف التعليق',
      message: 'هل تريد حذف هذا التعليق؟',
      confirmLabel: 'حذف',
      destructive: true,
    );
    if (!confirmed) return;

    try {
      await _repository.deleteComment(postId, comment.id);

      if (comment.parentId == null) {
        // حذف التعليق يحذف ردوده معه
        items.removeWhere((item) => item.id == comment.id);
        replies.remove(comment.id);
        _bumpCommentsCount(-(1 + comment.repliesCount));
      } else {
        final rootId = comment.parentId!;

        replies[rootId] = [...?replies[rootId]]..removeWhere((reply) => reply.id == comment.id);
        _updateRepliesCount(rootId, -1);
        _bumpCommentsCount(-1);
      }
    } catch (error) {
      AppFeedback.error(error);
    }
  }

  // ==================== التقييم ====================

  /// يرجع true إذا حُفظ التقييم.
  Future<bool> submitReview({required int rating, String? content}) async {
    try {
      await _repository.submitReview(postId, rating: rating, content: content);

      // المتوسط يُحسب في الخادم، فنعيد جلب المنشور
      final fresh = await _repository.details(postId);
      _actions.publish(fresh);

      AppFeedback.success('شكراً لك، تم حفظ تقييمك');
      return true;
    } catch (error) {
      AppFeedback.error(error);
      return false;
    }
  }

  // ==================== داخلي ====================

  void _updateRepliesCount(int commentId, int delta) {
    final index = items.indexWhere((item) => item.id == commentId);
    if (index == -1) return;

    final current = items[index];
    final next = current.repliesCount + delta;
    items[index] = current.copyWith(repliesCount: next < 0 ? 0 : next);
  }

  void _bumpCommentsCount(int delta) {
    final current = post.value;
    if (current == null) return;

    final next = current.commentsCount + delta;
    _actions.publish(current.copyWith(commentsCount: next < 0 ? 0 : next));
  }
}
