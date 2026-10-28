import 'dart:io';

import 'package:flutter/widgets.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/services/geocoding_service.dart';
import '../../../../core/utils/feedback.dart';
import '../../../../core/utils/navigation.dart';
import '../../../shared/models/account.dart';
import '../../domain/post.dart';
import '../../domain/posts_repository.dart';
import '../post_actions.dart';

/// نموذج إنشاء منشور جديد أو تعديل منشور موجود ([editing] != null).
class PostFormController extends GetxController {
  PostFormController(this._repository, this._actions, {this.editing});

  static const maxMedia = 5;
  static const maxTags = 5;
  static const _maxFileBytes = 10 * 1024 * 1024;

  final PostsRepository _repository;
  final PostActions _actions;

  final Post? editing;

  final title = TextEditingController();
  final description = TextEditingController();
  final location = TextEditingController();
  final tagInput = TextEditingController();

  final postType = PostType.event.obs;
  final categoryId = RxnInt();
  final isOnline = false.obs;
  final startDate = Rxn<DateTime>();
  final endDate = Rxn<DateTime>();
  final latitude = RxnDouble();
  final longitude = RxnDouble();
  final tags = <String>[].obs;

  /// وسائط المنشور الحالية (عند التعديل) التي لم تُحذف.
  final existingMedia = <PostMedia>[].obs;
  final newMedia = <File>[].obs;
  final _removedMediaIds = <int>[];

  final categories = <Category>[].obs;
  final isSaving = false.obs;
  final fieldErrors = <String, String>{}.obs;

  final _picker = ImagePicker();

  bool get isEditing => editing != null;

  /// المنشور الذي وضعته الإدارة قيد المراجعة أو رفضته لا يغيّر صاحبه حالته.
  bool get isModerated => editing?.status == PostStatus.underReview || editing?.status == PostStatus.rejected;

  int get mediaCount => existingMedia.length + newMedia.length;

  @override
  void onInit() {
    super.onInit();

    final post = editing;
    if (post != null) {
      title.text = post.title;
      description.text = post.description ?? '';
      location.text = post.location ?? '';
      postType.value = post.postType;
      categoryId.value = post.category?.id;
      isOnline.value = post.isOnline;
      startDate.value = post.startDate;
      endDate.value = post.endDate;
      latitude.value = post.latitude;
      longitude.value = post.longitude;
      tags.assignAll(post.tags);
      existingMedia.assignAll(post.media);
    }

    _loadCategories();
  }

  @override
  void onClose() {
    title.dispose();
    description.dispose();
    location.dispose();
    tagInput.dispose();
    super.onClose();
  }

  Future<void> _loadCategories() async {
    try {
      categories.assignAll(await _repository.categories());
    } catch (error) {
      AppFeedback.error(error);
    }
  }

  // ==================== الحقول ====================

  void setStartDate(DateTime value) {
    startDate.value = value;

    // تاريخ النهاية لا يسبق تاريخ البداية
    final end = endDate.value;
    if (end != null && end.isBefore(value)) endDate.value = value;
  }

  void setLocation(GeoPlace place) {
    latitude.value = place.latitude;
    longitude.value = place.longitude;
    location.text = place.name.length > 255 ? place.name.substring(0, 255) : place.name;
    fieldErrors.remove('location');
  }

  GeoPlace? get currentPlace {
    final lat = latitude.value;
    final lng = longitude.value;
    if (lat == null || lng == null) return null;

    return GeoPlace(name: location.text, latitude: lat, longitude: lng);
  }

  void addTag() {
    final value = tagInput.text.trim().replaceAll('#', '');
    tagInput.clear();

    if (value.isEmpty || tags.contains(value)) return;

    if (tags.length >= maxTags) {
      AppFeedback.info('يمكن إضافة $maxTags وسوم كحد أقصى.');
      return;
    }

    tags.add(value.length > 50 ? value.substring(0, 50) : value);
  }

