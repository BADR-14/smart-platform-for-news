/// خطأ موحّد لكل طلبات الـ API: رسالة عربية جاهزة للعرض + رمز ثابت + أخطاء الحقول.
class ApiException implements Exception {
  const ApiException(
    this.message, {
    this.statusCode = 0,
    this.code,
    this.fieldErrors = const {},
    this.extra = const {},
  });

  final String message;
  final int statusCode;

  /// رمز ثابت من الباك-إند (email_unverified, account_banned ...).
  final String? code;

  /// أخطاء التحقق لكل حقل (رد 422).
  final Map<String, List<String>> fieldErrors;

  /// أي مفاتيح إضافية في رد الخطأ (مثل email عند email_unverified).
  final Map<String, dynamic> extra;

  bool get isUnauthorized => statusCode == 401;
  bool get isValidation => statusCode == 422 && fieldErrors.isNotEmpty;
  bool get isNetwork => statusCode == 0;

  /// أول رسالة خطأ لحقل معيّن إن وجدت.
  String? field(String name) {
    final errors = fieldErrors[name];
    return (errors == null || errors.isEmpty) ? null : errors.first;
  }

  /// أول رسالة مفيدة: خطأ أول حقل إن وجد، وإلا الرسالة العامة.
  String get displayMessage {
    if (fieldErrors.isNotEmpty) {
      final first = fieldErrors.values.first;
      if (first.isNotEmpty) return first.first;
    }
    return message;
  }

  @override
  String toString() => 'ApiException($statusCode, $code): $message';
}
