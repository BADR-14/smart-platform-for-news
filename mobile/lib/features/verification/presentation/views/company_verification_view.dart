import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../../../../core/widgets/image_picker_field.dart';
import '../controllers/verification_form_controllers.dart';

class CompanyVerificationView extends StatefulWidget {
  const CompanyVerificationView({super.key});

  @override
  State<CompanyVerificationView> createState() => _CompanyVerificationViewState();
}

class _CompanyVerificationViewState extends State<CompanyVerificationView> {
  final _formKey = GlobalKey<FormState>();
  final CompanyVerificationController _controller = Get.find<CompanyVerificationController>();

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
      appBar: AppBar(title: const Text('طلب إنشاء شركة')),
      body: Form(
        key: _formKey,
        // Column وليس ListView: كل الحقول تبقى مبنية حتى يشملها التحقق عند الإرسال
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
            Text(
              'بعد الموافقة يتحول حسابك الحالي إلى حساب شركة باسمها وشعارها، وتستطيع إدارة '
              'المحفظة والباقات والمنشورات المميزة من لوحة الشركة على الويب.',
              style: theme.bodyMedium?.copyWith(color: AppColors.inkSoft, height: 1.6),
            ),

            const _SectionTitle('بيانات الشركة'),
            Obx(
              () => AppTextField(
                label: 'اسم الشركة',
                controller: controller.name,
                maxLength: 255,
                prefixIcon: Icons.apartment_outlined,
                textInputAction: TextInputAction.next,
                validator: (value) => Validators.minLength(value, 2, 'اسم الشركة'),
                serverError: controller.errorOf('name'),
              ),
            ),
            const SizedBox(height: 16),
            Text('التصنيف', style: theme.labelLarge?.copyWith(color: AppColors.inkSoft, fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            Obx(() {
              if (controller.categories.isEmpty) {
                return const Padding(padding: EdgeInsets.symmetric(vertical: 8), child: LinearProgressIndicator(minHeight: 2));
              }

              final error = controller.errorOf('category_id');

              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final category in controller.categories)
                        ChoiceChip(
                          label: Text(category.name),
                          selected: controller.categoryId.value == category.id,
                          onSelected: (_) {
                            controller.categoryId.value = category.id;
                            controller.fieldErrors.remove('category_id');
                          },
                        ),
                    ],
                  ),
                  if (error != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 6),
                      child: Text(error, style: theme.bodySmall?.copyWith(color: AppColors.danger)),
                    ),
                ],
              );
            }),
            const SizedBox(height: 16),
            Obx(
              () => AppTextField(
                label: 'وصف الشركة',
                controller: controller.description,
                hint: 'ماذا تقدّم الشركة؟ وما نوع الفعاليات التي تنظّمها؟',
                maxLines: 4,
                maxLength: 2000,
                validator: (value) => Validators.required(value, 'وصف الشركة مطلوب'),
                serverError: controller.errorOf('description'),
              ),
            ),
            const SizedBox(height: 16),
            Obx(
              () => AppTextField(
                label: 'هاتف الشركة',
                controller: controller.phone,
                hint: '+963112345678',
                ltr: true,
                prefixIcon: Icons.phone_outlined,
                keyboardType: TextInputType.phone,
                textInputAction: TextInputAction.next,
                validator: Validators.phone,
                serverError: controller.errorOf('phone'),
              ),
            ),
            const SizedBox(height: 16),
            Obx(
              () => AppTextField(
                label: 'الموقع الإلكتروني (اختياري)',
                controller: controller.website,
                hint: 'https://example.com',
                ltr: true,
                prefixIcon: Icons.language,
                keyboardType: TextInputType.url,
                textInputAction: TextInputAction.next,
                validator: Validators.url,
                serverError: controller.errorOf('website_url'),
              ),
            ),
            const SizedBox(height: 16),
            Obx(
              () => AppTextField(
                label: 'العنوان (اختياري)',
                controller: controller.address,
                hint: 'المدينة، الشارع، البناء',
                maxLength: 500,
                prefixIcon: Icons.place_outlined,
                textInputAction: TextInputAction.next,
                serverError: controller.errorOf('address'),
              ),
            ),
            const SizedBox(height: 16),
            Obx(
              () => ImagePickerField(
                label: 'شعار الشركة',
                hint: 'صورة مربعة بحجم أقصى 2 ميغابايت',
                icon: Icons.image_outlined,
                maxWidth: 800,
                maxBytes: 2 * 1024 * 1024,
                file: controller.logo.value,
                error: controller.errorOf('logo'),
                onChanged: (file) {
                  controller.logo.value = file;
                  controller.fieldErrors.remove('logo');
                },
              ),
            ),

            const _SectionTitle('الوثائق الرسمية'),
            Obx(
              () => AppTextField(
                label: 'رقم السجل التجاري',
                controller: controller.registrationNumber,
                ltr: true,
                maxLength: 100,
                prefixIcon: Icons.numbers,
                textInputAction: TextInputAction.next,
                validator: (value) => Validators.required(value, 'رقم السجل التجاري مطلوب'),
                serverError: controller.errorOf('company_registration_number'),
              ),
            ),
            const SizedBox(height: 16),
            Obx(
              () => AppTextField(
                label: 'الرقم الضريبي',
                controller: controller.taxId,
                ltr: true,
                maxLength: 100,
                prefixIcon: Icons.receipt_long_outlined,
                validator: (value) => Validators.required(value, 'الرقم الضريبي مطلوب'),
                serverError: controller.errorOf('tax_id'),
              ),
            ),
            const SizedBox(height: 16),
            Obx(
              () => ImagePickerField(
                label: 'صورة السجل التجاري',
                hint: 'صورة واضحة يظهر فيها اسم الشركة ورقم السجل',
                icon: Icons.description_outlined,
                file: controller.commercialRegister.value,
                error: controller.errorOf('commercial_register'),
                onChanged: (file) {
                  controller.commercialRegister.value = file;
                  controller.fieldErrors
                    ..remove('commercial_register')
                    ..remove('documents.commercial_register');
                },
              ),
            ),
            const SizedBox(height: 16),
            Obx(
              () => ImagePickerField(
                label: 'صورة الوثيقة الضريبية',
                hint: 'صورة واضحة يظهر فيها الرقم الضريبي',
                icon: Icons.description_outlined,
                file: controller.taxDocument.value,
                error: controller.errorOf('tax_document'),
                onChanged: (file) {
                  controller.taxDocument.value = file;
                  controller.fieldErrors
                    ..remove('tax_document')
                    ..remove('documents.tax_document');
                },
              ),
            ),
            const SizedBox(height: 26),
            Obx(
              () => AppButton(
                label: 'إرسال الطلب',
                icon: Icons.send,
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

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);

  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 24, bottom: 12),
      child: Row(
        children: [
          Container(width: 4, height: 18, decoration: BoxDecoration(color: AppColors.accent500, borderRadius: BorderRadius.circular(4))),
          const SizedBox(width: 8),
          Text(text, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }
}
