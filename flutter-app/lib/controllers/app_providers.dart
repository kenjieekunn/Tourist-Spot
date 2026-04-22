import 'dart:async';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:tourist_spot_app/models/municipality_model.dart';
import 'package:tourist_spot_app/models/tourist_spot_model.dart';
import 'package:tourist_spot_app/services/api_service.dart';

final apiServiceProvider = Provider((ref) => ApiService());

// **REAL-TIME SYNC ✅**: Auto-refresh every 15s – admin edits reflect quickly in Flutter
final touristSpotsByMunicipalityStreamProvider =
    StreamProvider.family<List<TouristSpot>, int>((ref, municipalityId) async* {
  final apiService = ref.watch(apiServiceProvider);
  while (true) {
    try {
      final spots =
          await apiService.getTouristSpotsByMunicipality(municipalityId);
      yield spots;
    } catch (e) {
      yield* Stream.error(e);
    }
    await Future.delayed(const Duration(
        seconds: 15)); // Poll every 15s for faster admin edit reflection ✅
  }
});

// Legacy (manual refresh only)
final touristSpotsByMunicipalityProvider =
    FutureProvider.family<List<TouristSpot>, int>((ref, municipalityId) async {
  final apiService = ref.watch(apiServiceProvider);
  return apiService.getTouristSpotsByMunicipality(municipalityId);
});

// Single Tourist Spot Provider
final touristSpotProvider =
    FutureProvider.family<TouristSpot, int>((ref, spotId) async {
  final apiService = ref.watch(apiServiceProvider);
  return apiService.getTouristSpot(spotId);
});

// Current Location Provider
final currentLocationProvider =
    StateProvider<Map<String, double>?>((ref) => null);

// Selected Municipality Provider
final selectedMunicipalityProvider =
    StateProvider<Municipality?>((ref) => null);

// User Reviews Provider
final userReviewsProvider =
    FutureProvider.family<List<Map<String, dynamic>>, int>((ref, spotId) async {
  final apiService = ref.watch(apiServiceProvider);
  return apiService.getReviews(spotId);
});

// Municipalities Provider
final municipalitiesProvider = FutureProvider<List<Municipality>>((ref) async {
  final apiService = ref.watch(apiServiceProvider);
  return apiService.getMunicipalities();
});
