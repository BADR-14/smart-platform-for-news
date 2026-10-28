import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/widgets/app_button.dart';
import '../controllers/auth_controller.dart';
import '../widgets/auth_scaffold.dart';

/// تأكيد البريد الإلكتروني برمز من 6 أرقام بعد إنشاء الحساب.
/// البريد يصل كـ argument من شاشة التسجيل أو الدخول.
class OtpView extends StatefulWidget {
  const OtpView({super.key});

  @override
  State<OtpView> createState() => _OtpViewState();
}

class _OtpViewState extends State<OtpView> {
  static const _resendAfterSeconds = 60;

  final _code = TextEditingController();
  late final String _email = (Get.arguments as String?) ?? '';

  Timer? _timer;
  int _secondsLeft = _resendAfterSeconds;

  AuthController get _controller => Get.find<AuthController>();

  @override
  void initState() {
    super.initState();
    _startCountdown();
  }

  @override
  void dispose() {
    _timer?.cancel();
    _code.dispose();
    super.dispose();
  }

  void _startCountdown() {
    _timer?.cancel();
    setState(() => _secondsLeft = _resendAfterSeconds);

    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_secondsLeft <= 1) timer.cancel();
      if (mounted) setState(() => _secondsLeft--);
    });
  }

  void _submit() {
    if (_code.text.length != 6) return;

    FocusScope.of(context).unfocus();
    _controller.verifyOtp(email: _email, code: _code.text);
  }

  Future<void> _resend() async {
    await _controller.resendOtp(_email);
    _startCountdown();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context).textTheme;

    return AuthScaffold(
      title: 'تأكيد البريد الإلكتروني',
      subtitle: 'أدخل الرمز المكوّن من 6 أرقام الذي أرسلناه إلى\n$_email',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          TextField(
            controller: _code,
            autofocus: true,
            textAlign: TextAlign.center,
            textDirection: TextDirection.ltr,
            keyboardType: TextInputType.number,
            maxLength: 6,
            inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            style: theme.headlineMedium?.copyWith(letterSpacing: 12, fontWeight: FontWeight.w700),
            decoration: const InputDecoration(counterText: '', hintText: '••••••'),
            onChanged: (value) {
              setState(() {});
              if (value.length == 6) _submit();
            },
          ),
          const SizedBox(height: 24),
          Obx(
            () => AppButton(
              label: 'تأكيد',
              loading: _controller.isLoading.value,
              onPressed: _code.text.length == 6 ? _submit : null,
            ),
          ),
          const SizedBox(height: 16),
          Center(
            child: _secondsLeft > 0
                ? Text('يمكنك طلب رمز جديد بعد $_secondsLeft ثانية', style: theme.bodySmall?.copyWith(color: AppColors.muted))
                : TextButton(onPressed: _resend, child: const Text('إعادة إرسال الرمز')),
          ),
        ],
      ),
    );
  }
}
