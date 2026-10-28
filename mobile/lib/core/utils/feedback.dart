import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../network/api_exception.dart';
import '../theme/app_colors.dart';

/// رسائل قصيرة للمستخدم (نجاح / خطأ) بشكل موحّد في كل التطبيق.
abstract final class AppFeedback {
  static void success(String message) => _show(message, AppColors.success, Icons.check_circle_outline);

  static void error(Object error) => _show(errorMessage(error), AppColors.danger, Icons.error_outline);

  static void info(String message) => _show(message, AppColors.brand800, Icons.info_outline);

  /// يحوّل أي خطأ إلى رسالة عربية قابلة للعرض (لا نعرض نص الاستثناء الخام للمستخدم).
  static String errorMessage(Object error) {
    if (error is ApiException) return error.displayMessage;
    if (error is String) return error;
    return 'حدث خطأ غير متوقع. حاول مجدداً.';
  }

  static void _show(String message, Color color, IconData icon) {
    if (Get.isSnackbarOpen) Get.closeCurrentSnackbar();

    Get.rawSnackbar(
      messageText: Text(message, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w500)),
      icon: Icon(icon, color: Colors.white),
      backgroundColor: color,
      borderRadius: 14,
      margin: const EdgeInsets.all(14),
      snackPosition: SnackPosition.BOTTOM,
      duration: const Duration(seconds: 3),
    );
  }
}
