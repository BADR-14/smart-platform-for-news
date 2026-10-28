import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/scheduler.dart';
import 'package:get/get.dart';

import '../../../../app/routes.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/motion.dart';
import '../../domain/post.dart';

/// شريط المنشورات المميزة أعلى الصفحة الرئيسية — حلقة تدور لوحدها.
///
/// البطاقات تنساب باستمرار وبسرعة ثابتة دون أي تدخل من المستخدم، وبعد آخر بطاقة
/// تعود الأولى فوراً (حلقة بلا نهاية). إذا لمس المستخدم الشريط يتوقف الدوران
/// ليتمكن من السحب بيده أو فتح منشور، ثم يستأنف تلقائياً بعد رفع إصبعه.
class FeaturedCarousel extends StatefulWidget {
  const FeaturedCarousel({super.key, required this.posts});

  final List<Post> posts;

  @override
  State<FeaturedCarousel> createState() => _FeaturedCarouselState();
}

class _FeaturedCarouselState extends State<FeaturedCarousel> with SingleTickerProviderStateMixin {
  /// سرعة الدوران (بكسل منطقي في الثانية).
  static const _speed = 42.0;

  static const _cardWidth = 272.0;
  static const _gap = 12.0;
  static const _extent = _cardWidth + _gap;

  /// مهلة استئناف الدوران بعد رفع الإصبع (حتى تنتهي حركة السحب).
  static const _resumeDelay = Duration(milliseconds: 1400);

  // نبدأ من موضع بعيد حتى يمكن السحب بالاتجاهين "إلى ما لا نهاية"
  late final ScrollController _scroll = ScrollController(
    initialScrollOffset: _loops ? _extent * widget.posts.length * 1000 : 0,
  );

  late final Ticker _ticker = createTicker(_onTick);
  Duration _lastTick = Duration.zero;
  bool _paused = false;
  Timer? _resumeTimer;

  bool get _loops => widget.posts.length > 1;

  @override
  void initState() {
    super.initState();
    if (_loops) _ticker.start();
  }

  @override
  void didUpdateWidget(FeaturedCarousel oldWidget) {
    super.didUpdateWidget(oldWidget);

    // تغيّرت قائمة المميز (تحديث الصفحة): نشغّل الدوران أو نوقفه حسب العدد
    if (_loops && !_ticker.isActive) {
      _lastTick = Duration.zero;
      _ticker.start();
    } else if (!_loops && _ticker.isActive) {
      _ticker.stop();
    }
  }

  @override
  void dispose() {
    _resumeTimer?.cancel();
    _ticker.dispose();
    _scroll.dispose();
    super.dispose();
  }

  /// يُستدعى مع كل إطار: يحرّك الشريط بمقدار يتناسب مع الزمن المنقضي (سرعة ثابتة على كل الأجهزة).
  void _onTick(Duration elapsed) {
    final delta = (elapsed - _lastTick).inMicroseconds / Duration.microsecondsPerSecond;
    _lastTick = elapsed;

    if (_paused || !_scroll.hasClients || delta <= 0 || delta > 0.25) return;

    _scroll.jumpTo(_scroll.offset + _speed * delta);
  }

  void _pause() {
    _resumeTimer?.cancel();
    _paused = true;
  }

  void _resumeSoon() {
    _resumeTimer?.cancel();
    _resumeTimer = Timer(_resumeDelay, () => _paused = false);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;
    final count = widget.posts.length;
    if (count == 0) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: const BoxDecoration(gradient: AppColors.accentGradient, shape: BoxShape.circle),
              child: const Icon(Icons.auto_awesome, size: 14, color: Colors.white),
            ),
            const SizedBox(width: 8),
            Text('منشورات مميزة', style: theme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
          ],
        ),
        const SizedBox(height: 12),
        SizedBox(
          height: 214,
          // Listener لا يستهلك اللمسة: السحب والنقر يعملان، ونحن فقط نوقف الدوران أثناءهما
          child: Listener(
            onPointerDown: (_) => _pause(),
            onPointerUp: (_) => _resumeSoon(),
            onPointerCancel: (_) => _resumeSoon(),
            child: ListView.builder(
              controller: _scroll,
              scrollDirection: Axis.horizontal,
              clipBehavior: Clip.none,
              // عرض ثابت لكل عنصر: القفز لموضع بعيد لا يكلّف شيئاً
              itemExtent: _extent,
              // بدون itemCount: القائمة تتكرر بلا نهاية (الحلقة)
              itemCount: _loops ? null : count,
              itemBuilder: (context, index) => Padding(
                padding: const EdgeInsetsDirectional.only(end: _gap, top: 4, bottom: 4),
                child: _FeaturedCard(post: widget.posts[index % count]),
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _FeaturedCard extends StatelessWidget {
  const _FeaturedCard({required this.post});

  final Post post;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;
    final author = post.author;

    return Pressable(
      child: Material(
        color: AppColors.brand800,
        elevation: 5,
        shadowColor: AppColors.brand800.withValues(alpha: 0.45),
        borderRadius: BorderRadius.circular(20),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: () => Get.toNamed(Routes.postDetails, arguments: post.id),
          // الصورة في الأعلى والعنوان في لوح كحلي تحتها: النص لا يُكتب فوق صورة قد تحمل كتابة
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Expanded(
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    if (post.coverUrl != null)
                      AppNetworkImage(url: post.coverUrl!)
                    else
                      const DecoratedBox(decoration: BoxDecoration(gradient: AppColors.brandGradient)),
                    PositionedDirectional(
                      top: 10,
                      start: 10,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          gradient: AppColors.accentGradient,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.auto_awesome, size: 13, color: Colors.white),
                            const SizedBox(width: 5),
                            Text(
                              post.postType.label,
                              style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w700),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(14, 10, 14, 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      post.title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: theme.titleSmall?.copyWith(color: Colors.white, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        AppAvatar(name: author?.name ?? '', url: author?.avatar, radius: 11),
                        const SizedBox(width: 8),
                        Flexible(
                          child: Text(
                            author?.name ?? '',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: theme.bodySmall?.copyWith(color: Colors.white.withValues(alpha: 0.9)),
                          ),
                        ),
                        if (author?.isVerified ?? false) ...[const SizedBox(width: 4), const VerifiedBadge(size: 14)],
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
