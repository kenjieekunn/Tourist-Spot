import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'dart:convert';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:tourist_spot_app/controllers/auth_providers.dart';
import 'package:tourist_spot_app/models/municipality_model.dart';
import 'package:tourist_spot_app/models/auth_user_model.dart';
import 'package:tourist_spot_app/models/tourist_spot_model.dart';
import 'package:tourist_spot_app/controllers/app_providers.dart';
import 'package:tourist_spot_app/views/widgets/cached_image_widget.dart';
import 'package:tourist_spot_app/config/theme/app_theme.dart';

enum _SpotCategoryFilter {
  all,
  nature,
  beach,
  falls,
  heritage,
  food,
  farm,
  church,
  adventure,
  parks,
  resort,
  favorites,
}

class TouristSpotsListScreen extends ConsumerStatefulWidget {
  final Municipality municipality;

  const TouristSpotsListScreen({
    super.key,
    required this.municipality,
  });

  @override
  ConsumerState<TouristSpotsListScreen> createState() =>
      _TouristSpotsListScreenState();
}

class _TouristSpotsListScreenState
    extends ConsumerState<TouristSpotsListScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';
  _SpotCategoryFilter _selectedCategory = _SpotCategoryFilter.all;
  bool _isMapView = false;
  final Set<int> _updatingFavorites = <int>{};

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final currentUser = ref.watch(authUserProvider);
    final spotsAsync = ref.watch(
      touristSpotsByMunicipalityStreamProvider(widget.municipality.id),
    );

    final now = DateTime.now();
    final timeFormatter = DateFormat('HH:mm');

    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.municipality.name,
          style: GoogleFonts.roboto(
            fontSize: 18.sp,
            fontWeight: FontWeight.bold,
          ),
        ),
        elevation: 0,
        backgroundColor: AppTheme.primaryColor,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => ref.invalidate(
                touristSpotsByMunicipalityStreamProvider(
                    widget.municipality.id)),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(
            touristSpotsByMunicipalityStreamProvider(widget.municipality.id)
                .future),
        child: spotsAsync.when(
          data: (spots) => _buildSpotsList(
            spots,
            context,
            timeFormatter.format(now),
            ref,
            currentUser,
          ),
          loading: () => _buildLoadingState(),
          error: (error, stackTrace) => _buildErrorState(error, context, ref),
        ),
      ),
    );
  }

  Widget _buildLoadingState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.location_on,
            size: 60.sp,
            color: AppTheme.primaryColor,
          ),
          SizedBox(height: 20.h),
          Text(
            'Loading Tourist Spots...',
            style: GoogleFonts.roboto(fontSize: 16.sp),
          ),
          SizedBox(height: 20.h),
          const CircularProgressIndicator(
            valueColor: AlwaysStoppedAnimation<Color>(AppTheme.primaryColor),
          ),
          const SizedBox(height: 10),
          Text(
            'Auto-refreshing every 15 seconds',
            style: GoogleFonts.roboto(fontSize: 12.sp, color: Colors.grey),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorState(Object error, BuildContext context, WidgetRef ref) {
    return ListView(
      children: [
        SizedBox(height: 16.h),
        Padding(
          padding: EdgeInsets.symmetric(horizontal: 16.w),
          child: Container(
            padding: EdgeInsets.all(16.w),
            decoration: BoxDecoration(
              color: Colors.red.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12.r),
              border: Border.all(color: Colors.red, width: 1.5),
            ),
            child: Column(
              children: [
                Row(
                  children: [
                    Icon(Icons.error_outline, color: Colors.red, size: 24.sp),
                    SizedBox(width: 12.w),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Connection Error',
                            style: GoogleFonts.roboto(
                              fontSize: 14.sp,
                              fontWeight: FontWeight.bold,
                              color: Colors.red,
                            ),
                          ),
                          SizedBox(height: 4.h),
                          Text(
                            error.toString(),
                            style: GoogleFonts.roboto(
                                fontSize: 12.sp, color: Colors.grey[800]),
                          ),
                          Text(
                            'Ensure XAMPP server is running and database seeded.',
                            style: GoogleFonts.roboto(
                                fontSize: 12.sp, color: Colors.grey[700]),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                SizedBox(height: 12.h),
                Row(
                  children: [
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: () => ref.invalidate(
                            touristSpotsByMunicipalityStreamProvider(
                                widget.municipality.id)),
                        icon: const Icon(Icons.refresh),
                        label: Text('Retry', style: GoogleFonts.roboto()),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppTheme.primaryColor,
                          padding: EdgeInsets.symmetric(vertical: 12.h),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildSpotsList(List<TouristSpot> spots, BuildContext context,
      String lastUpdated, WidgetRef ref, AuthUser? currentUser) {
    final filteredSpots = _filterSpots(spots, currentUser);

    if (spots.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.location_off, size: 60.sp, color: Colors.grey),
            SizedBox(height: 20.h),
            Text(
              'No Tourist Spots Available',
              style: GoogleFonts.roboto(fontSize: 16.sp, color: Colors.grey),
            ),
            SizedBox(height: 8.h),
            Text(
              'This municipality does not have approved spots yet.',
              style: GoogleFonts.roboto(fontSize: 12.sp, color: Colors.grey),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      );
    }

    if (filteredSpots.isEmpty) {
      return CustomScrollView(
        slivers: [
          SliverToBoxAdapter(
            child: Padding(
              padding: EdgeInsets.all(12.w),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Found ${spots.length} spots',
                    style: GoogleFonts.roboto(
                        fontSize: 14.sp, fontWeight: FontWeight.w600),
                  ),
                  SizedBox(height: 4.h),
                  Text(
                    'Last updated: $lastUpdated',
                    style: GoogleFonts.roboto(
                      fontSize: 11.sp,
                      color: Colors.grey[600],
                    ),
                  ),
                  SizedBox(height: 12.h),
                  _buildSearchBar(),
                  SizedBox(height: 12.h),
                  _buildCategoryChips(),
                  SizedBox(height: 10.h),
                  _buildViewToggle(),
                  SizedBox(height: 12.h),
                  Text(
                    'Showing ${filteredSpots.length} of ${spots.length} spots',
                    style: GoogleFonts.roboto(
                      fontSize: 11.sp,
                      color: Colors.grey[600],
                    ),
                  ),
                ],
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: EdgeInsets.only(top: 40.h),
              child: Column(
                children: [
                  Icon(Icons.filter_alt_off,
                      size: 60.sp, color: Colors.grey[500]),
                  SizedBox(height: 12.h),
                  Text(
                    _selectedCategory == _SpotCategoryFilter.favorites
                        ? 'No favorite spots yet.'
                        : 'No spots match your search or filter.',
                    style: GoogleFonts.roboto(
                      fontSize: 14.sp,
                      fontWeight: FontWeight.w600,
                      color: Colors.grey[700],
                    ),
                    textAlign: TextAlign.center,
                  ),
                  SizedBox(height: 6.h),
                  Text(
                    _selectedCategory == _SpotCategoryFilter.favorites
                        ? 'Use the heart button to save spots.'
                        : 'Try a different keyword or choose another category from the dropdown.',
                    style: GoogleFonts.roboto(
                      fontSize: 12.sp,
                      color: Colors.grey[600],
                    ),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
          ),
        ],
      );
    }

    return CustomScrollView(
      slivers: [
        SliverToBoxAdapter(
          child: Padding(
            padding: EdgeInsets.all(12.w),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Found ${filteredSpots.length} of ${spots.length} spots',
                  style: GoogleFonts.roboto(
                      fontSize: 14.sp, fontWeight: FontWeight.w600),
                ),
                SizedBox(height: 4.h),
                Text(
                  'Last updated: $lastUpdated',
                  style: GoogleFonts.roboto(
                    fontSize: 11.sp,
                    color: Colors.grey[600],
                  ),
                ),
                SizedBox(height: 12.h),
                _buildSearchBar(),
                SizedBox(height: 12.h),
                _buildCategoryChips(),
                SizedBox(height: 10.h),
                _buildViewToggle(),
                SizedBox(height: 12.h),
                Text(
                  'Showing ${filteredSpots.length} of ${spots.length} spots',
                  style: GoogleFonts.roboto(
                    fontSize: 11.sp,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          ),
        ),
        if (_isMapView)
          SliverToBoxAdapter(
            child: Padding(
              padding: EdgeInsets.fromLTRB(12.w, 0, 12.w, 12.h),
              child: _buildSpotsMap(filteredSpots),
            ),
          )
        else
          SliverPadding(
            padding: EdgeInsets.all(12.w),
            sliver: SliverGrid(
              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                crossAxisSpacing: 12.w,
                mainAxisSpacing: 12.w,
                childAspectRatio: 0.75,
              ),
              delegate: SliverChildBuilderDelegate(
                (context, index) =>
                    _buildSpotCard(filteredSpots[index], context),
                childCount: filteredSpots.length,
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildSearchBar() {
    return Container(
      padding: EdgeInsets.all(14.w),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16.r),
        gradient: const LinearGradient(
          colors: [
            Colors.white,
            Color(0xFFFFF7F1),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        border: Border.all(color: const Color(0xFFFFD7C2)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: TextField(
        controller: _searchController,
        onChanged: (value) {
          setState(() {
            _searchQuery = value;
          });
        },
        decoration: InputDecoration(
          hintText: 'Search tourist spots',
          prefixIcon: const Icon(Icons.search),
          suffixIcon: _searchQuery.isNotEmpty
              ? IconButton(
                  icon: const Icon(Icons.clear),
                  onPressed: () {
                    _searchController.clear();
                    setState(() => _searchQuery = '');
                  },
                )
              : null,
          filled: true,
          fillColor: Colors.white,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12.r),
            borderSide: BorderSide.none,
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12.r),
            borderSide: BorderSide(color: Colors.grey.shade300),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12.r),
            borderSide: const BorderSide(color: AppTheme.primaryColor),
          ),
          contentPadding: EdgeInsets.symmetric(
            horizontal: 12.w,
            vertical: 14.h,
          ),
        ),
      ),
    );
  }

  Widget _buildCategoryChips() {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: _SpotCategoryFilter.values.map((filter) {
          return Padding(
            padding: EdgeInsets.only(right: 8.w),
            child: FilterChip(
              label: Text(_categoryFilterLabel(filter)),
              selected: _selectedCategory == filter,
              onSelected: (_) => setState(() => _selectedCategory = filter),
              showCheckmark: false,
              selectedColor: AppTheme.primaryColor.withValues(alpha: 0.14),
              labelStyle: GoogleFonts.roboto(
                fontSize: 12.sp,
                color: _selectedCategory == filter
                    ? AppTheme.primaryColor
                    : Colors.grey[800],
                fontWeight: _selectedCategory == filter
                    ? FontWeight.w700
                    : FontWeight.w500,
              ),
              side: BorderSide(
                color: _selectedCategory == filter
                    ? AppTheme.primaryColor
                    : Colors.grey.shade300,
              ),
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _buildViewToggle() {
    return Align(
      alignment: Alignment.centerRight,
      child: SegmentedButton<bool>(
        segments: const [
          ButtonSegment(
            value: false,
            icon: Icon(Icons.grid_view),
            label: Text('List'),
          ),
          ButtonSegment(
            value: true,
            icon: Icon(Icons.map_outlined),
            label: Text('Map'),
          ),
        ],
        selected: {_isMapView},
        onSelectionChanged: (selection) {
          setState(() => _isMapView = selection.first);
        },
      ),
    );
  }

  Widget _buildSpotsMap(List<TouristSpot> spots) {
    final firstSpot = spots.first;
    final markers = spots.map((spot) {
      return Marker(
        markerId: MarkerId('spot-${spot.id}'),
        position: LatLng(spot.latitude, spot.longitude),
        icon:
            BitmapDescriptor.defaultMarkerWithHue(_categoryHue(spot.category)),
        infoWindow: InfoWindow(
          title: spot.name,
          snippet: spot.categoryLabel,
          onTap: () =>
              Navigator.of(context).pushNamed('/spot-detail', arguments: spot),
        ),
        onTap: () =>
            Navigator.of(context).pushNamed('/spot-detail', arguments: spot),
      );
    }).toSet();

    return ClipRRect(
      borderRadius: BorderRadius.circular(12.r),
      child: SizedBox(
        height: 500.h,
        child: GoogleMap(
          initialCameraPosition: CameraPosition(
            target: LatLng(firstSpot.latitude, firstSpot.longitude),
            zoom: spots.length == 1 ? 14 : 10,
          ),
          markers: markers,
          myLocationButtonEnabled: false,
          zoomControlsEnabled: true,
        ),
      ),
    );
  }

  double _categoryHue(String? category) {
    switch ((category ?? '').toLowerCase()) {
      case 'beach':
        return BitmapDescriptor.hueAzure;
      case 'falls':
      case 'waterfall':
        return BitmapDescriptor.hueCyan;
      case 'heritage':
      case 'church':
        return BitmapDescriptor.hueViolet;
      case 'food':
        return BitmapDescriptor.hueOrange;
      case 'farm':
      case 'nature':
        return BitmapDescriptor.hueGreen;
      case 'adventure':
        return BitmapDescriptor.hueRose;
      default:
        return BitmapDescriptor.hueRed;
    }
  }

  List<TouristSpot> _filterSpots(
      List<TouristSpot> spots, AuthUser? currentUser) {
    final query = _searchQuery.trim().toLowerCase();

    return spots.where((spot) {
      final category = (spot.category ?? '').toLowerCase().trim();
      final matchesCategory = switch (_selectedCategory) {
        _SpotCategoryFilter.all => true,
        _SpotCategoryFilter.nature => {'nature', 'natural'}.contains(category),
        _SpotCategoryFilter.beach => {'beach', 'beaches'}.contains(category),
        _SpotCategoryFilter.falls =>
          {'falls', 'waterfall', 'waterfalls'}.contains(category),
        _SpotCategoryFilter.heritage =>
          {'heritage', 'cultural'}.contains(category),
        _SpotCategoryFilter.food =>
          {'food', 'dining', 'local_food'}.contains(category),
        _SpotCategoryFilter.farm =>
          {'farm', 'agri_tourism', 'agritourism'}.contains(category),
        _SpotCategoryFilter.church =>
          {'church', 'religious'}.contains(category),
        _SpotCategoryFilter.adventure => category == 'adventure',
        _SpotCategoryFilter.parks => {'park', 'parks'}.contains(category),
        _SpotCategoryFilter.resort => category == 'resort',
        _SpotCategoryFilter.favorites => spot.isFavorited,
      };

      final matchesQuery = query.isEmpty ||
          spot.name.toLowerCase().contains(query) ||
          spot.address.toLowerCase().contains(query) ||
          spot.description.toLowerCase().contains(query) ||
          category.contains(query) ||
          (spot.municipalityName).toLowerCase().contains(query);

      return matchesCategory && matchesQuery;
    }).toList();
  }

  String _categoryFilterLabel(_SpotCategoryFilter filter) {
    switch (filter) {
      case _SpotCategoryFilter.all:
        return 'All';
      case _SpotCategoryFilter.heritage:
        return 'Heritage';
      case _SpotCategoryFilter.food:
        return 'Food';
      case _SpotCategoryFilter.farm:
        return 'Farm';
      case _SpotCategoryFilter.church:
        return 'Church';
      case _SpotCategoryFilter.adventure:
        return 'Adventure';
      case _SpotCategoryFilter.beach:
        return 'Beach';
      case _SpotCategoryFilter.parks:
        return 'Parks';
      case _SpotCategoryFilter.falls:
        return 'Falls';
      case _SpotCategoryFilter.nature:
        return 'Nature';
      case _SpotCategoryFilter.resort:
        return 'Resort';
      case _SpotCategoryFilter.favorites:
        return 'Favorites';
    }
  }

  Widget _buildSpotCard(TouristSpot spot, BuildContext context) {
    return GestureDetector(
      onTap: () {
        Navigator.of(context).pushNamed('/spot-detail', arguments: spot);
      },
      child: Card(
        elevation: 4,
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(12.r)),
        child: Stack(
          children: [
            // Background image
            Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12.r),
                color: Colors.grey[300],
              ),
              child: _buildImageWidget(spot),
            ),
            // Gradient overlay
            Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12.r),
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    Colors.transparent,
                    Colors.black.withOpacity(0.7),
                  ],
                ),
              ),
            ),
            Positioned(
              top: 8.h,
              right: 8.w,
              child: _buildFavoriteButton(spot, context),
            ),
            // Content
            Positioned(
              bottom: 0,
              left: 0,
              right: 0,
              child: Padding(
                padding: EdgeInsets.all(12.w),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      spot.name,
                      style: GoogleFonts.roboto(
                        fontSize: 14.sp,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    SizedBox(height: 6.h),
                    Wrap(
                      spacing: 6.w,
                      runSpacing: 6.h,
                      children: [
                        _buildSpotBadge(
                          spot.categoryLabel,
                          const Color(0xFFE8F3FF),
                          const Color(0xFF1565C0),
                        ),
                        _buildSpotBadge(
                          spot.municipalityName,
                          const Color(0xFFEAF2FF),
                          const Color(0xFF3B5BDB),
                        ),
                        _buildSpotBadge(
                          spot.isVerified ? 'APPROVED' : 'PENDING',
                          spot.isVerified
                              ? const Color(0xFFE8F5E9)
                              : const Color(0xFFFFF4DB),
                          spot.isVerified
                              ? const Color(0xFF2E7D32)
                              : const Color(0xFFB26A00),
                        ),
                        _buildSpotBadge(
                          spot.isOpen ? 'OPEN' : 'CLOSED',
                          spot.isOpen
                              ? const Color(0xFFE8F5E9)
                              : const Color(0xFFFFEBEE),
                          spot.isOpen
                              ? const Color(0xFF2E7D32)
                              : const Color(0xFFC62828),
                        ),
                      ],
                    ),
                    SizedBox(height: 6.h),
                    Row(
                      children: [
                        if (spot.averageRating != null) ...[
                          Icon(Icons.star, size: 12.sp, color: Colors.amber),
                          SizedBox(width: 4.w),
                          Text(
                            spot.averageRating?.toStringAsFixed(1) ?? 'N/A',
                            style: GoogleFonts.roboto(
                              fontSize: 11.sp,
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          if (spot.reviewsCount != null) ...[
                            SizedBox(width: 6.w),
                            Text(
                              '(${spot.reviewsCount})',
                              style: GoogleFonts.roboto(
                                fontSize: 10.sp,
                                color: Colors.white70,
                              ),
                            ),
                          ],
                        ],
                      ],
                    ),
                    SizedBox(height: 6.h),
                    Row(
                      children: [
                        Icon(Icons.location_on,
                            size: 12.sp, color: Colors.white70),
                        SizedBox(width: 4.w),
                        Expanded(
                          child: Text(
                            spot.address,
                            style: GoogleFonts.roboto(
                              fontSize: 10.sp,
                              color: Colors.white70,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFavoriteButton(TouristSpot spot, BuildContext context) {
    final isLoading = _updatingFavorites.contains(spot.id);

    return Material(
      color: Colors.black.withOpacity(0.18),
      shape: const CircleBorder(),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: isLoading ? null : () => _handleFavoritePressed(spot, context),
        child: Padding(
          padding: EdgeInsets.all(8.w),
          child: isLoading
              ? SizedBox(
                  width: 18.sp,
                  height: 18.sp,
                  child: const CircularProgressIndicator(
                      strokeWidth: 2,
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white)),
                )
              : Icon(
                  spot.isFavorited ? Icons.favorite : Icons.favorite_border,
                  color: spot.isFavorited ? Colors.red : Colors.white,
                  size: 20.sp,
                ),
        ),
      ),
    );
  }

  Future<void> _handleFavoritePressed(
      TouristSpot spot, BuildContext context) async {
    await _toggleFavorite(spot, context);
  }

  Future<void> _toggleFavorite(TouristSpot spot, BuildContext context) async {
    setState(() {
      _updatingFavorites.add(spot.id);
    });

    try {
      bool newFavoriteState = !spot.isFavorited;
      final currentUser = ref.read(authUserProvider);

      if (currentUser != null) {
        final apiService = ref.read(apiServiceProvider);
        await apiService.toggleSpotFavorite(spot.id);
      } else {
        await _toggleLocalFavorite(spot.id);
      }

      if (!mounted) return;

      await Future.delayed(const Duration(milliseconds: 500));
      ref.invalidate(
          touristSpotsByMunicipalityStreamProvider(widget.municipality.id));

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            newFavoriteState ? 'Added to favorites' : 'Removed from favorites',
            style: GoogleFonts.roboto(),
          ),
          backgroundColor: Colors.green,
          duration: const Duration(seconds: 2),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Could not update favorite: $e',
            style: GoogleFonts.roboto(),
          ),
          backgroundColor: Colors.red,
          duration: const Duration(seconds: 2),
        ),
      );
    } finally {
      if (mounted) {
        setState(() {
          _updatingFavorites.remove(spot.id);
        });
      }
    }
  }

  Future<void> _toggleLocalFavorite(int spotId) async {
    final prefs = await SharedPreferences.getInstance();
    final favoritesJson = prefs.getString('local_favorites') ?? '{}';
    final favorites = Map<String, bool>.from(
      (jsonDecode(favoritesJson) as Map).cast<String, bool>(),
    );

    final key = spotId.toString();
    if (favorites.containsKey(key)) {
      favorites.remove(key);
    } else {
      favorites[key] = true;
    }

    await prefs.setString('local_favorites', jsonEncode(favorites));

    if (!mounted) return;
    ref.invalidate(localFavoritesProvider);
  }

  Widget _buildSpotBadge(
    String label,
    Color backgroundColor,
    Color textColor,
  ) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 6.w, vertical: 2.h),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(4.r),
      ),
      child: Text(
        label,
        style: GoogleFonts.roboto(
          fontSize: 9.sp,
          fontWeight: FontWeight.bold,
          color: textColor,
        ),
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
    );
  }

  Widget _buildImageWidget(TouristSpot spot) {
    final imageUrl = spot.imageUrl ?? '';
    if (imageUrl.isNotEmpty) {
      return CachedImageWidget(
        imageUrl: imageUrl,
        fit: BoxFit.cover,
        placeholderBuilder: _buildImagePlaceholder,
      );
    }
    return _buildImagePlaceholder();
  }

  Widget _buildImagePlaceholder() {
    return Container(
      color: Colors.grey[300],
      child: Center(
        child: Icon(Icons.image_not_supported,
            color: Colors.grey[600], size: 50.sp),
      ),
    );
  }
}
