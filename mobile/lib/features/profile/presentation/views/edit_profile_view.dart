import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../controllers/edit_profile_controller.dart';

class EditProfileView extends StatefulWidget {
  const EditProfileView({super.key});

  @override
  State<EditProfileView> createState() => _EditProfileViewState();
}

class _EditProfileViewState extends State<EditProfileView> {
  final _formKey = GlobalKey<FormState>();
  final EditProfileController _controller = Get.find<EditProfileController>();

  void _submit() {
    FocusScope.of(context).unfocus();
    if (_formKey.currentState!.validate()) _controller.save();
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;

    return Scaffold(
      appBar: AppBar(title: const Text('تعديل الملف الشخصي')),
      body: Form(
        key: _formKey,
        // Column وليس ListView: كل الحقول تبقى مبنية حتى يشملها التحقق عند الإرسال
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
            Obx(
              () => AppTextField(
                label: 'الاسم',
                controller: controller.name,
                maxLength: 255,
                prefixIcon: Icons.person_outline,
                textInputAction: TextInputAction.next,
                validator: (value) => Validators.minLength(value, 2, 'الاسم'),
                serverError: controller.fieldErrors['name'],
              ),
            ),
            const SizedBox(height: 16),
            Obx(
              () => AppTextField(
                label: 'رقم الهاتف (اختياري)',
                controller: controller.phone,
                hint: '+963912345678',
                ltr: true,
                prefixIcon: Icons.phone_outlined,
                keyboardType: TextInputType.phone,
                textInputAction: TextInputAction.next,
                validator: (value) => Validators.phone(value, optional: true),
                serverError: controller.fieldErrors['phone_number'],
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
                textInputAction: TextInputAction.next,
                validator: Validators.url,
                serverError: controller.fieldErrors['linkedin_url'],
              ),
            ),
            const SizedBox(height: 16),
            Obx(
              () => AppTextField(
                label: 'نبذة قصيرة (اختياري)',
                controller: controller.bio,
                hint: 'عرّف عن نفسك واهتماماتك في سطرين',
                maxLines: 4,
                maxLength: 500,
                serverError: controller.fieldErrors['short_bio'],
              ),
            ),
            const SizedBox(height: 24),
            Obx(() => AppButton(label: 'حفظ', icon: Icons.check, style: AppButtonStyle.accent, loading: controller.isSaving.value, onPressed: _submit)),
            ],
          ),
        ),
      ),
    );
  }
}
