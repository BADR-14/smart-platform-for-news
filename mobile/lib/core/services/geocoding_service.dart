import 'package:dio/dio.dart';

import '../utils/json.dart';

class GeoPlace {
  const GeoPlace({required this.name, required this.latitude, required this.longitude});

  final String name;
  final double latitude;
  final double longitude;
}

/// تحويل الإحداثيات إلى عنوان والعكس عبر Nominatim (OpenStreetMap) — مجاني وبدون مفتاح.
/// سياسة الخدمة: طلب واحد في الثانية كحد أقصى، مع ترويسة تعرّف بالتطبيق.
class GeocodingService {
  GeocodingService([Dio? dio])
      : _dio = dio ??
            Dio(
              BaseOptions(
                baseUrl: 'https://nominatim.openstreetmap.org',
                connectTimeout: const Duration(seconds: 10),
                receiveTimeout: const Duration(seconds: 10),
                headers: {'User-Agent': 'SmartPlatformNews/1.0 (graduation project)', 'Accept-Language': 'ar'},
              ),
            );

  final Dio _dio;

  Future<List<GeoPlace>> search(String query) async {
    final response = await _dio.get<dynamic>('/search', queryParameters: {'format': 'jsonv2', 'q': query, 'limit': 6});
    final data = response.data;
    if (data is! List) return const [];

    final places = <GeoPlace>[];
    for (final item in data.whereType<Map>()) {
      final json = asJson(item);
      final latitude = asDoubleOrNull(json['lat']);
      final longitude = asDoubleOrNull(json['lon']);

      if (latitude != null && longitude != null) {
        places.add(GeoPlace(name: asString(json['display_name']), latitude: latitude, longitude: longitude));
      }
    }
    return places;
  }

  /// يرجع null إذا لم يُعثر على عنوان لهذه النقطة.
  Future<String?> reverse(double latitude, double longitude) async {
    final response = await _dio.get<dynamic>(
      '/reverse',
      queryParameters: {'format': 'jsonv2', 'lat': latitude, 'lon': longitude, 'zoom': 16},
    );

    return asStringOrNull(asJson(response.data)['display_name']);
  }
}
