import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/network/paginated.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/utils/navigation.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/dialogs.dart';
import '../../../../core/widgets/state_views.dart';
import '../../domain/comment.dart';
import '../../domain/post.dart';
import '../../domain/posts_repository.dart';
import '../controllers/post_details_controller.dart';

/// صف نجوم. إذا مُرِّر [onChanged] يصبح قابلاً للنقر (اختيار تقييم).
class StarRating extends StatelessWidget {
  const StarRating({super.key, required this.value, this.size = 18, this.onChanged});

  final double value;
  final double size;
  final ValueChanged<int>? onChanged;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        for (var star = 1; star <= 5; star++)
          GestureDetector(
            onTap: onChanged == null ? null : () => onChanged!(star),
            child: Padding(
              padding: EdgeInsets.symmetric(horizontal: onChanged == null ? 0 : 4),
              child: Icon(
                value >= star
                    ? Icons.star_rounded
                    : value >= star - 0.5
                        ? Icons.star_half_rounded
                        : Icons.star_outline_rounded,
                size: size,
                color: AppColors.star,
              ),
            ),
          ),
      ],
    );
  }
}

/// نافذة تقييم المنشور (من 1 إلى 5 مع تعليق اختياري).
class ReviewSheet extends StatefulWidget {
  const ReviewSheet({super.key, required this.post, required this.controller});

  final Post post;
  final PostDetailsController controller;

  static Future<void> show(Post post, PostDetailsController controller) {
    return Get.bottomSheet<void>(ReviewSheet(post: post, controller: controller), isScrollControlled: true);
  }

  @override
  State<ReviewSheet> createState() => _ReviewSheetState();
}

class _ReviewSheetState extends State<ReviewSheet> {
  final _content = TextEditingController();
  late int _rating = widget.post.myRating?.round() ?? 0;
  bool _sending = false;

  @override
  void dispose() {
    _content.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _sending = true);

    final text = _content.text.trim();
    final saved = await widget.controller.submitReview(rating: _rating, content: text.isEmpty ? null : text);

    if (!mounted) return;
    setState(() => _sending = false);
    if (saved) AppNav.back<void>();
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: EdgeInsets.fromLTRB(18, 0, 18, 18 + MediaQuery.of(context).viewInsets.bottom),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            SheetHeader(
              title: widget.post.myRating == null ? 'قيّم هذا المنشور' : 'تعديل تقييمك',
              subtitle: widget.post.title,
            ),
            Center(
              child: StarRating(value: _rating.toDouble(), size: 40, onChanged: (value) => setState(() => _rating = value)),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _content,
              maxLines: 3,
              maxLength: 1000,
              decoration: const InputDecoration(hintText: 'شاركنا رأيك (اختياري)…'),
            ),
            const SizedBox(height: 8),
            AppButton(label: 'حفظ التقييم', loading: _sending, onPressed: _rating == 0 ? null : _submit),
          ],
        ),
      ),
    );
  }
}

/// نافذة عرض تقييمات المنشور.
class ReviewsListSheet extends StatelessWidget {
  const ReviewsListSheet({super.key, required this.postId});

  final int postId;

  static Future<void> show(int postId) {
    return Get.bottomSheet<void>(ReviewsListSheet(postId: postId), isScrollControlled: true);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return SafeArea(
      child: ConstrainedBox(
        constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.75),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SheetHeader(title: 'التقييمات'),
            Flexible(
              child: FutureBuilder<Paginated<Review>>(
                future: Get.find<PostsRepository>().reviews(postId, page: 1),
                builder: (context, snapshot) {
                  if (snapshot.connectionState != ConnectionState.done) return const LoadingView();
                  if (snapshot.hasError) return ErrorView(error: snapshot.error!);

                  final reviews = snapshot.data?.items ?? const <Review>[];
                  if (reviews.isEmpty) {
                    return const EmptyView(title: 'لا توجد تقييمات بعد', icon: Icons.star_outline_rounded);
                  }

                  return ListView.separated(
                    shrinkWrap: true,
                    padding: const EdgeInsets.fromLTRB(18, 0, 18, 18),
                    itemCount: reviews.length,
                    separatorBuilder: (context, index) => const Divider(height: 22),
                    itemBuilder: (context, index) {
                      final review = reviews[index];

                      return Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          AppAvatar(name: review.author?.name ?? '', url: review.author?.avatar, radius: 18),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  review.author?.name ?? 'مستخدم',
                                  style: theme.labelLarge?.copyWith(fontWeight: FontWeight.w700),
                                ),
                                const SizedBox(height: 2),
                                Row(
                                  children: [
                                    StarRating(value: review.rating, size: 15),
                                    const SizedBox(width: 8),
                                    Text(
                                      Formatters.timeAgo(review.createdAt),
                                      style: theme.bodySmall?.copyWith(color: AppColors.muted),
                                    ),
                                  ],
                                ),
                                if (review.content != null) ...[
                                  const SizedBox(height: 6),
                                  Text(review.content!, style: theme.bodyMedium?.copyWith(height: 1.5)),
                                ],
                              ],
                            ),
                          ),
                        ],
                      );
                    },
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
