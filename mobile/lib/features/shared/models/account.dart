import '../../../core/utils/json.dart';

enum AccountType {
  user,
  company,
  admin;

  static AccountType parse(Object? value) {
    return AccountType.values.firstWhere(
      (type) => type.name == value?.toString(),
      orElse: () => AccountType.user,
    );
  }
}

/// الحساب المسجّل دخوله (يرجع مع login و /me).
class Account {
  const Account({
    required this.id,
    required this.name,
    required this.email,
    required this.type,
    required this.verificationStatus,
    required this.isVerified,
    this.avatar,
    this.companyId,
  });

  final int id;
  final String name;
  final String email;
  final String? avatar;
  final AccountType type;
  final String verificationStatus;
  final bool isVerified;
  final int? companyId;

  bool get isCompany => type == AccountType.company;

  /// النشر متاح للحسابات الموثّقة فقط (مستخدم موثّق أو شركة موافق عليها).
  bool get canPublish => isVerified;

  factory Account.fromJson(Json json) {
    return Account(
      id: asInt(json['id']),
      name: asString(json['name']),
      email: asString(json['email']),
      avatar: asStringOrNull(json['avatar']),
      type: AccountType.parse(json['type']),
      verificationStatus: asString(json['verification_status'], 'not_submitted'),
      isVerified: asBool(json['is_verified']),
      companyId: asIntOrNull(json['company_id']),
    );
  }

  Account copyWith({
    String? name,
    String? avatar,
    AccountType? type,
    String? verificationStatus,
    bool? isVerified,
    int? companyId,
  }) {
    return Account(
      id: id,
      name: name ?? this.name,
      email: email,
      avatar: avatar ?? this.avatar,
      type: type ?? this.type,
      verificationStatus: verificationStatus ?? this.verificationStatus,
      isVerified: isVerified ?? this.isVerified,
      companyId: companyId ?? this.companyId,
    );
  }
}

/// ملخص الحساب كما يظهر بجانب المنشورات والتعليقات.
class Author {
  const Author({
    required this.id,
    required this.name,
    required this.type,
    required this.isVerified,
    this.avatar,
    this.companyId,
  });

  final int id;
  final String name;
  final String? avatar;
  final AccountType type;
  final bool isVerified;
  final int? companyId;

  bool get isCompany => type == AccountType.company;

  factory Author.fromJson(Json json) {
    return Author(
      id: asInt(json['id']),
      name: asString(json['name'], 'مستخدم'),
      avatar: asStringOrNull(json['avatar']),
      type: AccountType.parse(json['type']),
      isVerified: asBool(json['is_verified']),
      companyId: asIntOrNull(json['company_id']),
    );
  }
}

class Category {
  const Category({required this.id, required this.name});

  final int id;
  final String name;

  factory Category.fromJson(Json json) {
    return Category(id: asInt(json['id']), name: asString(json['name']));
  }
}
