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
  final List<Map<String, dynamic>>? nearbyFacilities;
  final String? status;
  final String? statusReason;
  final String? verificationStatus;
  final bool isFavorited;
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
    this.nearbyFacilities,
    this.status,
    this.statusReason,
    this.verificationStatus,
    this.isFavorited = false,
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

    List<Map<String, dynamic>>? nearbyFacilities;
    final rawFacilities = json['nearby_facilities'];
    if (rawFacilities is List) {
      nearbyFacilities = rawFacilities
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    } else if (rawFacilities is String && rawFacilities.isNotEmpty) {
      try {
        final decoded = jsonDecode(rawFacilities);
        if (decoded is List) {
          nearbyFacilities = decoded
              .whereType<Map>()
              .map((item) => Map<String, dynamic>.from(item))
              .toList();
        }
      } catch (_) {
        nearbyFacilities = null;
      }
    }

    return TouristSpot(
      id: int.parse(json['id'].toString()),
      name: json['name']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      category: json['category']?.toString(),
      latitude: double.parse(json['latitude'].toString()),
      longitude: double.parse(json['longitude'].toString()),
      openingDays: openingDays,
      openingTime: json['opening_time']?.toString(),
      closingTime: json['closing_time']?.toString(),
      phone: json['phone']?.toString(),
      website: json['website']?.toString(),
      entranceFee: json['entrance_fee'] != null
          ? double.tryParse(json['entrance_fee'].toString())
          : null,
      imageUrl: json['image_url']?.toString(),
      nearbyDining: json['nearby_dining']?.toString(),
      nearbyGasStations: json['nearby_gas_stations']?.toString(),
      nearbyFacilities: nearbyFacilities,
      status: json['status']?.toString(),
      statusReason: json['status_reason']?.toString(),
      verificationStatus: json['verification_status']?.toString(),
      isFavorited: json['is_favorited'] == true ||
          json['is_favorited']?.toString() == '1',
      municipality: json['municipality'] is Map
          ? Map<String, dynamic>.from(json['municipality'])
          : null,
      averageRating: json['average_rating'] != null
          ? double.tryParse(json['average_rating'].toString())
          : null,
      reviewsCount: json['reviews_count'] != null
          ? int.tryParse(json['reviews_count'].toString())
          : null,
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
      'nearby_facilities': nearbyFacilities,
      'status': status,
      'status_reason': statusReason,
      'verification_status': verificationStatus,
      'is_favorited': isFavorited,
      'municipality': municipality,
      'average_rating': averageRating,
      'reviews_count': reviewsCount,
    };
  }

  bool get isVerified => verificationStatus == 'approved';

  bool get isOpen => status == 'open' || status == 'active';

  String get statusLabel {
    switch (status) {
      case 'under_maintenance':
        return 'UNDER MAINTENANCE';
      case 'seasonal':
        return 'SEASONAL';
      case 'open':
      case 'active':
        return 'OPEN';
      default:
        return 'CLOSED';
    }
  }

  String get municipalityName => municipality != null
      ? municipality!['name']?.toString() ?? 'Unknown Municipality'
      : 'Unknown Municipality';

  String? get municipalityImageUrl =>
      municipality != null ? municipality!['image_url']?.toString() : null;

  String get categoryLabel =>
      (category ?? 'nature').replaceAll('_', ' ').toUpperCase();
}
