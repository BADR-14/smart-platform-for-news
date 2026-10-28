import 'package:intl/intl.dart';

/// تنسيق التواريخ والأرقام للعرض بالعربية (بأرقام لاتينية مثل لوحة الويب).
abstract final class Formatters {
  static final DateFormat _date = DateFormat('d MMM y', 'ar');
  static final DateFormat _api = DateFormat('yyyy-MM-dd');
  static final NumberFormat _compact = NumberFormat.compact(locale: 'en');

  /// "5 أكتوبر 2026"
  static String date(DateTime? value) {
    if (value == null) return '—';
    return _latinDigits(_date.format(value));
  }

  /// الصيغة التي يتوقعها الـ API: 2026-10-05
  static String apiDate(DateTime value) => _api.format(value);

  /// 1200 ⟵ 1.2K
  static String count(int value) => _compact.format(value);

  /// "منذ 5 دقائق" / "أمس" ...
  static String timeAgo(DateTime? value) {
    if (value == null) return '';

    final diff = DateTime.now().difference(value);

    if (diff.inSeconds < 60) return 'الآن';
    if (diff.inMinutes < 60) return 'منذ ${_plural(diff.inMinutes, 'دقيقة', 'دقيقتين', 'دقائق')}';
    if (diff.inHours < 24) return 'منذ ${_plural(diff.inHours, 'ساعة', 'ساعتين', 'ساعات')}';
    if (diff.inDays == 1) return 'أمس';
    if (diff.inDays < 7) return 'منذ ${_plural(diff.inDays, 'يوم', 'يومين', 'أيام')}';
    if (diff.inDays < 30) return 'منذ ${_plural(diff.inDays ~/ 7, 'أسبوع', 'أسبوعين', 'أسابيع')}';
    if (diff.inDays < 365) return 'منذ ${_plural(diff.inDays ~/ 30, 'شهر', 'شهرين', 'أشهر')}';

    return date(value);
  }

  /// صيغ العدد في العربية: مفرد، مثنى، جمع (3–10)، ثم مفرد مع الرقم.
  static String _plural(int count, String one, String two, String few) {
    if (count == 1) return one;
    if (count == 2) return two;
    if (count <= 10) return '$count $few';
    return '$count $one';
  }

  static String _latinDigits(String input) {
    const arabic = '٠١٢٣٤٥٦٧٨٩';
    final buffer = StringBuffer();

    for (final rune in input.runes) {
      final char = String.fromCharCode(rune);
      final index = arabic.indexOf(char);
      buffer.write(index == -1 ? char : index.toString());
    }

    return buffer.toString();
  }
}
