import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../app/routes.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/motion.dart';
import '../../../shared/models/account.dart';
import '../../domain/post.dart';
import '../post_actions.dart';
import 'post_sheets.dart';
import 'reaction_button.dart';

/// يفتح صفحة صاحب المنشور: صفحة الشركة إن كان شركة، وإلا الملف العام للحساب.
void openAuthor(Author author) {
  if (author.isCompany && author.companyId != null) {
    Get.toNamed(Routes.companyProfile, arguments: author.companyId);
  } else {
    Get.toNamed(Routes.accountProfile, arguments: author.id);
  }
}

/// بطاقة المنشور في القوائم (الرئيسية، صفحة الشركة، المحفوظات ...).
class PostCard extends StatelessWidget {
  const PostCard({super.key, required this.post, this.share, this.showStatus = false});

  final Post post;

  /// بيانات المشاركة إذا كان العنصر مشاركة لمنشور وليس منشوراً أصلياً.
  final FeedItem? share;

  /// إظهار حالة المنشور (في "منشوراتي").
  final bool showStatus;

  void _open() => Get.toNamed(Routes.postDetails, arguments: post.id);

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Pressable(
      child: Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: _open,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (share != null && share!.isShare) _ShareBanner(item: share!),
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 12, 6, 0),
              child: _AuthorRow(post: post),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 10, 14, 0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Wrap(
                    spacing: 6,
                    runSpacing: 6,
                    children: [
                      _Tag(label: post.postType.label, color: Colors.white, background: AppColors.brand800),
                      if (post.category != null)
                        _Tag(label: post.category!.name, color: AppColors.brand700, background: AppColors.brand100),
                      if (post.isFeatured)
                        const _Tag(label: 'مميز', color: Colors.white, background: AppColors.accent500, icon: Icons.auto_awesome),
                      if (showStatus && post.status != PostStatus.published) PostStatusChip(status: post.status),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Text(post.title, style: theme.titleMedium?.copyWith(fontWeight: FontWeight.w700, height: 1.4)),
                  if (post.description != null) ...[
                    const SizedBox(height: 6),
                    Text(
                      // المقتطف سطر متصل: الأسطر الفارغة في الوصف تضيّع مساحة البطاقة
                      post.description!.replaceAll(RegExp(r'\s+'), ' '),
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                      style: theme.bodyMedium?.copyWith(color: AppColors.inkSoft, height: 1.6),
                    ),
                  ],
                ],
              ),
            ),
            if (post.coverUrl != null) ...[
              const SizedBox(height: 12),
              AspectRatio(aspectRatio: 16 / 9, child: AppNetworkImage(url: post.coverUrl!)),
            ],
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 10, 14, 0),
              child: PostMetaRow(post: post),
            ),
            const SizedBox(height: 4),
            const Divider(indent: 14, endIndent: 14),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              child: PostActionsBar(post: post, onComment: _open),
            ),
          ],
        ),
      ),
      ),
    );
  }
}

class _ShareBanner extends StatelessWidget {
  const _ShareBanner({required this.item});

  final FeedItem item;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;
    final sharedBy = item.sharedBy;

    return Container(
      width: double.infinity,
      color: AppColors.brand100,
      padding: const EdgeInsets.fromLTRB(14, 10, 6, 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.repeat, size: 16, color: AppColors.brand600),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  '${sharedBy?.name ?? 'مستخدم'} شارك هذا المنشور · ${Formatters.timeAgo(item.sharedAt)}',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: theme.bodySmall?.copyWith(color: AppColors.brand700, fontWeight: FontWeight.w600),
                ),
              ),
              if (item.isMyShare)
                IconButton(
                  tooltip: 'حذف المشاركة',
                  visualDensity: VisualDensity.compact,
                  icon: const Icon(Icons.close, size: 18, color: AppColors.muted),
                  onPressed: () => Get.find<PostActions>().deleteShare(item),
                )
              else
                const SizedBox(width: 8),
            ],
          ),
          if (item.shareText != null) ...[
            const SizedBox(height: 6),
            Padding(
              padding: const EdgeInsetsDirectional.only(end: 8),
              child: Text(item.shareText!, style: theme.bodyMedium?.copyWith(height: 1.5)),
            ),
          ],
        ],
      ),
    );
  }
}

