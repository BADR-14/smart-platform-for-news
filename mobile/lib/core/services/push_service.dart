import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:get/get.dart';

import '../network/api_client.dart';
import '../session/session_service.dart';
import '../utils/feedback.dart';

/// يُستدعى من النظام عند وصول إشعار والتطبيق مغلق أو في الخلفية.
/// الإشعار نفسه يعرضه النظام في شريط الإشعارات؛ لا نحتاج أي معالجة هنا.
@pragma('vm:entry-point')
Future<void> firebaseBackgroundHandler(RemoteMessage message) async {}

/// إشعارات Push عبر Firebase Cloud Messaging.
///
///  - يسجّل توكن الجهاز في الباك-إند عند تسجيل الدخول (ليعرف الخادم أين يرسل).
///  - والتطبيق مفتوح: يعرض الإشعار كرسالة داخل التطبيق ويبلّغ المستمعين عبر [received].
///  - والتطبيق في الخلفية أو مغلق: النظام يعرضه، والضغط عليه يفتح التطبيق ويبلّغ عبر [opened].
///
/// كل الأخطاء تُبتلع بهدوء: التطبيق يعمل كاملاً حتى لو لم يتوفر Firebase على الجهاز.
class PushService extends GetxService {
  PushService(this._api, this._session);

  final ApiClient _api;
  final SessionService _session;

  /// يتغير مع كل إشعار يصل والتطبيق مفتوح (لتحديث عدّاد الإشعارات).
  final received = 0.obs;

  /// يتغير عندما يفتح المستخدم التطبيق بالضغط على إشعار.
  final opened = 0.obs;

  final List<StreamSubscription<dynamic>> _subscriptions = [];
  Worker? _sessionWorker;
  bool _ready = false;
  String? _sentToken;

  Future<void> start() async {
    try {
      await Firebase.initializeApp();
      FirebaseMessaging.onBackgroundMessage(firebaseBackgroundHandler);

      final messaging = FirebaseMessaging.instance;

      // أندرويد 13+ و iOS يطلبان إذن المستخدم لعرض الإشعارات
      await messaging.requestPermission();

      _subscriptions
        ..add(FirebaseMessaging.onMessage.listen(_onForegroundMessage))
        ..add(FirebaseMessaging.onMessageOpenedApp.listen((_) => opened.value++))
        ..add(messaging.onTokenRefresh.listen(_register));

      // التطبيق فُتح من حالة الإغلاق بالضغط على إشعار
      if (await messaging.getInitialMessage() != null) opened.value++;

      _ready = true;

      // التوكن يُسجَّل لكل حساب يسجّل دخوله على هذا الجهاز
      _sessionWorker = ever(_session.account, (account) {
        if (account == null) {
          _sentToken = null;
        } else {
          _syncToken();
        }
      });

      if (_session.isLoggedIn) await _syncToken();
    } catch (error) {
      debugPrint('Push notifications unavailable: $error');
    }
  }

  @override
  void onClose() {
    _sessionWorker?.dispose();
    for (final subscription in _subscriptions) {
      subscription.cancel();
    }
    super.onClose();
  }

  Future<void> _syncToken() async {
    if (!_ready) return;

    try {
      final token = await FirebaseMessaging.instance.getToken();
      if (token != null) await _register(token);
    } catch (error) {
      debugPrint('Could not read the FCM token: $error');
    }
  }

  Future<void> _register(String token) async {
    if (!_session.isLoggedIn || token == _sentToken) return;

    try {
      await _api.post('/fcm-token', body: {'fcm_token': token});
      _sentToken = token;
    } catch (error) {
      // يُعاد المحاولة عند تسجيل الدخول التالي أو تجدّد التوكن
      debugPrint('Could not register the FCM token: $error');
    }
  }

  void _onForegroundMessage(RemoteMessage message) {
    received.value++;

    final notification = message.notification;
    final text = [notification?.title, notification?.body].whereType<String>().where((part) => part.isNotEmpty).join(' — ');
    if (text.isNotEmpty) AppFeedback.info(text);
  }
}
