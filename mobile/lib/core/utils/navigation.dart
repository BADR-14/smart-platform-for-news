import 'package:get/get.dart';

abstract final class AppNav {
  /// يغلق الصفحة أو النافذة الحالية (صفحة، Bottom Sheet، Dialog).
  ///
  /// لا نستخدم `Get.back()` لهذا الغرض: إذا كانت رسالة (snackbar) ظاهرة
  /// فإن `Get.back()` تغلق الرسالة فقط وتُبقي الصفحة مفتوحة.
  static void back<T>([T? result]) => Get.key.currentState?.pop<T>(result);
}
