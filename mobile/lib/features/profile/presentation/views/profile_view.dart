import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../app/routes.dart';
import '../../../../core/session/session_service.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/motion.dart';
import '../../../../core/widgets/state_views.dart';
import '../../domain/profile.dart';
import '../controllers/profile_controller.dart';

/// تبويب "حسابي". الزائر يرى دعوة لتسجيل الدخول.
class ProfileView extends GetView<ProfileController> {
  const ProfileView({super.key});

  @override
  Widget build(BuildContext context) {
    final session = Get.find<SessionService>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('حسابي'),
        actions: [
          Obx(
            () => session.account.value == null
                ? const SizedBox.shrink()
                : IconButton(
                    tooltip: 'الإعدادات',
                    icon: const Icon(Icons.settings_outlined),
                    onPressed: () => Get.toNamed(Routes.settings),
                  ),
          ),
        ],
      ),
      body: Obx(() {
        if (session.account.value == null) return const _GuestView();

        final profile = controller.profile.value;
        final error = controller.error.value;

        if (profile == null) {
          return error != null ? ErrorView(error: error, onRetry: controller.load) : const LoadingView();
        }

        return RefreshIndicator(
          onRefresh: controller.load,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(14),
            children: [
              _ProfileHeader(profile: profile, controller: controller),
              const SizedBox(height: 12),
              if (!profile.isVerified) ...[
                _VerificationBanner(status: profile.verificationStatus),
                const SizedBox(height: 12),
              ],
              Card(
                child: Column(
                  children: [
                    if (profile.isVerified || profile.postsCount > 0) ...[
                      _MenuTile(icon: Icons.article_outlined, label: 'منشوراتي', onTap: () => Get.toNamed(Routes.myPosts)),
                      const Divider(indent: 56),
                    ],
                    _MenuTile(icon: Icons.favorite_border_rounded, label: 'المفضلة', onTap: () => Get.toNamed(Routes.bookmarks)),
                    if (profile.isVerified || profile.postsCount > 0) ...[
                      const Divider(indent: 56),
                      _MenuTile(icon: Icons.archive_outlined, label: 'الأرشيف', onTap: () => Get.toNamed(Routes.archive)),
                    ],
                    const Divider(indent: 56),
                    _MenuTile(icon: Icons.star_outline_rounded, label: 'تقييماتي', onTap: () => Get.toNamed(Routes.myReviews)),
                    const Divider(indent: 56),
                    _MenuTile(
                      icon: Icons.verified_outlined,
                      label: 'توثيق الحساب',
                      trailing: profile.isVerified ? 'موثّق' : null,
                      onTap: () => Get.toNamed(Routes.verification),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Card(
                child: Column(
                  children: [
                    // بيانات الشركة (الاسم، الشعار، الوصف) تُدار من لوحة الشركة على الويب
                    if (!profile.isCompany) ...[
                      _MenuTile(
                        icon: Icons.person_outline,
                        label: 'تعديل الملف الشخصي',
                        onTap: () => Get.toNamed(Routes.editProfile),
                      ),
                      const Divider(indent: 56),
                    ] else if (profile.companyId != null) ...[
                      _MenuTile(
                        icon: Icons.apartment_outlined,
                        label: 'صفحة الشركة',
                        onTap: () => Get.toNamed(Routes.companyProfile, arguments: profile.companyId),
                      ),
                      const Divider(indent: 56),
                    ],
                    _MenuTile(icon: Icons.settings_outlined, label: 'الإعدادات', onTap: () => Get.toNamed(Routes.settings)),
                    const Divider(indent: 56),
                    _MenuTile(icon: Icons.logout, label: 'تسجيل الخروج', color: AppColors.danger, showChevron: false, onTap: controller.logout),
                  ],
                ),
              ),
              if (profile.isCompany)
                Padding(
                  padding: const EdgeInsets.fromLTRB(8, 14, 8, 0),
                  child: Text(
                    'إدارة المحفظة والباقات والمنشورات المميزة وبيانات الشركة تتم من لوحة الشركة على الويب.',
                    textAlign: TextAlign.center,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.muted, height: 1.6),
                  ),
                ),
            ],
          ),
        );
      }),
    );
  }
}

class _GuestView extends StatelessWidget {
  const _GuestView();

  @override
  Widget build(BuildContext context) {
    return EmptyView(
      icon: Icons.person_outline,
      title: 'أنت تتصفح كزائر',
      message: 'سجّل الدخول لتتفاعل مع المنشورات، تتابع الشركات، وتنشر فعالياتك بعد توثيق حسابك.',
      action: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          AppButton(label: 'تسجيل الدخول', icon: Icons.login, expanded: false, onPressed: () => Get.toNamed(Routes.login)),
          const SizedBox(height: 4),
          TextButton(onPressed: () => Get.toNamed(Routes.register), child: const Text('إنشاء حساب جديد')),
        ],
      ),
    );
  }
}

class _ProfileHeader extends StatelessWidget {
  const _ProfileHeader({required this.profile, required this.controller});

