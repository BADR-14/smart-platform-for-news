import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/paged_list_view.dart';
import '../../domain/company.dart';
import '../controllers/companies_controller.dart';
import '../widgets/company_card.dart';

class CompaniesView extends StatefulWidget {
  const CompaniesView({super.key});

  @override
  State<CompaniesView> createState() => _CompaniesViewState();
}

class _CompaniesViewState extends State<CompaniesView> {
  static final _searchBorder = OutlineInputBorder(
    borderRadius: BorderRadius.circular(14),
    borderSide: BorderSide.none,
  );

  final _search = TextEditingController();
  final CompaniesController _controller = Get.find<CompaniesController>();
  Timer? _debounce;

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    super.dispose();
  }

  void _onSearchChanged(String value) {
    setState(() {});
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 450), () => _controller.applySearch(value));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('الشركات'),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(112),
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14),
                child: TextField(
                  controller: _search,
                  textInputAction: TextInputAction.search,
                  onChanged: _onSearchChanged,
                  onSubmitted: _controller.applySearch,
                  decoration: InputDecoration(
                    hintText: 'ابحث عن شركة…',
                    isDense: true,
                    border: _searchBorder,
                    enabledBorder: _searchBorder,
                    focusedBorder: _searchBorder,
                    prefixIcon: const Icon(Icons.search, size: 20, color: AppColors.muted),
                    suffixIcon: _search.text.isEmpty
                        ? null
                        : IconButton(
                            tooltip: 'مسح',
                            icon: const Icon(Icons.close, size: 18),
                            onPressed: () {
                              _search.clear();
                              _onSearchChanged('');
                            },
                          ),
                  ),
                ),
              ),
              SizedBox(
                height: 56,
                child: Obx(() {
                  final selected = _controller.categoryId.value;

                  return ListView(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    children: [
                      _CategoryChip(label: 'الكل', selected: selected == null, onTap: () => _controller.selectCategory(null)),
                      for (final category in _controller.categories)
                        _CategoryChip(
                          label: category.name,
                          selected: selected == category.id,
                          onTap: () => _controller.selectCategory(category.id),
                        ),
                    ],
                  );
                }),
              ),
            ],
          ),
        ),
      ),
      body: PagedListView<Company>(
        controller: _controller,
        emptyIcon: Icons.apartment_outlined,
        emptyTitle: 'لا توجد شركات',
        emptyMessage: 'جرّب كلمة بحث أخرى أو تصنيفاً مختلفاً.',
        itemBuilder: (context, company, index) => CompanyCard(key: ValueKey(company.id), company: company),
      ),
    );
  }
}

class _CategoryChip extends StatelessWidget {
  const _CategoryChip({required this.label, required this.selected, required this.onTap});

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsetsDirectional.only(end: 8),
      child: ChoiceChip(label: Text(label), selected: selected, onSelected: (_) => onTap()),
    );
  }
}
