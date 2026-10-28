import 'dart:io';

import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../../domain/post.dart';
import '../controllers/post_form_controller.dart';
import 'location_picker_view.dart';

class PostFormView extends StatefulWidget {
  const PostFormView({super.key});

  @override
  State<PostFormView> createState() => _PostFormViewState();
}

class _PostFormViewState extends State<PostFormView> {
  final _formKey = GlobalKey<FormState>();

  final PostFormController _controller = Get.find<PostFormController>();

  void _save([String? action]) {
    FocusScope.of(context).unfocus();

    // نشغّل التحققين معاً حتى تظهر كل الأخطاء دفعة واحدة
    final fieldsValid = _formKey.currentState!.validate();
    final selectionsValid = _controller.validateSelections();
    if (fieldsValid && selectionsValid) _controller.save(action: action);
  }

  Future<void> _pickDate({required bool isStart}) async {
    final now = DateTime.now();
    final start = _controller.startDate.value;
    final current = isStart ? start : _controller.endDate.value;
    final first = isStart ? DateTime(now.year - 1) : (start ?? DateTime(now.year - 1));

    final picked = await showDatePicker(
      context: context,
      initialDate: current != null && !current.isBefore(first) ? current : (first.isAfter(now) ? first : now),
      firstDate: first,
      lastDate: DateTime(now.year + 5, 12, 31),
    );
    if (picked == null) return;

    if (isStart) {
      _controller.setStartDate(picked);
    } else {
      _controller.endDate.value = picked;
    }
  }

  Future<void> _pickLocation() async {
    final place = await LocationPickerView.open(initial: _controller.currentPlace);
    if (place != null) _controller.setLocation(place);
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;

    return Scaffold(
      backgroundColor: AppColors.surface,
      appBar: AppBar(title: Text(controller.isEditing ? 'تعديل المنشور' : 'منشور جديد')),
      body: Form(
        key: _formKey,
        // Column وليس ListView: كل الحقول تبقى مبنية حتى يشملها التحقق عند الإرسال
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
            if (controller.isModerated)
              Container(
                margin: const EdgeInsets.only(bottom: 16),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(color: AppColors.warningBg, borderRadius: BorderRadius.circular(12)),
                child: Text(
                  'هذا المنشور ${controller.editing!.status.label} من قبل الإدارة. يمكنك تعديل محتواه، '
                  'وتعيد الإدارة نشره بعد المراجعة.',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppColors.warning, height: 1.5),
                ),
              ),

            // ---- النوع ----
            const _Label('نوع المنشور'),
            Obx(
              () => Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  for (final type in PostType.values)
                    ChoiceChip(
                      label: Text(type.label),
                      selected: controller.postType.value == type,
                      onSelected: (_) => controller.postType.value = type,
                    ),
                ],
              ),
            ),
            const SizedBox(height: 18),

            // ---- العنوان والوصف ----
            Obx(
              () => AppTextField(
                label: 'العنوان',
                controller: controller.title,
                hint: 'مثال: محاضرة في أساسيات الذكاء الاصطناعي',
                maxLength: 255,
                textInputAction: TextInputAction.next,
                validator: (value) => Validators.required(value, 'عنوان المنشور مطلوب'),
                serverError: controller.fieldErrors['title'],
              ),
            ),
            const SizedBox(height: 16),
            Obx(
              () => AppTextField(
                label: 'الوصف',
                controller: controller.description,
                hint: 'اكتب تفاصيل المحاضرة أو الفعالية: المحاور، الفئة المستهدفة، طريقة التسجيل…',
                maxLines: 6,
                maxLength: 10000,
                serverError: controller.fieldErrors['description'],
              ),
            ),
            const SizedBox(height: 18),

