import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../app/routes.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../domain/company.dart';
import '../company_follow_service.dart';

class CompanyCard extends StatelessWidget {
  const CompanyCard({super.key, required this.company});

  final Company company;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => Get.toNamed(Routes.companyProfile, arguments: company.id),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              AppAvatar(name: company.name, url: company.logo, radius: 26),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            company.name,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: theme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                          ),
                        ),
                        if (company.isVerified) ...[const SizedBox(width: 4), const VerifiedBadge()],
                      ],
                    ),
                    if (company.category != null)
                      Text(company.category!.name, style: theme.bodySmall?.copyWith(color: AppColors.accent700, fontWeight: FontWeight.w600)),
                    const SizedBox(height: 4),
                    Text(
                      '${Formatters.count(company.followersCount)} متابع · ${Formatters.count(company.postsCount)} منشور',
                      style: theme.bodySmall?.copyWith(color: AppColors.muted),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              if (!company.isOwner) FollowButton(company: company, compact: true),
            ],
          ),
        ),
      ),
    );
  }
}

/// زر المتابعة / إلغاء المتابعة.
class FollowButton extends StatelessWidget {
  const FollowButton({super.key, required this.company, this.compact = false});

  final Company company;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final follows = Get.find<CompanyFollowService>();
    final size = compact ? const Size(0, 38) : const Size(0, 46);
    final padding = EdgeInsets.symmetric(horizontal: compact ? 14 : 22);

    return Obx(() {
      final pending = follows.pending.contains(company.id);
      final onPressed = pending ? null : () => follows.toggle(company);

      final Widget button = company.isFollowing
          ? OutlinedButton.icon(
              key: const ValueKey('following'),
              style: OutlinedButton.styleFrom(minimumSize: size, padding: padding),
              onPressed: onPressed,
              icon: const Icon(Icons.check, size: 18),
              label: const Text('أتابعها'),
            )
          : FilledButton.icon(
              key: const ValueKey('follow'),
              style: FilledButton.styleFrom(minimumSize: size, padding: padding, backgroundColor: AppColors.accent500),
              onPressed: onPressed,
              icon: const Icon(Icons.add, size: 18),
              label: const Text('متابعة'),
            );

      // تبديل الحالة بقفزة قصيرة بدل القطع المفاجئ
      return AnimatedSwitcher(
        duration: const Duration(milliseconds: 280),
        switchInCurve: Curves.easeOutBack,
        switchOutCurve: Curves.easeIn,
        transitionBuilder: (child, animation) => ScaleTransition(
          scale: Tween<double>(begin: 0.8, end: 1).animate(animation),
          child: FadeTransition(opacity: animation, child: child),
        ),
        child: button,
      );
    });
  }
}
