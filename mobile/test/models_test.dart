import 'package:flutter_test/flutter_test.dart';
import 'package:smart_news/core/network/paginated.dart';
import 'package:smart_news/core/utils/json.dart';
import 'package:smart_news/core/utils/validators.dart';
import 'package:smart_news/features/posts/domain/post.dart';
import 'package:smart_news/features/posts/domain/posts_repository.dart';
import 'package:smart_news/features/shared/models/account.dart';
import 'package:smart_news/features/verification/domain/verification_status.dart';

/// اختبارات تحويل ردود الـ API إلى نماذج — تضمن أن التطبيق لا ينهار
/// إذا وصلت قيمة بنوع مختلف (رقم كنص، حقل ناقص ...).
void main() {
  group('قراءة JSON الآمنة', () {
    test('الأرقام تُقرأ سواء وصلت رقماً أو نصاً', () {
      expect(asInt('12'), 12);
      expect(asInt(12.0), 12);
      expect(asInt(null, 7), 7);
      expect(asDoubleOrNull('4.5'), 4.5);
      expect(asIntOrNull('x'), isNull);
    });

    test('القيم المنطقية تُقرأ من 1 / "1" / true', () {
      expect(asBool(1), isTrue);
      expect(asBool('1'), isTrue);
      expect(asBool('true'), isTrue);
      expect(asBool(0), isFalse);
      expect(asBool(null), isFalse);
    });
  });

  group('Post.fromJson', () {
    final json = <String, dynamic>{
      'id': 5,
      'type': 'post',
      'title': 'محاضرة تجريبية',
      'description': null,
      'post_type': 'lecture',
      'status': 'under_review',
      'is_online': false,
      'location': 'دمشق',
      'latitude': '33.5138',
      'longitude': 36.2765,
      'start_date': '2026-10-05',
      'category': {'id': 2, 'name': 'تقنية'},
      'tags': [
        {'id': 1, 'name': 'ذكاء'},
        {'id': 2, 'name': 'برمجة'},
      ],
      'media': [
        {'id': 9, 'url': 'http://x/a.jpg', 'type': 'image'},
        {'id': 10, 'url': 'http://x/b.mp4', 'type': 'video'},
      ],
      'author': {'id': 3, 'name': 'شركة', 'type': 'company', 'is_verified': true, 'company_id': 1},
      'likes_count': '4',
      'rating_avg': null,
      'is_owner': 1,
      'my_reaction': 2,
    };

    test('يقرأ الحقول الأساسية والمتداخلة', () {
      final post = Post.fromJson(json);

      expect(post.id, 5);
      expect(post.postType, PostType.lecture);
      expect(post.status, PostStatus.underReview);
      expect(post.description, isNull);
      expect(post.hasCoordinates, isTrue);
      expect(post.latitude, 33.5138);
      expect(post.category?.name, 'تقنية');
      expect(post.tags, ['ذكاء', 'برمجة']);
      expect(post.images.length, 1);
      expect(post.media.last.isVideo, isTrue);
      expect(post.author?.isCompany, isTrue);
      expect(post.likesCount, 4);
      expect(post.ratingAvg, isNull);
      expect(post.isOwner, isTrue);
      expect(post.myReaction, 2);
    });

    test('copyWith يلغي التفاعل عند clearReaction', () {
      final post = Post.fromJson(json);

      expect(post.copyWith(likesCount: 3, clearReaction: true).myReaction, isNull);
      expect(post.copyWith(likesCount: 3).myReaction, 2);
    });
  });

  group('FeedItem.fromJson', () {
    test('منشور أصلي', () {
      final item = FeedItem.fromJson({'id': 1, 'type': 'post', 'title': 'a'});

      expect(item, isNotNull);
      expect(item!.isShare, isFalse);
      expect(item.key, 'post-1');
    });

    test('مشاركة لمنشور', () {
      final item = FeedItem.fromJson({
        'id': 8,
        'type': 'share',
        'share_text': 'أنصح بالحضور',
        'is_owner': true,
        'shared_by': {'id': 2, 'name': 'سارة', 'type': 'user'},
        'post': {'id': 1, 'title': 'a'},
      });

      expect(item!.isShare, isTrue);
      expect(item.key, 'share-8');
      expect(item.post.id, 1);
      expect(item.sharedBy?.name, 'سارة');
      expect(item.isMyShare, isTrue);
    });

    test('مشاركة لمنشور لم يعد متاحاً تُتجاهل', () {
      expect(FeedItem.fromJson({'id': 8, 'type': 'share', 'post': null}), isNull);
    });
  });

  group('Paginated', () {
    test('يقرأ العناصر وبيانات الصفحات والمفاتيح الإضافية', () {
      final page = Paginated.fromJson(
        {
          'data': [
            {'id': 1, 'name': 'تقنية'},
            {'id': 2, 'name': 'طب'},
          ],
          'meta': {'current_page': 1, 'last_page': 3, 'per_page': 2, 'total': 6},
          'unread_count': 4,
        },
        Category.fromJson,
      );

      expect(page.items.map((category) => category.name), ['تقنية', 'طب']);
      expect(page.hasMore, isTrue);
      expect(page.total, 6);
      expect(page.extra['unread_count'], 4);
    });

    test('رد بدون meta لا يسبب خطأ', () {
      final page = Paginated.fromJson(<String, dynamic>{'data': <dynamic>[]}, Category.fromJson);

      expect(page.items, isEmpty);
      expect(page.hasMore, isFalse);
    });
  });

  group('PostFilters', () {
    test('عدد الفلاتر المفعّلة لا يتضمن البحث', () {
      const filters = PostFilters(keyword: 'flutter', categoryId: 2, isOnline: true);

      expect(filters.activeCount, 2);
      expect(filters.isEmpty, isFalse);
      expect(const PostFilters(keyword: '  ').isEmpty, isTrue);
    });
  });

  group('VerificationStatus.fromJson', () {
    test('يقرأ حالة طلب المستخدم وطلب الشركة', () {
      final status = VerificationStatus.fromJson({
        'status': 'rejected',
        'is_verified': false,
        'account_type': 'user',
        'user': {
          'status': 'rejected',
          'rejection_reasons': ['الصورة غير واضحة'],
          'attempts_left': 2,
          'can_submit': true,
          'submitted_at': '2026-10-01T10:00:00Z',
        },
        'company': {'status': 'not_submitted', 'rejection_reasons': <String>[], 'attempts_left': 3, 'can_submit': true},
      });

      expect(status.user.status, RequestStatus.rejected);
      expect(status.user.rejectionReasons, ['الصورة غير واضحة']);
      expect(status.user.attemptsLeft, 2);
      expect(status.company.status, RequestStatus.notSubmitted);
      expect(status.isCompany, isFalse);
    });
  });

  group('Validators', () {
    test('البريد الإلكتروني', () {
      expect(Validators.email('user@test.com'), isNull);
      expect(Validators.email('user@test'), isNotNull);
      expect(Validators.email(''), isNotNull);
    });

    test('كلمة المرور: 8 محارف مع حرف ورقم', () {
      expect(Validators.password('abc12345'), isNull);
      expect(Validators.password('abcdefgh'), isNotNull);
      expect(Validators.password('12345678'), isNotNull);
      expect(Validators.password('ab12'), isNotNull);
    });

    test('الرقم الوطني 11 رقماً', () {
      expect(Validators.digits('01234567890', 11, 'الرقم الوطني'), isNull);
      expect(Validators.digits('0123', 11, 'الرقم الوطني'), isNotNull);
    });

    test('الهاتف والرابط الاختياريان', () {
      expect(Validators.phone('', optional: true), isNull);
      expect(Validators.phone('+963912345678'), isNull);
      expect(Validators.phone('abc'), isNotNull);
      expect(Validators.url(''), isNull);
      expect(Validators.url('https://example.com'), isNull);
      expect(Validators.url('example'), isNotNull);
    });
  });
}
