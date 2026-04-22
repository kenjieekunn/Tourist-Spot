import 'dart:convert';

class TouristSpot {
  final int id;
  final String name;
  final String description;
  final String address;
  final String? category;
  final double latitude;
  final double longitude;
  final List<String>? openingDays;
  final String? openingTime;
  final String? closingTime;
  final String? phone;
  final String? website;
  final double? entranceFee;
  final String? imageUrl;
  final String? nearbyDining;
  final String? nearbyGasStations;
  final Map<String, dynamic>? municipality;
  final double? averageRating;
  final int? reviewsCount;

  TouristSpot({
    required this.id,
    required this.name,
    required this.description,
    required this.address,
    this.category,
    required this.latitude,
    required this.longitude,
    this.openingDays,
    this.openingTime,
    this.closingTime,
    this.phone,
    this.website,
    this.entranceFee,
    this.imageUrl,
    this.nearbyDining,
    this.nearbyGasStations,
    this.municipality,
    this.averageRating,
    this.reviewsCount,
  });

  factory TouristSpot.fromJson(Map<String, dynamic> json) {
    // Parse opening_days from JSON string to List<String>
    List<String>? openingDays;
    if (json['opening_days'] != null) {
      try {
        if (json['opening_days'] is String) {
          final decodedList = jsonDecode(json['opening_days']) as List<dynamic>;
          openingDays = decodedList.cast<String>();
        } else if (json['opening_days'] is List) {
          openingDays = (json['opening_days'] as List<dynamic>).cast<String>();
        }
      } catch (e) {
        openingDays = null;
      }
    }

    return TouristSpot(
      id: json['id'],
      name: json['name'],
      description: json['description'],
      address: json['address'] ?? '',
      category: json['category'],
      latitude: double.parse(json['latitude'].toString()),
      longitude: double.parse(json['longitude'].toString()),
      openingDays: openingDays,
      openingTime: json['opening_time'],
      closingTime: json['closing_time'],
      phone: json['phone'],
      website: json['website'],
      entranceFee: json['entrance_fee'] != null
          ? double.tryParse(json['entrance_fee'].toString())
          : null,
      imageUrl: json['image_url'],
      nearbyDining: json['nearby_dining'],
      nearbyGasStations: json['nearby_gas_stations'],
      municipality: json['municipality'],
      averageRating: json['average_rating'] != null
          ? double.tryParse(json['average_rating'].toString())
          : null,
      reviewsCount: json['reviews_count'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'address': address,
      'category': category,
      'latitude': latitude,
      'longitude': longitude,
      'opening_days': openingDays != null ? jsonEncode(openingDays) : null,
      'opening_time': openingTime,
      'closing_time': closingTime,
      'phone': phone,
      'website': website,
      'entrance_fee': entranceFee,
      'image_url': imageUrl,
      'nearby_dining': nearbyDining,
      'nearby_gas_stations': nearbyGasStations,
      'municipality': municipality,
      'average_rating': averageRating,
      'reviews_count': reviewsCount,
    };
  }
}
