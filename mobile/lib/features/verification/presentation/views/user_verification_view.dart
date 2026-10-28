import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../../../../core/widgets/image_picker_field.dart';
import '../controllers/verification_form_controllers.dart';

class UserVerificationView extends StatefulWidget {
  const UserVerificationView({super.key});

  @override
  State<UserVerificationView> createState() => _UserVerificationViewState();
}

class _UserVerificationViewState extends State<UserVerificationView> {
  final _formKey = GlobalKey<FormState>();
  final UserVerificationController _controller = Get.find<UserVerificationController>();

  void _submit() {
    FocusScope.of(context).unfocus();
    _controller.submit(fieldsValid: _formKey.currentState!.validate());
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;
    final theme = Theme.of(context).textTheme;

    return Scaffold(
      backgroundColor: AppColors.surface,
      appBar: AppBar(title: const Text('توثيق المستخدم')),
      body: Form(
        key: _formKey,
        // Column وليس ListView: كل الحقول تبقى مبنية حتى يشملها التحقق عند الإرسال
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
            Text(
              'أدخل رقمك الوطني كما هو في الهوية، وارفع صورتين واضحتين لوجهَي الهوية. '
              'تُستخدم الوثائق للتحقق فقط ولا تظهر لأي مستخدم.',
              style: theme.bodyMedium?.copyWith(color: AppColors.inkSoft, height: 1.6),
            ),
            const SizedBox(height: 18),
            Obx(
              () => AppTextField(
                label: 'الرقم الوطني',
                controller: controller.nationalId,
                hint: '11 رقماً',
                ltr: true,
                maxLength: 11,
                prefixIcon: Icons.badge_outlined,
                keyboardType: TextInputType.number,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                textInputAction: TextInputAction.next,
                validator: (value) => Validators.digits(value, 11, 'الرقم الوطني'),
                serverError: controller.errorOf('national_id'),
              ),
            ),
            const SizedBox(height: 16),
            Obx(
              () => AppTextField(
                label: 'رابط LinkedIn (اختياري)',
                controller: controller.linkedin,
                hint: 'https://linkedin.com/in/username',
                ltr: true,
                prefixIcon: Icons.link,
                keyboardType: TextInputType.url,
                validator: Validators.url,
                serverError: controller.errorOf('linkedin_url'),
              ),
            ),
            const SizedBox(height: 18),
            Obx(
              () => ImagePickerField(
                label: 'الهوية — الوجه الأمامي',
                hint: 'صورة واضحة تظهر فيها كل البيانات',
                file: controller.identityFront.value,
                error: controller.errorOf('identity_front'),
                onChanged: (file) {
                  controller.identityFront.value = file;
                  controller.fieldErrors.remove('identity_front');
                },
              ),
            ),
            const SizedBox(height: 16),
            Obx(
              () => ImagePickerField(
                label: 'الهوية — الوجه الخلفي',
                hint: 'صورة واضحة للوجه الخلفي',
                file: controller.identityBack.value,
                error: controller.errorOf('identity_back'),
                onChanged: (file) {
                  controller.identityBack.value = file;
                  controller.fieldErrors.remove('identity_back');
                },
              ),
            ),
            const SizedBox(height: 26),
            Obx(
              () => AppButton(
                label: 'إرسال طلب التوثيق',
                icon: Icons.verified_outlined,
                style: AppButtonStyle.accent,
                loading: controller.isSubmitting.value,
                onPressed: _submit,
              ),
            ),
            ],
          ),
        ),
      ),
    );
  }
}
