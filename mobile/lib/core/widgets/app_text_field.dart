import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../theme/app_colors.dart';

/// حقل الإدخال الموحّد: عنوان فوق الحقل + تحقق محلي + خطأ قادم من الخادم.
class AppTextField extends StatefulWidget {
  const AppTextField({
    super.key,
    required this.label,
    this.controller,
    this.hint,
    this.validator,
    this.serverError,
    this.keyboardType,
    this.textInputAction,
    this.obscure = false,
    this.maxLines = 1,
    this.maxLength,
    this.prefixIcon,
    this.ltr = false,
    this.enabled = true,
    this.inputFormatters,
    this.onChanged,
    this.onSubmitted,
    this.autofillHints,
  });

  final String label;
  final TextEditingController? controller;
  final String? hint;
  final String? Function(String?)? validator;

  /// خطأ هذا الحقل القادم من الباك-إند (رد 422).
  final String? serverError;
  final TextInputType? keyboardType;
  final TextInputAction? textInputAction;
  final bool obscure;
  final int maxLines;
  final int? maxLength;
  final IconData? prefixIcon;

  /// للبريد وكلمة المرور والأرقام: النص يُكتب من اليسار لليمين.
  final bool ltr;
  final bool enabled;
  final List<TextInputFormatter>? inputFormatters;
  final ValueChanged<String>? onChanged;
  final ValueChanged<String>? onSubmitted;
  final Iterable<String>? autofillHints;

  @override
  State<AppTextField> createState() => _AppTextFieldState();
}

class _AppTextFieldState extends State<AppTextField> {
  late bool _hidden = widget.obscure;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          widget.label,
          style: Theme.of(context).textTheme.labelLarge?.copyWith(color: AppColors.inkSoft, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 6),
        TextFormField(
          controller: widget.controller,
          validator: widget.validator,
          keyboardType: widget.keyboardType,
          textInputAction: widget.textInputAction,
          obscureText: _hidden,
          maxLines: widget.obscure ? 1 : widget.maxLines,
          maxLength: widget.maxLength,
          enabled: widget.enabled,
          inputFormatters: widget.inputFormatters,
          onChanged: widget.onChanged,
          onFieldSubmitted: widget.onSubmitted,
          autofillHints: widget.autofillHints,
          textDirection: widget.ltr ? TextDirection.ltr : null,
          textAlign: widget.ltr ? TextAlign.left : TextAlign.start,
          decoration: InputDecoration(
            hintText: widget.hint,
            hintTextDirection: widget.ltr ? TextDirection.ltr : null,
            errorText: widget.serverError,
            errorMaxLines: 2,
            counterText: '',
            prefixIcon: widget.prefixIcon == null ? null : Icon(widget.prefixIcon, size: 20, color: AppColors.muted),
            suffixIcon: widget.obscure
                ? IconButton(
                    tooltip: _hidden ? 'إظهار' : 'إخفاء',
                    icon: Icon(_hidden ? Icons.visibility_outlined : Icons.visibility_off_outlined, size: 20),
                    color: AppColors.muted,
                    onPressed: () => setState(() => _hidden = !_hidden),
                  )
                : null,
          ),
        ),
      ],
    );
  }
}
