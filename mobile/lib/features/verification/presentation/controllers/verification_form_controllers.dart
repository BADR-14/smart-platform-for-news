import 'dart:io';

import 'package:flutter/widgets.dart';
import 'package:get/get.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/utils/feedback.dart';
import '../../../../core/utils/navigation.dart';
import '../../../companies/domain/companies_repository.dart';
import '../../../shared/models/account.dart';
import '../../domain/verification_repository.dart';
import '../../domain/verification_status.dart';
import 'verification_controller.dart';

/// المشترك بين نموذجي التوثيق: حالة الإرسال وأخطاء الحقول ومعالجة النتيجة.
abstract class VerificationFormController extends GetxController {
  VerificationFormController(this._verification);

  final VerificationController _verification;

  final isSubmitting = false.obs;
  final fieldErrors = <String, String>{}.obs;

  /// خطأ حقل، مع دعم مفاتيح الملفات المتداخلة مثل documents.tax_document
  String? errorOf(String field) => fieldErrors[field];

  Future<void> submitRequest(Future<VerificationStatus> Function() request) async {
    if (isSubmitting.value) return;

    isSubmitting.value = true;

    try {
      final status = await request();
      await _verification.applyStatus(status);

      AppNav.back<void>();
      AppFeedback.success('تم استلام طلبك وسيصلك إشعار بالنتيجة');
    } on ApiException catch (error) {
      if (error.isValidation) {
        fieldErrors.assignAll(error.fieldErrors.map((key, value) => MapEntry(key, value.first)));
      }
      AppFeedback.error(error);
    } catch (error) {
      AppFeedback.error(error);
    } finally {
      isSubmitting.value = false;
    }
  }
}

/// طلب توثيق مستخدم: الرقم الوطني + صورتا الهوية (وجه وخلف).
class UserVerificationController extends VerificationFormController {
  UserVerificationController(this._repository, super.verification);

  final VerificationRepository _repository;

  final nationalId = TextEditingController();
  final linkedin = TextEditingController();
  final identityFront = Rxn<File>();
  final identityBack = Rxn<File>();

  @override
  void onClose() {
    nationalId.dispose();
    linkedin.dispose();
    super.onClose();
  }

  bool _validateFiles() {
    final errors = <String, String>{};

    if (identityFront.value == null) errors['identity_front'] = 'صورة الوجه الأمامي للهوية مطلوبة';
    if (identityBack.value == null) errors['identity_back'] = 'صورة الوجه الخلفي للهوية مطلوبة';

    fieldErrors.assignAll(errors);
    return errors.isEmpty;
  }

  /// [fieldsValid]: نتيجة تحقق حقول النص في الواجهة.
  Future<void> submit({required bool fieldsValid}) async {
    final filesValid = _validateFiles();
    if (!fieldsValid || !filesValid) return;

    final link = linkedin.text.trim();

    await submitRequest(
      () => _repository.submitUser(
        nationalId: nationalId.text.trim(),
        linkedinUrl: link.isEmpty ? null : link,
        identityFront: identityFront.value!,
        identityBack: identityBack.value!,
      ),
    );
  }
}

/// طلب إنشاء شركة وتوثيقها. عند الموافقة يتحول الحساب نفسه إلى حساب شركة.
class CompanyVerificationController extends VerificationFormController {
  CompanyVerificationController(this._repository, this._companies, super.verification);

  final VerificationRepository _repository;
  final CompaniesRepository _companies;

  final name = TextEditingController();
  final description = TextEditingController();
  final phone = TextEditingController();
  final website = TextEditingController();
  final address = TextEditingController();
  final registrationNumber = TextEditingController();
  final taxId = TextEditingController();

  final categoryId = RxnInt();
  final categories = <Category>[].obs;

  final logo = Rxn<File>();
  final commercialRegister = Rxn<File>();
  final taxDocument = Rxn<File>();

  @override
  void onInit() {
    super.onInit();
    _loadCategories();
  }

  @override
  void onClose() {
    for (final controller in [name, description, phone, website, address, registrationNumber, taxId]) {
      controller.dispose();
    }
    super.onClose();
  }

  Future<void> _loadCategories() async {
    try {
      categories.assignAll(await _companies.categories());
    } catch (error) {
      AppFeedback.error(error);
    }
  }

  @override
  String? errorOf(String field) => fieldErrors[field] ?? fieldErrors['documents.$field'];

  bool _validateSelections() {
    final errors = <String, String>{};

    if (categoryId.value == null) errors['category_id'] = 'اختر تصنيف الشركة';
    if (logo.value == null) errors['logo'] = 'شعار الشركة مطلوب';
    if (commercialRegister.value == null) errors['commercial_register'] = 'صورة السجل التجاري مطلوبة';
    if (taxDocument.value == null) errors['tax_document'] = 'صورة الوثيقة الضريبية مطلوبة';

    fieldErrors.assignAll(errors);
    return errors.isEmpty;
  }

  Future<void> submit({required bool fieldsValid}) async {
    final selectionsValid = _validateSelections();
    if (!fieldsValid || !selectionsValid) return;

    final site = website.text.trim();
    final place = address.text.trim();

    await submitRequest(
      () => _repository.submitCompany(
        CompanyRequest(
          name: name.text.trim(),
          categoryId: categoryId.value!,
          description: description.text.trim(),
          phone: phone.text.trim(),
          registrationNumber: registrationNumber.text.trim(),
          taxId: taxId.text.trim(),
          websiteUrl: site.isEmpty ? null : site,
          address: place.isEmpty ? null : place,
          logo: logo.value!,
          commercialRegister: commercialRegister.value!,
          taxDocument: taxDocument.value!,
        ),
      ),
    );
  }
}
