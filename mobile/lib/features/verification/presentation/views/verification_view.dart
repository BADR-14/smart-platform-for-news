import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../app/routes.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/state_views.dart';
import '../../domain/verification_status.dart';
import '../controllers/verification_controller.dart';

class VerificationView extends GetView<VerificationController> {
  const VerificationView({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Scaffold(
      appBar: AppBar(title: const Text('توثيق الحساب')),
      body: Obx(() {
        final status = controller.status.value;
        final error = controller.error.value;

        if (status == null) {
          return error != null ? ErrorView(error: error, onRetry: controller.load) : const LoadingView();
        }

        return RefreshIndicator(
          onRefresh: controller.load,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(14),
            children: [
              _Intro(status: status),
              const SizedBox(height: 14),
              if (status.isCompany)
                const _CompanyDone()
              else ...[
                _RequestCard(
                  icon: Icons.person_outline,
                  title: 'توثيق كمستخدم',
                  description: 'للأفراد: محاضرون، مدرّبون، منظّمو فعاليات. نحتاج رقمك الوطني وصورتَي الهوية.',
                  summary: status.user,
                  actionLabel: 'بدء توثيق المستخدم',
                  onAction: () => Get.toNamed(Routes.userVerification),
                ),
                const SizedBox(height: 12),
                _RequestCard(
                  icon: Icons.apartment_outlined,
                  title: 'إنشاء حساب شركة',
                  description: 'للشركات والمؤسسات. بعد الموافقة يتحول حسابك نفسه إلى حساب شركة '
                      'ويحصل على لوحة الشركة (المحفظة، الباقات، المنشورات المميزة).',
                  summary: status.company,
                  actionLabel: 'تقديم طلب شركة',
                  onAction: () => Get.toNamed(Routes.companyVerification),
                ),
              ],
              const SizedBox(height: 16),
              Text(
                'يراجع فريق المنصة وثائقك وتصلك النتيجة بإشعار فور صدورها. لكل نوع ثلاث محاولات.',
                textAlign: TextAlign.center,
                style: theme.bodySmall?.copyWith(color: AppColors.muted, height: 1.6),
              ),
            ],
          ),
        );
      }),
    );
  }
}

class _Intro extends StatelessWidget {
  const _Intro({required this.status});

  final VerificationStatus status;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;
    final verified = status.isVerified;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: verified ? AppColors.successBg : AppColors.accent50,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Icon(
            verified ? Icons.verified : Icons.verified_outlined,
            size: 34,
            color: verified ? AppColors.success : AppColors.accent600,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  verified ? 'حسابك موثّق' : 'حسابك غير موثّق بعد',
                  style: theme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 2),
                Text(
                  verified
                      ? 'يمكنك نشر المحاضرات والمعارض والفعاليات والدورات.'
                      : 'النشر متاح للحسابات الموثّقة فقط. اختر نوع التوثيق المناسب لك.',
                  style: theme.bodyMedium?.copyWith(color: AppColors.inkSoft, height: 1.5),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _CompanyDone extends StatelessWidget {
  const _CompanyDone();

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            const Icon(Icons.apartment_outlined, color: AppColors.brand600),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                'حسابك حساب شركة موثّق. لا حاجة لأي طلب توثيق آخر.',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(height: 1.5),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _RequestCard extends StatelessWidget {
  const _RequestCard({
    required this.icon,
    required this.title,
    required this.description,
    required this.summary,
    required this.actionLabel,
    required this.onAction,
  });

  final IconData icon;
  final String title;
  final String description;
  final VerificationSummary summary;
  final String actionLabel;
  final VoidCallback onAction;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;
    final status = summary.status;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(color: AppColors.brand50, borderRadius: BorderRadius.circular(12)),
                  child: Icon(icon, color: AppColors.brand800),
                ),
                const SizedBox(width: 12),
                Expanded(child: Text(title, style: theme.titleMedium?.copyWith(fontWeight: FontWeight.w700))),
                if (status != RequestStatus.notSubmitted) _StatusBadge(status: status),
              ],
            ),
            const SizedBox(height: 10),
            Text(description, style: theme.bodyMedium?.copyWith(color: AppColors.inkSoft, height: 1.6)),

            if (status == RequestStatus.pending) ...[
              const SizedBox(height: 12),
              _Note(
                color: AppColors.info,
                background: AppColors.infoBg,
                text: 'طلبك قيد المراجعة${summary.submittedAt == null ? '' : ' منذ ${Formatters.date(summary.submittedAt)}'}. '
                    'سيصلك إشعار بالنتيجة.',
              ),
            ],

            if (status == RequestStatus.rejected) ...[
              const SizedBox(height: 12),
              _Note(
                color: AppColors.danger,
                background: AppColors.dangerBg,
                text: summary.rejectionReasons.isEmpty
                    ? 'رُفض الطلب. تأكد من وضوح الصور وصحة البيانات ثم أعد المحاولة.'
                    : 'أسباب الرفض:\n${summary.rejectionReasons.map((reason) => '• $reason').join('\n')}',
              ),
            ],

            if (summary.canSubmit) ...[
              const SizedBox(height: 14),
              AppButton(
                label: status == RequestStatus.rejected ? 'إعادة المحاولة' : actionLabel,
                style: AppButtonStyle.accent,
                onPressed: onAction,
              ),
              const SizedBox(height: 6),
              Center(
                child: Text(
                  'المحاولات المتبقية: ${summary.attemptsLeft}',
                  style: theme.bodySmall?.copyWith(color: AppColors.muted),
                ),
              ),
            ] else if (status == RequestStatus.rejected && summary.attemptsLeft == 0) ...[
              const SizedBox(height: 10),
              Text(
                'استنفدت المحاولات المتاحة. تواصل مع إدارة المنصة.',
                style: theme.bodySmall?.copyWith(color: AppColors.danger),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status});

  final RequestStatus status;

  @override
  Widget build(BuildContext context) {
    final (Color color, Color background) = switch (status) {
      RequestStatus.approved => (AppColors.success, AppColors.successBg),
      RequestStatus.pending => (AppColors.info, AppColors.infoBg),
      RequestStatus.rejected => (AppColors.danger, AppColors.dangerBg),
      RequestStatus.notSubmitted => (AppColors.inkSoft, AppColors.canvas),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: background, borderRadius: BorderRadius.circular(20)),
      child: Text(status.label, style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.w700)),
    );
  }
}

class _Note extends StatelessWidget {
  const _Note({required this.color, required this.background, required this.text});

  final Color color;
  final Color background;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: background, borderRadius: BorderRadius.circular(12)),
      child: Text(text, style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: color, height: 1.6)),
    );
  }
}
