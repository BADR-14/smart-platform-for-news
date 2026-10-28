import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../app/routes.dart';
import '../../../../core/config/app_config.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/navigation.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../../../../core/widgets/dialogs.dart';
import '../controllers/settings_controller.dart';

class SettingsView extends GetView<SettingsController> {
  const SettingsView({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Scaffold(
      appBar: AppBar(title: const Text('الإعدادات')),
      body: ListView(
        padding: const EdgeInsets.all(14),
        children: [
          const _SectionLabel('الحساب'),
          Card(
            child: Column(
              children: [
                ListTile(
                  leading: const Icon(Icons.lock_outline, color: AppColors.brand600),
                  title: const Text('تغيير كلمة المرور'),
                  trailing: const Icon(Icons.chevron_left, color: AppColors.muted),
                  onTap: () => Get.toNamed(Routes.changePassword),
                ),
                const Divider(indent: 56),
                ListTile(
                  leading: const Icon(Icons.verified_outlined, color: AppColors.brand600),
                  title: const Text('توثيق الحساب'),
                  trailing: const Icon(Icons.chevron_left, color: AppColors.muted),
                  onTap: () => Get.toNamed(Routes.verification),
                ),
              ],
            ),
          ),

          const _SectionLabel('الإشعارات'),
          Card(
            child: Obx(() {
              final preferences = controller.preferences.value;

              if (preferences == null) {
                return const Padding(
                  padding: EdgeInsets.all(20),
                  child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4))),
                );
              }

              return Column(
                children: [
                  SwitchListTile(
                    secondary: const Icon(Icons.mail_outline, color: AppColors.brand600),
                    title: const Text('إشعارات البريد الإلكتروني'),
                    subtitle: const Text('نسخة من الإشعارات المهمة إلى بريدك'),
                    value: preferences.emailNotifications,
                    onChanged: controller.setEmailNotifications,
                  ),
                  const Divider(indent: 56),
                  SwitchListTile(
                    secondary: const Icon(Icons.security_outlined, color: AppColors.brand600),
                    title: const Text('تنبيهات تسجيل الدخول'),
                    subtitle: const Text('تنبيه عند كل دخول جديد إلى حسابك'),
                    value: preferences.loginNotifications,
                    onChanged: controller.setLoginNotifications,
                  ),
                ],
              );
            }),
          ),

          const _SectionLabel('منطقة الخطر'),
          Card(
            child: ListTile(
              leading: const Icon(Icons.delete_forever_outlined, color: AppColors.danger),
              title: const Text('حذف الحساب', style: TextStyle(color: AppColors.danger, fontWeight: FontWeight.w600)),
              subtitle: const Text('يُحذف حسابك ومنشوراتك نهائياً'),
              onTap: () => Get.bottomSheet<void>(_DeleteAccountSheet(controller: controller), isScrollControlled: true),
            ),
          ),

          const SizedBox(height: 24),
          Center(
            child: Text('${AppConfig.appName} · الإصدار 1.0.0', style: theme.bodySmall?.copyWith(color: AppColors.muted)),
          ),
        ],
      ),
    );
  }
}

class _SectionLabel extends StatelessWidget {
  const _SectionLabel(this.text);

  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsetsDirectional.only(start: 6, top: 16, bottom: 8),
      child: Text(
        text,
        style: Theme.of(context).textTheme.labelLarge?.copyWith(color: AppColors.muted, fontWeight: FontWeight.w700),
      ),
    );
  }
}

class _DeleteAccountSheet extends StatefulWidget {
  const _DeleteAccountSheet({required this.controller});

  final SettingsController controller;

  @override
  State<_DeleteAccountSheet> createState() => _DeleteAccountSheetState();
}

class _DeleteAccountSheetState extends State<_DeleteAccountSheet> {
  final _password = TextEditingController();

  @override
  void dispose() {
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();

    // نغلق النافذة أولاً: بعد الحذف يُستبدل كل ما في المكدّس بالصفحة الرئيسية
    final password = _password.text;
    AppNav.back<void>();
    await widget.controller.deleteAccount(password);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return SafeArea(
      child: Padding(
        padding: EdgeInsets.fromLTRB(18, 0, 18, 18 + MediaQuery.of(context).viewInsets.bottom),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const SheetHeader(title: 'حذف الحساب نهائياً'),
            Text(
              'سيُحذف حسابك مع منشوراتك وتعليقاتك وتقييماتك، ولا يمكن التراجع عن هذا الإجراء.',
              textAlign: TextAlign.center,
              style: theme.bodyMedium?.copyWith(color: AppColors.inkSoft, height: 1.6),
            ),
            const SizedBox(height: 16),
            AppTextField(
              label: 'كلمة المرور للتأكيد',
              controller: _password,
              hint: 'اتركها فارغة إن كان حسابك عبر Google',
              ltr: true,
              obscure: true,
              prefixIcon: Icons.lock_outline,
            ),
            const SizedBox(height: 16),
            AppButton(label: 'حذف حسابي', icon: Icons.delete_forever_outlined, style: AppButtonStyle.danger, onPressed: _submit),
            const SizedBox(height: 8),
            AppButton(label: 'تراجع', style: AppButtonStyle.outline, onPressed: () => AppNav.back<void>()),
          ],
        ),
      ),
    );
  }
}
