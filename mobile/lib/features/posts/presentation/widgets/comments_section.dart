import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../domain/comment.dart';
import '../controllers/post_details_controller.dart';
import 'post_card.dart';

/// تعليق واحد مع أزراره (رد، عرض الردود، حذف) وردوده إن كانت مفتوحة.
class CommentTile extends StatelessWidget {
  const CommentTile({super.key, required this.comment, required this.controller});

  final Comment comment;
  final PostDetailsController controller;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _CommentBody(comment: comment, controller: controller),
        if (comment.repliesCount > 0)
          Padding(
            padding: const EdgeInsetsDirectional.only(start: 46),
            child: Obx(() {
              final opened = controller.replies.containsKey(comment.id);
              final loading = controller.loadingReplies.contains(comment.id);

              return TextButton.icon(
                style: TextButton.styleFrom(
                  visualDensity: VisualDensity.compact,
                  foregroundColor: AppColors.brand600,
                  padding: const EdgeInsets.symmetric(horizontal: 6),
                ),
                onPressed: loading ? null : () => controller.toggleReplies(comment),
                icon: loading
                    ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                    : Icon(opened ? Icons.expand_less : Icons.expand_more, size: 18),
                label: Text(opened ? 'إخفاء الردود' : 'عرض الردود (${comment.repliesCount})'),
              );
            }),
          ),
        Obx(() {
          final list = controller.replies[comment.id];
          if (list == null || list.isEmpty) return const SizedBox.shrink();

          return Padding(
            padding: const EdgeInsetsDirectional.only(start: 34, top: 4),
            child: Container(
              padding: const EdgeInsetsDirectional.only(start: 12),
              decoration: const BoxDecoration(
                border: BorderDirectional(start: BorderSide(color: AppColors.line, width: 2)),
              ),
              child: Column(
                children: [
                  for (final reply in list)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: _CommentBody(comment: reply, controller: controller, compact: true),
                    ),
                ],
              ),
            ),
          );
        }),
      ],
    );
  }
}

class _CommentBody extends StatelessWidget {
  const _CommentBody({required this.comment, required this.controller, this.compact = false});

  final Comment comment;
  final PostDetailsController controller;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;
    final author = comment.author;

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        GestureDetector(
          onTap: author == null ? null : () => openAuthor(author),
          child: AppAvatar(name: author?.name ?? '', url: author?.avatar, radius: compact ? 14 : 18),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
                decoration: BoxDecoration(color: AppColors.canvas, borderRadius: BorderRadius.circular(14)),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            author?.name ?? 'مستخدم',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: theme.labelLarge?.copyWith(fontWeight: FontWeight.w700),
                          ),
                        ),
                        if (author?.isVerified ?? false) ...[const SizedBox(width: 4), const VerifiedBadge(size: 14)],
                      ],
                    ),
                    const SizedBox(height: 3),
                    Text(comment.content, style: theme.bodyMedium?.copyWith(height: 1.5)),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsetsDirectional.only(start: 6, top: 2),
                child: Row(
                  children: [
                    Text(Formatters.timeAgo(comment.createdAt), style: theme.bodySmall?.copyWith(color: AppColors.muted)),
                    const SizedBox(width: 4),
                    _SmallAction(label: 'رد', onTap: () => controller.startReply(comment)),
                    if (comment.canDelete)
                      _SmallAction(label: 'حذف', color: AppColors.danger, onTap: () => controller.deleteComment(comment)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _SmallAction extends StatelessWidget {
  const _SmallAction({required this.label, required this.onTap, this.color = AppColors.brand600});

  final String label;
  final VoidCallback onTap;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(8),
      onTap: onTap,
      child: Padding(
        // النص صغير لكن منطقة اللمس كافية للإصبع
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        child: Text(label, style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.w700)),
      ),
    );
  }
}

/// حقل كتابة التعليق المثبت أسفل صفحة التفاصيل.
class CommentInputBar extends StatefulWidget {
  const CommentInputBar({super.key, required this.controller});

  final PostDetailsController controller;

  @override
  State<CommentInputBar> createState() => _CommentInputBarState();
}

class _CommentInputBarState extends State<CommentInputBar> {
  final _text = TextEditingController();
  final _focus = FocusNode();
  Worker? _replyWorker;

  @override
  void initState() {
    super.initState();

    // عند الضغط على "رد" ينتقل التركيز لحقل الكتابة
    _replyWorker = ever<Comment?>(widget.controller.replyingTo, (comment) {
      if (comment != null) _focus.requestFocus();
    });
  }

  @override
  void dispose() {
    _replyWorker?.dispose();
    _text.dispose();
    _focus.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    if (await widget.controller.sendComment(_text.text)) {
      _text.clear();
      _focus.unfocus();
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Material(
      color: AppColors.surface,
      elevation: 8,
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Obx(() {
              final replyingTo = widget.controller.replyingTo.value;
              if (replyingTo == null) return const SizedBox.shrink();

              return Container(
                color: AppColors.brand50,
                padding: const EdgeInsetsDirectional.only(start: 14, end: 4),
                child: Row(
                  children: [
                    const Icon(Icons.reply, size: 16, color: AppColors.brand600),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        'الرد على ${replyingTo.author?.name ?? 'تعليق'}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: theme.bodySmall?.copyWith(color: AppColors.brand700, fontWeight: FontWeight.w600),
                      ),
                    ),
                    IconButton(
                      tooltip: 'إلغاء الرد',
                      visualDensity: VisualDensity.compact,
                      icon: const Icon(Icons.close, size: 18),
                      onPressed: widget.controller.cancelReply,
                    ),
                  ],
                ),
              );
            }),
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 8),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Expanded(
                    child: TextField(
                      controller: _text,
                      focusNode: _focus,
                      minLines: 1,
                      maxLines: 4,
                      maxLength: 1000,
                      textInputAction: TextInputAction.newline,
                      decoration: const InputDecoration(hintText: 'اكتب تعليقاً…', isDense: true, counterText: ''),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Obx(
                    () => IconButton.filled(
                      tooltip: 'إرسال',
                      style: IconButton.styleFrom(backgroundColor: AppColors.accent500),
                      onPressed: widget.controller.isSending.value ? null : _send,
                      icon: widget.controller.isSending.value
                          ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                          : const Icon(Icons.send, size: 20),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
