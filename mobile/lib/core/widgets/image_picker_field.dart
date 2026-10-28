import 'dart:io';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';

import '../theme/app_colors.dart';
import '../utils/feedback.dart';
import '../utils/navigation.dart';
import 'dialogs.dart';

/// حقل اختيار صورة (وثيقة، شعار ...) من الكاميرا أو المعرض، مع معاينة.
class ImagePickerField extends StatelessWidget {
  const ImagePickerField({
    super.key,
    required this.label,
    required this.file,
    required this.onChanged,
    this.hint,
    this.error,
    this.icon = Icons.badge_outlined,
    this.maxWidth = 2000,
    this.maxBytes = 10 * 1024 * 1024,
  });

  final String label;
  final File? file;
  final ValueChanged<File?> onChanged;
  final String? hint;
  final String? error;
  final IconData icon;

  /// الصورة تُصغَّر لهذا العرض قبل الرفع.
  final double maxWidth;
  final int maxBytes;

  Future<void> _pick() async {
    final source = await Get.bottomSheet<ImageSource>(const _SourceSheet());
    if (source == null) return;

    try {
      final picked = await ImagePicker().pickImage(source: source, imageQuality: 85, maxWidth: maxWidth);
      if (picked == null) return;

      final selected = File(picked.path);
      if (selected.lengthSync() > maxBytes) {
        AppFeedback.error('حجم الصورة كبير. الحد الأقصى ${(maxBytes / (1024 * 1024)).round()} ميغابايت.');
        return;
      }

      onChanged(selected);
    } catch (_) {
      AppFeedback.error(source == ImageSource.camera ? 'تعذّر فتح الكاميرا.' : 'تعذّر فتح معرض الصور.');
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;
    final hasError = error != null;
    final picked = file;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: theme.labelLarge?.copyWith(color: AppColors.inkSoft, fontWeight: FontWeight.w600)),
        const SizedBox(height: 6),
        Material(
          color: AppColors.brand50,
          borderRadius: BorderRadius.circular(14),
          clipBehavior: Clip.antiAlias,
          child: InkWell(
            onTap: _pick,
            child: Container(
              height: 150,
              width: double.infinity,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: hasError ? AppColors.danger : AppColors.brand200),
              ),
              child: picked == null
                  ? Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(icon, size: 34, color: AppColors.brand600),
                        const SizedBox(height: 8),
                        Text('اضغط لاختيار صورة', style: theme.bodyMedium?.copyWith(color: AppColors.brand700, fontWeight: FontWeight.w600)),
                        if (hint != null)
                          Padding(
                            padding: const EdgeInsets.fromLTRB(16, 4, 16, 0),
                            child: Text(
                              hint!,
                              textAlign: TextAlign.center,
                              style: theme.bodySmall?.copyWith(color: AppColors.muted),
                            ),
                          ),
                      ],
                    )
                  : Stack(
                      fit: StackFit.expand,
                      children: [
                        ClipRRect(borderRadius: BorderRadius.circular(13), child: Image.file(picked, fit: BoxFit.cover)),
                        PositionedDirectional(
                          top: 6,
                          end: 6,
                          child: Material(
                            color: Colors.black54,
                            shape: const CircleBorder(),
                            child: InkWell(
                              customBorder: const CircleBorder(),
                              onTap: () => onChanged(null),
                              child: const Padding(
                                padding: EdgeInsets.all(6),
                                child: Icon(Icons.close, size: 18, color: Colors.white),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
            ),
          ),
        ),
        if (hasError)
          Padding(
            padding: const EdgeInsetsDirectional.only(top: 6, start: 4),
            child: Text(error!, style: theme.bodySmall?.copyWith(color: AppColors.danger)),
          ),
      ],
    );
  }
}

class _SourceSheet extends StatelessWidget {
  const _SourceSheet();

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const SheetHeader(title: 'اختيار صورة'),
          ListTile(
            leading: const Icon(Icons.photo_camera_outlined, color: AppColors.brand600),
            title: const Text('التقاط صورة بالكاميرا'),
            onTap: () => AppNav.back<ImageSource>(ImageSource.camera),
          ),
          ListTile(
            leading: const Icon(Icons.photo_library_outlined, color: AppColors.brand600),
            title: const Text('اختيار من المعرض'),
            onTap: () => AppNav.back<ImageSource>(ImageSource.gallery),
          ),
          const SizedBox(height: 10),
        ],
      ),
    );
  }
}
