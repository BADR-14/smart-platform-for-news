import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../app/routes.dart';
import '../../../../core/session/session_service.dart';
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
import '../../domain/profile.dart';
import '../controllers/account_profile_controller.dart';

/// الملف العام لمستخدم آخر (يُفتح من اسم صاحب منشور أو تعليق).
class AccountProfileView extends StatefulWidget {
  const AccountProfileView({super.key});

  @override
  State<AccountProfileView> createState() => _AccountProfileViewState();
}

class _AccountProfileViewState extends ArgumentState<AccountProfileView, AccountProfileController> {
  @override
  Widget build(BuildContext context) {
    final myAccountId = Get.find<SessionService>().account.value?.id;

    return Obx(() {
      final profile = controller.profile.value;
      final error = controller.profileError.value;

      if (profile == null) {
        return Scaffold(
          appBar: AppBar(),
          body: error != null ? ErrorView(error: error, onRetry: controller.loadProfile) : const LoadingView(),
        );
      }

      return Scaffold(
        appBar: AppBar(
          title: Text(profile.name),
          actions: [
            if (profile.id != myAccountId)
              PopupMenuButton<String>(
                tooltip: 'خيارات',
                onSelected: (_) => PostSheets.reportAccount(accountId: profile.id, name: profile.name),
                itemBuilder: (context) => const [
                  PopupMenuItem(value: 'report', child: Text('إبلاغ عن الحساب')),
                ],
              ),
          ],
        ),
        body: PagedListView<Post>(
          controller: controller,
          header: _Header(profile: profile),
          emptyIcon: Icons.article_outlined,
          emptyTitle: 'لا توجد منشورات',
          emptyMessage: 'لم ينشر هذا الحساب أي منشور حتى الآن.',
          itemBuilder: (context, post, index) => PostCard(key: ValueKey(post.id), post: post),
        ),
      );
    });
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.profile});

  final PublicProfile profile;

  Future<void> _openLinkedin() async {
    final uri = Uri.tryParse(profile.linkedinUrl ?? '');
    if (uri == null) return;

    final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!opened) AppFeedback.error('تعذّر فتح الرابط.');
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          children: [
            AppAvatar(name: profile.name, url: profile.avatar, radius: 40),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Flexible(
                  child: Text(
                    profile.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: theme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
                  ),
                ),
                if (profile.isVerified) ...[const SizedBox(width: 6), const VerifiedBadge(size: 20)],
              ],
            ),
            const SizedBox(height: 4),
            Text(
              '${Formatters.count(profile.postsCount)} منشور'
              '${profile.createdAt == null ? '' : ' · انضم ${Formatters.date(profile.createdAt)}'}',
              style: theme.bodySmall?.copyWith(color: AppColors.muted),
            ),
            if (profile.shortBio != null) ...[
              const SizedBox(height: 12),
              Text(
                profile.shortBio!,
                textAlign: TextAlign.center,
                style: theme.bodyMedium?.copyWith(color: AppColors.inkSoft, height: 1.6),
              ),
            ],
            if (profile.linkedinUrl != null || profile.companyId != null) ...[
              const SizedBox(height: 14),
              Wrap(
                spacing: 10,
                runSpacing: 8,
                alignment: WrapAlignment.center,
                children: [
                  if (profile.linkedinUrl != null)
                    OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(minimumSize: const Size(0, 42)),
                      onPressed: _openLinkedin,
                      icon: const Icon(Icons.link, size: 18),
                      label: const Text('LinkedIn'),
                    ),
                  if (profile.companyId != null)
                    OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(minimumSize: const Size(0, 42)),
                      onPressed: () => Get.toNamed(Routes.companyProfile, arguments: profile.companyId),
                      icon: const Icon(Icons.apartment_outlined, size: 18),
                      label: const Text('صفحة الشركة'),
                    ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}
