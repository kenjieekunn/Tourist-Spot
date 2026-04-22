class User {
  final int? id;
  final String? name;
  final String? email;
  final String? phoneNumber;
  final String? profileImageUrl;
  final int? reviewsCount;
  final DateTime? createdAt;

  User({
    this.id,
    this.name,
    this.email,
    this.phoneNumber,
    this.profileImageUrl,
    this.reviewsCount,
    this.createdAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      phoneNumber: json['phone_number'],
      profileImageUrl: json['profile_image_url'],
      reviewsCount: json['reviews_count'],
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'phone_number': phoneNumber,
      'profile_image_url': profileImageUrl,
      'reviews_count': reviewsCount,
      'created_at': createdAt?.toIso8601String(),
    };
  }
}
