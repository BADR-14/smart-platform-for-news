import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../app/routes.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/paged_list_view.dart';
import '../../../posts/domain/comment.dart';
import '../../../posts/presentation/widgets/review_sheets.dart';
import '../controllers/account_profile_controller.dart';

class MyReviewsView extends GetView<MyReviewsController> {
  const MyReviewsView({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Scaffold(
      appBar: AppBar(title: const Text('تقييماتي')),
      body: PagedListView<Review>(
        controller: controller,
        emptyIcon: Icons.star_outline_rounded,
        emptyTitle: 'لم تقيّم أي منشور بعد',
        emptyMessage: 'تقييماتك للمحاضرات والفعاليات تظهر هنا.',
        itemBuilder: (context, review, index) {
          return Card(
            clipBehavior: Clip.antiAlias,
            child: InkWell(
              onTap: review.postId == null ? null : () => Get.toNamed(Routes.postDetails, arguments: review.postId),
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      review.postTitle ?? 'منشور محذوف',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: theme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        StarRating(value: review.rating, size: 17),
                        const SizedBox(width: 8),
                        Text(Formatters.timeAgo(review.createdAt), style: theme.bodySmall?.copyWith(color: AppColors.muted)),
                      ],
                    ),
                    if (review.content != null) ...[
                      const SizedBox(height: 8),
                      Text(review.content!, style: theme.bodyMedium?.copyWith(color: AppColors.inkSoft, height: 1.5)),
                    ],
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