            // ---- التصنيف ----
            const _Label('التصنيف'),
            Obx(() {
              if (controller.categories.isEmpty) {
                return const Padding(
                  padding: EdgeInsets.symmetric(vertical: 8),
                  child: LinearProgressIndicator(minHeight: 2),
                );
              }

              return Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  for (final category in controller.categories)
                    ChoiceChip(
                      label: Text(category.name),
                      selected: controller.categoryId.value == category.id,
                      onSelected: (_) {
                        controller.categoryId.value = category.id;
                        controller.fieldErrors.remove('category_id');
                      },
                    ),
                ],
              );
            }),
            _FieldError(controller: controller, field: 'category_id'),
            const SizedBox(height: 18),

            // ---- المكان ----
            Obx(
              () => SwitchListTile.adaptive(
                contentPadding: EdgeInsets.zero,
                title: const Text('تُعقد أونلاين'),
                subtitle: const Text('عطّل الخيار إذا كانت الفعالية حضورية لتحدد مكانها'),
                value: controller.isOnline.value,
                onChanged: (value) {
                  controller.isOnline.value = value;
                  controller.fieldErrors.remove('location');
                },
              ),
            ),
            Obx(() {
              if (controller.isOnline.value) return const SizedBox.shrink();

              final hasPoint = controller.latitude.value != null;

              return Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const SizedBox(height: 8),
                  AppTextField(
                    label: 'عنوان المكان',
                    controller: controller.location,
                    hint: 'المدينة، الشارع، اسم القاعة',
                    maxLength: 255,
                    prefixIcon: Icons.place_outlined,
                    serverError: controller.fieldErrors['location'] ??
                        controller.fieldErrors['latitude'] ??
                        controller.fieldErrors['longitude'],
                  ),
                  const SizedBox(height: 10),
                  OutlinedButton.icon(
                    onPressed: _pickLocation,
                    icon: Icon(hasPoint ? Icons.check_circle : Icons.map_outlined, size: 20, color: hasPoint ? AppColors.success : null),
                    label: Text(hasPoint ? 'تم تحديد الموقع — اضغط للتغيير' : 'حدّد الموقع على الخريطة'),
                  ),
                ],
              );
            }),
            const SizedBox(height: 18),

            // ---- التواريخ ----
            Obx(
              () => Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: _DateField(
                      label: 'تاريخ البداية',
                      value: controller.startDate.value,
                      error: controller.fieldErrors['start_date'],
                      onTap: () => _pickDate(isStart: true),
                      onClear: () => controller.startDate.value = null,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _DateField(
                      label: 'تاريخ النهاية',
                      value: controller.endDate.value,
                      error: controller.fieldErrors['end_date'],
                      onTap: () => _pickDate(isStart: false),
                      onClear: () => controller.endDate.value = null,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),

            // ---- الوسوم ----
            const _Label('الوسوم (حتى ${PostFormController.maxTags})'),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: controller.tagInput,
                    maxLength: 50,
                    textInputAction: TextInputAction.done,
                    onSubmitted: (_) => controller.addTag(),
                    decoration: const InputDecoration(hintText: 'اكتب وسماً ثم اضغط إضافة', counterText: '', isDense: true),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton.filledTonal(tooltip: 'إضافة وسم', onPressed: controller.addTag, icon: const Icon(Icons.add)),
              ],
            ),
            Obx(() {
              if (controller.tags.isEmpty) return const SizedBox.shrink();

              return Padding(
                padding: const EdgeInsets.only(top: 10),
                child: Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    for (final tag in controller.tags)
                      InputChip(label: Text('#$tag'), onDeleted: () => controller.removeTag(tag)),
                  ],
                ),
              );
            }),
            _FieldError(controller: controller, field: 'tags'),
            const SizedBox(height: 18),

            // ---- الوسائط ----
            const _Label('الصور والفيديو (حتى ${PostFormController.maxMedia})'),
            _MediaGrid(controller: controller),
            _FieldError(controller: controller, field: 'media'),
            const SizedBox(height: 26),

            // ---- الحفظ ----
            Obx(() {
              final saving = controller.isSaving.value;
              final status = controller.editing?.status;

              if (!controller.isEditing) {
                return Column(
                  children: [
                    AppButton(label: 'نشر الآن', icon: Icons.send, style: AppButtonStyle.accent, loading: saving, onPressed: () => _save('publish')),
                    const SizedBox(height: 10),
                    AppButton(
                      label: 'حفظ كمسودة',
                      style: AppButtonStyle.outline,
                      onPressed: saving ? null : () => _save('draft'),
                    ),
                  ],
                );
              }

              return Column(
                children: [
                  AppButton(label: 'حفظ التعديلات', icon: Icons.check, style: AppButtonStyle.accent, loading: saving, onPressed: _save),
                  if (status == PostStatus.draft || status == PostStatus.archived) ...[
                    const SizedBox(height: 10),
                    AppButton(
                      label: 'حفظ ونشر',
                      icon: Icons.send,
                      style: AppButtonStyle.outline,
                      onPressed: saving ? null : () => _save('publish'),
                    ),
                  ],
                ],
              );
            }),
            ],
          ),
        ),
      ),
    );
  }
}

