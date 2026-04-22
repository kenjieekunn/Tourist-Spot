# Pangasinan 2nd District Tourist Navigation & Information System
## Flutter App - Implementation Guide

> **Status**: App Architecture & UI Complete | Ready for Backend Integration & Testing

---

## Project Overview

This is a comprehensive mobile application for the Pangasinan 2nd District Tourist Navigation System, built with Flutter. The app provides a seamless user experience for discovering, exploring, and reviewing tourist attractions across 8 municipalities.

### Municipalities Covered
- Aguilar
- Basista
- Binmaley
- Bugallon
- Labrador
- Lingayen
- Mangatarem
- Urbiztondo

---

## Architecture & Design Pattern

### **State Management: Riverpod**
- Functional reactive programming approach
- Type-safe providers
- Better testability
- Clear dependency injection

### **Navigation: Material Navigation**
- Named route-based navigation
- Route generation with `onGenerateRoute`
- Proper argument passing between screens

### **UI Framework**
- **ScreenUtil** - Responsive design for all screen sizes
- **Google Fonts** - Typography consistency
- **Material Design 3** - Modern design principles

---

## Folder Structure

```
tourist_spot_app/
├── lib/
│   ├── main.dart                          # App entry point (Riverpod enabled)
│   ├── models/
│   │   ├── municipality_model.dart        # Municipality data model
│   │   ├── tourist_spot_model.dart        # Tourist spot details
│   │   ├── review_model.dart              # User reviews
│   │   ├── poi_model.dart                 # Points of Interest (NEW)
│   │   └── user_model.dart                # User profile (NEW)
│   ├── services/
│   │   └── api_service.dart               # API client (Enhanced)
│   ├── providers/
│   │   └── app_providers.dart             # Riverpod providers (NEW)
│   ├── screens/
│   │   ├── municipality_landing_screen.dart    # Home - Municipality selector
│   │   ├── tourist_spots_list_screen.dart      # List of spots per municipality
│   │   ├── tourist_spot_detail_screen.dart     # Detail page with all info
│   │   └── add_review_screen.dart              # Review submission form
│   ├── config/
│   │   ├── constants/
│   │   │   └── api_constants.dart        # API configuration (TODO)
│   │   ├── routes/
│   │   │   └── app_routes.dart           # Navigation routes (Updated)
│   │   └── theme/
│   │       └── app_theme.dart            # App theme configuration
│   └── assets/
│       ├── images/
│       ├── icons/
│       └── animations/
└── pubspec.yaml                           # Dependencies (Updated)
```

---

## Screens & Features

### 1. **Municipality Landing Screen** (`municipality_landing_screen.dart`)
**Purpose**: Home page for browsing municipalities

**Features**:
- Grid layout of 8 municipalities
- Each municipality card shows:
  - Background image
  - Municipality name
  - Number of tourist spots
- Smooth navigation to tourist spots list
- Loading and error states

**Key Components**:
```dart
- Riverpod Provider: municipalitiesProvider
- Gesture Detection for navigation
- NetworkImage + Placeholder handling
- Responsive Grid (2 columns on mobile)
```

---

### 2. **Tourist Spots List Screen** (`tourist_spots_list_screen.dart`)
**Purpose**: Show all touristspots in selected municipality

**Features**:
- Paginated list of tourist spots
- Each card displays:
  - Spot image
  - Spot name & description
  - Rating (if available)
  - View count
- Tap to navigate to detail screen
- Loading and error states

**Key Components**:
```dart
- Riverpod Provider: touristSpotsByMunicipalityProvider
- ListView with Network Images
- Star ratings display
- Error handling with retry
```

---

### 3. **Tourist Spot Detail Screen** (`tourist_spot_detail_screen.dart`)
**Purpose**: Comprehensive information about a specific tourist spot

**Sections**:
1. **Image Carousel**
   - Carousel slider with all spot images
   - Auto-play feature
   - Featured/Rating badges

2. **Quick Actions**
   - Get Directions button
   - Call button
   - Website button

3. **Basic Information**
   - Spot name & description
   - GPS coordinates

4. **Details Section**
   - Opening hours
   - Contact number
   - Price range
   - Amenities (visual chips)

