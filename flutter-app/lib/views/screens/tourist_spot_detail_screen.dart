import 'dart:async';
import 'dart:convert';
import 'dart:math';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:http/http.dart' as http;
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import 'package:map_launcher/map_launcher.dart';
import 'package:share_plus/share_plus.dart';
import 'package:tourist_spot_app/models/tourist_spot_model.dart';
import 'package:tourist_spot_app/controllers/app_providers.dart';
import 'package:tourist_spot_app/config/constants/api_constants.dart';
import 'package:tourist_spot_app/views/widgets/cached_image_widget.dart';

class TouristSpotDetailScreen extends ConsumerStatefulWidget {
  final TouristSpot spot;

  const TouristSpotDetailScreen({
    super.key,
    required this.spot,
  });

  @override
  ConsumerState<TouristSpotDetailScreen> createState() =>
      _TouristSpotDetailScreenState();
}

class _TouristSpotDetailScreenState
    extends ConsumerState<TouristSpotDetailScreen> {
  late ScrollController _scrollController;
  bool _isAppBarVisible = false;
  GoogleMapController? _mapController;
  StreamSubscription<Position>? _positionStream;
  LatLng? _userLatLng;
  bool _hasLocationPermission = false;
  String? _locationError;
  List<LatLng> _routePoints = [];
  String? _routeDistance;
  String? _routeDuration;
  List<String> _routeSteps = [];
  bool _isFetchingRoute = false;
  DateTime? _lastRouteFetch;
  LatLng? _lastRouteFrom;

  @override
  void initState() {
    super.initState();
    _scrollController = ScrollController();
    _scrollController.addListener(_handleScroll);
    _initLocationTracking();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _positionStream?.cancel();
    _mapController?.dispose();
    super.dispose();
  }

  void _handleScroll() {
    if (_scrollController.offset > 100 && !_isAppBarVisible) {
      setState(() => _isAppBarVisible = true);
    } else if (_scrollController.offset <= 100 && _isAppBarVisible) {
      setState(() => _isAppBarVisible = false);
    }
  }

  Future<void> _initLocationTracking() async {
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      if (!mounted) return;
      setState(() {
        _locationError = 'Location services are disabled.';
      });
      return;
    }

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      if (!mounted) return;
      setState(() {
        _hasLocationPermission = false;
        _locationError = 'Location permission is not granted.';
      });
      return;
    }

    if (!mounted) return;
    setState(() {
      _hasLocationPermission = true;
      _locationError = null;
    });

    try {
      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
      if (mounted) {
        _updateUserLocation(position);
      }
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _locationError = 'Unable to get current location.';
      });
    }

    _positionStream = Geolocator.getPositionStream(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.best,
        distanceFilter: 5,
      ),
    ).listen(
      (position) {
        if (mounted) {
          _updateUserLocation(position);
        }
      },
      onError: (_) {
        if (!mounted) return;
        setState(() {
          _locationError = 'Unable to get live location updates.';
        });
      },
    );
  }

  void _updateUserLocation(Position position) {
    final newLatLng = LatLng(position.latitude, position.longitude);
    setState(() {
      _userLatLng = newLatLng;
    });
    _updateCameraToBounds();
    _fetchRouteIfNeeded();
  }

  void _updateCameraToBounds() {
    if (_mapController == null) return;

    final points = _mapPoints();
    if (points.isEmpty) return;

    final southwest = LatLng(
      points.map((point) => point.latitude).reduce(min),
      points.map((point) => point.longitude).reduce(min),
    );
    final northeast = LatLng(
      points.map((point) => point.latitude).reduce(max),
      points.map((point) => point.longitude).reduce(max),
    );

    try {
      _mapController!.animateCamera(
        CameraUpdate.newLatLngBounds(
          LatLngBounds(southwest: southwest, northeast: northeast),
          60,
        ),
      );
    } catch (_) {
      _mapController!.animateCamera(CameraUpdate.newLatLng(points.first));
    }
  }

  Future<void> _fetchRouteIfNeeded({bool force = false}) async {
    if (!_hasLocationPermission || _userLatLng == null) return;
    if (ApiConstants.mapsApiKey.isEmpty) {
      if (!mounted) return;
      setState(() {
        _routePoints = [];
        _routeDistance = null;
        _routeDuration = null;
        _routeSteps = [];
      });
      return;
    }

    final now = DateTime.now();
    final lastFetch = _lastRouteFetch;
    if (!force && lastFetch != null) {
      final secondsSinceLast = now.difference(lastFetch).inSeconds;
      if (secondsSinceLast < 20) return;
    }

    if (!force && _lastRouteFrom != null) {
      final movedMeters = Geolocator.distanceBetween(
        _lastRouteFrom!.latitude,
        _lastRouteFrom!.longitude,
        _userLatLng!.latitude,
        _userLatLng!.longitude,
      );
      if (movedMeters < 30) return;
    }

    await _fetchRoute();
  }

  Future<void> _fetchRoute() async {
    if (_isFetchingRoute || _userLatLng == null) return;
    setState(() {
      _isFetchingRoute = true;
    });

    final origin = '${_userLatLng!.latitude},${_userLatLng!.longitude}';
    final destination = '${widget.spot.latitude},${widget.spot.longitude}';
    final uri = Uri.parse(
      'https://maps.googleapis.com/maps/api/directions/json'
      '?origin=$origin&destination=$destination&mode=driving&key=${ApiConstants.mapsApiKey}',
    );

    try {
      final response = await http.get(uri).timeout(
            const Duration(seconds: 12),
          );
      if (response.statusCode != 200) {
        throw Exception('Directions API error');
      }
      final data = jsonDecode(response.body) as Map<String, dynamic>;
      final routes = (data['routes'] as List<dynamic>?);
      if (routes == null || routes.isEmpty) {
        throw Exception('No routes found');
      }
      final route = routes.first as Map<String, dynamic>;
      final overview = route['overview_polyline'] as Map<String, dynamic>?;
      final points = overview?['points'] as String?;

      String? distanceText;
      String? durationText;
      List<String> stepsText = [];
      final legs = route['legs'] as List<dynamic>?;
      if (legs != null && legs.isNotEmpty) {
        final leg = legs.first as Map<String, dynamic>;
        distanceText = (leg['distance']?['text'])?.toString();
        durationText = (leg['duration']?['text'])?.toString();
        final steps = leg['steps'] as List<dynamic>?;
        if (steps != null) {
          stepsText = steps
              .map((step) {
                final stepMap = step as Map<String, dynamic>;
                final raw = (stepMap['html_instructions'] ?? '').toString();
                final clean = _stripHtml(raw);
                final stepDistance = (stepMap['distance']?['text'])?.toString();
                return stepDistance == null || stepDistance.isEmpty
                    ? clean
                    : '$clean • $stepDistance';
              })
              .where((step) => step.trim().isNotEmpty)
              .toList();
        }
      }

      final decoded = points == null ? <LatLng>[] : _decodePolyline(points);

      if (!mounted) return;
      setState(() {
        _routePoints = decoded;
        _routeDistance = distanceText;
        _routeDuration = durationText;
        _routeSteps = stepsText;
        _lastRouteFetch = DateTime.now();
        _lastRouteFrom = _userLatLng;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _routePoints = [];
        _routeDistance = null;
        _routeDuration = null;
        _routeSteps = [];
      });
    } finally {
      if (mounted) {
        setState(() {
          _isFetchingRoute = false;
        });
      }
    }
  }

  List<LatLng> _decodePolyline(String encoded) {
    final List<LatLng> points = [];
    int index = 0;
    int lat = 0;
    int lng = 0;

    while (index < encoded.length) {
      int shift = 0;
      int result = 0;
      int b;
      do {
        b = encoded.codeUnitAt(index++) - 63;
        result |= (b & 0x1f) << shift;
        shift += 5;
      } while (b >= 0x20);
      final dlat = (result & 1) != 0 ? ~(result >> 1) : (result >> 1);
      lat += dlat;

      shift = 0;
      result = 0;
      do {
        b = encoded.codeUnitAt(index++) - 63;
        result |= (b & 0x1f) << shift;
        shift += 5;
      } while (b >= 0x20);
      final dlng = (result & 1) != 0 ? ~(result >> 1) : (result >> 1);
      lng += dlng;

      points.add(LatLng(lat / 1E5, lng / 1E5));
    }

    return points;
  }

  String _stripHtml(String html) {
    return html
        .replaceAll(RegExp(r'<[^>]*>'), ' ')
        .replaceAll(RegExp(r'\\s+'), ' ')
        .trim();
  }

  @override
  Widget build(BuildContext context) {
    final reviewsAsync = ref.watch(userReviewsProvider(widget.spot.id));

    return Scaffold(
      body: CustomScrollView(
        controller: _scrollController,
        slivers: [
          // AppBar
          SliverAppBar(
            elevation: 0,
            stretch: true,
            pinned: false,
            floating: true,
            backgroundColor: const Color(0xFFFF6B35),
            leading: GestureDetector(
              onTap: () => Navigator.pop(context),
              child: Container(
                margin: EdgeInsets.all(8.w),
                decoration: const BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.arrow_back,
                  color: Color(0xFFFF6B35),
                ),
              ),
            ),
            actions: [
              Padding(
                padding: EdgeInsets.all(8.w),
                child: Container(
                  decoration: const BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                  ),
                  child: IconButton(
                    icon: const Icon(
                      Icons.share,
                      color: Color(0xFFFF6B35),
                    ),
                    onPressed: _shareSpot,
                  ),
                ),
              ),
            ],
            expandedHeight: 0,
          ),

          // Image Gallery
          SliverToBoxAdapter(
            child: _buildImageGallery(),
          ),

          // Basic Info
          SliverToBoxAdapter(
            child: _buildBasicInfo(),
          ),

          // Map
          SliverToBoxAdapter(
            child: _buildMapSection(),
          ),

          // Description
          SliverToBoxAdapter(
            child: _buildDescriptionSection(),
          ),

          // Opening Hours & Contact
          SliverToBoxAdapter(
            child: _buildContactInfo(),
          ),

          // Nearby Facilities
          SliverToBoxAdapter(
            child: _buildNearbyFacilities(),
          ),

          // Reviews Section
          SliverToBoxAdapter(
            child: _buildReviewsSection(reviewsAsync),
          ),

          SliverToBoxAdapter(
            child: SizedBox(height: 30.h),
          ),
        ],
      ),
    );
  }

  Widget _buildImageGallery() {
    final imageUrl = widget.spot.imageUrl ?? '';
    if (imageUrl.isEmpty) {
      return Container(
        height: 250.h,
        color: Colors.grey[300],
        child: Center(
          child: Icon(
            Icons.image_not_supported,
            color: Colors.grey[600],
            size: 50.sp,
          ),
        ),
      );
    }

    return SizedBox(
      height: 250.h,
      child: CachedImageWidget(
        imageUrl: imageUrl,
        fit: BoxFit.cover,
        placeholderBuilder: () => Container(
          color: Colors.grey[300],
          child: Center(
            child: Icon(
              Icons.image_not_supported,
              color: Colors.grey[600],
              size: 50.sp,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildBasicInfo() {
    return Container(
      padding: EdgeInsets.all(16.w),
      color: Colors.white,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            widget.spot.name,
            style: GoogleFonts.roboto(
              fontSize: 24.sp,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 10.h),
          Wrap(
            spacing: 8.w,
            runSpacing: 8.h,
            children: [
              if ((widget.spot.category ?? '').isNotEmpty)
                _buildChip(
                  label: widget.spot.categoryLabel,
                  backgroundColor: const Color(0xFFE8F3FF),
                  textColor: const Color(0xFF1565C0),
                ),
              _buildChip(
                label: widget.spot.municipalityName,
                backgroundColor: const Color(0xFFEAF2FF),
                textColor: const Color(0xFF3B5BDB),
              ),
              _buildChip(
                label: widget.spot.isVerified ? 'APPROVED' : 'PENDING',
                backgroundColor: widget.spot.isVerified
                    ? const Color(0xFFE8F5E9)
                    : const Color(0xFFFFF4DB),
                textColor: widget.spot.isVerified
                    ? const Color(0xFF2E7D32)
                    : const Color(0xFFB26A00),
              ),
              _buildChip(
                label: widget.spot.isOpen ? 'OPEN' : 'CLOSED',
                backgroundColor: widget.spot.isOpen
                    ? const Color(0xFFE8F5E9)
                    : const Color(0xFFFFEBEE),
                textColor: widget.spot.isOpen
                    ? const Color(0xFF2E7D32)
                    : const Color(0xFFC62828),
              ),
            ],
          ),
          SizedBox(height: 10.h),
          Row(
            children: [
              Icon(
                Icons.place,
                size: 16.sp,
                color: const Color(0xFFFF6B35),
              ),
              SizedBox(width: 6.w),
              Expanded(
                child: Text(
                  widget.spot.address,
                  style: GoogleFonts.roboto(
                    fontSize: 12.sp,
                    color: Colors.grey[600],
                  ),
                ),
              ),
            ],
          ),
          SizedBox(height: 10.h),
          if (widget.spot.averageRating != null)
            Container(
              padding: EdgeInsets.symmetric(
                horizontal: 10.w,
                vertical: 4.h,
              ),
              decoration: BoxDecoration(
                color: const Color(0xFFFF6B35),
                borderRadius: BorderRadius.circular(20.r),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    Icons.star,
                    size: 12.sp,
                    color: Colors.white,
                  ),
                  SizedBox(width: 4.w),
                  Text(
                    '${widget.spot.averageRating?.toStringAsFixed(1) ?? 'N/A'} (${widget.spot.reviewsCount ?? 0})',
                    style: GoogleFonts.roboto(
                      fontSize: 10.sp,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildMapSection() {
    final spotLatLng = LatLng(widget.spot.latitude, widget.spot.longitude);
    final facilityMarkers =
        _buildFacilityMarkers(widget.spot.nearbyFacilities ?? []);
    final markers = <Marker>{
      Marker(
        markerId: const MarkerId('spot'),
        position: spotLatLng,
        infoWindow: InfoWindow(
          title: widget.spot.name,
          snippet: widget.spot.address,
        ),
      ),
      if (_userLatLng != null)
        Marker(
          markerId: const MarkerId('user'),
          position: _userLatLng!,
          icon: BitmapDescriptor.defaultMarkerWithHue(
            BitmapDescriptor.hueAzure,
          ),
          infoWindow: const InfoWindow(title: 'Your location'),
        ),
      ...facilityMarkers,
    };

    final hasRoute = _routePoints.isNotEmpty;
    final routePoints = hasRoute
        ? _routePoints
        : (_userLatLng != null ? [_userLatLng!, spotLatLng] : <LatLng>[]);
    final polylines = <Polyline>{
      if (_userLatLng != null)
        Polyline(
          polylineId: const PolylineId('live_route'),
          points: routePoints,
          color: const Color(0xFFFF6B35),
          width: 4,
        ),
    };

    return Container(
      padding: EdgeInsets.all(16.w),
      color: Colors.white,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Location',
            style: GoogleFonts.roboto(
              fontSize: 16.sp,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 12.h),
          ClipRRect(
            borderRadius: BorderRadius.circular(12.r),
            child: SizedBox(
              height: 250.h,
              child: GoogleMap(
                initialCameraPosition: CameraPosition(
                  target: spotLatLng,
                  zoom: 15,
                ),
                markers: markers,
                polylines: polylines,
                myLocationEnabled: _hasLocationPermission,
                myLocationButtonEnabled: _hasLocationPermission,
                zoomControlsEnabled: true,
                mapToolbarEnabled: true,
                onMapCreated: (controller) {
                  _mapController = controller;
                  Future.delayed(const Duration(milliseconds: 500), () {
                    _updateCameraToBounds();
                    _fetchRouteIfNeeded(force: true);
                  });
                },
              ),
            ),
          ),
          SizedBox(height: 12.h),
          Container(
            padding: EdgeInsets.all(12.w),
            decoration: BoxDecoration(
              color: Colors.grey[50],
              borderRadius: BorderRadius.circular(8.r),
              border: Border.all(color: Colors.grey[300]!),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(
                      Icons.location_on,
                      size: 16.sp,
                      color: const Color(0xFFFF6B35),
                    ),
                    SizedBox(width: 8.w),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Coordinates',
                            style: GoogleFonts.roboto(
                              fontSize: 11.sp,
                              color: Colors.grey[600],
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          SizedBox(height: 2.h),
                          Text(
                            '${widget.spot.latitude.toStringAsFixed(6)}, ${widget.spot.longitude.toStringAsFixed(6)}',
                            style: GoogleFonts.roboto(
                              fontSize: 12.sp,
                              fontWeight: FontWeight.w600,
                              color: Colors.grey[800],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          if (_locationError != null) ...[
            SizedBox(height: 8.h),
            Container(
              padding: EdgeInsets.all(10.w),
              decoration: BoxDecoration(
                color: Colors.orange[50],
                borderRadius: BorderRadius.circular(6.r),
                border: Border.all(color: Colors.orange[300]!),
              ),
              child: Row(
                children: [
                  Icon(
                    Icons.info_outline,
                    size: 16.sp,
                    color: Colors.orange[700],
                  ),
                  SizedBox(width: 8.w),
                  Expanded(
                    child: Text(
                      _locationError!,
                      style: GoogleFonts.roboto(
                        fontSize: 11.sp,
                        color: Colors.orange[700],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ] else if (_routeDistance != null || _routeDuration != null) ...[
            SizedBox(height: 8.h),
            Container(
              padding: EdgeInsets.all(10.w),
              decoration: BoxDecoration(
                color: Colors.blue[50],
                borderRadius: BorderRadius.circular(6.r),
                border: Border.all(color: Colors.blue[300]!),
              ),
              child: Row(
                children: [
                  Icon(
                    Icons.directions,
                    size: 16.sp,
                    color: Colors.blue[700],
                  ),
                  SizedBox(width: 8.w),
                  Expanded(
                    child: Text(
                      [
                        if (_routeDistance != null) 'Distance: $_routeDistance',
                        if (_routeDuration != null) 'ETA: $_routeDuration',
                      ].join(' • '),
                      style: GoogleFonts.roboto(
                        fontSize: 11.sp,
                        color: Colors.blue[700],
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ] else if (_hasLocationPermission && _userLatLng == null) ...[
            SizedBox(height: 8.h),
            Container(
              padding: EdgeInsets.all(10.w),
              decoration: BoxDecoration(
                color: Colors.blue[50],
                borderRadius: BorderRadius.circular(6.r),
                border: Border.all(color: Colors.blue[300]!),
              ),
              child: Row(
                children: [
                  SizedBox(
                    width: 16.sp,
                    height: 16.sp,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      valueColor:
                          AlwaysStoppedAnimation<Color>(Colors.blue[700]!),
                    ),
                  ),
                  SizedBox(width: 8.w),
                  Expanded(
                    child: Text(
                      'Waiting for your location...',
                      style: GoogleFonts.roboto(
                        fontSize: 11.sp,
                        color: Colors.blue[700],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
          if (_routeSteps.isNotEmpty) ...[
            SizedBox(height: 12.h),
            ExpansionTile(
              tilePadding: EdgeInsets.zero,
              childrenPadding: EdgeInsets.symmetric(horizontal: 8.w),
              title: Text(
                'Turn-by-turn Directions',
                style: GoogleFonts.roboto(
                  fontSize: 13.sp,
                  fontWeight: FontWeight.w600,
                ),
              ),
              children: [
                ...List.generate(
                  _routeSteps.length > 8 ? 8 : _routeSteps.length,
                  (index) => Padding(
                    padding: EdgeInsets.only(bottom: 8.h),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 24.w,
                          height: 24.w,
                          decoration: BoxDecoration(
                            color: const Color(0xFFFF6B35),
                            borderRadius: BorderRadius.circular(12.r),
                          ),
                          child: Center(
                            child: Text(
                              '${index + 1}',
                              style: GoogleFonts.roboto(
                                fontSize: 10.sp,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                          ),
                        ),
                        SizedBox(width: 10.w),
                        Expanded(
                          child: Text(
                            _routeSteps[index],
                            style: GoogleFonts.roboto(
                              fontSize: 11.sp,
                              color: Colors.grey[700],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                if (_routeSteps.length > 8) ...[
                  SizedBox(height: 8.h),
                  Text(
                    'Showing first 8 of ${_routeSteps.length} steps',
                    style: GoogleFonts.roboto(
                      fontSize: 10.sp,
                      color: Colors.grey[500],
                      fontStyle: FontStyle.italic,
                    ),
                  ),
                ],
              ],
            ),
          ],
          SizedBox(height: 12.h),
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: _openMapNavigation,
                  icon: const Icon(Icons.navigation),
                  label: Text(
                    'Navigate',
                    style: GoogleFonts.roboto(
                      fontSize: 14.sp,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFFF6B35),
                    foregroundColor: Colors.white,
                    padding: EdgeInsets.symmetric(vertical: 12.h),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8.r),
                    ),
                  ),
                ),
              ),
              SizedBox(width: 12.w),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => Navigator.pushNamed(
                    context,
                    '/spot-map',
                    arguments: widget.spot,
                  ),
                  icon: const Icon(Icons.map),
                  label: Text(
                    'Full Map',
                    style: GoogleFonts.roboto(
                      fontSize: 14.sp,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.blue,
                    foregroundColor: Colors.white,
                    padding: EdgeInsets.symmetric(vertical: 12.h),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8.r),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildDescriptionSection() {
    return Container(
      padding: EdgeInsets.all(16.w),
      color: Colors.white,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Description',
            style: GoogleFonts.roboto(
              fontSize: 16.sp,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 10.h),
          Text(
            widget.spot.description,
            style: GoogleFonts.roboto(
              fontSize: 13.sp,
              color: Colors.grey[700],
              height: 1.5,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildContactInfo() {
    final hasInfo = widget.spot.openingDays != null ||
        widget.spot.openingTime != null ||
        widget.spot.closingTime != null ||
        widget.spot.phone != null ||
        widget.spot.website != null ||
        widget.spot.entranceFee != null;

    if (!hasInfo) return const SizedBox.shrink();

    return Container(
      padding: EdgeInsets.all(16.w),
      margin: EdgeInsets.symmetric(vertical: 8.h),
      color: Colors.grey[50],
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Details',
            style: GoogleFonts.roboto(
              fontSize: 16.sp,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 12.h),
          if (widget.spot.openingDays != null &&
              widget.spot.openingDays!.isNotEmpty)
            _buildInfoRow(
              icon: Icons.calendar_today,
              label: 'Opening Days',
              value: widget.spot.openingDays!.join(', '),
            ),
          if (widget.spot.openingTime != null) ...[
            SizedBox(height: 8.h),
            _buildInfoRow(
              icon: Icons.schedule,
              label: 'Opening Time',
              value: widget.spot.openingTime!,
            ),
          ],
          if (widget.spot.closingTime != null) ...[
            SizedBox(height: 8.h),
            _buildInfoRow(
              icon: Icons.schedule,
              label: 'Closing Time',
              value: widget.spot.closingTime!,
            ),
          ],
          if (widget.spot.phone != null) ...[
            SizedBox(height: 8.h),
            _buildInfoRow(
              icon: Icons.phone,
              label: 'Phone',
              value: widget.spot.phone!,
            ),
          ],
          if (widget.spot.website != null) ...[
            SizedBox(height: 8.h),
            _buildInfoRow(
              icon: Icons.web,
              label: 'Website',
              value: widget.spot.website!,
            ),
          ],
          if (widget.spot.entranceFee != null) ...[
            SizedBox(height: 8.h),
            _buildInfoRow(
              icon: Icons.local_activity,
              label: 'Entrance Fee',
              value: '₱${widget.spot.entranceFee!.toStringAsFixed(2)}',
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildInfoRow({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(
          icon,
          size: 18.sp,
          color: const Color(0xFFFF6B35),
        ),
        SizedBox(width: 12.w),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: GoogleFonts.roboto(
                  fontSize: 11.sp,
                  color: Colors.grey[600],
                  fontWeight: FontWeight.w500,
                ),
              ),
              SizedBox(height: 2.h),
              Text(
                value,
                style: GoogleFonts.roboto(
                  fontSize: 13.sp,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildNearbyFacilities() {
    final structuredFacilities = widget.spot.nearbyFacilities ?? [];
    final diningFacilities = _facilitiesByType(structuredFacilities, 'dining');
    final gasFacilities =
        _facilitiesByType(structuredFacilities, 'gas_station');
    final restroomFacilities =
        _facilitiesByType(structuredFacilities, 'restroom');
    final dining = _splitFacilities(widget.spot.nearbyDining);
    final gas = _splitFacilities(widget.spot.nearbyGasStations);

    if (dining.isEmpty &&
        gas.isEmpty &&
        diningFacilities.isEmpty &&
        gasFacilities.isEmpty &&
        restroomFacilities.isEmpty) {
      return const SizedBox.shrink();
    }

    return Container(
      padding: EdgeInsets.all(16.w),
      margin: EdgeInsets.symmetric(vertical: 8.h),
      color: Colors.grey[50],
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Nearby Facilities',
            style: GoogleFonts.roboto(
              fontSize: 16.sp,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 12.h),
          if (diningFacilities.isNotEmpty) ...[
            _buildFacilitySection(
              title: 'Dining',
              icon: Icons.restaurant,
              color: const Color(0xFFE65100),
              facilities: diningFacilities,
            ),
          ] else if (dining.isNotEmpty) ...[
            _buildFacilityList('Dining', dining, Icons.restaurant),
          ],
          if (gasFacilities.isNotEmpty) ...[
            SizedBox(height: 12.h),
            _buildFacilitySection(
              title: 'Gas Stations',
              icon: Icons.local_gas_station,
              color: const Color(0xFF0D47A1),
              facilities: gasFacilities,
            ),
          ] else if (gas.isNotEmpty) ...[
            SizedBox(height: 12.h),
            _buildFacilityList('Gas Stations', gas, Icons.local_gas_station),
          ],
          if (restroomFacilities.isNotEmpty) ...[
            SizedBox(height: 12.h),
            _buildFacilitySection(
              title: 'Restrooms',
              icon: Icons.wc,
              color: const Color(0xFF2E7D32),
              facilities: restroomFacilities,
            ),
          ],
          if (structuredFacilities.isNotEmpty &&
              diningFacilities.isEmpty &&
              gasFacilities.isEmpty &&
              restroomFacilities.isEmpty) ...[
            SizedBox(height: 12.h),
            _buildStructuredFacilitiesList(structuredFacilities),
          ],
        ],
      ),
    );
  }

  List<String> _splitFacilities(String? raw) {
    if (raw == null || raw.trim().isEmpty) return [];
    return raw
        .split(RegExp(r'[\n,]+'))
        .map((item) => item.trim())
        .where((item) => item.isNotEmpty)
        .toList();
  }

  Widget _buildFacilityList(String title, List<String> items, IconData icon) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, size: 16.sp, color: const Color(0xFFFF6B35)),
            SizedBox(width: 6.w),
            Text(
              title,
              style: GoogleFonts.roboto(
                fontSize: 13.sp,
                fontWeight: FontWeight.w600,
                color: const Color(0xFFFF6B35),
              ),
            ),
          ],
        ),
        SizedBox(height: 8.h),
        ...items.map(
          (item) => Padding(
            padding: EdgeInsets.only(bottom: 6.h),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(Icons.circle, size: 6.sp, color: Colors.grey[600]),
                SizedBox(width: 8.w),
                Expanded(
                  child: Text(
                    item,
                    style: GoogleFonts.roboto(
                      fontSize: 12.sp,
                      color: Colors.grey[700],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildFacilitySection({
    required String title,
    required IconData icon,
    required Color color,
    required List<Map<String, dynamic>> facilities,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, size: 16.sp, color: color),
            SizedBox(width: 6.w),
            Text(
              title,
              style: GoogleFonts.roboto(
                fontSize: 13.sp,
                fontWeight: FontWeight.w600,
                color: color,
              ),
            ),
          ],
        ),
        SizedBox(height: 8.h),
        ...facilities.map((facility) {
          final name = facility['name']?.toString() ?? 'Facility';
          return Padding(
            padding: EdgeInsets.only(bottom: 8.h),
            child: Container(
              padding: EdgeInsets.all(12.w),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12.r),
                border: Border.all(color: Colors.grey.shade200),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 34.w,
                    height: 34.w,
                    decoration: BoxDecoration(
                      color: color.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(10.r),
                    ),
                    child: Icon(icon, size: 18.sp, color: color),
                  ),
                  SizedBox(width: 10.w),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          name,
                          style: GoogleFonts.roboto(
                            fontSize: 12.sp,
                            fontWeight: FontWeight.w600,
                            color: Colors.grey[800],
                          ),
                        ),
                        SizedBox(height: 3.h),
                        Text(
                          _facilityCoordinatesLabel(facility),
                          style: GoogleFonts.roboto(
                            fontSize: 10.sp,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                  ),
                  SizedBox(width: 8.w),
                  TextButton.icon(
                    onPressed: _hasFacilityCoordinates(facility)
                        ? () => _navigateToFacility(facility)
                        : null,
                    icon: const Icon(Icons.place, size: 16),
                    label: Text(
                      'Navigate',
                      style: GoogleFonts.roboto(fontSize: 11.sp),
                    ),
                    style: TextButton.styleFrom(
                      foregroundColor: const Color(0xFFFF6B35),
                      padding: EdgeInsets.symmetric(
                        horizontal: 10.w,
                        vertical: 8.h,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        }),
      ],
    );
  }

  Widget _buildReviewsSection(
      AsyncValue<List<Map<String, dynamic>>> reviewsAsync) {
    return Container(
      padding: EdgeInsets.all(16.w),
      color: Colors.white,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Reviews',
                style: GoogleFonts.roboto(
                  fontSize: 16.sp,
                  fontWeight: FontWeight.bold,
                ),
              ),
              GestureDetector(
                onTap: () async {
                  final result = await Navigator.pushNamed(
                    context,
                    '/add-review',
                    arguments: widget.spot,
                  );
                  if (result == true) {
                    ref.invalidate(userReviewsProvider(widget.spot.id));
                  }
                },
                child: Container(
                  padding: EdgeInsets.symmetric(
                    horizontal: 12.w,
                    vertical: 6.h,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFF6B35),
                    borderRadius: BorderRadius.circular(6.r),
                  ),
                  child: Text(
                    'Add Review',
                    style: GoogleFonts.roboto(
                      fontSize: 12.sp,
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
            ],
          ),
          SizedBox(height: 12.h),
          reviewsAsync.when(
            loading: () => const Center(
              child: CircularProgressIndicator(),
            ),
            error: (error, stackTrace) => Text(
              'Error loading reviews',
              style: GoogleFonts.roboto(fontSize: 12.sp, color: Colors.red),
            ),
            data: (reviews) {
              if (reviews.isEmpty) {
                return Container(
                  padding: EdgeInsets.all(20.w),
                  child: Text(
                    'No reviews yet. Be the first to review!',
                    style: GoogleFonts.roboto(
                      fontSize: 12.sp,
                      color: Colors.grey[600],
                    ),
                    textAlign: TextAlign.center,
                  ),
                );
              }
              return Column(
                children: reviews.take(3).map((review) {
                  return _buildReviewItem(review);
                }).toList(),
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildStructuredFacilitiesList(List<Map<String, dynamic>> items) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(Icons.place, size: 16.sp, color: const Color(0xFFFF6B35)),
            SizedBox(width: 6.w),
            Text(
              'Structured Facilities',
              style: GoogleFonts.roboto(
                fontSize: 13.sp,
                fontWeight: FontWeight.w600,
                color: const Color(0xFFFF6B35),
              ),
            ),
          ],
        ),
        SizedBox(height: 8.h),
        ...items.map(
          (facility) {
            final name = facility['name']?.toString() ?? 'Facility';
            final type = facility['type']?.toString() ?? 'facility';
            final hasCoords = _hasFacilityCoordinates(facility);
            return Padding(
              padding: EdgeInsets.only(bottom: 8.h),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Icon(Icons.circle, size: 6.sp, color: Colors.grey[600]),
                  SizedBox(width: 8.w),
                  Expanded(
                    child: Text(
                      '$name (${type.replaceAll('_', ' ')})',
                      style: GoogleFonts.roboto(
                        fontSize: 12.sp,
                        color: Colors.grey[700],
                      ),
                    ),
                  ),
                  if (hasCoords) ...[
                    SizedBox(width: 8.w),
                    TextButton.icon(
                      onPressed: () => _navigateToFacility(facility),
                      icon: const Icon(Icons.place, size: 16),
                      label: Text(
                        'Navigate',
                        style: GoogleFonts.roboto(fontSize: 11.sp),
                      ),
                      style: TextButton.styleFrom(
                        foregroundColor: const Color(0xFFFF6B35),
                        padding: EdgeInsets.symmetric(
                          horizontal: 10.w,
                          vertical: 8.h,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _buildReviewItem(Map<String, dynamic> review) {
    return Container(
      margin: EdgeInsets.only(bottom: 12.h),
      padding: EdgeInsets.all(12.w),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(8.r),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                review['user_name'] ?? 'Anonymous',
                style: GoogleFonts.roboto(
                  fontSize: 12.sp,
                  fontWeight: FontWeight.bold,
                ),
              ),
              RatingBarIndicator(
                rating: double.tryParse(review['rating'].toString()) ?? 0,
                itemBuilder: (context, index) => const Icon(
                  Icons.star,
                  color: Colors.amber,
                ),
                itemCount: 5,
                itemSize: 14.sp,
              ),
            ],
          ),
          SizedBox(height: 6.h),
          Text(
            review['comment'] ?? '',
            style: GoogleFonts.roboto(
              fontSize: 11.sp,
              color: Colors.grey[700],
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          if (review['created_at'] != null) ...[
            SizedBox(height: 6.h),
            Text(
              DateFormat('MMM d, yyyy').format(
                DateTime.tryParse(review['created_at'].toString()) ??
                    DateTime.now(),
              ),
              style: GoogleFonts.roboto(
                fontSize: 10.sp,
                color: Colors.grey[500],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildChip({
    required String label,
    required Color backgroundColor,
    required Color textColor,
  }) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 10.w, vertical: 5.h),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(999.r),
      ),
      child: Text(
        label,
        style: GoogleFonts.roboto(
          fontSize: 10.sp,
          fontWeight: FontWeight.w700,
          color: textColor,
        ),
      ),
    );
  }

  Future<void> _shareSpot() async {
    try {
      final shareText = '''Check out ${widget.spot.name}!

${widget.spot.description}

Location: ${widget.spot.address}
Category: ${widget.spot.category ?? 'Nature'}
Municipality: ${widget.spot.municipalityName}
Status: ${widget.spot.isOpen ? 'Open' : 'Closed'}

Latitude: ${widget.spot.latitude}
Longitude: ${widget.spot.longitude}''';

      await Share.share(
        shareText,
        subject: widget.spot.name,
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Error sharing: $e',
            style: GoogleFonts.roboto(),
          ),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _openMapNavigation() async {
    try {
      final coords = Coords(
        widget.spot.latitude,
        widget.spot.longitude,
      );
      final availableMaps = await MapLauncher.installedMaps;
      if (availableMaps.isEmpty) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'No maps app installed',
              style: GoogleFonts.roboto(),
            ),
            backgroundColor: Colors.red,
          ),
        );
        return;
      }
      if (availableMaps.length == 1) {
        await availableMaps.first.showDirections(
          destination: coords,
        );
      } else {
        if (!mounted) return;
        showModalBottomSheet(
          context: context,
          builder: (context) => Container(
            padding: EdgeInsets.all(16.w),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'Choose Navigation App',
                  style: GoogleFonts.roboto(
                    fontSize: 16.sp,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                SizedBox(height: 16.h),
                ...availableMaps.map((map) => ListTile(
                      leading: CircleAvatar(
                        backgroundColor: Colors.grey[200],
                        child: const Icon(
                          Icons.map,
                          color: Color(0xFFFF6B35),
                        ),
                      ),
                      title: Text(
                        map.mapName,
                        style: GoogleFonts.roboto(fontSize: 14.sp),
                      ),
                      onTap: () async {
                        Navigator.pop(context);
                        await map.showDirections(
                          destination: coords,
                        );
                      },
                    )),
              ],
            ),
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Error opening map: $e',
            style: GoogleFonts.roboto(),
          ),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  List<Map<String, dynamic>> _facilitiesByType(
    List<Map<String, dynamic>> facilities,
    String type,
  ) {
    return facilities
        .where((facility) =>
            facility['type']?.toString().toLowerCase() == type.toLowerCase())
        .toList();
  }

  bool _hasFacilityCoordinates(Map<String, dynamic> facility) {
    return _facilityLatitude(facility) != null &&
        _facilityLongitude(facility) != null;
  }

  double? _facilityLatitude(Map<String, dynamic> facility) {
    return double.tryParse(facility['latitude']?.toString() ?? '');
  }

  double? _facilityLongitude(Map<String, dynamic> facility) {
    return double.tryParse(facility['longitude']?.toString() ?? '');
  }

  String _facilityCoordinatesLabel(Map<String, dynamic> facility) {
    final latitude = _facilityLatitude(facility);
    final longitude = _facilityLongitude(facility);
    if (latitude == null || longitude == null) {
      return 'No coordinates available';
    }
    return 'Pin: ${latitude.toStringAsFixed(6)}, ${longitude.toStringAsFixed(6)}';
  }

  List<LatLng> _mapPoints() {
    final points = <LatLng>[
      LatLng(widget.spot.latitude, widget.spot.longitude),
      if (_userLatLng != null) _userLatLng!,
    ];

    for (final facility in widget.spot.nearbyFacilities ?? []) {
      final latitude = _facilityLatitude(facility);
      final longitude = _facilityLongitude(facility);
      if (latitude != null && longitude != null) {
        points.add(LatLng(latitude, longitude));
      }
    }

    return points;
  }

  Set<Marker> _buildFacilityMarkers(List<Map<String, dynamic>> facilities) {
    return facilities.where(_hasFacilityCoordinates).map((facility) {
      final latitude = _facilityLatitude(facility)!;
      final longitude = _facilityLongitude(facility)!;
      final type = facility['type']?.toString().toLowerCase() ?? '';
      final name = facility['name']?.toString() ?? 'Facility';
      return Marker(
        markerId: MarkerId(
          'facility_${type}_${name.hashCode}_${latitude}_$longitude',
        ),
        position: LatLng(latitude, longitude),
        icon: BitmapDescriptor.defaultMarkerWithHue(
          _facilityMarkerHue(type),
        ),
        infoWindow: InfoWindow(
          title: name,
          snippet: _facilityTypeLabel(type),
        ),
      );
    }).toSet();
  }

  double _facilityMarkerHue(String type) {
    switch (type) {
      case 'dining':
        return BitmapDescriptor.hueOrange;
      case 'gas_station':
        return BitmapDescriptor.hueAzure;
      case 'restroom':
        return BitmapDescriptor.hueGreen;
      default:
        return BitmapDescriptor.hueViolet;
    }
  }

  String _facilityTypeLabel(String type) {
    switch (type) {
      case 'dining':
        return 'Dining';
      case 'gas_station':
        return 'Gas Station';
      case 'restroom':
        return 'Restroom';
      default:
        return 'Nearby Facility';
    }
  }

  Future<void> _navigateToFacility(Map<String, dynamic> facility) async {
    final latitude = _facilityLatitude(facility);
    final longitude = _facilityLongitude(facility);
    if (latitude == null || longitude == null) {
      return;
    }

    try {
      final coords = Coords(latitude, longitude);
      final availableMaps = await MapLauncher.installedMaps;
      if (availableMaps.isEmpty) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'No maps app installed',
              style: GoogleFonts.roboto(),
            ),
            backgroundColor: Colors.red,
          ),
        );
        return;
      }

      final title = facility['name']?.toString() ?? 'Nearby Facility';
      final description = _facilityTypeLabel(
        facility['type']?.toString().toLowerCase() ?? '',
      );

      if (availableMaps.length == 1) {
        await availableMaps.first.showMarker(
          coords: coords,
          title: title,
          description: description,
        );
        return;
      }

      if (!mounted) return;
      showModalBottomSheet(
        context: context,
        builder: (context) => SafeArea(
          child: Container(
            padding: EdgeInsets.all(16.w),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'Open Facility in',
                  style: GoogleFonts.roboto(
                    fontSize: 16.sp,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                SizedBox(height: 16.h),
                ...availableMaps.map(
                  (map) => ListTile(
                    leading: CircleAvatar(
                      backgroundColor: Colors.grey[200],
                      child: const Icon(
                        Icons.place,
                        color: Color(0xFFFF6B35),
                      ),
                    ),
                    title: Text(
                      map.mapName,
                      style: GoogleFonts.roboto(fontSize: 14.sp),
                    ),
                    subtitle: Text(
                      title,
                      style: GoogleFonts.roboto(fontSize: 12.sp),
                    ),
                    onTap: () async {
                      Navigator.pop(context);
                      await map.showMarker(
                        coords: coords,
                        title: title,
                        description: description,
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Error opening facility map: $e',
            style: GoogleFonts.roboto(),
          ),
          backgroundColor: Colors.red,
        ),
      );
    }
  }
}
