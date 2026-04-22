import 'dart:async';
import 'dart:convert';
import 'dart:math';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:http/http.dart' as http;
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import 'package:tourist_spot_app/models/tourist_spot_model.dart';
import 'package:tourist_spot_app/controllers/app_providers.dart';
import 'package:tourist_spot_app/config/constants/api_constants.dart';

class TouristSpotDetailScreen extends ConsumerStatefulWidget {
  final TouristSpot spot;

  const TouristSpotDetailScreen({
    Key? key,
    required this.spot,
  }) : super(key: key);

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
    if (_mapController == null || _userLatLng == null) return;

    final spotLatLng = LatLng(widget.spot.latitude, widget.spot.longitude);
    final userLatLng = _userLatLng!;

    final southwest = LatLng(
      min(spotLatLng.latitude, userLatLng.latitude),
      min(spotLatLng.longitude, userLatLng.longitude),
    );
    final northeast = LatLng(
      max(spotLatLng.latitude, userLatLng.latitude),
      max(spotLatLng.longitude, userLatLng.longitude),
    );

    try {
      _mapController!.animateCamera(
        CameraUpdate.newLatLngBounds(
          LatLngBounds(southwest: southwest, northeast: northeast),
          60,
        ),
      );
    } catch (_) {
      _mapController!.animateCamera(
        CameraUpdate.newLatLng(userLatLng),
      );
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
                decoration: BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  Icons.arrow_back,
                  color: const Color(0xFFFF6B35),
                ),
              ),
            ),
            actions: [
              Padding(
                padding: EdgeInsets.all(8.w),
                child: Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                  ),
                  child: IconButton(
                    icon: Icon(
                      Icons.share,
                      color: const Color(0xFFFF6B35),
                    ),
                    onPressed: () {
                      // Share functionality
                    },
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

    return Column(
      children: [
        SizedBox(
          height: 250.h,
          child: CachedNetworkImage(
            imageUrl: imageUrl,
            fit: BoxFit.cover,
            placeholder: (context, url) =>
                const Center(child: CircularProgressIndicator()),
            errorWidget: (context, url, error) => Center(
              child: Icon(
                Icons.error,
                color: Colors.grey[600],
              ),
            ),
          ),
        ),
        SizedBox(height: 12.h),
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
          SizedBox(height: 8.h),
          Row(
            children: [
              Icon(
                Icons.location_on_outlined,
                size: 16.sp,
                color: const Color(0xFFFF6B35),
              ),
              SizedBox(width: 6.w),
              Expanded(
                child: Text(
                  '${widget.spot.latitude.toStringAsFixed(4)}, ${widget.spot.longitude.toStringAsFixed(4)}',
                  style: GoogleFonts.roboto(
                    fontSize: 12.sp,
                    color: Colors.grey[600],
                  ),
                ),
              ),
            ],
          ),
          SizedBox(height: 6.h),
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
        ],
      ),
    );
  }

  Widget _buildMapSection() {
    final spotLatLng = LatLng(widget.spot.latitude, widget.spot.longitude);
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
          Row(
            children: [
              Icon(
                Icons.map_outlined,
                size: 18.sp,
                color: const Color(0xFFFF6B35),
              ),
              SizedBox(width: 8.w),
              Text(
                'Map',
                style: GoogleFonts.roboto(
                  fontSize: 16.sp,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          SizedBox(height: 12.h),
          ClipRRect(
            borderRadius: BorderRadius.circular(12.r),
            child: SizedBox(
              height: 200.h,
              child: GoogleMap(
                initialCameraPosition: CameraPosition(
                  target: spotLatLng,
                  zoom: 14,
                ),
                markers: markers,
                polylines: polylines,
                myLocationEnabled: _hasLocationPermission,
                myLocationButtonEnabled: _hasLocationPermission,
                zoomControlsEnabled: false,
                onMapCreated: (controller) {
                  _mapController = controller;
                  if (_userLatLng != null) {
                    _updateCameraToBounds();
                    _fetchRouteIfNeeded(force: true);
                  }
                },
              ),
            ),
          ),
          if (_locationError != null) ...[
            SizedBox(height: 8.h),
            Text(
              _locationError!,
              style: GoogleFonts.roboto(
                fontSize: 11.sp,
                color: Colors.red[600],
              ),
            ),
          ] else if (ApiConstants.mapsApiKey.isEmpty) ...[
            SizedBox(height: 8.h),
            Text(
              'Turn-by-turn route requires a Maps API key.',
              style: GoogleFonts.roboto(
                fontSize: 11.sp,
                color: Colors.grey[600],
              ),
            ),
          ] else if (_routeDistance != null || _routeDuration != null) ...[
            SizedBox(height: 8.h),
            Text(
              [
                if (_routeDistance != null) 'Distance: $_routeDistance',
                if (_routeDuration != null) 'ETA: $_routeDuration',
              ].join(' • '),
              style: GoogleFonts.roboto(
                fontSize: 11.sp,
                color: Colors.grey[700],
                fontWeight: FontWeight.w600,
              ),
            ),
          ] else if (_hasLocationPermission) ...[
            SizedBox(height: 8.h),
            Text(
              _userLatLng == null
                  ? 'Waiting for live location...'
                  : 'Live location tracking enabled.',
              style: GoogleFonts.roboto(
                fontSize: 11.sp,
                color: Colors.grey[600],
              ),
            ),
          ],
          if (_routeSteps.isNotEmpty) ...[
            SizedBox(height: 10.h),
            ExpansionTile(
              tilePadding: EdgeInsets.zero,
              childrenPadding: EdgeInsets.zero,
              title: Text(
                'Turn-by-turn',
                style: GoogleFonts.roboto(
                  fontSize: 13.sp,
                  fontWeight: FontWeight.w600,
                ),
              ),
              children: [
                ..._routeSteps.take(8).map(
                      (step) => Padding(
                        padding: EdgeInsets.only(bottom: 6.h),
                        child: Text(
                          step,
                          style: GoogleFonts.roboto(
                            fontSize: 11.sp,
                            color: Colors.grey[700],
                          ),
                        ),
                      ),
                    ),
                if (_routeSteps.length > 8)
                  Text(
                    'Showing first 8 steps.',
                    style: GoogleFonts.roboto(
                      fontSize: 10.sp,
                      color: Colors.grey[500],
                    ),
                  ),
              ],
            ),
          ],
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
            'About',
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
    return Container(
      padding: EdgeInsets.all(16.w),
      margin: EdgeInsets.symmetric(vertical: 8.h),
      color: Colors.grey[50],
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Information',
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
    final dining = _splitFacilities(widget.spot.nearbyDining);
    final gas = _splitFacilities(widget.spot.nearbyGasStations);

    if (dining.isEmpty && gas.isEmpty) {
      return const SizedBox.shrink();
    }

    return Container(
      padding: EdgeInsets.all(16.w),
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
          if (dining.isNotEmpty)
            _buildFacilityList('Dining', dining, Icons.restaurant),
          if (gas.isNotEmpty) ...[
            SizedBox(height: 12.h),
            _buildFacilityList('Gas Stations', gas, Icons.local_gas_station),
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
                child: Text(
                  'Add Review',
                  style: GoogleFonts.roboto(
                    fontSize: 12.sp,
                    color: const Color(0xFFFF6B35),
                    fontWeight: FontWeight.bold,
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
                rating: (review['rating'] ?? 0).toDouble(),
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
        ],
      ),
    );
  }
}
