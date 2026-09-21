class AuthUser {
  final int id;
  final String name;
  final String email;
  final String? profileImageUrl;
  final String provider;
  final String token;

  AuthUser({
    required this.id,
    required this.name,
    required this.email,
    this.profileImageUrl,
    required this.provider,
    required this.token,
  });

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    final userData = json['user'] ?? json;
    return AuthUser(
      id: userData['id'],
      name: userData['name'] ?? 'User',
      email: userData['email'] ?? '',
      profileImageUrl: userData['profile_image_url'],
      provider: userData['provider'] ?? 'unknown',
      token: json['token'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'user': {
        'id': id,
        'name': name,
        'email': email,
        'profile_image_url': profileImageUrl,
        'provider': provider,
      },
      'token': token,
    };
  }

  AuthUser copyWith({
    int? id,
    String? name,
    String? email,
    String? profileImageUrl,
    String? provider,
    String? token,
  }) {
    return AuthUser(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      profileImageUrl: profileImageUrl ?? this.profileImageUrl,
      provider: provider ?? this.provider,
      token: token ?? this.token,
    );
  }
}
