import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/session/session_service.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/navigation.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/dialogs.dart';
import '../../domain/post.dart';
import '../post_actions.dart';

/// النوافذ السفلية الخاصة بالمنشور: المشاركة والإبلاغ.
abstract final class PostSheets {
  /// مشاركة المنشور مع نص اختياري.
  static Future<void> share(Post post) async {
    if (!Get.find<SessionService>().requireLogin('سجّل الدخول لتشارك المنشورات مع متابعيك.')) return;

    await Get.bottomSheet<void>(_ShareSheet(post: post), isScrollControlled: true);
  }

  /// الإبلاغ عن منشور.
  static Future<void> reportPost(Post post) {
    return _report(type: 'post', id: post.id, subject: post.title);
  }

  /// الإبلاغ عن حساب.
  static Future<void> reportAccount({required int accountId, required String name}) {
    return _report(type: 'account', id: accountId, subject: name);
  }

  static Future<void> _report({required String type, required int id, required String subject}) async {
    if (!Get.find<SessionService>().requireLogin('سجّل الدخول لتتمكن من الإبلاغ.')) return;

    await Get.bottomSheet<void>(_ReportSheet(type: type, id: id, subject: subject), isScrollControlled: true);
  }
}

class _ShareSheet extends StatefulWidget {
  const _ShareSheet({required this.post});

  final Post post;

  @override
  State<_ShareSheet> createState() => _ShareSheetState();
}

class _ShareSheetState extends State<_ShareSheet> {
  final _text = TextEditingController();
  bool _sending = false;

  @override
  void dispose() {
    _text.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _sending = true);

    final text = _text.text.trim();
    final shared = await Get.find<PostActions>().share(widget.post, text: text.isEmpty ? null : text);

    if (!mounted) return;
    setState(() => _sending = false);
    if (shared) AppNav.back<void>();
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        // يرفع المحتوى فوق لوحة المفاتيح
        padding: EdgeInsets.fromLTRB(18, 0, 18, 18 + MediaQuery.of(context).viewInsets.bottom),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            SheetHeader(title: 'مشاركة المنشور', subtitle: widget.post.title),
            TextField(
              controller: _text,
              maxLines: 4,
              maxLength: 1000,
              decoration: const InputDecoration(hintText: 'أضف تعليقاً على المشاركة (اختياري)…'),
            ),
            const SizedBox(height: 8),
            AppButton(label: 'مشاركة', icon: Icons.repeat, loading: _sending, onPressed: _submit),
          ],
        ),
      ),
    );
  }
}

class _ReportSheet extends StatefulWidget {
  const _ReportSheet({required this.type, required this.id, required this.subject});

  final String type;
  final int id;
  final String subject;

  @override
  State<_ReportSheet> createState() => _ReportSheetState();
}

class _ReportSheetState extends State<_ReportSheet> {
  static const _postReasons = ['محتوى مضلل أو كاذب', 'إعلان مزعج', 'محتوى مسيء', 'انتهاك حقوق ملكية', 'سبب آخر'];
  static const _accountReasons = ['انتحال شخصية', 'تعليقات مسيئة', 'حساب وهمي', 'نشر محتوى مخالف', 'سبب آخر'];

  final _other = TextEditingController();
  String? _selected;
  bool _sending = false;

  List<String> get _reasons => widget.type == 'post' ? _postReasons : _accountReasons;

  bool get _isOther => _selected == 'سبب آخر';

  String get _reason => _isOther ? _other.text.trim() : (_selected ?? '');

  @override
  void dispose() {
    _other.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_reason.length < 3) return;

    setState(() => _sending = true);

    final sent = await Get.find<PostActions>().report(type: widget.type, id: widget.id, reason: _reason);

    if (!mounted) return;
    setState(() => _sending = false);
    if (sent) AppNav.back<void>();
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: SingleChildScrollView(
        padding: EdgeInsets.fromLTRB(18, 0, 18, 18 + MediaQuery.of(context).viewInsets.bottom),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            SheetHeader(
              title: widget.type == 'post' ? 'الإبلاغ عن المنشور' : 'الإبلاغ عن الحساب',
              subtitle: widget.subject,
            ),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final reason in _reasons)
                  ChoiceChip(
                    label: Text(reason),
                    selected: _selected == reason,
                    onSelected: (_) => setState(() => _selected = reason),
                  ),
              ],
            ),
            if (_isOther) ...[
              const SizedBox(height: 14),
              TextField(
                controller: _other,
                maxLines: 3,
                maxLength: 255,
                autofocus: true,
                decoration: const InputDecoration(hintText: 'اكتب سبب البلاغ…'),
                onChanged: (_) => setState(() {}),
              ),
            ],
            const SizedBox(height: 16),
            AppButton(
              label: 'إرسال البلاغ',
              icon: Icons.flag_outlined,
              style: AppButtonStyle.danger,
              loading: _sending,
              onPressed: _reason.length >= 3 ? _submit : null,
            ),
            const SizedBox(height: 8),
            Text(
              'تراجع الإدارة البلاغات وتتخذ الإجراء المناسب.',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.muted),
            ),
          ],
        ),
      ),
    );
  }
}
