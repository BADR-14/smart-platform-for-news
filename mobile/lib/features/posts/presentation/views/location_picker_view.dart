import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:get/get.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/services/geocoding_service.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/feedback.dart';
import '../../../../core/utils/navigation.dart';
import '../../../../core/widgets/app_button.dart';

/// صفحة اختيار موقع الفعالية من الخريطة: بحث بالاسم أو ضغط على الخريطة.
/// ترجع [GeoPlace] عند التأكيد.
class LocationPickerView extends StatefulWidget {
  const LocationPickerView({super.key, this.initial});

  final GeoPlace? initial;

  static Future<GeoPlace?> open({GeoPlace? initial}) async {
    return Get.to<GeoPlace>(() => LocationPickerView(initial: initial), fullscreenDialog: true);
  }

  @override
  State<LocationPickerView> createState() => _LocationPickerViewState();
}

class _LocationPickerViewState extends State<LocationPickerView> {
  // دمشق — نقطة البداية إذا لم يُحدَّد موقع سابق
  static const _defaultCenter = LatLng(33.5138, 36.2765);

  final _geocoding = GeocodingService();
  final _map = MapController();
  final _search = TextEditingController();

  Timer? _debounce;
  LatLng? _point;
  String? _address;
  bool _resolving = false;
  bool _searching = false;
  List<GeoPlace> _results = const [];

  @override
  void initState() {
    super.initState();

    final initial = widget.initial;
    if (initial != null) {
      _point = LatLng(initial.latitude, initial.longitude);
      _address = initial.name;
    }
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    _map.dispose();
    super.dispose();
  }

  void _onSearchChanged(String value) {
    _debounce?.cancel();

    if (value.trim().length < 3) {
      setState(() => _results = const []);
      return;
    }

    // مهلة أطول من المعتاد احتراماً لحد الطلبات في Nominatim
    _debounce = Timer(const Duration(milliseconds: 800), () => _runSearch(value.trim()));
  }

  Future<void> _runSearch(String query) async {
    setState(() => _searching = true);

    try {
      final results = await _geocoding.search(query);
      if (mounted) setState(() => _results = results);
    } catch (_) {
      if (mounted) AppFeedback.error('تعذّر البحث عن المكان. تحقق من اتصالك.');
    } finally {
      if (mounted) setState(() => _searching = false);
    }
  }

  void _selectResult(GeoPlace place) {
    final point = LatLng(place.latitude, place.longitude);

    FocusScope.of(context).unfocus();
    setState(() {
      _point = point;
      _address = place.name;
      _results = const [];
      _search.clear();
    });
    _map.move(point, 15);
  }

  Future<void> _onMapTap(LatLng point) async {
    setState(() {
      _point = point;
      _address = null;
      _resolving = true;
      _results = const [];
    });

    String? address;
    try {
      address = await _geocoding.reverse(point.latitude, point.longitude);
    } catch (_) {
      // بدون عنوان نعرض الإحداثيات، والمستخدم يكتب العنوان بنفسه في النموذج
    }

    // تجاهل الرد إذا اختار المستخدم نقطة أخرى أثناء الانتظار
    if (!mounted || _point != point) return;

    setState(() {
      _address = address;
      _resolving = false;
    });
  }

  void _confirm() {
    final point = _point;
    if (point == null) return;

    AppNav.back<GeoPlace>(
      GeoPlace(
        name: _address ?? '${point.latitude.toStringAsFixed(5)}, ${point.longitude.toStringAsFixed(5)}',
        latitude: point.latitude,
        longitude: point.longitude,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;
    final point = _point;

    return Scaffold(
      appBar: AppBar(title: const Text('تحديد الموقع')),
      body: Stack(
        children: [
          FlutterMap(
            mapController: _map,
            options: MapOptions(
              initialCenter: point ?? _defaultCenter,
              initialZoom: point == null ? 11 : 15,
              onTap: (_, tapped) => _onMapTap(tapped),
            ),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'com.smartplatform.news',
              ),
              if (point != null)
                MarkerLayer(
                  markers: [
                    Marker(
                      point: point,
                      width: 46,
                      height: 46,
                      alignment: Alignment.topCenter,
                      child: const Icon(Icons.location_on, size: 46, color: AppColors.accent500),
                    ),
                  ],
                ),
            ],
          ),
          Positioned(
            top: 12,
            left: 12,
            right: 12,
            child: Column(
              children: [
                Material(
                  elevation: 3,
                  borderRadius: BorderRadius.circular(14),
                  child: TextField(
                    controller: _search,
                    textInputAction: TextInputAction.search,
                    onChanged: _onSearchChanged,
                    onSubmitted: (value) {
                      if (value.trim().length >= 3) _runSearch(value.trim());
                    },
                    decoration: InputDecoration(
                      hintText: 'ابحث عن مدينة أو شارع أو مكان…',
                      prefixIcon: const Icon(Icons.search, size: 20, color: AppColors.muted),
                      suffixIcon: _searching
                          ? const Padding(
                              padding: EdgeInsets.all(14),
                              child: SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)),
                            )
                          : null,
                    ),
                  ),
                ),
                if (_results.isNotEmpty)
                  Container(
                    margin: const EdgeInsets.only(top: 6),
                    constraints: const BoxConstraints(maxHeight: 260),
                    child: Material(
                      elevation: 3,
                      borderRadius: BorderRadius.circular(14),
                      clipBehavior: Clip.antiAlias,
                      child: ListView.separated(
                        shrinkWrap: true,
                        padding: EdgeInsets.zero,
                        itemCount: _results.length,
                        separatorBuilder: (context, index) => const Divider(),
                        itemBuilder: (context, index) {
                          final place = _results[index];

                          return ListTile(
                            dense: true,
                            leading: const Icon(Icons.place_outlined, color: AppColors.brand600),
                            title: Text(place.name, maxLines: 2, overflow: TextOverflow.ellipsis),
                            onTap: () => _selectResult(place),
                          );
                        },
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
      bottomNavigationBar: Material(
        color: AppColors.surface,
        elevation: 8,
        child: SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (point == null)
                  Text(
                    'اضغط على الخريطة لتحديد مكان الفعالية.',
                    textAlign: TextAlign.center,
                    style: theme.bodyMedium?.copyWith(color: AppColors.muted),
                  )
                else if (_resolving)
                  Text('جارٍ تحديد العنوان…', textAlign: TextAlign.center, style: theme.bodyMedium?.copyWith(color: AppColors.muted))
                else
                  Text(
                    _address ?? 'لم نجد عنواناً لهذه النقطة. يمكنك كتابته يدوياً بعد التأكيد.',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: theme.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
                  ),
                const SizedBox(height: 12),
                AppButton(
                  label: 'تأكيد الموقع',
                  icon: Icons.check,
                  onPressed: point == null || _resolving ? null : _confirm,
                ),
                const SizedBox(height: 6),
                Text(
                  '© OpenStreetMap contributors',
                  textAlign: TextAlign.center,
                  style: theme.labelSmall?.copyWith(color: AppColors.muted),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
