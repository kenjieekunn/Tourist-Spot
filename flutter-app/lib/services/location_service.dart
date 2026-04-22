import 'package:geolocator/geolocator.dart';

class LocationService {
  static const String TAG = 'LocationService';

  /// Request location permissions and return the status
  static Future<bool> requestLocationPermission() async {
    try {
      final status = await Geolocator.requestPermission();

      if (status == LocationPermission.denied) {
        return false;
      } else if (status == LocationPermission.deniedForever) {
        // Permissions are denied forever, we cannot request them again
        openAppSettings();
        return false;
      }

      return true;
    } catch (e) {
      print('Error requesting location permission: $e');
      return false;
    }
  }

  /// Check if location permissions are granted
  static Future<bool> isLocationPermissionGranted() async {
    try {
      final status = await Geolocator.checkPermission();
      return status == LocationPermission.whileInUse ||
          status == LocationPermission.always;
    } catch (e) {
      print('Error checking location permission: $e');
      return false;
    }
  }

  /// Get the current user position
  static Future<Position?> getCurrentPosition() async {
    try {
      // Check if location services are enabled
      final isServiceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!isServiceEnabled) {
        print('Location services are disabled.');
        return null;
      }

      // Check if we have permission
      final hasPermission = await isLocationPermissionGranted();
      if (!hasPermission) {
        final granted = await requestLocationPermission();
        if (!granted) {
          print('Location permission denied.');
          return null;
        }
      }

      // Get the current position
      final position = await Geolocator.getCurrentPosition(
        timeLimit: const Duration(seconds: 10),
      );

      return position;
    } catch (e) {
      print('Error getting current position: $e');
      return null;
    }
  }

  /// Get a stream of position updates
  static Stream<Position> getPositionStream() {
    return Geolocator.getPositionStream();
  }

  /// Calculate distance between two coordinates in kilometers
  static double calculateDistance(
    double startLatitude,
    double startLongitude,
    double endLatitude,
    double endLongitude,
  ) {
    return Geolocator.distanceBetween(
      startLatitude,
      startLongitude,
      endLatitude,
      endLongitude,
    );
  }

  /// Format distance for display
  static String formatDistance(double meters) {
    if (meters < 1000) {
      return '${meters.toStringAsFixed(0)}m';
    } else {
      final kilometers = meters / 1000;
      return '${kilometers.toStringAsFixed(2)}km';
    }
  }

  /// Open location settings to enable location services or grant permissions
  static Future<bool> openLocationSettings() async {
    return await Geolocator.openLocationSettings();
  }

  /// Open app settings
  static Future<bool> openAppSettings() async {
    return await Geolocator.openAppSettings();
  }
}
