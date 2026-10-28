import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';

import '../../core/theme/app_colors.dart';
import 'logo_pieces.dart';
import 'splash_controller.dart';

/// شاشة البداية — شعار التطبيق يتركّب، وكل جزء منه "يمثّل" معناه في المنصة.
///
/// الشعار الأصلي مقسوم إلى قطع ([LogoPieces]). القصة:
///  1. **شبكة تتصل**: الحلقة تُرسم نقطةً بنقطة، وكل عقدة عليها تنبض عند وصول الخط إليها
///     (المنصة تربط الناس بالفعاليات).
///  2. **محاضرة تبدأ**: فقاعة الفيديو تظهر وتخرج منها موجات بثّ — وتحتها كلمة "محاضرات".
///  3. **فعالية تُحجز**: التقويم يُعلَّق في مكانه، ثم علامة ✓ تُختم عليه — "فعاليات".
///  4. **مهارة تنمو**: الدرع يصعد، يظهر الشخص، وسهم النمو يرتفع — "دورات".
///  5. **الاسم**: "Smart platform" تصعد حرفاً حرفاً، ثم "for news"، ثم سطر يلخّص المحتوى.
/// بعدها يبقى الشعار حيّاً: نبضة بيانات تدور على الحلقة.
class SplashView extends StatefulWidget {
  const SplashView({super.key});

  @override
  State<SplashView> createState() => _SplashViewState();
}

class _SplashViewState extends State<SplashView> with TickerProviderStateMixin {
  final SplashController _controller = Get.find<SplashController>();

  late final AnimationController _intro = AnimationController(vsync: this, duration: SplashController.animationDuration);

  // دورة محيطة لا تتوقف: نبضة البيانات على الحلقة ونبض الهالة
  late final AnimationController _ambient = AnimationController(vsync: this, duration: const Duration(seconds: 6))
    ..repeat();

