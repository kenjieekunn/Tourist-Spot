# Flutter & Laravel Integration Guide

This document explains how the Flutter mobile application connects to the Laravel admin system.

## 📌 Overview

The Flutter app communicates with the Laravel backend through RESTful API endpoints. When admins update tourist spots or manage reviews in the web dashboard, the Flutter app fetches the latest data through these APIs.

## 🔗 Connection Flow

### Data Flow Diagram

```
Flutter App
    ↓
API Endpoints (Laravel)
    ↓
Database (MySQL)
    ↓
Admin Dashboard Updates
    ↓
API Returns Updated Data
    ↓
Flutter App Displays Data
```

## 🌐 API Configuration

### Step 1: Update Flutter App Configuration

In your Flutter app's `services/api_service.dart`:

```dart
class ApiService {
  static const String baseUrl = 'http://your-server-ip:8000/api/v1';
  
  // For local development on Android emulator:
  // static const String baseUrl = 'http://10.0.2.2:8000/api/v1';
  
  // For production:
  // static const String baseUrl = 'https://your-domain.com/api/v1';

  static Future<List<Municipality>> getMunicipalities() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/municipalities'),
      );

      if (response.statusCode == 200) {
        List<dynamic> data = json.decode(response.body)['data'];
        return data
            .map((json) => Municipality.fromJson(json))
            .toList();
      }
      throw Exception('Failed to load municipalities');
    } catch (e) {
      throw Exception('Error: $e');
    }
  }

  static Future<List<TouristSpot>> getTouristSpots(
      int municipalityId) async {
    try {
      final response = await http.get(
        Uri.parse(
            '$baseUrl/municipalities/$municipalityId/spots'),
      );

      if (response.statusCode == 200) {
        List<dynamic> data = json.decode(response.body)['data'];
        return data
            .map((json) => TouristSpot.fromJson(json))
            .toList();
      }
      throw Exception('Failed to load tourist spots');
    } catch (e) {
      throw Exception('Error: $e');
    }
  }

  static Future<TouristSpot> getSpotDetail(int spotId) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/spots/$spotId'),
      );

      if (response.statusCode == 200) {
        return TouristSpot.fromJson(json.decode(response.body)['data']);
      }
      throw Exception('Failed to load spot details');
    } catch (e) {
      throw Exception('Error: $e');
    }
  }

  static Future<bool> submitReview({
    required int spotId,
    required String userName,
    required int rating,
    required String comment,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/spots/$spotId/reviews'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'user_name': userName,
          'rating': rating,
          'comment': comment,
        }),
      );

      if (response.statusCode == 201) {
        return true;
      }
      return false;
    } catch (e) {
      throw Exception('Error: $e');
    }
  }
}
```

## 📱 Flutter Models

Ensure your Flutter models match the API response structure:

### Municipality Model
```dart
class Municipality {
  final int id;
  final String name;
  final String? description;
  final double latitude;
  final double longitude;
  final String? imageUrl;

  Municipality({
    required this.id,
    required this.name,
    this.description,
    required this.latitude,
    required this.longitude,
    this.imageUrl,
  });

  factory Municipality.fromJson(Map<String, dynamic> json) {
    return Municipality(
      id: json['id'],
      name: json['name'],
      description: json['description'],
      latitude: double.parse(json['latitude'].toString()),
      longitude: double.parse(json['longitude'].toString()),
      imageUrl: json['image_url'],
    );
  }
}
```

### Tourist Spot Model
```dart
class TouristSpot {
  final int id;
  final String name;
  final String description;
  final String address;
  final double latitude;
  final double longitude;
  final String? phone;
  final String? website;
  final String? openingHours;
  final double? entranceFee;
  final String? imageUrl;
  final double averageRating;
  final int reviewsCount;

  TouristSpot({
    required this.id,
    required this.name,
    required this.description,
    required this.address,
    required this.latitude,
    required this.longitude,
    this.phone,
    this.website,
    this.openingHours,
    this.entranceFee,
    this.imageUrl,
    this.averageRating = 0.0,
    this.reviewsCount = 0,
  });

  factory TouristSpot.fromJson(Map<String, dynamic> json) {
    return TouristSpot(
      id: json['id'],
      name: json['name'],
      description: json['description'],
      address: json['address'],
      latitude: double.parse(json['latitude'].toString()),
      longitude: double.parse(json['longitude'].toString()),
      phone: json['phone'],
      website: json['website'],
      openingHours: json['opening_hours'],
      entranceFee: json['entrance_fee'] != null
          ? double.parse(json['entrance_fee'].toString())
          : null,
      imageUrl: json['image_url'],
      averageRating: double.parse(json['average_rating'].toString()),
      reviewsCount: json['reviews_count'] ?? 0,
    );
  }
}
```

