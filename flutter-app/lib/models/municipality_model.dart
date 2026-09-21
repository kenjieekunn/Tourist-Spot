class Municipality {
  final int id;
  final String name;
  final String? description;
  final String? imageUrl;
  final double latitude;
  final double longitude;
  final int? touristSpotsCount;

  Municipality({
    required this.id,
    required this.name,
    this.description,
    this.imageUrl,
    required this.latitude,
    required this.longitude,
    this.touristSpotsCount,
  });

  factory Municipality.fromJson(Map<String, dynamic> json) {
    return Municipality(
      id: int.parse(json['id'].toString()),
      name: json['name']?.toString() ?? '',
      description: json['description'],
      imageUrl: json['image_url'] ?? json['image_path'],
      latitude: double.parse(json['latitude'].toString()),
      longitude: double.parse(json['longitude'].toString()),
      touristSpotsCount: json['tourist_spots_count'] != null
          ? int.tryParse(json['tourist_spots_count'].toString())
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'image_url': imageUrl,
      'latitude': latitude,
      'longitude': longitude,
      'tourist_spots_count': touristSpotsCount,
    };
  }
}
