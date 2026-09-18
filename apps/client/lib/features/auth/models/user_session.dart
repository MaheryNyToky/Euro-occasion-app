class TenantInfo {
  final String id;
  final String code;
  final String name;
  final String currency;
  final String timezone;

  TenantInfo({
    required this.id,
    required this.code,
    required this.name,
    required this.currency,
    required this.timezone,
  });

  factory TenantInfo.fromJson(Map<String, dynamic> json) {
    return TenantInfo(
      id: json['id'] ?? '',
      code: json['code'] ?? '',
      name: json['name'] ?? '',
      currency: json['accounting_currency'] ?? 'MGA',
      timezone: json['timezone'] ?? 'UTC',
    );
  }
}

class UserInfo {
  final String id;
  final String name;
  final String email;
  final String status;
  final List<dynamic> permissions;

  UserInfo({
    required this.id,
    required this.name,
    required this.email,
    required this.status,
    required this.permissions,
  });

  factory UserInfo.fromJson(Map<String, dynamic> json) {
    return UserInfo(
      id: json['id'] ?? '',
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      status: json['status'] ?? 'active',
      permissions: json['permissions'] is List ? json['permissions'] : [],
    );
  }
}

class AuthSession {
  final String token;
  final String tokenType;
  final UserInfo user;
  final TenantInfo tenant;

  AuthSession({
    required this.token,
    required this.tokenType,
    required this.user,
    required this.tenant,
  });

  factory AuthSession.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;
    return AuthSession(
      token: data['token'] ?? '',
      tokenType: data['token_type'] ?? 'Bearer',
      user: UserInfo.fromJson(data['user'] as Map<String, dynamic>),
      tenant: TenantInfo.fromJson(data['tenant'] as Map<String, dynamic>),
    );
  }
}