  void removeTag(String tag) => tags.remove(tag);

  // ==================== الوسائط ====================

  Future<void> pickImages() async {
    final remaining = maxMedia - mediaCount;
    if (remaining <= 0) return _mediaLimitReached();

    try {
      final picked = await _picker.pickMultiImage(imageQuality: 85, maxWidth: 1920);
      _addFiles(picked.map((file) => File(file.path)).toList());
    } catch (_) {
      AppFeedback.error('تعذّر فتح معرض الصور.');
    }
  }

  Future<void> pickVideo() async {
    if (mediaCount >= maxMedia) return _mediaLimitReached();

    try {
      final picked = await _picker.pickVideo(source: ImageSource.gallery, maxDuration: const Duration(minutes: 5));
      if (picked != null) _addFiles([File(picked.path)]);
    } catch (_) {
      AppFeedback.error('تعذّر فتح معرض الفيديو.');
    }
  }

  void _addFiles(List<File> files) {
    var skippedLarge = false;

    for (final file in files) {
      if (mediaCount >= maxMedia) {
        _mediaLimitReached();
        break;
      }

      if (file.lengthSync() > _maxFileBytes) {
        skippedLarge = true;
        continue;
      }

      newMedia.add(file);
    }

    if (skippedLarge) AppFeedback.error('حجم الملف الواحد يجب ألا يتجاوز 10 ميغابايت.');
  }

  void _mediaLimitReached() => AppFeedback.info('يمكن إرفاق $maxMedia ملفات كحد أقصى.');

  void removeExistingMedia(PostMedia media) {
    existingMedia.remove(media);
    _removedMediaIds.add(media.id);
  }

  void removeNewMedia(File file) => newMedia.remove(file);

  // ==================== الحفظ ====================

  /// تحقق محلي للحقول التي ليست حقول نص (القوائم والتواريخ والموقع).
  bool validateSelections() {
    final errors = <String, String>{};

    if (categoryId.value == null) errors['category_id'] = 'اختر تصنيف المنشور';

    if (postType.value == PostType.event) {
      if (startDate.value == null) errors['start_date'] = 'تاريخ البداية مطلوب للفعاليات';
      if (endDate.value == null) errors['end_date'] = 'تاريخ النهاية مطلوب للفعاليات';
    }

    if (!isOnline.value) {
      if (location.text.trim().isEmpty) {
        errors['location'] = 'عنوان المكان مطلوب للفعاليات الحضورية';
      } else if (latitude.value == null || longitude.value == null) {
        errors['location'] = 'حدّد الموقع على الخريطة';
      }
    }

    fieldErrors.assignAll(errors);
    return errors.isEmpty;
  }

  /// [action]: publish | draft — أو null عند التعديل لإبقاء الحالة كما هي.
  Future<void> save({String? action}) async {
    if (isSaving.value || !validateSelections()) return;

    isSaving.value = true;

    final draft = PostDraft(
      title: title.text.trim(),
      description: description.text.trim(),
      postType: postType.value,
      categoryId: categoryId.value!,
      isOnline: isOnline.value,
      location: location.text.trim(),
      latitude: latitude.value,
      longitude: longitude.value,
      startDate: startDate.value,
      endDate: endDate.value,
      tags: tags.toList(),
      newMedia: newMedia.toList(),
      removedMediaIds: List.of(_removedMediaIds),
      action: isModerated ? null : action,
    );

    try {
      final post = editing;
      final saved = post == null ? await _repository.create(draft) : await _repository.update(post.id, draft);

      _actions
        ..publish(saved)
        ..notifyFeedChanged();

      AppNav.back<void>();
      AppFeedback.success(switch (saved.status) {
        PostStatus.draft => 'تم حفظ المسودة',
        PostStatus.published => isEditing ? 'تم حفظ التعديلات' : 'تم نشر المنشور',
        _ => 'تم حفظ التعديلات',
      });
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
}
