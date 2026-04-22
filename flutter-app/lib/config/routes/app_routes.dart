import 'package:flutter/material.dart';
import 'package:tourist_spot_app/models/municipality_model.dart';
import 'package:tourist_spot_app/models/tourist_spot_model.dart';
import 'package:tourist_spot_app/views/screens/municipality_landing_screen.dart';
import 'package:tourist_spot_app/views/screens/tourist_spots_list_screen.dart';
import 'package:tourist_spot_app/views/screens/tourist_spot_detail_screen.dart';
import 'package:tourist_spot_app/views/screens/tourist_spot_map_screen.dart';
import 'package:tourist_spot_app/views/screens/add_review_screen.dart';

class AppRoutes {
  static const String splash = '/';
  static const String home = '/home';
  static const String touristSpotsList = '/tourist-spots-list';
  static const String spotDetail = '/spot-detail';
  static const String spotMap = '/spot-map';
  static const String addReview = '/add-review';

  static Route<dynamic> generateRoute(RouteSettings settings) {
    switch (settings.name) {
      case home:
        return MaterialPageRoute(
          builder: (_) => const MunicipalityLandingScreen(),
        );
      case touristSpotsList:
        final municipality = settings.arguments as Municipality;
        return MaterialPageRoute(
          builder: (_) => TouristSpotsListScreen(municipality: municipality),
        );
      case spotDetail:
        final spot = settings.arguments as TouristSpot;
        return MaterialPageRoute(
          builder: (_) => TouristSpotDetailScreen(spot: spot),
        );
      case spotMap:
        final spot = settings.arguments as TouristSpot;
        return MaterialPageRoute(
          builder: (_) => TouristSpotMapScreen(touristSpot: spot),
        );
      case addReview:
        final spot = settings.arguments as TouristSpot;
        return MaterialPageRoute(
          builder: (_) => AddReviewScreen(spot: spot),
        );
      default:
        return MaterialPageRoute(
          builder: (_) => Scaffold(
            body: Center(
              child: Text('No route defined for ${settings.name}'),
            ),
          ),
        );
    }
  }
}