### Review Model
```dart
class Review {
  final int id;
  final String userName;
  final int rating;
  final String comment;
  final String status;

  Review({
    required this.id,
    required this.userName,
    required this.rating,
    required this.comment,
    required this.status,
  });

  factory Review.fromJson(Map<String, dynamic> json) {
    return Review(
      id: json['id'],
      userName: json['user_name'],
      rating: json['rating'],
      comment: json['comment'],
      status: json['status'],
    );
  }
}
```

## 🔄 Real-time Data Synchronization

### When Admin Updates Data

1. **Admin edits a Tourist Spot** in the web dashboard
2. **Data is saved** to the database immediately
3. **Flutter app fetches** the updated data when:
   - User opens the app
   - User navigates to a specific municipality
   - User pulls to refresh

### When Flutter User Submits a Review

1. **User submits review** from the Flutter app
2. **Review is stored** in the database with status: `pending`
3. **Admin reviews** pending reviews in the dashboard
4. **Admin approves/rejects** the review
5. **Flutter app fetches** approved reviews only

## 🔐 CORS Configuration (if needed)

If you're running WordPress on a different domain/IP, configure CORS in Laravel:

Add to `app/Http/Middleware/api.php` or create new middleware:

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    protected $hosts = [
        'your-domain.com',
        'your-server-ip',
    ];
}
```

Or use CORS package:
```bash
composer require fruitcake/laravel-cors
```

## 🧪 Testing the API

Use Postman or cURL to test endpoints:

### Test Get Municipalities
```bash
curl -X GET http://localhost:8000/api/v1/municipalities
```

### Test Get Tourist Spots
```bash
curl -X GET http://localhost:8000/api/v1/municipalities/1/spots
```

### Test Submit Review
```bash
curl -X POST http://localhost:8000/api/v1/spots/1/reviews \
  -H "Content-Type: application/json" \
  -d '{
    "user_name": "John Doe",
    "rating": 5,
    "comment": "Great place to visit!"
  }'
```

## 🌍 Deployment Configuration

### For Production

1. **Update base URL in Flutter:**
   ```dart
   static const String baseUrl = 'https://your-domain.com/api/v1';
   ```

2. **Configure Laravel `.env`:**
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com
   ```

3. **Enable HTTPS:**
   - Use SSL certificate
   - Update API URLs to use HTTPS

4. **Set proper permissions:**
   ```bash
   chmod -R 755 storage
   chmod -R 755 bootstrap/cache
   ```

## 📊 Database Synchronization

The Flutter app doesn't store a local copy of data by default. Each request fetches fresh data from the API:

- ✅ **Real-time updates** - See admin changes immediately
- ⚠️ **Internet required** - App needs connection to fetch data
- ✅ **Data consistency** - Always current with database

### Optional: Local Caching (Advanced)

To cache data locally for offline access, use `sqflite`:

```dart
Future<void> cacheSpots(List<TouristSpot> spots) async {
  // Store in local database
  final database = openDatabase('tourist_app.db');
  // Implementation details...
}
```

## 🐛 Common Issues & Solutions

### Issue: "Connection Refused"
**Solution:** 
- Ensure Laravel server is running: `php artisan serve`
- Check correct IP/domain in Flutter API configuration
- For emulator: Use `10.0.2.2` instead of `localhost`

### Issue: "CORS Error"
**Solution:**
- Check if Laravel is allowing cross-origin requests
- Install and configure CORS middleware

### Issue: "Reviews not showing"
**Solution:**
- Ensure reviews are approved by admin
- Check that spot ID is correct
- Verify database has review records

### Issue: "Slow data loading"
**Solution:**
- Check internet connection
- Optimize images/content sizes
- Add pagination to API responses

## 📞 Support

For integration issues, refer to:
- Laravel API responses in network inspector
- Flutter console logs for errors
- Check Laravel logs: `storage/logs/laravel.log`

---

**Last Updated:** March 2026
