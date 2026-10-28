import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/feedback.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/argument_state.dart';
import '../../../../core/widgets/paged_list_view.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../posts/domain/post.dart';
import '../../../posts/presentation/widgets/post_card.dart';
import '../../../posts/presentation/widgets/post_sheets.dart';
import '../../domain/company.dart';
import '../controllers/company_profile_controller.dart';
import '../widgets/company_card.dart';

class CompanyProfileView extends StatefulWidget {
  const CompanyProfileView({super.key});

  @override
  State<CompanyProfileView> createState() => _CompanyProfileViewState();
}

class _CompanyProfileViewState extends ArgumentState<CompanyProfileView, CompanyProfileController> {
  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final company = controller.company.value;
      final error = controller.companyError.value;

      if (company == null) {
        return Scaffold(
          appBar: AppBar(),
          body: error != null ? ErrorView(error: error, onRetry: controller.loadCompany) : const LoadingView(),
        );
      }

      return Scaffold(
        appBar: AppBar(
          title: Text(company.name),
          actions: [
            if (!company.isOwner)
              PopupMenuButton<String>(
                tooltip: 'خيارات',
                onSelected: (_) => PostSheets.reportAccount(accountId: company.accountId, name: company.name),
                itemBuilder: (context) => const [
                  PopupMenuItem(value: 'report', child: Text('إبلاغ عن الشركة')),
                ],
              ),
          ],
        ),
        body: PagedListView<Post>(
          controller: controller,
          header: _CompanyHeader(company: company),
          emptyIcon: Icons.article_outlined,
          emptyTitle: 'لا توجد منشورات بعد',
          emptyMessage: 'لم تنشر هذه الشركة أي منشور حتى الآن.',
          itemBuilder: (context, post, index) => PostCard(key: ValueKey(post.id), post: post),
        ),
      );
    });
  }
}

class _CompanyHeader extends StatelessWidget {
  const _CompanyHeader({required this.company});

  final Company company;

  Future<void> _open(Uri uri) async {
    final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!opened) AppFeedback.error('تعذّر فتح الرابط.');
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                AppAvatar(name: company.name, url: company.logo, radius: 34),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Flexible(
                            child: Text(company.name, style: theme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
                          ),
                          if (company.isVerified) ...[const SizedBox(width: 6), const VerifiedBadge(size: 18)],
                        ],
                      ),
                      if (company.category != null)
                        Text(
                          company.category!.name,
                          style: theme.bodySmall?.copyWith(color: AppColors.accent700, fontWeight: FontWeight.w600),
                        ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                _Stat(value: Formatters.count(company.followersCount), label: 'متابع'),
                const SizedBox(width: 22),
                _Stat(value: Formatters.count(company.postsCount), label: 'منشور'),
                const Spacer(),
                if (!company.isOwner) FollowButton(company: company),
              ],
            ),
            if (company.description != null) ...[
              const SizedBox(height: 14),
              Text(company.description!, style: theme.bodyMedium?.copyWith(color: AppColors.inkSoft, height: 1.7)),
            ],
            if (company.address != null || company.websiteUrl != null || company.phone != null) ...[
              const SizedBox(height: 12),
              const Divider(),
              const SizedBox(height: 6),
              if (company.address != null) _ContactRow(icon: Icons.place_outlined, text: company.address!),
              if (company.websiteUrl != null)
                _ContactRow(
                  icon: Icons.language,
                  text: company.websiteUrl!,
                  ltr: true,
                  onTap: () {
                    final uri = Uri.tryParse(company.websiteUrl!);
                    if (uri != null) _open(uri);
                  },
                ),
              if (company.phone != null)
                _ContactRow(
                  icon: Icons.phone_outlined,
                  text: company.phone!,
                  ltr: true,
                  onTap: () => _open(Uri(scheme: 'tel', path: company.phone)),
                ),
            ],
          ],
        ),
      ),
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.value, required this.label});

  final String value;
  final String label;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(value, style: theme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
        Text(label, style: theme.bodySmall?.copyWith(color: AppColors.muted)),
      ],
    );
  }
}

class _ContactRow extends StatelessWidget {
  const _ContactRow({required this.icon, required this.text, this.onTap, this.ltr = false});

  final IconData icon;
  final String text;
  final VoidCallback? onTap;
  final bool ltr;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 6),
        child: Row(
          children: [
            Icon(icon, size: 18, color: AppColors.brand600),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                text,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                textDirection: ltr ? TextDirection.ltr : null,
                // النص اللاتيني يبقى ملاصقاً للأيقونة في الواجهة العربية
                textAlign: ltr ? TextAlign.right : TextAlign.start,
                style: theme.bodyMedium?.copyWith(
                  color: onTap == null ? AppColors.inkSoft : AppColors.brand600,
                  fontWeight: onTap == null ? FontWeight.w400 : FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
