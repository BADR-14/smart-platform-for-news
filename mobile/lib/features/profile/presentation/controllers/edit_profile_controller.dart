import 'package:flutter/widgets.dart';
import 'package:get/get.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/utils/feedback.dart';
import '../../../../core/utils/navigation.dart';
import '../../domain/profile_repository.dart';
import 'profile_controller.dart';

class EditProfileController extends GetxController {
  EditProfileController(this._repository, this._profile);

  final ProfileRepository _repository;
  final ProfileController _profile;

  final name = TextEditingController();
  final phone = TextEditingController();
  final linkedin = TextEditingController();
  final bio = TextEditingController();

  final isSaving = false.obs;
  final fieldErrors = <String, String>{}.obs;

  @override
  void onInit() {
    super.onInit();

    final current = _profile.profile.value;
    if (current != null) {
      name.text = current.name;
      phone.text = current.phoneNumber ?? '';
      linkedin.text = current.linkedinUrl ?? '';
      bio.text = current.shortBio ?? '';
    }
  }

  @override
  void onClose() {
    name.dispose();
    phone.dispose();
    linkedin.dispose();
    bio.dispose();
    super.onClose();
  }

  Future<void> save() async {
    if (isSaving.value) return;

    isSaving.value = true;
    fieldErrors.clear();

    try {
      final updated = await _repository.update(
        name: name.text.trim(),
        phoneNumber: _nullIfEmpty(phone.text),
        linkedinUrl: _nullIfEmpty(linkedin.text),
        shortBio: _nullIfEmpty(bio.text),
      );

      _profile.setProfile(updated);

      AppNav.back<void>();
      AppFeedback.success('تم تحديث الملف الشخصي');
    } on ApiException catch (error) {
      if (error.isValidation) {
        fieldErrors.assignAll(error.fieldErrors.map((key, value) => MapEntry(key, value.first)));
      }
      AppFeedback.error(error);
    } catch (error) {
      AppFeedback.error(error);
    } finally {
      isSaving.value = false;
    }
  }

  String? _nullIfEmpty(String value) {
    final text = value.trim();
    return text.isEmpty ? null : text;
  }
}
