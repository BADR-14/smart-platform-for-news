/// قواعد التحقق من حقول النماذج (مطابقة لقواعد الباك-إند حتى يظهر الخطأ قبل الإرسال).
abstract final class Validators {
  static final RegExp _email = RegExp(r'^[^\s@]+@[^\s@]+\.[^\s@]+$');
  static final RegExp _phone = RegExp(r'^\+?[0-9]{7,15}$');
  static final RegExp _hasLetter = RegExp(r'\p{L}', unicode: true);
  static final RegExp _hasDigit = RegExp(r'\d');

  static String? required(String? value, [String message = 'هذا الحقل مطلوب']) {
    return (value == null || value.trim().isEmpty) ? message : null;
  }

  static String? email(String? value) {
    final text = value?.trim() ?? '';
    if (text.isEmpty) return 'البريد الإلكتروني مطلوب';
    return _email.hasMatch(text) ? null : 'أدخل بريداً إلكترونياً صالحاً';
  }

  /// 8 محارف على الأقل مع حرف ورقم.
  static String? password(String? value) {
    final text = value ?? '';
    if (text.isEmpty) return 'كلمة المرور مطلوبة';
    if (text.length < 8) return 'كلمة المرور يجب أن تكون 8 محارف على الأقل';
    if (!_hasLetter.hasMatch(text)) return 'كلمة المرور يجب أن تحتوي على حرف واحد على الأقل';
    if (!_hasDigit.hasMatch(text)) return 'كلمة المرور يجب أن تحتوي على رقم واحد على الأقل';
    return null;
  }

  static String? Function(String?) confirm(String Function() original) {
    return (value) => value == original() ? null : 'كلمتا المرور غير متطابقتين';
  }

  static String? phone(String? value, {bool optional = false}) {
    final text = value?.trim() ?? '';
    if (text.isEmpty) return optional ? null : 'رقم الهاتف مطلوب';
    return _phone.hasMatch(text) ? null : 'رقم هاتف غير صالح (7 إلى 15 رقماً)';
  }

  static String? url(String? value, {bool optional = true}) {
    final text = value?.trim() ?? '';
    if (text.isEmpty) return optional ? null : 'الرابط مطلوب';

    final uri = Uri.tryParse(text);
    final valid = uri != null && uri.hasScheme && (uri.scheme == 'http' || uri.scheme == 'https') && uri.host.isNotEmpty;
    return valid ? null : 'أدخل رابطاً صالحاً يبدأ بـ https://';
  }

  static String? digits(String? value, int length, String label) {
    final text = value?.trim() ?? '';
    if (text.isEmpty) return '$label مطلوب';
    return RegExp('^\\d{$length}\$').hasMatch(text) ? null : '$label يجب أن يتكون من $length رقماً';
  }

  static String? minLength(String? value, int min, String label) {
    final text = value?.trim() ?? '';
    if (text.isEmpty) return '$label مطلوب';
    return text.length < min ? '$label قصير جداً' : null;
  }
}
