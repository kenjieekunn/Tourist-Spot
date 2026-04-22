class POI {
  final int id;
  final String name;
  final String
      type; // 'restaurant', 'cafe', 'gas_station', 'convenience_store', 'atm', etc.
  final String description;
  final double latitude;
  final double longitude;
  final double? distance; // Distance from tourist spot in km
  final String? contactNumber;
  final String? websiteUrl;
  final double? rating;
  final int? reviewCount;

  POI({
    required this.id,
    required this.name,
    required this.type,
    required this.description,
    required this.latitude,
    required this.longitude,
    this.distance,
    this.contactNumber,
    this.websiteUrl,
    this.rating,
    this.reviewCount,
  });

  factory POI.fromJson(Map<String, dynamic> json) {
    return POI(
      id: json['id'],
      name: json['name'],
      type: json['type'],
      description: json['description'] ?? '',
      latitude: double.parse(json['latitude'].toString()),
      longitude: double.parse(json['longitude'].toString()),
      distance: json['distance'] != null
          ? double.parse(json['distance'].toString())
          : null,
      contactNumber: json['contact_number'],
      websiteUrl: json['website_url'],
      rating: json['rating']?.toDouble(),
      reviewCount: json['review_count'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'type': type,
      'description': description,
      'latitude': latitude,
      'longitude': longitude,
      'distance': distance,
      'contact_number': contactNumber,
      'website_url': websiteUrl,
      'rating': rating,
      'review_count': reviewCount,
    };
  }

  // Get icon based on POI type
  String getCategory() {
    switch (type.toLowerCase()) {
      case 'restaurant':
      case 'cafe':
        return 'Dining';
      case 'gas_station':
      case 'convenience_store':
      case 'atm':
      case 'pharmacy':
      case 'hospital':
        return 'Essential Services';
      default:
        return 'Services';
    }
  }
}
