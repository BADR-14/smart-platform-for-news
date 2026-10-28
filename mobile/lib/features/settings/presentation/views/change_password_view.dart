import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../controllers/settings_controller.dart';

class ChangePasswordView extends StatefulWidget {
  const ChangePasswordView({super.key});

  @override
  State<ChangePasswordView> createState() => _ChangePasswordViewState();
}

class _ChangePasswordViewState extends State<ChangePasswordView> {
  final _formKey = GlobalKey<FormState>();
  final _current = TextEditingController();
  final _next = TextEditingController();
  final _confirmation = TextEditingController();

  final SettingsController _controller = Get.find<SettingsController>();

  @override
  void dispose() {
    _current.dispose();
    _next.dispose();
    _confirmation.dispose();
    super.dispose();
  }

  void _submit() {
    FocusScope.of(context).unfocus();
    if (!_formKey.currentState!.validate()) return;

    _controller.changePassword(current: _current.text, next: _next.text, confirmation: _confirmation.text);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.surface,
      appBar: AppBar(title: const Text('تغيير كلمة المرور')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Obx(
              () => AppTextField(
                label: 'كلمة المرور الحالية',
                controller: _current,
                ltr: true,
                obscure: true,
                prefixIcon: Icons.lock_outline,
                textInputAction: TextInputAction.next,
                validator: (value) => Validators.required(value, 'كلمة المرور الحالية مطلوبة'),
                serverError: _controller.fieldErrors['current_password'],
              ),
            ),
            const SizedBox(height: 16),
            Obx(
              () => AppTextField(
                label: 'كلمة المرور الجديدة',
                controller: _next,
                hint: '8 محارف على الأقل مع حرف ورقم',
                ltr: true,
                obscure: true,
                prefixIcon: Icons.lock_reset,
                textInputAction: TextInputAction.next,
                validator: (value) {
                  final error = Validators.password(value);
                  if (error != null) return error;
                  return value == _current.text ? 'كلمة المرور الجديدة يجب أن تختلف عن الحالية' : null;
                },
                serverError: _controller.fieldErrors['new_password'],
              ),
            ),
            const SizedBox(height: 16),
            AppTextField(
              label: 'تأكيد كلمة المرور الجديدة',
              controller: _confirmation,
              ltr: true,
              obscure: true,
              prefixIcon: Icons.lock_reset,
              textInputAction: TextInputAction.done,
              validator: Validators.confirm(() => _next.text),
              onSubmitted: (_) => _submit(),
            ),
            const SizedBox(height: 24),
            Obx(() => AppButton(label: 'حفظ كلمة المرور', icon: Icons.check, style: AppButtonStyle.accent, loading: _controller.isBusy.value, onPressed: _submit)),
          ],
        ),
      ),
    );
  }
}