5. **Nearby POIs (Points of Interest)**
   - Categorized by type:
     - **Dining**: Restaurants, Cafes
     - **Essential Services**: ATM, Gas Stations, Pharmacies
   - Distance from spot
   - Brief description

6. **Reviews Section**
   - Latest reviews (top 3)
   - Star rating display
   - User name & comment
   - "Add Review" button link

**Key Components**:
```dart
- Riverpod Providers:
  - touristSpotProvider
  - poisForSpotProvider
  - userReviewsProvider
- CarouselSlider for image gallery
- CachedNetworkImage for performance
- RatingBarIndicator for star display
- CustomScrollView for smooth scrolling
```

---

### 4. **Add Review Screen** (`add_review_screen.dart`)
**Purpose**: Submit new reviews and photos

**Features**:
- Star rating selector (1-5 stars)
- User name field (required)
- Email field (required, validated)
- Review comment (minimum 10 characters)
- Photo upload (up to 5 images)
- Preview of selected images
- Submit button with loading state

**Key Components**:
```dart
- RatingBar for interactive rating selection
- TextFormField with validation
- ImagePicker integration
- Grid display of selected images
- Form validation before submission
- Error/Success notifications with SnackBar
```

---

## Riverpod Providers

Located in `lib/providers/app_providers.dart`:

### **Data Providers**
```dart
// Core API Service
final apiServiceProvider = Provider((ref) => ApiService());

// Fetch all municipalities
final municipalitiesProvider = FutureProvider<List<Municipality>>((ref) async);

// Fetch spots by municipality
final touristSpotsByMunicipalityProvider = 
  FutureProvider.family<List<TouristSpot>, int>((ref, municipalityId) async);

// Fetch single spot details
final touristSpotProvider = 
  FutureProvider.family<TouristSpot, int>((ref, spotId) async);

// Fetch POIs near a spot
final poisForSpotProvider = 
  FutureProvider.family<Map<String, List<POI>>, int>((ref, spotId) async);

// Fetch reviews for a spot
final userReviewsProvider = 
  FutureProvider.family<List<Map<String, dynamic>>, int>((ref, spotId) async);
```

### **State Providers**
```dart
// Store selected municipality
final selectedMunicipalityProvider = 
  StateProvider<Municipality?>((ref) => null);

// Store user's current location
final currentLocationProvider = 
  StateProvider<Map<String, double>?>((ref) => null);
```

---

## API Service Endpoints

All endpoints in `lib/services/api_service.dart`:

### **Municipalities**
```
GET /municipalities              # Get all municipalities
GET /municipalities/{id}         # Get single municipality
```

### **Tourist Spots**
```
GET /tourist-spots               # Get all spots
GET /municipalities/{id}/tourist-spots    # Get spots by municipality
GET /tourist-spots/{id}          # Get spot details
GET /tourist-spots/search?q=term  # Search spots
```

### **POIs**
```
GET /tourist-spots/{id}/pois     # Get POIs near spot
GET /pois/search?lat=X&lng=Y&radius_km=Z  # Search by coordinates
```

### **Reviews**
```
GET /tourist-spots/{id}/reviews              # Get reviews
POST /tourist-spots/{id}/reviews             # Submit new review
POST /reviews/upload-image                   # Upload review image
```

---

## Dependencies

Key packages added/updated in `pubspec.yaml`:

```yaml
# Essential
flutter_riverpod: ^2.4.0          # State management
flutter_screenutil: ^5.9.0        # Responsive UI

# Maps & Location
google_maps_flutter: ^2.5.0       # Maps display
geolocator: ^9.0.0                # GPS/Location
map_launcher: ^4.4.0              # Launch maps app

# UI Components
carousel_slider: ^4.2.1           # Image carousel
flutter_rating_bar: ^4.0.1        # Star ratings
modal_bottom_sheet: ^2.1.2        # Bottom sheets
cached_network_image: ^3.3.0      # Image caching

# Data & API
dio: ^5.3.0                       # HTTP client
json_serializable: optional       # JSON serialization

# Utilities
intl: ^0.19.0                     # Internationalization
image_picker: ^1.0.0              # Photo picker
connectivity_plus: ^5.0.0         # Network monitoring
```

---

## Getting Started

### Prerequisites
- Flutter 3.2.0 or higher
- Dart 3.2.0 or higher
- Android SDK 24+ (for Android)
- Xcode 13+ (for iOS)

### Installation

