import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:intl/date_symbol_data_local.dart';

import 'app/app.dart';
import 'core/session/session_service.dart';
import 'core/storage/token_storage.dart';
import 'features/splash/splash_controller.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // أسماء الأشهر العربية لتنسيق التواريخ تُحمَّل مع الجلسة أدناه.
  // الجلسة تُسجَّل قبل تشغيل التطبيق: ApiClient يقرأ التوكن منها من أول طلب
  final storage = TokenStorage();
  final session = Get.put(SessionService(storage), permanent: true);

  // القراءات الثلاث تجري معاً حتى لا تؤخّر إحداها أول إطار
  final introSeen = storage.introSeen().catchError((_) => false);
  await Future.wait<void>([initializeDateFormatting('ar'), session.restoreToken()]);

  SplashController.firstLaunch = !await introSeen;
  if (SplashController.firstLaunch) unawaited(storage.markIntroSeen().catchError((_) {}));

  runApp(const SmartNewsApp());
}