  final Profile profile;
  final ProfileController controller;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          children: [
            Stack(
              clipBehavior: Clip.none,
              children: [
                AppAvatar(name: profile.name, url: profile.avatar, radius: 44),
                // شعار الشركة يُغيَّر من لوحة الشركة
                if (!profile.isCompany)
                  PositionedDirectional(
                    bottom: -4,
                    end: -4,
                    child: Obx(
                      () => Material(
                        color: AppColors.accent500,
                        shape: const CircleBorder(side: BorderSide(color: Colors.white, width: 2)),
                        child: InkWell(
                          customBorder: const CircleBorder(),
                          onTap: controller.isUploadingAvatar.value ? null : controller.changeAvatar,
                          child: Padding(
                            padding: const EdgeInsets.all(7),
                            child: controller.isUploadingAvatar.value
                                ? const SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                  )
                                : const Icon(Icons.camera_alt_outlined, size: 16, color: Colors.white),
                          ),
                        ),
                      ),
                    ),
                  ),
              ],
            ),
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
            const SizedBox(height: 2),
            Text(profile.email, textDirection: TextDirection.ltr, style: theme.bodyMedium?.copyWith(color: AppColors.muted)),
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
              decoration: BoxDecoration(color: AppColors.brand50, borderRadius: BorderRadius.circular(20)),
              child: Text(
                profile.isCompany ? 'حساب شركة' : (profile.isVerified ? 'مستخدم موثّق' : 'مستخدم'),
                style: theme.labelMedium?.copyWith(color: AppColors.brand800, fontWeight: FontWeight.w600),
              ),
            ),
            if (profile.shortBio != null) ...[
              const SizedBox(height: 12),
              Text(
                profile.shortBio!,
                textAlign: TextAlign.center,
                style: theme.bodyMedium?.copyWith(color: AppColors.inkSoft, height: 1.6),
              ),
            ],
            const SizedBox(height: 16),
            const Divider(),
            const SizedBox(height: 12),
            Row(
              children: [
                _Stat(value: profile.postsCount, label: 'منشور'),
                _Stat(value: profile.followingCount, label: 'أتابع'),
                _Stat(value: profile.reviewsCount, label: 'تقييم'),
                _Stat(value: profile.bookmarksCount, label: 'مفضلة'),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.value, required this.label});

  final int value;
  final String label;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Expanded(
      child: Column(
        children: [
          CountUpText(value: value, format: Formatters.count, style: theme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
          Text(label, style: theme.bodySmall?.copyWith(color: AppColors.muted)),
        ],
      ),
    );
  }
}

/// دعوة لتوثيق الحساب: النشر متاح للحسابات الموثّقة فقط.
class _VerificationBanner extends StatelessWidget {
  const _VerificationBanner({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    final (String title, String message, Color color, Color background, IconData icon) = switch (status) {
      'pending' => (
          'طلب التوثيق قيد المراجعة',
          'سنُعلمك بالنتيجة فور انتهاء المراجعة.',
          AppColors.info,
          AppColors.infoBg,
          Icons.hourglass_top,
        ),
      'rejected' => (
          'رُفض طلب التوثيق',
          'اطّلع على أسباب الرفض وأعد المحاولة.',
          AppColors.danger,
          AppColors.dangerBg,
          Icons.error_outline,
        ),
      _ => (
          'وثّق حسابك لتتمكن من النشر',
          'النشر متاح للحسابات الموثّقة فقط. التوثيق يستغرق دقائق.',
          AppColors.accent600,
          AppColors.accent50,
          Icons.verified_outlined,
        ),
    };

    return Material(
      color: background,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => Get.toNamed(Routes.verification),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              Icon(icon, color: color),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: theme.titleSmall?.copyWith(color: color, fontWeight: FontWeight.w700)),
                    const SizedBox(height: 2),
                    Text(message, style: theme.bodySmall?.copyWith(color: AppColors.inkSoft)),
                  ],
                ),
              ),
              Icon(Icons.chevron_left, color: color),
            ],
          ),
        ),
      ),
    );
  }
}

class _MenuTile extends StatelessWidget {
  const _MenuTile({required this.icon, required this.label, required this.onTap, this.color, this.trailing, this.showChevron = true});

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color? color;
  final String? trailing;

  /// الإجراءات التي لا تفتح صفحة (كتسجيل الخروج) لا تحمل سهماً.
  final bool showChevron;

  @override
  Widget build(BuildContext context) {
    final foreground = color ?? AppColors.ink;

    return ListTile(
      leading: Icon(icon, color: color ?? AppColors.brand600),
      title: Text(label, style: TextStyle(color: foreground, fontWeight: FontWeight.w600)),
      trailing: !showChevron && trailing == null
          ? null
          : Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (trailing != null)
                  Text(trailing!, style: const TextStyle(color: AppColors.success, fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(width: 4),
                if (showChevron) const Icon(Icons.chevron_left, color: AppColors.muted),
              ],
            ),
      onTap: onTap,
    );
  }
}