```bash
# 1. Navigate to project directory
cd flutter-app

# 2. Get dependencies
flutter pub get

# 3. Run the app
flutter run

# 4. Build for release
flutter build apk
flutter build ios
```

---

## Configuration & Setup

### 1. **API Configuration**
Edit `lib/config/constants/api_constants.dart`:

```dart
class ApiConstants {
  static const String baseUrl = 'https://your-api.com/api';
  static const int timeoutSeconds = 30;
}
```

### 2. **Theme Customization**
Edit `lib/config/theme/app_theme.dart` to customize colors and typography.

### 3. **Assets**
Place images/icons in:
- `assets/images/` - For network fallback images
- `assets/icons/` - For app icons
- `assets/animations/` - For Lottie animations

---

## Integration Checklist

- [ ] Configure API endpoints in `api_constants.dart`
- [ ] Verify API response format matches models
- [ ] Test data fetching with real API
- [ ] Configure Google Maps API key (Android & iOS)
- [ ] Set up image hosting/CDN for spot images
- [ ] Implement authentication (if required)
- [ ] Set up Firebase (optional, for notifications)
- [ ] Test on physical devices
- [ ] Configure app signing for release
- [ ] Set up play store/app store accounts

---

## Testing

### Run Tests
```bash
# Unit tests
flutter test

# Integration tests
flutter test integration_test/

# Build release APK
flutter build apk --release
```

### Manual Testing Checklist
- [ ] Load municipalities on landing
- [ ] Select municipality and see spots list
- [ ] View spot details with all sections
- [ ] Submit review with validation
- [ ] Test image loading and caching
- [ ] Navigate back between screens
- [ ] Test error states (no internet, API errors)

---

## Troubleshooting

### Common Issues

**Issue**: Gradle build fails
**Solution**: 
```bash
flutter clean
flutter pub get
flutter run
```

**Issue**: Images not loading
**Solution**: 
- Check API endpoints return correct image URLs
- Verify CachedNetworkImage configuration
- Check network connectivity

**Issue**: Riverpod providers not updating
**Solution**:
- Ensure using `ref.watch()` not `ref.read()` for rebuilds
- Check provider is properly defined
- Clear app cache and rebuild

---

## Performance Optimization

✅ **Image Optimization**
- CachedNetworkImage for caching
- Image quality adjustments (85%)
- Lazy loading in lists

✅ **State Management**
- Riverpod for efficient rebuilds
- Only watched providers trigger rebuilds
- Provider family for parameterized data

✅ **Network**
- Dio with timeout configuration
- Proper error handling
- Request/response logging for debugging

---

## Next Steps

1. **Backend Integration**
   - Implement actual API endpoints
   - Test with real data
   - Configure authentication if needed

2. **Advanced Features** (Future)
   - Push notifications
   - Offline support with sqflite
   - User authentication & profiles
   - Favorites/bookmarking
   - Search filters
   - AR features for landmarks

3. **Deployment**
   - Configure app signing
   - Set up CI/CD pipeline
   - Submit to Play Store & App Store

---

## Support & Documentation

- Flutter Docs: https://docs.flutter.dev
- Riverpod Docs: https://riverpod.dev
- Google Maps SDK: https://developers.google.com/maps

---

## App Screenshots & Features Summary

### User Flow
```
Splash Screen (2s)
         ↓
Municipality Landing (Grid of 8)
         ↓
Tourist Spots List (Municipality-specific)
         ↓
Spot Detail (Full information)
    ├─→ Gallery (Carousel)
    ├─→ Info (Hours, Contact, Price)
    ├─→ POIs (Dining, Services)
    ├─→ Reviews (Star ratings, Comments)
    └─→ Add Review (Form + Photo Upload)
```

###Color Scheme
- **Primary**: #FF6B35 (Orange)
- **Secondary**: #F7931E (Light Orange)
- **Background**: #FFFFFF (White)
- **Text**: #333333 (Dark Gray)
- **Accent**: #FFD700 (Gold - ratings)

---

## Version History

**v1.0.0** - Initial Release
- Complete app architecture
- All core screens
- Riverpod state management
- API service integration ready

---

**Last Updated**: March 15, 2026
**Built with**: Flutter 3.2.0 | Dart 3.2.0
**Status**: ✅ Ready for Backend Integration
