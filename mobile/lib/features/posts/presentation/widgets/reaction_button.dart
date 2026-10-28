import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/utils/navigation.dart';
import '../../../../core/widgets/motion.dart';
import '../../domain/comment.dart';
import '../../domain/post.dart';
import '../post_actions.dart';

/// زر التفاعل: نقرة = إعجاب (أو إلغاء تفاعلي)، ضغطة مطوّلة = اختيار نوع التفاعل.
class ReactionButton extends StatelessWidget {
  const ReactionButton({super.key, required this.post});

  final Post post;

  @override
  Widget build(BuildContext context) {
    final actions = Get.find<PostActions>();
    final mine = Reaction.byId(post.myReaction);

    return InkWell(
      borderRadius: BorderRadius.circular(10),
      onTap: () => actions.react(post, mine ?? Reaction.like),
      onLongPress: () => _showPicker(context, actions),
      child: Padding(
        // ارتفاع منطقة اللمس 48 على الأقل
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 14),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            PopSwitcher(
              child: mine == null
                  ? const Icon(Icons.thumb_up_alt_outlined, key: ValueKey(0), size: 20, color: AppColors.muted)
                  : Text(mine.emoji, key: ValueKey(mine.id), style: const TextStyle(fontSize: 18)),
            ),
            const SizedBox(width: 6),
            RollingText(
              post.likesCount > 0 ? Formatters.count(post.likesCount) : 'تفاعل',
              style: TextStyle(
                color: mine == null ? AppColors.muted : AppColors.accent600,
                fontWeight: mine == null ? FontWeight.w500 : FontWeight.w700,
                fontSize: 13,
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showPicker(BuildContext context, PostActions actions) {
    Get.bottomSheet<void>(
      SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12, 18, 12, 18),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              for (final reaction in Reaction.values)
                Tooltip(
                  message: reaction.label,
                  child: InkResponse(
                    radius: 30,
                    onTap: () {
                      AppNav.back<void>();
                      actions.react(post, reaction);
                    },
                    child: AnimatedScale(
                      scale: post.myReaction == reaction.id ? 1.25 : 1,
                      duration: const Duration(milliseconds: 150),
                      child: Padding(
                        padding: const EdgeInsets.all(6),
                        child: Text(reaction.emoji, style: const TextStyle(fontSize: 30)),
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
