import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/paged_list_view.dart';
import '../../domain/post.dart';
import '../controllers/home_controller.dart';
import '../widgets/featured_carousel.dart';
import '../widgets/filter_sheet.dart';
import '../widgets/post_card.dart';

class HomeView extends StatefulWidget {
  const HomeView({super.key});

  @override
  State<HomeView> createState() => _HomeViewState();
}

class _HomeViewState extends State<HomeView> {
  static final _searchBorder = OutlineInputBorder(
    borderRadius: BorderRadius.circular(14),
    borderSide: BorderSide.none,
  );

  final _search = TextEditingController();
  Timer? _debounce;

  HomeController get _controller => Get.find<HomeController>();

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    super.dispose();
  }

  /// ننتظر توقف الكتابة قبل إرسال البحث حتى لا يُرسل طلب مع كل حرف.
  void _onSearchChanged(String value) {
    setState(() {});
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 450), () => _controller.search(value));
  }

  Future<void> _openFilters() async {
    final result = await FilterSheet.show(initial: _controller.filters.value, categories: _controller.categories);
    if (result != null) _controller.applyFilters(result);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return Scaffold(
      appBar: AppBar(
        centerTitle: false,
        titleSpacing: 14,
        title: Row(
          children: [
            // الشعار كحلي، فيوضع على خلفية بيضاء ليظهر فوق الترويسة الكحلية
            Container(
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
              child: Image.asset('assets/images/logo_mark.png', width: 30, height: 30),
            ),
            const SizedBox(width: 10),
            Text('Smart Platform', style: theme.titleMedium?.copyWith(fontWeight: FontWeight.w700, color: Colors.white)),
          ],
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(108),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
            child: Column(
              children: [
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _search,
                        textInputAction: TextInputAction.search,
                        onChanged: _onSearchChanged,
                        onSubmitted: _controller.search,
                        decoration: InputDecoration(
                          hintText: 'ابحث عن محاضرة، معرض، دورة…',
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
                    const SizedBox(width: 8),
                    Obx(() {
                      final count = _controller.filters.value.activeCount;

                      return Badge(
                        isLabelVisible: count > 0,
                        label: Text('$count'),
                        backgroundColor: AppColors.accent500,
                        child: IconButton.filled(
                          tooltip: 'تصفية',
                          style: IconButton.styleFrom(backgroundColor: Colors.white, foregroundColor: AppColors.brand800),
                          onPressed: _openFilters,
                          icon: const Icon(Icons.tune),
                        ),
                      );
                    }),
                  ],
                ),
                const SizedBox(height: 10),
                Obx(
                  () => SizedBox(
                    width: double.infinity,
                    child: SegmentedButton<HomeTab>(
                      showSelectedIcon: false,
                      segments: const [
                        ButtonSegment(value: HomeTab.all, label: Text('كل المنشورات')),
                        ButtonSegment(value: HomeTab.following, label: Text('أتابعهم')),
                      ],
                      selected: {_controller.tab.value},
                      onSelectionChanged: (selection) => _controller.changeTab(selection.first),
                      style: const ButtonStyle(visualDensity: VisualDensity.compact),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
      body: Obx(() {
        final following = _controller.tab.value == HomeTab.following;
        final filtered = !_controller.filters.value.isEmpty;

        return PagedListView<FeedItem>(
          controller: _controller,
          header: _controller.showFeatured ? FeaturedCarousel(posts: _controller.featured) : null,
          emptyIcon: following ? Icons.groups_outlined : Icons.search_off,
          emptyTitle: following
              ? 'لا توجد منشورات ممن تتابعهم'
              : filtered
                  ? 'لا توجد نتائج'
                  : 'لا توجد منشورات بعد',
          emptyMessage: following
              ? 'تابع شركات من تبويب الشركات لتظهر منشوراتها هنا.'
              : filtered
                  ? 'جرّب كلمات أخرى أو غيّر الفلاتر.'
                  : null,
          itemBuilder: (context, item, index) => PostCard(key: ValueKey(item.key), post: item.post, share: item),
        );
      }),
    );
  }
}
