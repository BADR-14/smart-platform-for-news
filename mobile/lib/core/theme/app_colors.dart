import 'package:flutter/material.dart';

/// ألوان التطبيق — مأخوذة من الشعار (كحلي + برتقالي على خلفية فاتحة)
/// ومطابقة لألوان لوحة الويب. لا يُكتب أي لون مباشرة داخل الواجهات.
abstract final class AppColors {
  // الكحلي (اللون الأساسي)
  static const brand50 = Color(0xFFE8F0F8);
  static const brand100 = Color(0xFFD3E2F0);
  static const brand200 = Color(0xFFAFC8DF);
  static const brand300 = Color(0xFF7FA3C4);
  static const brand600 = Color(0xFF2A5B88);
  static const brand700 = Color(0xFF21486B);
  static const brand800 = Color(0xFF1A344B);
  static const brand900 = Color(0xFF12283C);

  // البرتقالي (لون الإبراز)
  static const accent50 = Color(0xFFFFF0E3);
  static const accent100 = Color(0xFFFFDDBF);
  static const accent500 = Color(0xFFEA7A26);
  static const accent600 = Color(0xFFCF5F12);

  /// للنصوص البرتقالية الصغيرة على خلفية فاتحة: تباين 5.9:1 (معيار WCAG AA).
  /// accent500 و accent600 للأزرار والأيقونات والعناصر الكبيرة فقط.
  static const accent700 = Color(0xFFA94A0E);

  // الأسطح والنصوص
  static const canvas = Color(0xFFEEF3F9);
  static const surface = Color(0xFFFFFFFF);
  static const line = Color(0xFFDCE5EF);
  static const lineStrong = Color(0xFFCBD5E1);
  static const ink = Color(0xFF0F2233);
  static const inkSoft = Color(0xFF3D4F61);
  static const muted = Color(0xFF5A6B80);

  // الحالات
  static const success = Color(0xFF12915A);
  static const successBg = Color(0xFFDFF5EA);
  static const warning = Color(0xFFB7791F);
  static const warningBg = Color(0xFFFFF1CF);
  static const danger = Color(0xFFDC3545);
  static const dangerBg = Color(0xFFFDE3E5);
  static const info = Color(0xFF1F6FC2);
  static const infoBg = Color(0xFFE0EEFC);
  static const star = Color(0xFFF5A623);

  /// تدرّج الترويسات (الرئيسية، البداية، البطاقات المميزة).
  static const brandGradient = LinearGradient(
    begin: AlignmentDirectional.topStart,
    end: AlignmentDirectional.bottomEnd,
    colors: [Color(0xFF12283C), Color(0xFF1A344B), Color(0xFF2A5B88)],
  );

  static const accentGradient = LinearGradient(
    begin: AlignmentDirectional.topStart,
    end: AlignmentDirectional.bottomEnd,
    colors: [Color(0xFFF59A45), Color(0xFFEA7A26)],
  );
}
