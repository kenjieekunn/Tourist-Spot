import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart' hide MapType;
import 'package:geolocator/geolocator.dart';
import 'package:tourist_spot_app/models/tourist_spot_model.dart';
import 'package:tourist_spot_app/services/location_service.dart';
import 'package:tourist_spot_app/services/api_service.dart';
import 'package:map_launcher/map_launcher.dart';

class TouristSpotMapScreen extends StatefulWidget {
  final TouristSpot touristSpot;

  const TouristSpotMapScreen({
    Key? key,
    required this.touristSpot,
  }) : super(key: key);

  @override
  State<TouristSpotMapScreen> createState() => _TouristSpotMapScreenState();
}

class _TouristSpotMapScreenState extends State<TouristSpotMapScreen> {
  late GoogleMapController _mapController;
  Position? _userPosition;
  Set<Marker> _markers = {};
  Set<Polyline> _polylines = {};
  double? _distanceInMeters;
  List<Map<String, dynamic>> _directions = [];
  bool _isLoading = true;
  bool _locationError = false;

  @override
  void initState() {
    super.initState();
    _initializeMap();
  }

  Future<void> _initializeMap() async {
    try {
      // Request location permission
      final hasPermission = await LocationService.requestLocationPermission();
      if (!hasPermission) {
        setState(() {
          _locationError = true;
          _isLoading = false;
        });
        return;
      }

      // Get current user position
      final position = await LocationService.getCurrentPosition();
      if (position == null) {
        setState(() {
          _locationError = true;
          _isLoading = false;
        });
        return;
      }

      setState(() {
        _userPosition = position;
      });

      // Calculate distance
      _calculateDistance();

      // Setup map markers and polylines
      _setupMapElements();

      // Fetch directions
      await _fetchDirections();

      setState(() {
        _isLoading = false;
      });
    } catch (e) {
      print('Error initializing map: $e');
      setState(() {
        _locationError = true;
        _isLoading = false;
      });
    }
  }

  void _calculateDistance() {
    if (_userPosition == null) return;

    final distance = LocationService.calculateDistance(
      _userPosition!.latitude,
      _userPosition!.longitude,
      widget.touristSpot.latitude,
      widget.touristSpot.longitude,
    );

    setState(() {
      _distanceInMeters = distance;
    });
  }

  void _setupMapElements() {
    if (_userPosition == null) return;

    // Create user location marker
    final userMarker = Marker(
      markerId: const MarkerId('user_location'),
      position: LatLng(_userPosition!.latitude, _userPosition!.longitude),
      infoWindow: const InfoWindow(
        title: 'Your Location',
        snippet: 'Current position',
      ),
      icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueBlue),
    );

    // Create destination marker
    final destMarker = Marker(
      markerId: const MarkerId('destination'),
      position:
          LatLng(widget.touristSpot.latitude, widget.touristSpot.longitude),
      infoWindow: InfoWindow(
        title: widget.touristSpot.name,
        snippet: widget.touristSpot.address,
      ),
      icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
    );

    setState(() {
      _markers = {userMarker, destMarker};
    });

    // Create polyline (direct line to destination)
    final polyline = Polyline(
      polylineId: const PolylineId('route'),
      color: Colors.blue,
      width: 3,
      points: [
        LatLng(_userPosition!.latitude, _userPosition!.longitude),
        LatLng(widget.touristSpot.latitude, widget.touristSpot.longitude),
      ],
      geodesic: true,
    );

