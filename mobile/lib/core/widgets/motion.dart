import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// حركات الواجهة المشتركة. كلها مبنية على أدوات Flutter نفسها بدون مكتبات إضافية.

/// يُظهر العنصر بانزلاق خفيف للأعلى مع تلاشٍ عند أول ظهور له.
/// [index] يؤخّر العناصر المتتالية قليلاً فتدخل القائمة بالتتابع.
class FadeSlideIn extends StatefulWidget {
  const FadeSlideIn({super.key, required this.child, this.index = 0, this.offset = 26});

  final Widget child;
  final int index;
  final double offset;

  @override
  State<FadeSlideIn> createState() => _FadeSlideInState();
}

class _FadeSlideInState extends State<FadeSlideIn> with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 420),
  );
  late final Animation<double> _curve = CurvedAnimation(parent: _controller, curve: Curves.easeOutCubic);

  @override
  void initState() {
    super.initState();

    // التأخير للعناصر الأولى فقط: العناصر التي تظهر أثناء التمرير تدخل فوراً
    final delay = Duration(milliseconds: 60 * widget.index.clamp(0, 5).toInt());
    Future<void>.delayed(delay, () {
      if (mounted) _controller.forward();
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    // المستخدم عطّل الحركات من إعدادات النظام: نعرض العنصر مباشرة
    if (MediaQuery.disableAnimationsOf(context)) return widget.child;

    return AnimatedBuilder(
      animation: _curve,
      child: widget.child,
      builder: (context, child) => Opacity(
        opacity: _curve.value,
        child: Transform.translate(offset: Offset(0, (1 - _curve.value) * widget.offset), child: child),
      ),
    );
  }
}

/// يصغّر العنصر قليلاً أثناء الضغط عليه (إحساس الزر الحقيقي).
class Pressable extends StatefulWidget {
  const Pressable({super.key, required this.child, this.scale = 0.975});

  final Widget child;
  final double scale;

  @override
  State<Pressable> createState() => _PressableState();
}

class _PressableState extends State<Pressable> {
  bool _down = false;

  void _set(bool value) {
    if (_down != value) setState(() => _down = value);
  }

  @override
  Widget build(BuildContext context) {
    // Listener لا يستهلك اللمسة: النقر يصل للعنصر الداخلي كالمعتاد
    return Listener(
      onPointerDown: (_) => _set(true),
      onPointerUp: (_) => _set(false),
      onPointerCancel: (_) => _set(false),
      child: AnimatedScale(
        scale: _down ? widget.scale : 1,
        duration: const Duration(milliseconds: 130),
        curve: Curves.easeOut,
        child: widget.child,
      ),
    );
  }
}

/// يبدّل بين عنصرين بحركة "قفزة" مرنة (تغيّر التفاعل، الحفظ ...).
/// يجب أن يحمل كل عنصر مفتاحاً مختلفاً حتى تُلاحَظ التبديلة.
class PopSwitcher extends StatelessWidget {
  const PopSwitcher({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return AnimatedSwitcher(
      duration: const Duration(milliseconds: 320),
      switchInCurve: Curves.elasticOut,
      switchOutCurve: Curves.easeIn,
      transitionBuilder: (child, animation) => ScaleTransition(scale: animation, child: child),
      child: child,
    );
  }
}

/// لمعان متحرك فوق عناصر التحميل الوهمية (Skeleton).
class Shimmer extends StatefulWidget {
  const Shimmer({super.key, required this.child});

  final Widget child;

  @override
  State<Shimmer> createState() => _ShimmerState();
}

class _ShimmerState extends State<Shimmer> with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1300),
  )..repeat();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _controller,
      child: widget.child,
      builder: (context, child) {
        final slide = _controller.value * 3 - 1.5;

        return ShaderMask(
          blendMode: BlendMode.srcATop,
          shaderCallback: (bounds) => LinearGradient(
            begin: Alignment(slide - 1, 0),
            end: Alignment(slide + 1, 0),
            colors: const [AppColors.brand100, Color(0xFFF7FAFD), AppColors.brand100],
          ).createShader(bounds),
          child: child,
        );
      },
    );
  }
}

/// قائمة بطاقات وهمية تُعرض أثناء تحميل أول صفحة بدل مؤشر الدوران.
class SkeletonList extends StatelessWidget {
  const SkeletonList({super.key, this.count = 3});

  final int count;

