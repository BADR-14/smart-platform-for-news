import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../app/routes.dart';
import '../../../../core/utils/navigation.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../controllers/auth_controller.dart';
import '../widgets/auth_scaffold.dart';

class RegisterView extends StatefulWidget {
  const RegisterView({super.key});

  @override
  State<RegisterView> createState() => _RegisterViewState();
}

class _RegisterViewState extends State<RegisterView> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();

  AuthController get _controller => Get.find<AuthController>();

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  void _submit() {
    FocusScope.of(context).unfocus();
    if (!_formKey.currentState!.validate()) return;

    _controller.register(
      name: _name.text.trim(),
      email: _email.text.trim().toLowerCase(),
      password: _password.text,
      passwordConfirmation: _confirm.text,
    );
  }

  @override
  Widget build(BuildContext context) {
    return AuthScaffold(
      title: 'إنشاء حساب',
      subtitle: 'سجّل الآن لتتابع الشركات وتحفظ الفعاليات التي تهمك.',
      child: Form(
        key: _formKey,
        child: Obx(() {
          final errors = _controller.fieldErrors;

          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              AppTextField(
                label: 'الاسم الكامل',
                controller: _name,
                prefixIcon: Icons.person_outline,
                textInputAction: TextInputAction.next,
                serverError: errors['name'],
                validator: (value) => Validators.minLength(value, 2, 'الاسم'),
              ),
              const SizedBox(height: 16),
              AppTextField(
                label: 'البريد الإلكتروني',
                controller: _email,
                hint: 'name@example.com',
                ltr: true,
                prefixIcon: Icons.mail_outline,
                keyboardType: TextInputType.emailAddress,
                textInputAction: TextInputAction.next,
                serverError: errors['email'],
                validator: Validators.email,
              ),
              const SizedBox(height: 16),
              AppTextField(
                label: 'كلمة المرور',
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
              AppButton(label: 'إنشاء الحساب', loading: _controller.isLoading.value, onPressed: _submit),
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text('لديك حساب؟'),
                  TextButton(
                    // فُتحت من شاشة الدخول: نرجع إليها. فُتحت من مكان آخر: نستبدلها بشاشة الدخول
                    onPressed: () {
                      if (Get.previousRoute == Routes.login) {
                        AppNav.back<void>();
                      } else {
                        Get.offNamed(Routes.login);
                      }
                    },
                    child: const Text('سجّل الدخول'),
                  ),
                ],
              ),
            ],
          );
        }),
      ),
    );
  }
}
