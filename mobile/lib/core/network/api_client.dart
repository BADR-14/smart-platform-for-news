import 'package:dio/dio.dart';

import '../config/app_config.dart';
import '../utils/json.dart';
import 'api_exception.dart';

/// نسخة Dio الوحيدة في التطبيق.
///  - تضيف توكن الدخول لكل طلب.
///  - تحوّل كل الأخطاء إلى [ApiException] برسالة عربية.
///  - عند 401 تبلّغ [onUnauthorized] (ينهي الجلسة ويعيد المستخدم لتسجيل الدخول).
class ApiClient {
  ApiClient({required this.tokenProvider, this.onUnauthorized, Dio? dio})
      : _dio = dio ??
            Dio(
              BaseOptions(
                baseUrl: AppConfig.apiUrl,
                connectTimeout: const Duration(seconds: 15),
                receiveTimeout: const Duration(seconds: 30),
                sendTimeout: const Duration(seconds: 60),
                headers: {'Accept': 'application/json'},
              ),
            ) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          final token = tokenProvider();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );
  }

  final Dio _dio;

  /// يرجع التوكن الحالي من الذاكرة (SessionService) دون قراءة التخزين مع كل طلب.
  final String? Function() tokenProvider;

  final void Function()? onUnauthorized;

  Future<Json> get(String path, {Map<String, dynamic>? query}) {
    return _send(() => _dio.get<dynamic>(path, queryParameters: _clean(query)));
  }

  Future<Json> post(String path, {Object? body}) {
    return _send(() => _dio.post<dynamic>(path, data: body));
  }

  Future<Json> patch(String path, {Object? body}) {
    return _send(() => _dio.patch<dynamic>(path, data: body));
  }

  Future<Json> delete(String path, {Object? body}) {
    return _send(() => _dio.delete<dynamic>(path, data: body));
  }

  /// طلب multipart (رفع ملفات).
  Future<Json> upload(String path, FormData form) {
    return _send(() => _dio.post<dynamic>(path, data: form));
  }

  Future<Json> _send(Future<Response<dynamic>> Function() request) async {
    try {
      final response = await request();
      return asJson(response.data);
    } on DioException catch (error) {
      throw _toApiException(error);
    }
  }

  ApiException _toApiException(DioException error) {
    final response = error.response;

    if (response == null) {
      final isTimeout = error.type == DioExceptionType.connectionTimeout ||
          error.type == DioExceptionType.receiveTimeout ||
          error.type == DioExceptionType.sendTimeout;

      return ApiException(
        isTimeout
            ? 'انتهت مهلة الاتصال بالخادم. حاول مجدداً.'
            : 'تعذّر الاتصال بالخادم. تحقق من اتصالك بالإنترنت.',
      );
    }

    final status = response.statusCode ?? 0;
    final body = asJson(response.data);

    // طلبات الدخول نفسها ترجع 401 عند خطأ كلمة المرور، وهذا ليس انتهاء جلسة
    final isAuthRequest = error.requestOptions.path.contains('/login') ||
        error.requestOptions.path.contains('/auth/google');

    if (status == 401 && !isAuthRequest) {
      onUnauthorized?.call();
    }

    final fieldErrors = <String, List<String>>{};
    final rawErrors = body['errors'];
    if (rawErrors is Map) {
      rawErrors.forEach((key, value) {
        if (value is List) {
          fieldErrors[key.toString()] = value.map((item) => item.toString()).toList();
        }
      });
    }

    return ApiException(
      asString(body['message'], 'حدث خطأ غير متوقع. حاول مجدداً.'),
      statusCode: status,
      code: asStringOrNull(body['code']),
      fieldErrors: fieldErrors,
      extra: body,
    );
  }

  /// يحذف القيم الفارغة حتى لا تُرسل كـ query params.
  Map<String, dynamic>? _clean(Map<String, dynamic>? query) {
    if (query == null) return null;

    final cleaned = <String, dynamic>{};
    query.forEach((key, value) {
      if (value == null) return;
      if (value is String && value.isEmpty) return;
      cleaned[key] = value is bool ? (value ? 1 : 0) : value;
    });
    return cleaned;
  }
}