class _AuthorRow extends StatelessWidget {
  const _AuthorRow({required this.post});

  final Post post;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;
    final author = post.author;

    return Row(
      children: [
        GestureDetector(
          onTap: author == null ? null : () => openAuthor(author),
          child: AppAvatar(name: author?.name ?? '', url: author?.avatar),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: GestureDetector(
            behavior: HitTestBehavior.opaque,
            onTap: author == null ? null : () => openAuthor(author),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Flexible(
                      child: Text(
                        author?.name ?? 'حساب محذوف',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: theme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                      ),
                    ),
                    if (author?.isVerified ?? false) ...[const SizedBox(width: 4), const VerifiedBadge()],
                  ],
                ),
                Text(
                  '${(author?.isCompany ?? false) ? 'شركة' : 'مستخدم'} · ${Formatters.timeAgo(post.createdAt)}',
                  style: theme.bodySmall?.copyWith(color: AppColors.muted),
                ),
              ],
            ),
          ),
        ),
        PostMenuButton(post: post),
      ],
    );
  }
}

/// قائمة خيارات المنشور: المفضلة للجميع، وصاحب المنشور يعدّل ويؤرشف ويحذف، وغيره يبلّغ.
class PostMenuButton extends StatelessWidget {
  const PostMenuButton({super.key, required this.post, this.onDeleted, this.color = AppColors.muted});

  final Post post;

  /// لون الأيقونة (أبيض داخل الترويسة الكحلية).
  final Color color;

  /// يُستدعى بعد حذف المنشور (صفحة التفاصيل تغلق نفسها).
  final VoidCallback? onDeleted;

  @override
  Widget build(BuildContext context) {
    final actions = Get.find<PostActions>();

    return PopupMenuButton<String>(
      tooltip: 'خيارات',
      icon: Icon(Icons.more_vert, color: color),
      onSelected: (value) async {
        switch (value) {
          case 'favorite':
            await actions.toggleBookmark(post);
          case 'edit':
            Get.toNamed(Routes.postForm, arguments: post);
          case 'publish':
            await actions.changeStatus(post, 'publish');
          case 'archive':
            await actions.changeStatus(post, 'archive');
          case 'delete':
            if (await actions.delete(post)) onDeleted?.call();
          case 'report':
            await PostSheets.reportPost(post);
        }
      },
      itemBuilder: (context) => [
        PopupMenuItem(
          value: 'favorite',
          child: _MenuRow(
            icon: post.isBookmarked ? Icons.favorite_rounded : Icons.favorite_border_rounded,
            label: post.isBookmarked ? 'إزالة من المفضلة' : 'إضافة إلى المفضلة',
            color: post.isBookmarked ? AppColors.accent600 : AppColors.inkSoft,
          ),
        ),
        if (post.isOwner) ...[
          const PopupMenuItem(value: 'edit', child: _MenuRow(icon: Icons.edit_outlined, label: 'تعديل')),
          if (post.status == PostStatus.draft)
            const PopupMenuItem(value: 'publish', child: _MenuRow(icon: Icons.send_outlined, label: 'نشر')),
          if (post.status == PostStatus.archived)
            const PopupMenuItem(
              value: 'publish',
              child: _MenuRow(icon: Icons.unarchive_outlined, label: 'إلغاء الأرشفة'),
            ),
          if (post.status == PostStatus.published)
            const PopupMenuItem(value: 'archive', child: _MenuRow(icon: Icons.archive_outlined, label: 'أرشفة')),
          const PopupMenuItem(
            value: 'delete',
            child: _MenuRow(icon: Icons.delete_outline, label: 'حذف', color: AppColors.danger),
          ),
        ] else
          const PopupMenuItem(value: 'report', child: _MenuRow(icon: Icons.flag_outlined, label: 'إبلاغ')),
      ],
    );
  }
}

class _MenuRow extends StatelessWidget {
  const _MenuRow({required this.icon, required this.label, this.color = AppColors.inkSoft});

  final IconData icon;
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 20, color: color),
        const SizedBox(width: 10),
        Text(label, style: TextStyle(color: color)),
      ],
    );
  }
}

class _Tag extends StatelessWidget {
  const _Tag({required this.label, required this.color, required this.background, this.icon});

