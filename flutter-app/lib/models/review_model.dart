class Review {
  final int id;
  final int spotId;
  final String userName;
  final String? email;
  final int rating;
  final String comment;
  final bool isVerified;
  final int helpfulCount;
  final List<Map<String, dynamic>>? images;
  final DateTime createdAt;

  Review({
    required this.id,
    required this.spotId,
    required this.userName,
    this.email,
    required this.rating,
    required this.comment,
    required this.isVerified,
    required this.helpfulCount,
    this.images,
    required this.createdAt,
  });

  factory Review.fromJson(Map<String, dynamic> json) {
    return Review(
      id: json['id'],
      spotId: json['spot_id'],
      userName: json['user_name'],
      email: json['email'],
      rating: json['rating'],
      comment: json['comment'],
      isVerified: json['is_verified'] ?? false,
      helpfulCount: json['helpful_count'] ?? 0,
      images: json['images'] != null
          ? List<Map<String, dynamic>>.from(json['images'])
          : null,
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'spot_id': spotId,
      'user_name': userName,
      'email': email,
      'rating': rating,
      'comment': comment,
      'is_verified': isVerified,
      'helpful_count': helpfulCount,
      'images': images,
      'created_at': createdAt.toIso8601String(),
    };
  }
}
