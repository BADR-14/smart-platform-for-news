import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

enum AppButtonStyle { primary, accent, outline, danger }

/// الزر الموحّد: يعرض مؤشر تحميل ويتعطل أثناء تنفيذ الطلب.
class AppButton extends StatelessWidget {
  const AppButton({
    super.key,
    required this.label,
    required this.onPressed,
    this.loading = false,
    this.icon,
    this.style = AppButtonStyle.primary,
    this.expanded = true,
  });

  final String label;
  final VoidCallback? onPressed;
  final bool loading;
  final IconData? icon;
  final AppButtonStyle style;
  final bool expanded;

  @override
  Widget build(BuildContext context) {
    final onTap = loading ? null : onPressed;

    final child = loading
        ? const SizedBox(
            width: 22,
            height: 22,
            child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white),
          )
        : Row(
            mainAxisSize: MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (icon != null) ...[Icon(icon, size: 20), const SizedBox(width: 8)],
              Flexible(child: Text(label, overflow: TextOverflow.ellipsis)),
            ],
          );

    final Widget button = switch (style) {
      AppButtonStyle.outline => OutlinedButton(onPressed: onTap, child: child),
      AppButtonStyle.accent => FilledButton(
          onPressed: onTap,
          style: FilledButton.styleFrom(backgroundColor: AppColors.accent500),
          child: child,
        ),
      AppButtonStyle.danger => FilledButton(
          onPressed: onTap,
          style: FilledButton.styleFrom(backgroundColor: AppColors.danger),
          child: child,
        ),
      AppButtonStyle.primary => FilledButton(onPressed: onTap, child: child),
    };

    return expanded ? SizedBox(width: double.infinity, child: button) : button;
  }
}
