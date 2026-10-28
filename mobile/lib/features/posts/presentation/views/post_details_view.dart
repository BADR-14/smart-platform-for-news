import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/session/session_service.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/feedback.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/argument_state.dart';
import '../../../../core/widgets/state_views.dart';
import '../../domain/post.dart';
import '../controllers/post_details_controller.dart';
import '../widgets/comments_section.dart';
import '../widgets/post_card.dart';
import '../widgets/review_sheets.dart';

class PostDetailsView extends StatefulWidget {
  const PostDetailsView({super.key});

  @override
  State<PostDetailsView> createState() => _PostDetailsViewState();
}

class _PostDetailsViewState extends ArgumentState<PostDetailsView, PostDetailsController> {
  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final post = controller.post.value;
      final error = controller.postError.value;

      if (post == null) {
        return Scaffold(
          appBar: AppBar(),
          body: error != null ? ErrorView(error: error, onRetry: controller.loadPost) : const LoadingView(),
        );
      }

      return Scaffold(
        backgroundColor: AppColors.surface,
        appBar: AppBar(
          title: Text(post.postType.label),
          actions: [PostMenuButton(post: post, color: Colors.white)],
        ),
        bottomNavigationBar: Padding(
          // يرفع حقل التعليق فوق لوحة المفاتيح
          padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
          child: CommentInputBar(controller: controller),
        ),
        body: RefreshIndicator(
          onRefresh: controller.refreshAll,
          child: NotificationListener<ScrollNotification>(
            onNotification: (notification) {
              if (notification.metrics.pixels >= notification.metrics.maxScrollExtent - 300) controller.loadMore();
              return false;
            },
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.only(bottom: 24),
              children: [
                if (post.media.isNotEmpty) _MediaGallery(post: post),
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                  child: _PostBody(post: post),
                ),
                const SizedBox(height: 8),
                const Divider(indent: 16, endIndent: 16),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 8),
                  child: PostActionsBar(post: post, onComment: () {}),
                ),
                const Divider(indent: 16, endIndent: 16),
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 14, 16, 0),
                  child: _RatingCard(post: post, controller: controller),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 22, 16, 10),
                  child: Text(
                    'التعليقات (${post.commentsCount})',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                  ),
                ),
                _CommentsList(controller: controller),
              ],
            ),
          ),
        ),
      );
    });
  }
}

class _MediaGallery extends StatefulWidget {
  const _MediaGallery({required this.post});

  final Post post;

  @override
  State<_MediaGallery> createState() => _MediaGalleryState();
}

class _MediaGalleryState extends State<_MediaGallery> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final media = widget.post.media;

    return AspectRatio(
      aspectRatio: 16 / 10,
      child: Stack(
        fit: StackFit.expand,
        children: [
          PageView.builder(
            itemCount: media.length,
            onPageChanged: (index) => setState(() => _index = index),
            itemBuilder: (context, index) {
              final item = media[index];

              if (!item.isVideo) return AppNetworkImage(url: item.url);

              // الفيديو يُفتح في مشغّل الجهاز
              return InkWell(
                onTap: () => launchUrl(Uri.parse(item.url), mode: LaunchMode.externalApplication),
                child: const ColoredBox(
                  color: AppColors.brand900,
                  child: Center(child: Icon(Icons.play_circle_fill, size: 64, color: Colors.white)),
                ),
              );
            },
          ),
          if (media.length > 1)
            Positioned(
              bottom: 10,
              left: 0,
              right: 0,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  for (var dot = 0; dot < media.length; dot++)
                    AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      margin: const EdgeInsets.symmetric(horizontal: 3),
                      width: dot == _index ? 18 : 7,
                      height: 7,
                      decoration: BoxDecoration(
                        color: dot == _index ? AppColors.accent500 : Colors.white.withValues(alpha: 0.8),
                        borderRadius: BorderRadius.circular(7),
                      ),
                    ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _PostBody extends StatelessWidget {
  const _PostBody({required this.post});

  final Post post;

  Future<void> _openMap() async {
    final query = post.hasCoordinates ? '${post.latitude},${post.longitude}' : Uri.encodeComponent(post.location ?? '');
    final opened = await launchUrl(
      Uri.parse('https://www.google.com/maps/search/?api=1&query=$query'),
      mode: LaunchMode.externalApplication,
    );

    if (!opened) AppFeedback.error('تعذّر فتح الخريطة.');
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;
    final author = post.author;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (post.moderationNote != null && post.isOwner)
          Container(
            width: double.infinity,
            margin: const EdgeInsets.only(bottom: 14),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: AppColors.warningBg, borderRadius: BorderRadius.circular(12)),
            child: Text(
              'ملاحظة الإدارة: ${post.moderationNote}',
              style: theme.bodyMedium?.copyWith(color: AppColors.warning),
            ),
          ),
        Wrap(
          spacing: 6,
          runSpacing: 6,
          children: [
            if (post.status != PostStatus.published) PostStatusChip(status: post.status),
            if (post.category != null) Chip(label: Text(post.category!.name), visualDensity: VisualDensity.compact),
            if (post.isFeatured)
              const Chip(
                avatar: Icon(Icons.auto_awesome, size: 16, color: AppColors.accent600),
                label: Text('مميز'),
                backgroundColor: AppColors.accent50,
                visualDensity: VisualDensity.compact,
              ),
          ],
        ),
        const SizedBox(height: 10),
        Text(post.title, style: theme.headlineSmall?.copyWith(fontWeight: FontWeight.w700, height: 1.4)),
        const SizedBox(height: 14),
        if (author != null)
          InkWell(
            borderRadius: BorderRadius.circular(12),
            onTap: () => openAuthor(author),
            child: Row(
              children: [
                AppAvatar(name: author.name, url: author.avatar, radius: 22),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Flexible(
                            child: Text(
                              author.name,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: theme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                            ),
                          ),
                          if (author.isVerified) ...[const SizedBox(width: 4), const VerifiedBadge()],
                        ],
                      ),
                      Text(
                        '${author.isCompany ? 'شركة' : 'مستخدم'} · ${Formatters.timeAgo(post.createdAt)}',
                        style: theme.bodySmall?.copyWith(color: AppColors.muted),
                      ),
                    ],
                  ),
                ),
                const Icon(Icons.chevron_left, color: AppColors.muted),
              ],
            ),
          ),
        const SizedBox(height: 16),
        _InfoTile(
          icon: post.isOnline ? Icons.videocam_outlined : Icons.place_outlined,
          title: post.isOnline ? 'أونلاين' : (post.location ?? 'حضوري'),
          subtitle: post.isOnline ? 'تُعقد عن بُعد عبر الإنترنت' : 'اضغط لفتح الموقع في الخرائط',
          onTap: post.isOnline ? null : _openMap,
        ),
        if (post.startDate != null)
          _InfoTile(
            icon: Icons.event_outlined,
            title: post.endDate != null && post.endDate != post.startDate
                ? '${Formatters.date(post.startDate)} ← ${Formatters.date(post.endDate)}'
                : Formatters.date(post.startDate),
            subtitle: 'موعد الانعقاد',
          ),
        if (post.description != null) ...[
          const SizedBox(height: 12),
          SelectableText(post.description!, style: theme.bodyLarge?.copyWith(height: 1.8, color: AppColors.inkSoft)),
        ],
        if (post.tags.isNotEmpty) ...[
          const SizedBox(height: 14),
          Wrap(
            spacing: 8,
            runSpacing: 4,
            children: [
              for (final tag in post.tags)
                Text('#$tag', style: theme.bodyMedium?.copyWith(color: AppColors.accent700, fontWeight: FontWeight.w600)),
            ],
          ),
        ],
      ],
    );
  }
}