  final String label;
  final Color color;
  final Color background;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: background, borderRadius: BorderRadius.circular(20)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[Icon(icon, size: 13, color: color), const SizedBox(width: 4)],
          Text(label, style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}

class PostStatusChip extends StatelessWidget {
  const PostStatusChip({super.key, required this.status});

  final PostStatus status;

  @override
  Widget build(BuildContext context) {
    final (Color color, Color background) = switch (status) {
      PostStatus.published => (AppColors.success, AppColors.successBg),
      PostStatus.draft => (AppColors.inkSoft, AppColors.canvas),
      PostStatus.underReview => (AppColors.warning, AppColors.warningBg),
      PostStatus.rejected => (AppColors.danger, AppColors.dangerBg),
      PostStatus.archived => (AppColors.info, AppColors.infoBg),
    };

    return _Tag(label: status.label, color: color, background: background);
  }
}

/// سطر المعلومات: مكان الانعقاد والتاريخ والتقييم.
class PostMetaRow extends StatelessWidget {
  const PostMetaRow({super.key, required this.post});

  final Post post;

  @override
  Widget build(BuildContext context) {
    final style = Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.muted);

    Widget item(IconData icon, String text) => Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 15, color: AppColors.accent500),
            const SizedBox(width: 4),
            Flexible(child: Text(text, style: style, maxLines: 1, overflow: TextOverflow.ellipsis)),
          ],
        );

    return Wrap(
      spacing: 14,
      runSpacing: 6,
      children: [
        item(
          post.isOnline ? Icons.videocam_outlined : Icons.place_outlined,
          post.isOnline ? 'أونلاين' : (post.location ?? 'حضوري'),
        ),
        if (post.startDate != null) item(Icons.event_outlined, Formatters.date(post.startDate)),
        if (post.ratingAvg != null) item(Icons.star_rounded, '${post.ratingAvg!.toStringAsFixed(1)} (${post.reviewsCount})'),
        item(Icons.visibility_outlined, Formatters.count(post.viewsCount)),
      ],
    );
  }
}

/// شريط الإجراءات: تفاعل، تعليق، مشاركة، حفظ.
class PostActionsBar extends StatelessWidget {
  const PostActionsBar({super.key, required this.post, required this.onComment});

  final Post post;
  final VoidCallback onComment;

  @override
  Widget build(BuildContext context) {
    final actions = Get.find<PostActions>();

    return Row(
      children: [
        ReactionButton(post: post),
        _ActionButton(
          icon: Icons.mode_comment_outlined,
          label: post.commentsCount > 0 ? Formatters.count(post.commentsCount) : 'تعليق',
          onTap: onComment,
        ),
        _ActionButton(
          icon: Icons.repeat,
          label: post.sharesCount > 0 ? Formatters.count(post.sharesCount) : 'مشاركة',
          active: post.isShared,
          // المشاركة مرة واحدة لكل منشور، وصاحب المنشور لا يشارك منشوره
          onTap: post.isShared || post.isOwner ? null : () => PostSheets.share(post),
        ),
        const Spacer(),
        IconButton(
          tooltip: post.isBookmarked ? 'إزالة من المفضلة' : 'إضافة إلى المفضلة',
          icon: PopSwitcher(
            child: Icon(
              post.isBookmarked ? Icons.favorite_rounded : Icons.favorite_border_rounded,
              key: ValueKey(post.isBookmarked),
              color: post.isBookmarked ? AppColors.accent500 : AppColors.muted,
            ),
          ),
          onPressed: () => actions.toggleBookmark(post),
        ),
      ],
    );
  }
}

class _ActionButton extends StatelessWidget {
  const _ActionButton({required this.icon, required this.label, required this.onTap, this.active = false});

  final IconData icon;
  final String label;
  final VoidCallback? onTap;
  final bool active;

  @override
  Widget build(BuildContext context) {
    final color = active ? AppColors.accent600 : AppColors.muted;

    return InkWell(
      borderRadius: BorderRadius.circular(10),
      onTap: onTap,
      child: Padding(
        // ارتفاع منطقة اللمس 48 على الأقل
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 14),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 20, color: color),
            const SizedBox(width: 6),
            Text(label, style: TextStyle(color: color, fontSize: 13, fontWeight: active ? FontWeight.w700 : FontWeight.w500)),
          ],
        ),
      ),
    );
  }
}
