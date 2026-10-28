import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/navigation.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/dialogs.dart';
import '../../../shared/models/account.dart';
import '../../domain/post.dart';
import '../../domain/posts_repository.dart';

/// نافذة فلاتر الصفحة الرئيسية. ترجع الفلاتر المختارة أو null إذا أُغلقت دون تطبيق.
class FilterSheet extends StatefulWidget {
  const FilterSheet({super.key, required this.initial, required this.categories});

  final PostFilters initial;
  final List<Category> categories;

  static Future<PostFilters?> show({required PostFilters initial, required List<Category> categories}) {
    return Get.bottomSheet<PostFilters>(
      FilterSheet(initial: initial, categories: categories),
      isScrollControlled: true,
    );
  }

  @override
  State<FilterSheet> createState() => _FilterSheetState();
}

class _FilterSheetState extends State<FilterSheet> {
  late PostType? _type = widget.initial.postType;
  late int? _categoryId = widget.initial.categoryId;
  late bool? _isOnline = widget.initial.isOnline;
  late int? _minRating = widget.initial.minRating;

  void _apply() {
    AppNav.back<PostFilters>(
      PostFilters(postType: _type, categoryId: _categoryId, isOnline: _isOnline, minRating: _minRating),
    );
  }

  void _reset() {
    setState(() {
      _type = null;
      _categoryId = null;
      _isOnline = null;
      _minRating = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: ConstrainedBox(
        constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.85),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SheetHeader(title: 'تصفية المنشورات'),
            Flexible(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 18),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _Section(
                      title: 'نوع المنشور',
                      children: [
                        for (final type in PostType.values)
                          ChoiceChip(
                            label: Text(type.label),
                            selected: _type == type,
                            onSelected: (selected) => setState(() => _type = selected ? type : null),
                          ),
                      ],
                    ),
                    _Section(
                      title: 'مكان الانعقاد',
                      children: [
                        ChoiceChip(
                          label: const Text('أونلاين'),
                          selected: _isOnline == true,
                          onSelected: (selected) => setState(() => _isOnline = selected ? true : null),
                        ),
                        ChoiceChip(
                          label: const Text('حضوري'),
                          selected: _isOnline == false,
                          onSelected: (selected) => setState(() => _isOnline = selected ? false : null),
                        ),
                      ],
                    ),
                    if (widget.categories.isNotEmpty)
                      _Section(
                        title: 'التصنيف',
                        children: [
                          for (final category in widget.categories)
                            ChoiceChip(
                              label: Text(category.name),
                              selected: _categoryId == category.id,
                              onSelected: (selected) => setState(() => _categoryId = selected ? category.id : null),
                            ),
                        ],
                      ),
                    _Section(
                      title: 'التقييم',
                      children: [
                        for (final rating in const [3, 4, 5])
                          ChoiceChip(
                            avatar: const Icon(Icons.star_rounded, size: 16, color: AppColors.star),
                            label: Text(rating == 5 ? '5' : '$rating فأكثر'),
                            selected: _minRating == rating,
                            onSelected: (selected) => setState(() => _minRating = selected ? rating : null),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(18),
              child: Row(
                children: [
                  Expanded(child: AppButton(label: 'مسح الكل', style: AppButtonStyle.outline, onPressed: _reset)),
                  const SizedBox(width: 12),
                  Expanded(flex: 2, child: AppButton(label: 'تطبيق', onPressed: _apply)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Section extends StatelessWidget {
  const _Section({required this.title, required this.children});

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          Wrap(spacing: 8, runSpacing: 8, children: children),
        ],
      ),
    );
  }
}
