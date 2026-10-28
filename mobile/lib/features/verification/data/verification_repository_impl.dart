import 'dart:io';

import 'package:dio/dio.dart';

import '../../../core/network/api_client.dart';
import '../../../core/utils/json.dart';
import '../domain/verification_repository.dart';
import '../domain/verification_status.dart';

class VerificationRepositoryImpl implements VerificationRepository {
  VerificationRepositoryImpl(this._api);

  final ApiClient _api;

  @override
  Future<VerificationStatus> status() async {
    final json = await _api.get('/profile/verification-status');
    return VerificationStatus.fromJson(asJson(json['data']));
  }

  @override
  Future<VerificationStatus> submitUser({
    required String nationalId,
    required File identityFront,
    required File identityBack,
    String? linkedinUrl,
  }) async {
    final form = FormData.fromMap({
      'national_id': nationalId,
      if (linkedinUrl != null) 'linkedin_url': linkedinUrl,
      'identity_front': await _file(identityFront),
      'identity_back': await _file(identityBack),
    });

    final json = await _api.upload('/verification/user', form);
    return VerificationStatus.fromJson(asJson(json['data']));
  }

  @override
  Future<VerificationStatus> submitCompany(CompanyRequest request) async {
    final form = FormData.fromMap({
      'name': request.name,
      'category_id': request.categoryId,
      'description': request.description,
      'phone': request.phone,
      'company_registration_number': request.registrationNumber,
      'tax_id': request.taxId,
      if (request.websiteUrl != null) 'website_url': request.websiteUrl,
      if (request.address != null) 'address': request.address,
      'logo': await _file(request.logo),
      'documents[commercial_register]': await _file(request.commercialRegister),
      'documents[tax_document]': await _file(request.taxDocument),
    });

    final json = await _api.upload('/companies', form);
    return VerificationStatus.fromJson(asJson(asJson(json['data'])['verification']));
  }

  Future<MultipartFile> _file(File file) {
    return MultipartFile.fromFile(file.path, filename: file.uri.pathSegments.last);
  }
}
