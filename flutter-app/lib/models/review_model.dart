import 'dart:convert';

class Review {
  final int id;
  final int? touristSpotId;
  final String userName;
  final int rating;
  final String comment;
  final String? status;
  final List<String> images;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  Review({
    required this.id,
    this.touristSpotId,
    required this.userName,
    required this.rating,
    required this.comment,
    this.status,
    this.images = const [],
    this.createdAt,
    this.updatedAt,
  });

  factory Review.fromJson(Map<String, dynamic> json) {
    final rawImages = json['images'];
    List<String> parsedImages = [];
    if (rawImages is List) {
      parsedImages = rawImages.map((item) => item.toString()).toList();
    } else if (rawImages is String && rawImages.isNotEmpty) {
      try {
        final decoded = jsonDecode(rawImages);
        if (decoded is List) {
          parsedImages = decoded.map((item) => item.toString()).toList();
        }
      } catch (_) {
        parsedImages = [];
      }
    }

    return Review(
      id: int.parse(json['id'].toString()),
      touristSpotId: json['tourist_spot_id'] != null
          ? int.tryParse(json['tourist_spot_id'].toString())
          : null,
      userName: json['user_name']?.toString() ?? 'Anonymous',
      rating: int.tryParse(json['rating'].toString()) ?? 0,
      comment: json['comment']?.toString() ?? '',
      status: json['status']?.toString(),
      images: parsedImages,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'].toString())
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'tourist_spot_id': touristSpotId,
      'user_name': userName,
      'rating': rating,
      'comment': comment,
      'status': status,
      'images': images,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }
}
