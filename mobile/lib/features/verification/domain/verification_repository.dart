import 'dart:io';

import 'verification_status.dart';

/// بيانات طلب إنشاء شركة وتوثيقها.
class CompanyRequest {
  const CompanyRequest({
    required this.name,
    required this.categoryId,
    required this.description,
    required this.phone,
    required this.registrationNumber,
    required this.taxId,
    required this.logo,
    required this.commercialRegister,
    required this.taxDocument,
    this.websiteUrl,
    this.address,
  });

  final String name;
  final int categoryId;
  final String description;
  final String phone;
  final String registrationNumber;
  final String taxId;
  final String? websiteUrl;
  final String? address;
  final File logo;
  final File commercialRegister;
  final File taxDocument;
}

abstract interface class VerificationRepository {
  Future<VerificationStatus> status();

  /// طلب توثيق مستخدم: الرقم الوطني + صورتا الهوية. يرجع الحالة بعد التقديم.
  Future<VerificationStatus> submitUser({
    required String nationalId,
    required File identityFront,
    required File identityBack,
    String? linkedinUrl,
  });

  /// طلب إنشاء شركة. عند الموافقة يتحول الحساب نفسه إلى حساب شركة.
  Future<VerificationStatus> submitCompany(CompanyRequest request);
}
