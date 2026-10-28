import '../../../core/utils/json.dart';
import '../../shared/models/account.dart';

enum RequestStatus {
  notSubmitted('not_submitted', 'لم يُقدَّم'),
  pending('pending', 'قيد المراجعة'),
  approved('approved', 'موافق عليه'),
  rejected('rejected', 'مرفوض');

  const RequestStatus(this.value, this.label);

  final String value;
  final String label;

  static RequestStatus parse(Object? value) {
    return RequestStatus.values.firstWhere(
      (status) => status.value == value?.toString(),
      orElse: () => RequestStatus.notSubmitted,
    );
  }
}

/// ملخص آخر طلب توثيق من نوع معيّن (مستخدم أو شركة).
class VerificationSummary {
  const VerificationSummary({
    required this.status,
    required this.rejectionReasons,
    required this.attemptsLeft,
    required this.canSubmit,
    this.submittedAt,
  });

  final RequestStatus status;
  final List<String> rejectionReasons;
  final int attemptsLeft;
  final bool canSubmit;
  final DateTime? submittedAt;

  factory VerificationSummary.fromJson(Json json) {
    final reasons = json['rejection_reasons'];

    return VerificationSummary(
      status: RequestStatus.parse(json['status']),
      rejectionReasons: reasons is List ? reasons.map((reason) => reason.toString()).toList() : const [],
      attemptsLeft: asInt(json['attempts_left']),
      canSubmit: asBool(json['can_submit']),
      submittedAt: asDateOrNull(json['submitted_at']),
    );
  }
}

/// حالة توثيق الحساب: الحالة العامة + تفاصيل طلب المستخدم وطلب الشركة.
class VerificationStatus {
  const VerificationStatus({
    required this.status,
    required this.isVerified,
    required this.accountType,
    required this.user,
    required this.company,
  });

  final RequestStatus status;
  final bool isVerified;
  final AccountType accountType;
  final VerificationSummary user;
  final VerificationSummary company;

  bool get isCompany => accountType == AccountType.company;

  factory VerificationStatus.fromJson(Json json) {
    return VerificationStatus(
      status: RequestStatus.parse(json['status']),
      isVerified: asBool(json['is_verified']),
      accountType: AccountType.parse(json['account_type']),
      user: VerificationSummary.fromJson(asJson(json['user'])),
      company: VerificationSummary.fromJson(asJson(json['company'])),
    );
  }
}