  bool _started = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_started) return;
    _started = true;

    // نحمّل كل القطع قبل بدء الحركة حتى لا تظهر قطعة متأخرة عن دورها
    Future.wait([for (final piece in LogoPieces.all) precacheImage(AssetImage(piece.asset), context)]).whenComplete(() {
      if (mounted) _intro.forward();
    });
  }

  @override
  void dispose() {
    _intro.dispose();
    _ambient.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.dark,
      child: Scaffold(
        backgroundColor: Colors.white,
        body: Stack(
          fit: StackFit.expand,
          children: [
            // نقرة في أي مكان تتخطّى الحركة
            Positioned.fill(
              child: GestureDetector(behavior: HitTestBehavior.opaque, onTap: _controller.skipIntro),
            ),
            IgnorePointer(
              child: LayoutBuilder(
              builder: (context, constraints) => AnimatedBuilder(
                animation: Listenable.merge([_intro, _ambient]),
                builder: (context, _) => _Scene(
                  size: constraints.biggest,
                  progress: _intro.value,
                  ambient: _ambient.value,
                  textTheme: theme,
                ),
              ),
              ),
            ),
            Positioned(
              left: 0,
              right: 0,
              bottom: 0,
              child: SafeArea(
                child: SizedBox(
                  height: 120,
                  child: Obx(() {
                    if (!_controller.failed.value) {
                      return FadeTransition(
                        opacity: CurvedAnimation(parent: _intro, curve: const Interval(0.95, 1)),
                        child: const Center(
                          child: SizedBox(
                            width: 24,
                            height: 24,
                            child: CircularProgressIndicator(strokeWidth: 2.4, color: AppColors.accent500),
                          ),
                        ),
                      );
                    }

                    return Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 32),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text('تعذّر الاتصال بالخادم', style: theme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
                          const SizedBox(height: 4),
                          Text(
                            'تحقق من اتصالك بالإنترنت ثم أعد المحاولة.',
                            textAlign: TextAlign.center,
                            style: theme.bodyMedium?.copyWith(color: AppColors.muted),
                          ),
                          const SizedBox(height: 10),
                          FilledButton.icon(
                            style: FilledButton.styleFrom(
                              backgroundColor: AppColors.accent500,
                              minimumSize: const Size(0, 42),
                            ),
                            onPressed: _controller.start,
                            icon: const Icon(Icons.refresh, size: 20),
                            label: const Text('إعادة المحاولة'),
                          ),
                        ],
                      ),
                    );
                  }),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// توقيت المشهد كنِسب من مدة الحركة الكلية.
abstract final class _Timeline {
  static const drawStart = 0.03;
  static const drawEnd = 0.30;

  // محاضرات
  static const video = 0.28;
  // فعاليات
  static const calendar = 0.40;
  static const check = 0.47;
  // دورات
  static const shield = 0.52;
  static const person = 0.56;
  static const arrow = 0.58;

  static const captionsEnd = 0.68;

  static const line1Start = 0.68;
  static const line1Step = 0.014;
  static const line1Duration = 0.15;

  static const line2Start = 0.82;
  static const line2End = 0.93;

  static const taglineStart = 0.90;
  static const shineStart = 0.90;
}

/// مركز ونصف قطر الدائرة الكبرى داخل لوحة الشعار.
const _circleCenter = Offset(242, 201);
const _circleRadius = 174.0;

/// مواضع عُقد الشبكة على حلقة الشعار (بإحداثيات لوحة الشعار).
const _nodes = [
  Offset(237, 27),
  Offset(330, 80),
  Offset(412, 178),
  Offset(350, 300),
  Offset(240, 375),
  Offset(135, 305),
  Offset(73, 258),
  Offset(100, 157),
  Offset(186, 62),
];

/// الكلمة التي تظهر تحت الشعار مع كل رمز، ولحظة ظهورها.
const _captions = [
  (text: 'محاضرات', start: _Timeline.video, end: _Timeline.calendar, orange: false),
  (text: 'فعاليات', start: _Timeline.calendar, end: _Timeline.shield, orange: true),
  (text: 'دورات', start: _Timeline.shield, end: _Timeline.captionsEnd, orange: false),
];

double _unit(double value) => value.clamp(0.0, 1.0).toDouble();

/// المشهد كاملاً عند لحظة [progress] من الحركة و[ambient] من الدورة المحيطة.
class _Scene extends StatelessWidget {
  const _Scene({required this.size, required this.progress, required this.ambient, required this.textTheme});

  final Size size;
  final double progress;
  final double ambient;
  final TextTheme textTheme;

  double _at(double start, double end, [Curve curve = Curves.easeOutCubic]) {
    return curve.transform(_unit((progress - start) / (end - start)));
  }

  @override
  Widget build(BuildContext context) {
    final logoWidth = math.min(size.width * 0.72, 310.0);
    final scale = logoWidth / LogoPieces.size.width;
    final logoHeight = LogoPieces.size.height * scale;
    final origin = Offset((size.width - logoWidth) / 2, size.height * 0.43 - logoHeight / 2);
    final markCenter = origin + _circleCenter * scale;
    final breath = 0.5 + 0.5 * math.sin(ambient * math.pi * 2 * 2);

    Image image(LogoPiece piece) => Image.asset(piece.asset, fit: BoxFit.fill, filterQuality: FilterQuality.medium);

    Widget place(LogoPiece piece, Widget child) {
      final rect = piece.rect;

      return Positioned(
        left: origin.dx + rect.left * scale,
        top: origin.dy + rect.top * scale,
        width: rect.width * scale,
        height: rect.height * scale,
        child: child,
      );
    }

    final draw = _at(_Timeline.drawStart, _Timeline.drawEnd, Curves.easeInOutCubic);

    // ---- 2. محاضرة تبدأ: الفقاعة تظهر بارتداد ----
    final videoPop = _at(_Timeline.video, _Timeline.video + 0.1, Curves.easeOutBack);

    // ---- 3. فعالية تُحجز: التقويم يُعلَّق ويتأرجح ثم تُختم علامة ✓ ----
    final hang = _at(_Timeline.calendar, _Timeline.calendar + 0.12, Curves.easeOutBack);
    final stamp = _at(_Timeline.check, _Timeline.check + 0.06, Curves.easeIn);

    // ---- 4. مهارة تنمو: الدرع يصعد، ثم الشخص، ثم سهم النمو يرتفع ----
    final shieldRise = _at(_Timeline.shield, _Timeline.shield + 0.1);
    final personIn = _at(_Timeline.person, _Timeline.person + 0.06, Curves.easeOutBack);
    final grow = _at(_Timeline.arrow, _Timeline.arrow + 0.09);

    // ---- 5. الاسم ----
    final lineTop = LogoPieces.line1.map((piece) => piece.rect.top).reduce(math.min);
    final lineBottom = LogoPieces.line1.map((piece) => piece.rect.bottom).reduce(math.max);
    final lineHeight = lineBottom - lineTop;

    final line2Center = (LogoPieces.line2.first.rect.left + LogoPieces.line2.last.rect.right) / 2;
    final spread = _at(_Timeline.line2Start, _Timeline.line2End);
    final line2Opacity = _at(_Timeline.line2Start, _Timeline.line2Start + 0.07, Curves.easeOut);

    Widget logo = Stack(
      clipBehavior: Clip.none,
      children: [
        place(LogoPieces.ring, ClipPath(clipper: _SweepClipper(draw), child: image(LogoPieces.ring))),

        place(
          LogoPieces.video,
          Opacity(
            opacity: _at(_Timeline.video, _Timeline.video + 0.04, Curves.easeOut),
            child: Transform.scale(scale: videoPop * (1 + breath * 0.015), child: image(LogoPieces.video)),
          ),
        ),

        place(
          LogoPieces.calendarBody,
          Opacity(
            opacity: _at(_Timeline.calendar, _Timeline.calendar + 0.04, Curves.easeOut),
            child: Transform.translate(
              offset: Offset(0, (hang - 1) * 34 * scale * 1.6),
              // يتأرجح حول حافته العليا كتقويم معلّق
              child: Transform.rotate(
                angle: (1 - hang) * -0.3,
                alignment: Alignment.topCenter,
                child: image(LogoPieces.calendarBody),
              ),
            ),
          ),
        ),
        place(
          LogoPieces.calendarCheck,
          Opacity(
            opacity: _at(_Timeline.check, _Timeline.check + 0.03, Curves.easeOut),
            // الختم: يهبط من حجم كبير ويستقر
            child: Transform.scale(scale: 2.3 - 1.3 * stamp, child: image(LogoPieces.calendarCheck)),
          ),
        ),

        place(
          LogoPieces.shieldOutline,
          Opacity(
            opacity: _at(_Timeline.shield, _Timeline.shield + 0.05, Curves.easeOut),
            child: Transform.translate(
              offset: Offset(0, (1 - shieldRise) * 26 * scale * 1.6),
              child: image(LogoPieces.shieldOutline),
            ),
          ),
        ),
        place(
          LogoPieces.shieldPerson,
          Opacity(
            opacity: _at(_Timeline.person, _Timeline.person + 0.03, Curves.easeOut),
            child: Transform.scale(scale: personIn, alignment: Alignment.bottomCenter, child: image(LogoPieces.shieldPerson)),
          ),
        ),
        // سهم النمو يُكشف من أسفله إلى أعلاه
        place(
          LogoPieces.shieldArrow,
          ClipRect(
            clipper: _BottomRevealClipper(grow),
            child: Transform.translate(
              offset: Offset(0, (1 - grow) * 10 * scale * 1.6),
              child: image(LogoPieces.shieldArrow),
            ),
          ),
        ),

        // السطر الأول: كل حرف يصعد من خلف خط السطر (كشف بالقناع)
        for (var index = 0; index < LogoPieces.line1.length; index++)
          _maskedLetter(LogoPieces.line1[index], index, origin, scale, lineTop, lineBottom, lineHeight, image),

        // السطر الثاني: الحروف تتباعد من المركز إلى أماكنها
        for (final piece in LogoPieces.line2)
          place(
            piece,
            Opacity(
              opacity: line2Opacity,
              child: Transform.translate(
                offset: Offset((line2Center - piece.rect.center.dx) * 0.5 * (1 - spread) * scale, 0),
                child: image(piece),
              ),
            ),
          ),
      ],
    );

    // لمعة ضوء تمر على الشعار بعد اكتماله
    final shine = _at(_Timeline.shineStart, 1, Curves.easeInOut);
    if (shine > 0 && shine < 1) {
      final position = -1.5 + shine * 3;

      logo = ShaderMask(
        blendMode: BlendMode.srcATop,
        shaderCallback: (bounds) => LinearGradient(
          begin: Alignment(position - 0.35, -0.25),
          end: Alignment(position + 0.35, 0.25),
          colors: const [Color(0x00FFFFFF), Color(0xCCFFFFFF), Color(0x00FFFFFF)],
        ).createShader(bounds),
        child: logo,
      );
    }

    final haloSize = _circleRadius * 3 * scale * (1 + breath * 0.04);
    final captionTop = origin.dy + lineTop * scale;
    final captionHeight = lineHeight * scale;

    return Stack(
      fit: StackFit.expand,
      children: [
        // خلفية تقنية هادئة: شبكة نقاط + هالة لونية خلف الرمز
        Positioned.fill(
          child: CustomPaint(painter: _GridPainter(center: markCenter, fade: _at(0, 0.12, Curves.easeOut))),
        ),
        Positioned(
          left: markCenter.dx - haloSize / 2,
          top: markCenter.dy - haloSize / 2,
          width: haloSize,
          height: haloSize,
          child: Opacity(
            opacity: _at(0.1, 0.5, Curves.easeOut) * (0.75 + breath * 0.25),
            child: const DecoratedBox(
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [Color(0x38EA7A26), Color(0x1F2A5B88), Color(0x00FFFFFF)],
                  stops: [0, 0.55, 1],
                ),
              ),
            ),
          ),
        ),
        Positioned.fill(child: logo),

        // مؤثرات المعنى فوق الشعار: نبض العُقد، موجات البث، وميض الختم، نبضة البيانات
        Positioned.fill(
          child: CustomPaint(
            painter: _MeaningPainter(
              progress: progress,
              ambient: ambient,
              origin: origin,
              scale: scale,
              draw: draw,
            ),
          ),
        ),

        // الكلمة المرافقة لكل رمز، في مكان اسم التطبيق قبل ظهوره
        for (final caption in _captions)
          Positioned(
            left: 0,
            right: 0,
            top: captionTop,
            height: captionHeight,
            child: _caption(caption.text, caption.start, caption.end, caption.orange),
          ),

        // سطر يلخّص محتوى المنصة تحت الاسم
        Positioned(
          left: 16,
          right: 16,
          top: origin.dy + logoHeight + 14,
          child: Opacity(
            opacity: _at(_Timeline.taglineStart, 1, Curves.easeOut),
            child: Transform.translate(
              offset: Offset(0, (1 - _at(_Timeline.taglineStart, 1)) * 10),
              child: Text(
                'محاضرات · معارض · فعاليات · دورات',
                textAlign: TextAlign.center,
                textDirection: TextDirection.rtl,
                style: textTheme.bodyMedium?.copyWith(color: AppColors.muted, fontWeight: FontWeight.w600, letterSpacing: 0.4),
              ),
            ),
          ),
        ),
      ],
    );
  }

  /// كلمة تصعد وتظهر ثم تواصل الصعود وتختفي.
  Widget _caption(String text, double start, double end, bool orange) {
    final enter = _at(start + 0.01, start + 0.06);
    final leave = _at(end - 0.035, end + 0.005, Curves.easeIn);
    final opacity = enter * (1 - leave);
    if (opacity <= 0) return const SizedBox.shrink();

    return Opacity(
      opacity: opacity,
      child: Transform.translate(
        offset: Offset(0, (1 - enter) * 14 - leave * 14),
        child: Center(
          child: Text(
            text,
            textDirection: TextDirection.rtl,
            style: textTheme.headlineSmall?.copyWith(
              fontWeight: FontWeight.w700,
              color: orange ? AppColors.accent500 : AppColors.brand800,
            ),
          ),
        ),
      ),
    );
  }

  Widget _maskedLetter(
    LogoPiece piece,
    int index,
    Offset origin,
    double scale,
    double lineTop,
    double lineBottom,
    double lineHeight,
    Image Function(LogoPiece) image,
  ) {
    final start = _Timeline.line1Start + index * _Timeline.line1Step;
    final rise = _at(start, start + _Timeline.line1Duration);

    return Positioned(
      left: origin.dx + piece.rect.left * scale,
      top: origin.dy + lineTop * scale,
      width: piece.rect.width * scale,
      height: lineHeight * scale,
      // القناع بارتفاع السطر كله: الحرف يدخل من أسفله وكأنه يخرج من خلف حافة
      child: ClipRect(
        child: Transform.translate(
          offset: Offset(0, (1 - rise) * lineHeight * scale * 1.05),
          child: Padding(
            padding: EdgeInsets.only(
              top: (piece.rect.top - lineTop) * scale,
              bottom: (lineBottom - piece.rect.bottom) * scale,
            ),
            child: image(piece),
          ),
        ),
      ),
    );
  }
}

/// شبكة نقاط خفيفة توحي بالطابع التقني، تتلاشى كلما ابتعدت عن الشعار.
class _GridPainter extends CustomPainter {
  _GridPainter({required this.center, required this.fade});

  final Offset center;
  final double fade;

  @override
  void paint(Canvas canvas, Size size) {
    if (fade <= 0) return;

    const gap = 22.0;
    final reach = size.shortestSide * 0.95;
    final paint = Paint();

    for (var x = (center.dx % gap) - gap; x < size.width + gap; x += gap) {
      for (var y = (center.dy % gap) - gap; y < size.height + gap; y += gap) {
        final distance = (Offset(x, y) - center).distance;
        final alpha = (1 - distance / reach) * 0.42 * fade;
        if (alpha <= 0.01) continue;

        paint.color = AppColors.brand300.withValues(alpha: alpha);
        canvas.drawCircle(Offset(x, y), 1.3, paint);
      }
    }
  }

  @override
  bool shouldRepaint(_GridPainter oldDelegate) => oldDelegate.fade != fade || oldDelegate.center != center;
}

/// المؤثرات التي تشرح معنى كل جزء من الشعار.
class _MeaningPainter extends CustomPainter {
  _MeaningPainter({
    required this.progress,
    required this.ambient,
    required this.origin,
    required this.scale,
    required this.draw,
  });

  final double progress;
  final double ambient;
  final Offset origin;
  final double scale;
  final double draw;

  Offset _point(Offset logoPoint) => origin + logoPoint * scale;

  /// حلقة تتسع وتتلاشى (نبضة) حول [center] عند اللحظة [start].
  void _ping(Canvas canvas, Offset center, double start, {double duration = 0.09, double from = 4, double to = 20, Color? color}) {
    final t = (progress - start) / duration;
    if (t <= 0 || t >= 1) return;

    final eased = Curves.easeOut.transform(t);
    canvas.drawCircle(
      center,
      (from + (to - from) * eased) * scale * 1.6,
      Paint()
        ..style = PaintingStyle.stroke
        ..strokeWidth = 2.6 * (1 - eased) + 0.4
        ..color = (color ?? AppColors.accent500).withValues(alpha: 0.85 * (1 - eased)),
    );
  }

  @override
  void paint(Canvas canvas, Size size) {
    final center = _point(_circleCenter);
    final radius = _circleRadius * scale;

    // ---- 1. رأس الرسم + نبض كل عقدة عند وصول الخط إليها (الشبكة تتصل) ----
    if (draw > 0 && draw < 1) {
      final head = -math.pi / 2 + math.pi * 2 * draw;
      final fade = 1 - _unit((draw - 0.93) / 0.07);
      final headPoint = center + Offset(math.cos(head), math.sin(head)) * radius;

      canvas
        ..drawCircle(
          headPoint,
          11,
          Paint()
            ..color = AppColors.accent500.withValues(alpha: 0.55 * fade)
            ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 9),
        )
        ..drawCircle(headPoint, 4.2, Paint()..color = AppColors.accent500.withValues(alpha: fade));
    }

    const drawSpan = _Timeline.drawEnd - _Timeline.drawStart;
    for (final node in _nodes) {
      final offset = node - _circleCenter;
      // موضع العقدة على محيط الدائرة كنسبة من الدورة (تبدأ من الأعلى باتجاه عقارب الساعة)
      final turn = ((math.atan2(offset.dy, offset.dx) + math.pi / 2) / (math.pi * 2)) % 1;
      // لحظة وصول الرسم (المنحنى easeInOutCubic) إلى هذه النسبة
      final reached = _Timeline.drawStart + drawSpan * _inverseEaseInOutCubic(turn);

      _ping(canvas, _point(node), reached, to: 17);
    }

    // ---- 2. موجات البث تخرج من فقاعة المحاضرة ----
    final videoCenter = _point(LogoPieces.video.rect.center);
    for (var wave = 0; wave < 3; wave++) {
      _ping(
        canvas,
        videoCenter,
        _Timeline.video + 0.06 + wave * 0.03,
        duration: 0.1,
        from: 30,
        to: 62,
        color: AppColors.brand600,
      );
    }

    // ---- 3. وميض الختم لحظة هبوط علامة ✓ ----
    final checkCenter = _point(LogoPieces.calendarCheck.rect.center);
    _ping(canvas, checkCenter, _Timeline.check + 0.055, duration: 0.08, from: 16, to: 40);

    // ---- 4. شرطات صغيرة فوق السهم لحظة اكتمال النمو ----
    final tip = _point(LogoPieces.shieldArrow.rect.topCenter);
    final burst = (progress - (_Timeline.arrow + 0.08)) / 0.07;
    if (burst > 0 && burst < 1) {
      final eased = Curves.easeOut.transform(burst);
      final paint = Paint()
        ..strokeCap = StrokeCap.round
        ..strokeWidth = 2.2
        ..color = AppColors.accent500.withValues(alpha: 1 - eased);

      for (final angle in const [-2.2, -1.57, -0.94]) {
        final direction = Offset(math.cos(angle), math.sin(angle));
        canvas.drawLine(
          tip + direction * (6 + 10 * eased) * scale * 1.6,
          tip + direction * (11 + 16 * eased) * scale * 1.6,
          paint,
        );
      }
    }

    // ---- بعد الاكتمال: نبضة بيانات تدور على الحلقة ----
    final alive = _unit((progress - 0.92) / 0.08);
    if (alive > 0) {
      final angle = -math.pi / 2 + ambient * math.pi * 2;

      for (var index = 0; index < 10; index++) {
        final near = 1 - index / 10;
        final point = center + Offset(math.cos(angle - index * 0.045), math.sin(angle - index * 0.045)) * radius;

        canvas.drawCircle(
          point,
          (1.2 + 2.6 * near) * math.max(scale * 1.6, 0.8),
          Paint()..color = AppColors.accent500.withValues(alpha: 0.9 * near * near * alive),
        );
      }
    }
  }

  /// معكوس المنحنى easeInOutCubic: يعطي الزمن الذي تبلغ عنده الحركة النسبة [value].
  static double _inverseEaseInOutCubic(double value) {
    var low = 0.0;
    var high = 1.0;

    for (var step = 0; step < 24; step++) {
      final middle = (low + high) / 2;
      if (Curves.easeInOutCubic.transform(middle) < value) {
        low = middle;
      } else {
        high = middle;
      }
    }

    return (low + high) / 2;
  }

  @override
  bool shouldRepaint(_MeaningPainter oldDelegate) => true;
}

/// يكشف الصورة كقطاع دائري يتسع من الأعلى باتجاه عقارب الساعة (رسم الحلقة).
class _SweepClipper extends CustomClipper<Path> {
  const _SweepClipper(this.fraction);

  final double fraction;

  @override
  Path getClip(Size size) {
    if (fraction >= 1) return Path()..addRect((Offset.zero & size).inflate(40));
    if (fraction <= 0) return Path();

    // مركز القطاع هو مركز الدائرة الكبرى لا مركز الصورة، ليطابق مسار رأس الرسم
    final ringRect = LogoPieces.ring.rect;
    final center = Offset(
      (_circleCenter.dx - ringRect.left) / ringRect.width * size.width,
      (_circleCenter.dy - ringRect.top) / ringRect.height * size.height,
    );

    return Path()
      ..moveTo(center.dx, center.dy)
      ..arcTo(Rect.fromCircle(center: center, radius: size.longestSide), -math.pi / 2, math.pi * 2 * fraction, false)
      ..close();
  }

  @override
  bool shouldReclip(_SweepClipper oldClipper) => oldClipper.fraction != fraction;
}

/// يكشف الصورة من أسفلها إلى أعلاها (نمو السهم).
class _BottomRevealClipper extends CustomClipper<Rect> {
  const _BottomRevealClipper(this.fraction);

  final double fraction;

  @override
  Rect getClip(Size size) => Rect.fromLTRB(0, size.height * (1 - fraction), size.width, size.height);

  @override
  bool shouldReclip(_BottomRevealClipper oldClipper) => oldClipper.fraction != fraction;
}
