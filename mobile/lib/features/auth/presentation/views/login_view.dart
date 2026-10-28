import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../app/routes.dart';
import '../../../../core/config/app_config.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../controllers/auth_controller.dart';
import '../widgets/auth_scaffold.dart';
import '../../../shell/presentation/shell_controller.dart';

class LoginView extends StatefulWidget {
  const LoginView({super.key});

  @override
  State<LoginView> createState() => _LoginViewState();
}

class _LoginViewState extends State<LoginView> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();

  AuthController get _controller => Get.find<AuthController>();

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  void _submit() {
    FocusScope.of(context).unfocus();
    if (!_formKey.currentState!.validate()) return;

    _controller.login(email: _email.text.trim().toLowerCase(), password: _password.text);
  }

  @override
  Widget build(BuildContext context) {
    return AuthScaffold(
      title: 'تسجيل الدخول',
      subtitle: 'أهلاً بعودتك. سجّل الدخول لتتابع الفعاليات وتتفاعل معها.',
      // إذا لم توجد صفحة سابقة (فُتحت بعد انتهاء الجلسة) لا يظهر زر الرجوع
      showBack: Navigator.of(context).canPop(),
      child: Form(
        key: _formKey,
        child: AutofillGroup(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              AppTextField(
                label: 'البريد الإلكتروني',
                controller: _email,
                hint: 'name@example.com',
                ltr: true,
                prefixIcon: Icons.mail_outline,
                keyboardType: TextInputType.emailAddress,
                textInputAction: TextInputAction.next,
                autofillHints: const [AutofillHints.email],
                validator: Validators.email,
              ),
              const SizedBox(height: 16),
              AppTextField(
                label: 'كلمة المرور',
                controller: _password,
                ltr: true,
                obscure: true,
                prefixIcon: Icons.lock_outline,
                textInputAction: TextInputAction.done,
                autofillHints: const [AutofillHints.password],
                validator: (value) => Validators.required(value, 'كلمة المرور مطلوبة'),
                onSubmitted: (_) => _submit(),
              ),
              Align(
                alignment: AlignmentDirectional.centerEnd,
                child: TextButton(
                  onPressed: () => Get.toNamed(Routes.forgotPassword),
                  child: const Text('نسيت كلمة المرور؟'),
                ),
              ),
              const SizedBox(height: 4),
              Obx(() => AppButton(label: 'دخول', icon: Icons.login, loading: _controller.isLoading.value, onPressed: _submit)),
              if (AppConfig.googleSignInEnabled) ...[
                const SizedBox(height: 12),
                Obx(
                  () => AppButton(
                    label: 'المتابعة عبر Google',
                    icon: Icons.g_mobiledata,
                    style: AppButtonStyle.outline,
                    onPressed: _controller.isLoading.value ? null : _controller.loginWithGoogle,
                  ),
                ),
              ],
              const SizedBox(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text('ليس لديك حساب؟'),
                  TextButton(onPressed: () => Get.toNamed(Routes.register), child: const Text('أنشئ حساباً')),
                ],
              ),
              TextButton(
                onPressed: () => ShellController.open(),
                child: const Text('التصفح كزائر'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
