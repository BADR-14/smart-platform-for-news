import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// صورة الحساب. إذا لم توجد صورة يُعرض أول حرف من الاسم.
class AppAvatar extends StatelessWidget {
  const AppAvatar({super.key, required this.name, this.url, this.radius = 20});

  final String name;
  final String? url;
  final double radius;

  @override
  Widget build(BuildContext context) {
    final trimmed = name.trim();
    final initial = trimmed.isEmpty ? '؟' : trimmed.characters.first.toUpperCase();

    final fallback = CircleAvatar(
      radius: radius,
      backgroundColor: AppColors.brand100,
      child: Text(
        initial,
        style: TextStyle(color: AppColors.brand800, fontWeight: FontWeight.w700, fontSize: radius * 0.8),
      ),
    );

    if (url == null || url!.isEmpty) return fallback;

    return CachedNetworkImage(
      imageUrl: url!,
      imageBuilder: (context, image) => CircleAvatar(radius: radius, backgroundImage: image),
      placeholder: (context, _) => fallback,
      errorWidget: (context, _, __) => fallback,
    );
  }
}

/// شارة التوثيق بجانب اسم الحساب الموثّق.
class VerifiedBadge extends StatelessWidget {
  const VerifiedBadge({super.key, this.size = 16});

  final double size;

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: 'حساب موثّق',
      child: Icon(Icons.verified, size: size, color: AppColors.accent500),
    );
  }
}

/// صورة من الشبكة مع حالة تحميل وحالة فشل.
class AppNetworkImage extends StatelessWidget {
  const AppNetworkImage({super.key, required this.url, this.fit = BoxFit.cover, this.height, this.width});

  final String url;
  final BoxFit fit;
  final double? height;
  final double? width;

  @override
  Widget build(BuildContext context) {
    return CachedNetworkImage(
      imageUrl: url,
      fit: fit,
      height: height,
      width: width,
      placeholder: (context, _) => Container(color: AppColors.brand50, height: height, width: width),
      errorWidget: (context, _, __) => Container(
        color: AppColors.brand50,
        height: height,
        width: width,
        alignment: Alignment.center,
        child: const Icon(Icons.broken_image_outlined, color: AppColors.brand300),
      ),
    );
  }
}
