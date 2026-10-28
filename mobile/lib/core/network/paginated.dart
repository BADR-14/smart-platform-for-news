import '../utils/json.dart';

/// صفحة واحدة من قائمة مقسّمة: `{ data: [...], meta: {...} }`.
class Paginated<T> {
  const Paginated({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.total,
    this.extra = const {},
  });

  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int total;

  /// مفاتيح إضافية بجانب data و meta (مثل unread_count).
  final Json extra;

  bool get hasMore => currentPage < lastPage;

  factory Paginated.fromJson(Json json, T Function(Json item) parse) {
    final meta = asJson(json['meta']);

    return Paginated<T>(
      items: asList<T>(json['data'], parse),
      currentPage: asInt(meta['current_page'], 1),
      lastPage: asInt(meta['last_page'], 1),
      total: asInt(meta['total']),
      extra: json,
    );
  }

  /// للقوائم التي عناصرها من أنواع مختلفة (الصفحة الرئيسية: منشور أو مشاركة).
  factory Paginated.fromJsonNullable(Json json, T? Function(Json item) parse) {
    final meta = asJson(json['meta']);
    final raw = json['data'];
    final items = <T>[];

    if (raw is List) {
      for (final entry in raw) {
        if (entry is! Map) continue;
        final parsed = parse(asJson(entry));
        if (parsed != null) items.add(parsed);
      }
    }

    return Paginated<T>(
      items: items,
      currentPage: asInt(meta['current_page'], 1),
      lastPage: asInt(meta['last_page'], 1),
      total: asInt(meta['total']),
      extra: json,
    );
  }
}
