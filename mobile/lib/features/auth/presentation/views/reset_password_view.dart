import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';

import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../controllers/auth_controller.dart';
import '../widgets/auth_scaffold.dart';

/// إدخال الرمز المرسل بالبريد + كلمة المرور الجديدة.
class ResetPasswordView extends StatefulWidget {
  const ResetPasswordView({super.key});

  @override
  State<ResetPasswordView> createState() => _ResetPasswordViewState();
}

class _ResetPasswordViewState extends State<ResetPasswordView> {
  final _formKey = GlobalKey<FormState>();
  final _code = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();

  late final String _email = (Get.arguments as String?) ?? '';

  AuthController get _controller => Get.find<AuthController>();

  @override
  void dispose() {
    _code.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  void _submit() {
    FocusScope.of(context).unfocus();
    if (!_formKey.currentState!.validate()) return;

    _controller.resetPassword(
      email: _email,
      code: _code.text,
      password: _password.text,
      passwordConfirmation: _confirm.text,
    );
  }

  @override
  Widget build(BuildContext context) {
    return AuthScaffold(
      title: 'كلمة مرور جديدة',
      subtitle: 'أدخل الرمز الذي وصلك على $_email ثم اختر كلمة مرور جديدة.',
      child: Form(
        key: _formKey,
        child: Obx(() {
          final errors = _controller.fieldErrors;

          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              AppTextField(
                label: 'رمز التحقق',
                controller: _code,
                hint: '6 أرقام',
                ltr: true,
                prefixIcon: Icons.pin_outlined,
                keyboardType: TextInputType.number,
                maxLength: 6,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                textInputAction: TextInputAction.next,
                serverError: errors['otp_code'],
                validator: (value) => Validators.digits(value, 6, 'رمز التحقق'),
              ),
              const SizedBox(height: 16),
              AppTextField(
                label: 'كلمة المرور الجديدة',
                controller: _password,
                hint: '8 محارف على الأقل مع حرف ورقم',
                ltr: true,
                obscure: true,
                prefixIcon: Icons.lock_outline,
                textInputAction: TextInputAction.next,
                serverError: errors['password'],
                validator: Validators.password,
              ),
              const SizedBox(height: 16),
              AppTextField(
                label: 'تأكيد كلمة المرور',
                controller: _confirm,
                ltr: true,
                obscure: true,
                prefixIcon: Icons.lock_outline,
                textInputAction: TextInputAction.done,
                validator: Validators.confirm(() => _password.text),
                onSubmitted: (_) => _submit(),
              ),
              const SizedBox(height: 24),
              AppButton(label: 'حفظ كلمة المرور', loading: _controller.isLoading.value, onPressed: _submit),
            ],
          );
        }),
      ),
    );
  }
}
