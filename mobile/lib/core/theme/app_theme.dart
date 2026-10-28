import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';

import 'app_colors.dart';

/// الثيم الوحيد للتطبيق (فاتح). كل الواجهات تأخذ ألوانها وخطوطها من هنا.
abstract final class AppTheme {
  static const double radius = 14;

  /// سلّم أحجام الخط: أصغر من افتراضي Material بدرجة، لأن الخط العربي يبدو أكبر
  /// من اللاتيني عند نفس الحجم. النص الأساسي 14، والثانوي 12–13، والعناوين 15–20.
  static TextTheme _compact(TextTheme theme) {
    TextStyle? size(TextStyle? style, double fontSize, {double height = 1.45}) =>
        style?.copyWith(fontSize: fontSize, height: height);

    return theme.copyWith(
      displayLarge: size(theme.displayLarge, 40, height: 1.2),
      displayMedium: size(theme.displayMedium, 34, height: 1.2),
      displaySmall: size(theme.displaySmall, 28, height: 1.25),
      headlineLarge: size(theme.headlineLarge, 24, height: 1.3),
      headlineMedium: size(theme.headlineMedium, 22, height: 1.3),
      headlineSmall: size(theme.headlineSmall, 19, height: 1.35),
      titleLarge: size(theme.titleLarge, 17, height: 1.35),
      titleMedium: size(theme.titleMedium, 15, height: 1.4),
      titleSmall: size(theme.titleSmall, 13.5, height: 1.4),
      bodyLarge: size(theme.bodyLarge, 14.5, height: 1.6),
      bodyMedium: size(theme.bodyMedium, 13.5, height: 1.55),
      bodySmall: size(theme.bodySmall, 12),
      labelLarge: size(theme.labelLarge, 13),
      labelMedium: size(theme.labelMedium, 12),
      labelSmall: size(theme.labelSmall, 11),
    );
  }

  static ThemeData get light {
    final colorScheme = ColorScheme.fromSeed(
      seedColor: AppColors.brand800,
      brightness: Brightness.light,
    ).copyWith(
      primary: AppColors.brand800,
      onPrimary: Colors.white,
      secondary: AppColors.accent500,
      onSecondary: Colors.white,
      surface: AppColors.surface,
      onSurface: AppColors.ink,
      error: AppColors.danger,
      outline: AppColors.lineStrong,
      outlineVariant: AppColors.line,
    );

    final base = ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: AppColors.canvas,
    );

    // خط عربي واضح. إن تعذّر تحميله (بدون إنترنت) يُستخدم خط النظام.
    final textTheme = _compact(GoogleFonts.ibmPlexSansArabicTextTheme(base.textTheme)).apply(
      bodyColor: AppColors.ink,
      displayColor: AppColors.ink,
    );

    final shape = RoundedRectangleBorder(borderRadius: BorderRadius.circular(radius));

    OutlineInputBorder inputBorder(Color color, [double width = 1]) => OutlineInputBorder(
          borderRadius: BorderRadius.circular(radius),
          borderSide: BorderSide(color: color, width: width),
        );

    return base.copyWith(
      textTheme: textTheme,
      // ترويسة كحلية بلون الشعار: تعطي التطبيق هويته بدل المظهر الأبيض الباهت
      appBarTheme: AppBarTheme(
        backgroundColor: AppColors.brand800,
        foregroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: true,
        systemOverlayStyle: SystemUiOverlayStyle.light,
        iconTheme: const IconThemeData(color: Colors.white),
        actionsIconTheme: const IconThemeData(color: Colors.white),
        titleTextStyle: textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700, color: Colors.white),
      ),
      cardTheme: CardThemeData(
        color: AppColors.surface,
        surfaceTintColor: Colors.transparent,
        elevation: 2.5,
        shadowColor: AppColors.brand800.withValues(alpha: 0.18),
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radius + 4)),
      ),
      dividerTheme: const DividerThemeData(color: AppColors.line, thickness: 1, space: 1),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surface,
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        hintStyle: textTheme.bodyMedium?.copyWith(color: AppColors.muted),
        labelStyle: textTheme.bodyMedium?.copyWith(color: AppColors.inkSoft),
        border: inputBorder(AppColors.lineStrong),
        enabledBorder: inputBorder(AppColors.lineStrong),
        focusedBorder: inputBorder(AppColors.brand600, 1.6),
        errorBorder: inputBorder(AppColors.danger),
        focusedErrorBorder: inputBorder(AppColors.danger, 1.6),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: AppColors.brand800,
          foregroundColor: Colors.white,
          minimumSize: const Size(0, 48),
          shape: shape,
          textStyle: textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.brand800,
          minimumSize: const Size(0, 48),
          side: const BorderSide(color: AppColors.lineStrong),
          shape: shape,
          textStyle: textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.accent700,
          textStyle: textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
        ),
      ),
      chipTheme: base.chipTheme.copyWith(
        backgroundColor: AppColors.brand50,
        selectedColor: AppColors.accent500,
        side: BorderSide.none,
        shape: const StadiumBorder(),
        labelStyle: textTheme.labelLarge?.copyWith(color: AppColors.brand800),
        secondaryLabelStyle: textTheme.labelLarge?.copyWith(color: Colors.white),
        checkmarkColor: Colors.white,
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: AppColors.surface,
        indicatorColor: AppColors.accent500,
        surfaceTintColor: Colors.transparent,
        shadowColor: AppColors.brand800,
        elevation: 12,
        height: 64,
        labelTextStyle: WidgetStateProperty.resolveWith(
          (states) => textTheme.labelSmall?.copyWith(
            fontWeight: states.contains(WidgetState.selected) ? FontWeight.w700 : FontWeight.w500,
            color: states.contains(WidgetState.selected) ? AppColors.accent600 : AppColors.muted,
          ),
        ),
        iconTheme: WidgetStateProperty.resolveWith(
          (states) => IconThemeData(
            color: states.contains(WidgetState.selected) ? Colors.white : AppColors.muted,
          ),
        ),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: AppColors.accent500,
        foregroundColor: Colors.white,
        elevation: 6,
      ),
      switchTheme: SwitchThemeData(
        trackColor: WidgetStateProperty.resolveWith(
          (states) => states.contains(WidgetState.selected) ? AppColors.accent500 : AppColors.brand300,
        ),
        thumbColor: const WidgetStatePropertyAll(Colors.white),
        trackOutlineColor: const WidgetStatePropertyAll(Colors.transparent),
      ),
      segmentedButtonTheme: SegmentedButtonThemeData(
        style: ButtonStyle(
          backgroundColor: WidgetStateProperty.resolveWith(
            (states) => states.contains(WidgetState.selected) ? AppColors.accent500 : Colors.white.withValues(alpha: 0.12),
          ),
          foregroundColor: const WidgetStatePropertyAll(Colors.white),
          side: WidgetStatePropertyAll(BorderSide(color: Colors.white.withValues(alpha: 0.25))),
          textStyle: WidgetStatePropertyAll(textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w700)),
        ),
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: AppColors.surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: AppColors.surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      ),
      snackBarTheme: const SnackBarThemeData(behavior: SnackBarBehavior.floating),
      progressIndicatorTheme: const ProgressIndicatorThemeData(color: AppColors.accent500),
      tabBarTheme: TabBarThemeData(
        labelColor: AppColors.brand800,
        unselectedLabelColor: AppColors.muted,
        indicatorColor: AppColors.accent500,
        labelStyle: textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
        unselectedLabelStyle: textTheme.titleSmall,
      ),
    );
  }
}
