import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:get/get.dart';

import '../core/config/app_config.dart';
import '../core/theme/app_colors.dart';
import '../core/theme/app_theme.dart';
import 'app_pages.dart';
import 'bindings/initial_binding.dart';
import 'routes.dart';

class SmartNewsApp extends StatelessWidget {
  const SmartNewsApp({super.key});

  static const _arabic = Locale('ar');

  @override
  Widget build(BuildContext context) {
    return GetMaterialApp(
      title: AppConfig.appName,
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,

      // التطبيق عربي فقط: الاتجاه من اليمين لليسار يأتي تلقائياً من اللغة
      locale: _arabic,
      fallbackLocale: _arabic,
      supportedLocales: const [_arabic],
      localizationsDelegates: GlobalMaterialLocalizations.delegates,

      // انتقال انزلاقي ناعم بين الصفحات (يحترم اتجاه RTL)
      defaultTransition: Transition.cupertino,
      transitionDuration: const Duration(milliseconds: 320),

      // حجم خط النظام يُحترم لكن ضمن حدود: التكبير الزائد يكسر تخطيط البطاقات
      builder: (context, child) => MediaQuery.withClampedTextScaling(
        minScaleFactor: 0.9,
        maxScaleFactor: 1.15,
        // أندرويد يرسم التطبيق تحت شريط التنقل السفلي: نحجز مساحته مرة واحدة هنا
        // حتى لا يختفي آخر المحتوى أو أزرار الحفظ خلفه في أي شاشة.
        child: ColoredBox(
          color: AppColors.canvas,
          child: SafeArea(top: false, child: child ?? const SizedBox.shrink()),
        ),
      ),

      initialBinding: InitialBinding(),
      initialRoute: Routes.splash,
      getPages: AppPages.pages,
    );
  }
}
