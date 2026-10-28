import 'dart:io';

import 'package:dio/dio.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/paginated.dart';
import '../../../core/utils/json.dart';
import '../../posts/domain/comment.dart';
import '../domain/profile.dart';
import '../domain/profile_repository.dart';

class ProfileRepositoryImpl implements ProfileRepository {
  ProfileRepositoryImpl(this._api);

  final ApiClient _api;

  @override
  Future<Profile> me() async {
    final json = await _api.get('/profile');
    return Profile.fromJson(asJson(json['data']));
  }

  @override
  Future<Profile> update({required String name, String? phoneNumber, String? linkedinUrl, String? shortBio}) async {
    final json = await _api.patch('/profile', body: {
      'name': name,
      'phone_number': phoneNumber,
      'linkedin_url': linkedinUrl,
      'short_bio': shortBio,
    });

    return Profile.fromJson(asJson(json['data']));
  }

  @override
  Future<String?> uploadAvatar(File image) async {
    final form = FormData.fromMap({
      'avatar': await MultipartFile.fromFile(image.path, filename: image.uri.pathSegments.last),
    });

    final json = await _api.upload('/profile/avatar', form);
    return asStringOrNull(asJson(json['data'])['avatar']);
  }

  @override
  Future<PublicProfile> publicProfile(int accountId) async {
    final json = await _api.get('/accounts/$accountId');
    return PublicProfile.fromJson(asJson(json['data']));
  }

  @override
  Future<Paginated<Review>> myReviews({required int page}) async {
    final json = await _api.get('/profile/reviews', query: {'page': page});
    return Paginated.fromJson(json, Review.fromJson);
  }
}
