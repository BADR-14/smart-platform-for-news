import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../theme/app_colors.dart';
import '../utils/navigation.dart';

abstract final class AppDialogs {
  /// تأكيد قبل إجراء لا يمكن التراجع عنه. يرجع true إذا وافق المستخدم.
  static Future<bool> confirm({
    required String title,
    required String message,
    String confirmLabel = 'تأكيد',
    String cancelLabel = 'إلغاء',
    bool destructive = false,
  }) async {
    final result = await Get.dialog<bool>(
      AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => AppNav.back<bool>(false),
            style: TextButton.styleFrom(foregroundColor: AppColors.muted),
            child: Text(cancelLabel),
          ),
          TextButton(
            onPressed: () => AppNav.back<bool>(true),
            style: TextButton.styleFrom(foregroundColor: destructive ? AppColors.danger : AppColors.brand800),
            child: Text(confirmLabel),
          ),
        ],
      ),
    );

    return result ?? false;
  }
}

/// رأس موحّد للـ Bottom Sheet: مقبض + عنوان.
class SheetHeader extends StatelessWidget {
  const SheetHeader({super.key, required this.title, this.subtitle});

  final String title;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Column(
      children: [
        const SizedBox(height: 10),
        Container(
          width: 40,
          height: 4,
          decoration: BoxDecoration(color: AppColors.lineStrong, borderRadius: BorderRadius.circular(4)),
        ),
        const SizedBox(height: 14),
        Text(title, style: theme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
        if (subtitle != null) ...[
          const SizedBox(height: 4),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Text(
              subtitle!,
              style: theme.bodySmall?.copyWith(color: AppColors.muted),
              textAlign: TextAlign.center,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
        const SizedBox(height: 12),
      ],
    );
  }
}