class _Label extends StatelessWidget {
  const _Label(this.text);

  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(
        text,
        style: Theme.of(context).textTheme.labelLarge?.copyWith(color: AppColors.inkSoft, fontWeight: FontWeight.w600),
      ),
    );
  }
}

/// خطأ حقل ليس حقل نص (تصنيف، وسوم، وسائط).
class _FieldError extends StatelessWidget {
  const _FieldError({required this.controller, required this.field});

  final PostFormController controller;
  final String field;

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      // أخطاء عناصر المصفوفة تأتي بمفاتيح مثل media.0
      String? error = controller.fieldErrors[field];
      if (error == null) {
        for (final entry in controller.fieldErrors.entries) {
          if (entry.key.startsWith('$field.')) {
            error = entry.value;
            break;
          }
        }
      }

      if (error == null) return const SizedBox.shrink();

      return Padding(
        padding: const EdgeInsets.only(top: 6),
        child: Text(error, style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.danger)),
      );
    });
  }
}

class _DateField extends StatelessWidget {
  const _DateField({required this.label, required this.value, required this.onTap, required this.onClear, this.error});

  final String label;
  final DateTime? value;
  final String? error;
  final VoidCallback onTap;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _Label(label),
        InkWell(
          borderRadius: BorderRadius.circular(14),
          onTap: onTap,
          child: InputDecorator(
            decoration: InputDecoration(
              isDense: true,
              errorText: error,
              errorMaxLines: 2,
              prefixIcon: const Icon(Icons.event_outlined, size: 20, color: AppColors.muted),
              suffixIcon: value == null
                  ? null
                  : IconButton(tooltip: 'مسح', icon: const Icon(Icons.close, size: 18), onPressed: onClear),
            ),
            child: Text(
              value == null ? 'اختر' : Formatters.date(value),
              style: theme.bodyMedium?.copyWith(color: value == null ? AppColors.muted : AppColors.ink),
            ),
          ),
        ),
      ],
    );
  }
}

class _MediaGrid extends StatelessWidget {
  const _MediaGrid({required this.controller});

  final PostFormController controller;

  static bool _isVideoFile(File file) => file.path.toLowerCase().endsWith('.mp4');

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final canAdd = controller.mediaCount < PostFormController.maxMedia;

      return GridView.count(
        crossAxisCount: 3,
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        mainAxisSpacing: 10,
        crossAxisSpacing: 10,
        children: [
          for (final media in controller.existingMedia)
            _MediaTile(
              onRemove: () => controller.removeExistingMedia(media),
              child: media.isVideo ? const _VideoPlaceholder() : AppNetworkImage(url: media.url),
            ),
          for (final file in controller.newMedia)
            _MediaTile(
              onRemove: () => controller.removeNewMedia(file),
              child: _isVideoFile(file) ? const _VideoPlaceholder() : Image.file(file, fit: BoxFit.cover),
            ),
          if (canAdd) ...[
            _AddTile(icon: Icons.add_photo_alternate_outlined, label: 'صور', onTap: controller.pickImages),
            _AddTile(icon: Icons.video_call_outlined, label: 'فيديو', onTap: controller.pickVideo),
          ],
        ],
      );
    });
  }
}

class _MediaTile extends StatelessWidget {
  const _MediaTile({required this.child, required this.onRemove});

  final Widget child;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: Stack(
        fit: StackFit.expand,
        children: [
          child,
          PositionedDirectional(
            top: 4,
            end: 4,
            child: Material(
              color: Colors.black54,
              shape: const CircleBorder(),
              child: InkWell(
                customBorder: const CircleBorder(),
                onTap: onRemove,
                child: const Padding(padding: EdgeInsets.all(4), child: Icon(Icons.close, size: 16, color: Colors.white)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _VideoPlaceholder extends StatelessWidget {
  const _VideoPlaceholder();

  @override
  Widget build(BuildContext context) {
    return const ColoredBox(
      color: AppColors.brand900,
      child: Center(child: Icon(Icons.play_circle_outline, size: 34, color: Colors.white)),
    );
  }
}

class _AddTile extends StatelessWidget {
  const _AddTile({required this.icon, required this.label, required this.onTap});

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.brand50,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: AppColors.brand600),
            const SizedBox(height: 4),
            Text(label, style: const TextStyle(color: AppColors.brand600, fontSize: 12, fontWeight: FontWeight.w600)),
          ],
        ),
      ),
    );
  }
}