    setState(() {
      _polylines = {polyline};
    });
  }

  Future<void> _fetchDirections() async {
    try {
      if (_userPosition == null) return;

      final apiService = ApiService();
      final directions = await apiService.getDirections(
        originLat: _userPosition!.latitude,
        originLng: _userPosition!.longitude,
        destLat: widget.touristSpot.latitude,
        destLng: widget.touristSpot.longitude,
      );

      setState(() {
        _directions = directions;
      });
    } catch (e) {
      print('Error fetching directions: $e');
      // Fall back to mock directions if API fails
      final mockDirections = _generateMockDirections();
      setState(() {
        _directions = mockDirections;
      });
    }
  }

  List<Map<String, dynamic>> _generateMockDirections() {
    // Mock directions - in production, fetch from API
    return [
      {
        'instruction': 'Start heading towards the destination',
        'distance': '100 m',
        'duration': '1 min'
      },
      {
        'instruction': 'Continue straight',
        'distance': '250 m',
        'duration': '2 mins'
      },
      {
        'instruction': 'Turn left onto Main Street',
        'distance': '150 m',
        'duration': '1 min',
      },
      {
        'instruction': 'Arrive at destination on the right',
        'distance': '50 m',
        'duration': '1 min'
      },
    ];
  }

  Future<void> _launchNativeMaps() async {
    try {
      // Launch native maps app (Google Maps on Android, Apple Maps on iOS)
      final coords = Coords(
        widget.touristSpot.latitude,
        widget.touristSpot.longitude,
      );

      await MapLauncher.showMarker(
        mapType: MapType.google,
        coords: coords,
        title: widget.touristSpot.name,
        description: widget.touristSpot.address ?? '',
      );
    } catch (e) {
      print('Error launching maps: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Could not launch maps: $e')),
      );
    }
  }

  void _onMapCreated(GoogleMapController controller) {
    _mapController = controller;

    // Animate camera to show both user and destination
    if (_userPosition != null) {
      final bounds = _calculateBounds();
      _mapController.animateCamera(
        CameraUpdate.newLatLngBounds(bounds, 100),
      );
    }
  }

  LatLngBounds _calculateBounds() {
    final userLat = _userPosition!.latitude;
    final userLng = _userPosition!.longitude;
    final destLat = widget.touristSpot.latitude;
    final destLng = widget.touristSpot.longitude;

    final southwestLat = userLat < destLat ? userLat : destLat;
    final southwestLng = userLng < destLng ? userLng : destLng;
    final northeastLat = userLat > destLat ? userLat : destLat;
    final northeastLng = userLng > destLng ? userLng : destLng;

    return LatLngBounds(
      southwest: LatLng(southwestLat - 0.01, southwestLng - 0.01),
      northeast: LatLng(northeastLat + 0.01, northeastLng + 0.01),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Navigation to ${widget.touristSpot.name}'),
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(
              child: CircularProgressIndicator(),
            )
          : _locationError
              ? _buildLocationErrorWidget()
              : Column(
                  children: [
                    // Map section
                    Expanded(
                      flex: _directions.isEmpty ? 1 : 2,
                      child: Stack(
                        children: [
                          if (_userPosition != null)
                            GoogleMap(
                              onMapCreated: _onMapCreated,
                              initialCameraPosition: CameraPosition(
                                target: LatLng(
                                  _userPosition!.latitude,
                                  _userPosition!.longitude,
                                ),
                                zoom: 15,
                              ),
                              markers: _markers,
                              polylines: _polylines,
                              myLocationEnabled: true,
                              myLocationButtonEnabled: true,
                              zoomControlsEnabled: true,
                            ),
                          // Distance info overlay
                          Positioned(
                            top: 12,
                            left: 12,
                            right: 12,
                            child: Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(8),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withOpacity(0.1),
                                    blurRadius: 8,
                                  ),
                                ],
                              ),
                              child: Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      const Text(
                                        'Distance',
                                        style: TextStyle(
                                          color: Colors.grey,
                                          fontSize: 12,
                                        ),
                                      ),
                                      if (_distanceInMeters != null)
                                        Text(
                                          LocationService.formatDistance(
                                            _distanceInMeters!,
                                          ),
                                          style: const TextStyle(
                                            fontSize: 18,
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                    ],
                                  ),
                                  ElevatedButton.icon(
                                    onPressed: _launchNativeMaps,
                                    icon: const Icon(Icons.navigation),
                                    label: const Text('Navigate'),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: Colors.blue,
                                      foregroundColor: Colors.white,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    // Directions panel
                    if (_directions.isNotEmpty)
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Padding(
                              padding: EdgeInsets.all(12.0),
                              child: Text(
                                'Turn-by-Turn Directions',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                            Expanded(
                              child: ListView.builder(
                                itemCount: _directions.length,
                                itemBuilder: (context, index) {
                                  final direction = _directions[index];
                                  return Padding(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 12,
                                      vertical: 8,
                                    ),
                                    child: Container(
                                      padding: const EdgeInsets.all(12),
                                      decoration: BoxDecoration(
                                        border: Border.all(
                                          color: Colors.grey[300]!,
                                        ),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Row(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Container(
                                            width: 32,
                                            height: 32,
                                            decoration: BoxDecoration(
                                              color: Colors.blue,
                                              borderRadius:
                                                  BorderRadius.circular(16),
                                            ),
                                            child: Center(
                                              child: Text(
                                                '${index + 1}',
                                                style: const TextStyle(
                                                  color: Colors.white,
                                                  fontWeight: FontWeight.bold,
                                                ),
                                              ),
                                            ),
                                          ),
                                          const SizedBox(width: 12),
                                          Expanded(
                                            child: Column(
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.start,
                                              children: [
                                                Text(
                                                  direction['instruction'] ??
                                                      '',
                                                  style: const TextStyle(
                                                    fontSize: 14,
                                                    fontWeight: FontWeight.w500,
                                                  ),
                                                ),
                                                const SizedBox(height: 4),
                                                Text(
                                                  '${direction['distance'] ?? ''} • ${direction['duration'] ?? ''}',
                                                  style: TextStyle(
                                                    fontSize: 12,
                                                    color: Colors.grey[600],
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  );
                                },
                              ),
                            ),
                          ],
                        ),
                      ),
                  ],
                ),
    );
  }

  Widget _buildLocationErrorWidget() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(
            Icons.location_off,
            size: 48,
            color: Colors.grey,
          ),
          const SizedBox(height: 16),
          const Text(
            'Location services not available',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: 24.0),
            child: Text(
              'Please enable location services and grant permission to use the map feature.',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: Colors.grey,
                fontSize: 14,
              ),
            ),
          ),
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: () {
              LocationService.openAppSettings().then((_) {
                setState(() {
                  _isLoading = true;
                  _locationError = false;
                });
                _initializeMap();
              });
            },
            icon: const Icon(Icons.settings),
            label: const Text('Open Settings'),
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _mapController.dispose();
    super.dispose();
  }
}