  @override
  Widget build(BuildContext context) {
    return Shimmer(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          children: [
            for (var index = 0; index < count; index++)
              Container(
                margin: const EdgeInsets.only(bottom: 12),
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18)),
                child: const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        _Bone(width: 42, height: 42, radius: 21),
                        SizedBox(width: 10),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [_Bone(width: 130, height: 12), SizedBox(height: 8), _Bone(width: 80, height: 10)],
                        ),
                      ],
                    ),
                    SizedBox(height: 16),
                    _Bone(width: double.infinity, height: 14),
                    SizedBox(height: 10),
                    _Bone(width: 220, height: 12),
                    SizedBox(height: 14),
                    _Bone(width: double.infinity, height: 130, radius: 14),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _Bone extends StatelessWidget {
  const _Bone({required this.width, required this.height, this.radius = 8});

  final double width;
  final double height;
  final double radius;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(color: AppColors.brand100, borderRadius: BorderRadius.circular(radius)),
    );
  }
}

/// يعرض تبويباً واحداً من عدة تبويبات مع تلاشٍ عند التبديل، ويُبقي حالة كل تبويب.
class FadeIndexedStack extends StatefulWidget {
  const FadeIndexedStack({super.key, required this.index, required this.children});

  final int index;
  final List<Widget> children;

  @override
  State<FadeIndexedStack> createState() => _FadeIndexedStackState();
}

class _FadeIndexedStackState extends State<FadeIndexedStack> with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 260),
    value: 1,
  );
  late final Animation<double> _curve = CurvedAnimation(parent: _controller, curve: Curves.easeOut);
  late final Animation<Offset> _slide = Tween<Offset>(begin: const Offset(0, 0.015), end: Offset.zero).animate(_curve);

  @override
  void didUpdateWidget(FadeIndexedStack oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.index != widget.index) _controller.forward(from: 0);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: _curve,
      child: SlideTransition(
        position: _slide,
        child: IndexedStack(index: widget.index, children: widget.children),
      ),
    );
  }
}

/// رقم يعدّ تصاعدياً إلى قيمته عند أول ظهور وعند كل تغيّر (إحصاءات الملف الشخصي).
class CountUpText extends StatelessWidget {
  const CountUpText({super.key, required this.value, required this.format, this.style});

  final int value;
  final String Function(int value) format;
  final TextStyle? style;

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.disableAnimationsOf(context)) return Text(format(value), style: style);

    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: value.toDouble()),
      duration: const Duration(milliseconds: 900),
      curve: Curves.easeOutCubic,
      builder: (context, current, _) => Text(format(current.round()), style: style),
    );
  }
}

/// نص يتبدّل بانزلاق عمودي قصير عند تغيّره (عدّادات التفاعل والتعليقات).
class RollingText extends StatelessWidget {
  const RollingText(this.text, {super.key, this.style});

  final String text;
  final TextStyle? style;

  @override
  Widget build(BuildContext context) {
    return AnimatedSwitcher(
      duration: const Duration(milliseconds: 260),
      switchInCurve: Curves.easeOutCubic,
      switchOutCurve: Curves.easeInCubic,
      transitionBuilder: (child, animation) => ClipRect(
        child: SlideTransition(
          position: Tween<Offset>(begin: const Offset(0, 0.7), end: Offset.zero).animate(animation),
          child: FadeTransition(opacity: animation, child: child),
        ),
      ),
      child: Text(text, key: ValueKey(text), style: style),
    );
  }
}

/// يُدخل العنصر بقفزة مرنة من حجم أصغر (أيقونة التبويب المختار، زر الإضافة، أيقونات الحالات الفارغة).
class ScaleIn extends StatelessWidget {
  const ScaleIn({super.key, required this.child, this.from = 0.6, this.duration = const Duration(milliseconds: 520)});

  final Widget child;
  final double from;
  final Duration duration;

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.disableAnimationsOf(context)) return child;

    return TweenAnimationBuilder<double>(
      tween: Tween(begin: from, end: 1),
      duration: duration,
      curve: Curves.elasticOut,
      child: child,
      builder: (context, scale, child) => Transform.scale(scale: scale, child: child),
    );
  }
}

/// طفو هادئ لا يتوقف (أيقونة الحالة الفارغة): يوحي بأن الشاشة حيّة حتى وهي بلا محتوى.
class Floating extends StatefulWidget {
  const Floating({super.key, required this.child, this.distance = 6});

  final Widget child;
  final double distance;

  @override
  State<Floating> createState() => _FloatingState();
}

class _FloatingState extends State<Floating> with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(vsync: this, duration: const Duration(milliseconds: 2600))
    ..repeat(reverse: true);
  late final Animation<double> _curve = CurvedAnimation(parent: _controller, curve: Curves.easeInOut);

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.disableAnimationsOf(context)) return widget.child;

    return AnimatedBuilder(
      animation: _curve,
      child: widget.child,
      builder: (context, child) => Transform.translate(offset: Offset(0, -widget.distance * _curve.value), child: child),
    );
  }
}