class _InfoTile extends StatelessWidget {
  const _InfoTile({required this.icon, required this.title, required this.subtitle, this.onTap});

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: AppColors.canvas,
        borderRadius: BorderRadius.circular(14),
        child: InkWell(
          borderRadius: BorderRadius.circular(14),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(color: AppColors.brand100, borderRadius: BorderRadius.circular(12)),
                  child: Icon(icon, color: AppColors.brand800, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title, style: theme.titleSmall?.copyWith(fontWeight: FontWeight.w600)),
                      Text(subtitle, style: theme.bodySmall?.copyWith(color: AppColors.muted)),
                    ],
                  ),
                ),
                if (onTap != null) const Icon(Icons.open_in_new, size: 18, color: AppColors.muted),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _RatingCard extends StatelessWidget {
  const _RatingCard({required this.post, required this.controller});

  final Post post;
  final PostDetailsController controller;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;
    final hasRating = post.ratingAvg != null;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        border: Border.all(color: AppColors.line),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Expanded(
            child: InkWell(
              onTap: post.reviewsCount > 0 ? () => ReviewsListSheet.show(post.id) : null,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Text(
                        hasRating ? post.ratingAvg!.toStringAsFixed(1) : '—',
                        style: theme.headlineSmall?.copyWith(fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(width: 8),
                      StarRating(value: post.ratingAvg ?? 0),
                    ],
                  ),
                  Text(
                    post.reviewsCount > 0 ? '${post.reviewsCount} تقييم · اضغط للعرض' : 'لا توجد تقييمات بعد',
                    style: theme.bodySmall?.copyWith(color: AppColors.muted),
                  ),
                ],
              ),
            ),
          ),
          // صاحب المنشور لا يقيّم منشوره
          if (!post.isOwner)
            OutlinedButton.icon(
              style: OutlinedButton.styleFrom(minimumSize: const Size(0, 42)),
              onPressed: () {
                if (Get.find<SessionService>().requireLogin('سجّل الدخول لتقيّم المنشور.')) {
                  ReviewSheet.show(post, controller);
                }
              },
              icon: const Icon(Icons.star_outline_rounded, size: 18),
              label: Text(post.myRating == null ? 'قيّم' : 'تقييمك ${post.myRating!.round()}'),
            ),
        ],
      ),
    );
  }
}

class _CommentsList extends StatelessWidget {
  const _CommentsList({required this.controller});

  final PostDetailsController controller;

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      if (controller.isLoading.value && controller.items.isEmpty) return const LoadingView();

      if (controller.items.isEmpty) {
        return const EmptyView(
          icon: Icons.mode_comment_outlined,
          title: 'لا توجد تعليقات بعد',
          message: 'كن أول من يعلّق على هذا المنشور.',
        );
      }

      return Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        child: Column(
          children: [
            for (final comment in controller.items)
              Padding(
                padding: const EdgeInsets.only(bottom: 14),
                child: CommentTile(key: ValueKey(comment.id), comment: comment, controller: controller),
              ),
            if (controller.isLoadingMore.value)
              const Padding(padding: EdgeInsets.all(12), child: CircularProgressIndicator(strokeWidth: 2.4)),
          ],
        ),
      );
    });
  }
}
