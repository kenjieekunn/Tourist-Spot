import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import 'package:map_launcher/map_launcher.dart';
import 'package:share_plus/share_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:tourist_spot_app/models/tourist_spot_model.dart';
import 'package:tourist_spot_app/controllers/app_providers.dart';
import 'package:tourist_spot_app/controllers/auth_providers.dart';
import 'package:tourist_spot_app/views/widgets/cached_image_widget.dart';
import 'package:tourist_spot_app/config/theme/app_theme.dart';

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
  late bool _isSaved;
  int _currentGalleryPage = 0;

  @override
  void initState() {
    super.initState();
    _isSaved = widget.spot.isFavorited;
    _scrollController = ScrollController();
    _scrollController.addListener(_handleScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _handleScroll() {
    if (_scrollController.offset > 100 && !_isAppBarVisible) {
      setState(() => _isAppBarVisible = true);
    } else if (_scrollController.offset <= 100 && _isAppBarVisible) {
      setState(() => _isAppBarVisible = false);
    }
  }

  Future<void> _toggleSaved() async {
    final shouldSave = !_isSaved;
    try {
      if (ref.read(authUserProvider) != null) {
        await ref.read(apiServiceProvider).toggleSpotFavorite(widget.spot.id);
      } else {
        final prefs = await SharedPreferences.getInstance();
        final encoded = prefs.getString('local_favorites') ?? '{}';
        final favorites = Map<String, bool>.from(
          (jsonDecode(encoded) as Map).cast<String, bool>(),
        );
        final key = widget.spot.id.toString();
        if (shouldSave) {
          favorites[key] = true;
        } else {
          favorites.remove(key);
        }
        await prefs.setString('local_favorites', jsonEncode(favorites));
        ref.invalidate(localFavoritesProvider);
      }

      if (!mounted) return;
      setState(() => _isSaved = shouldSave);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(shouldSave ? 'Spot saved' : 'Spot removed')),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Could not update saved spot: $error')),
      );
    }
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
            backgroundColor: AppTheme.primaryColor,
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
                  color: AppTheme.primaryColor,
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
                      color: AppTheme.primaryColor,
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
            child: SizedBox(height: 96.h),
          ),
        ],
      ),
      bottomNavigationBar: SafeArea(
        top: false,
        child: Container(
          padding: EdgeInsets.fromLTRB(16.w, 10.h, 16.w, 10.h),
          decoration: BoxDecoration(
            color: Colors.white,
            border: Border(top: BorderSide(color: Colors.grey.shade200)),
          ),
          child: Row(
            children: [
              Expanded(
                child: FilledButton.icon(
                  onPressed: _openMapNavigation,
                  icon: const Icon(Icons.navigation),
                  label: const Text('Navigate'),
                  style: FilledButton.styleFrom(
                    backgroundColor: AppTheme.primaryColor,
                    minimumSize: Size.fromHeight(48.h),
                  ),
                ),
              ),
              SizedBox(width: 10.w),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: _toggleSaved,
                  icon: Icon(_isSaved ? Icons.bookmark : Icons.bookmark_border),
                  label: Text(_isSaved ? 'Saved' : 'Save'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppTheme.primaryColor,
                    side: const BorderSide(color: AppTheme.primaryColor),
                    minimumSize: Size.fromHeight(48.h),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildImageGallery() {
    final images = widget.spot.imageUrls.isNotEmpty
        ? widget.spot.imageUrls
        : [if ((widget.spot.imageUrl ?? '').isNotEmpty) widget.spot.imageUrl!];
    if (images.isEmpty) {
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
      child: Stack(
        fit: StackFit.expand,
        children: [
          PageView.builder(
            itemCount: images.length,
            onPageChanged: (index) =>
                setState(() => _currentGalleryPage = index),
            itemBuilder: (context, index) => GestureDetector(
              onTap: () => _openImageGallery(images, index),
              child: CachedImageWidget(
                imageUrl: images[index],
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
            ),
          ),
          if (images.length > 1)
            Positioned(
              right: 12.w,
              bottom: 12.h,
              child: Container(
                padding: EdgeInsets.symmetric(horizontal: 10.w, vertical: 5.h),
                decoration: BoxDecoration(
                  color: Colors.black.withValues(alpha: 0.65),
                  borderRadius: BorderRadius.circular(16.r),
                ),
                child: Text(
                  '${_currentGalleryPage + 1} / ${images.length}',
                  style: TextStyle(color: Colors.white, fontSize: 12.sp),
                ),
              ),
            ),
        ],
      ),
    );
  }

  void _openImageGallery(
    List<String> images,
    int initialIndex, {
    String? title,
  }) {
    Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => _SpotImageGalleryPage(
          spotName: title ?? widget.spot.name,
          images: images,
          initialIndex: initialIndex,
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
                label: widget.spot.statusLabel,
                backgroundColor: widget.spot.isOpen
                    ? const Color(0xFFE8F5E9)
                    : const Color(0xFFFFEBEE),
                textColor: widget.spot.isOpen
                    ? const Color(0xFF2E7D32)
                    : const Color(0xFFC62828),
              ),
            ],
          ),
          if ((widget.spot.statusReason ?? '').isNotEmpty)
            Padding(
              padding: EdgeInsets.only(top: 6.h),
              child: Text(
                widget.spot.statusReason!,
                style: TextStyle(fontSize: 12.sp, color: Colors.grey[700]),
              ),
            ),
          SizedBox(height: 10.h),
          Row(
            children: [
              Icon(
                Icons.place,
                size: 16.sp,
                color: AppTheme.primaryColor,
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
                color: AppTheme.primaryColor,
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

  Widget _buildDescriptionSection() {
    final description = widget.spot.description.trim();
    final paragraphs = description.isEmpty
        ? const <String>[]
        : description.split(RegExp(r'\n\s*\n'));

    return Container(
      margin: EdgeInsets.only(top: 8.h),
      padding: EdgeInsets.fromLTRB(16.w, 18.h, 16.w, 20.h),
      color: const Color(0xfff8fafc),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 4.w,
                height: 22.h,
                decoration: BoxDecoration(
                  color: AppTheme.primaryColor,
                  borderRadius: BorderRadius.circular(4.r),
                ),
              ),
              SizedBox(width: 10.w),
              Text(
                'Description',
                style: GoogleFonts.roboto(
                  fontSize: 17.sp,
                  fontWeight: FontWeight.bold,
                  color: const Color(0xff173f43),
                ),
              ),
            ],
          ),
          SizedBox(height: 12.h),
          if (paragraphs.isEmpty)
            Text(
              'No description available.',
              style: GoogleFonts.roboto(
                fontSize: 14.sp,
                color: Colors.grey[600],
                fontStyle: FontStyle.italic,
              ),
            )
          else
            ...paragraphs.map(
              (paragraph) => Padding(
                padding: EdgeInsets.only(bottom: 10.h),
                child: Text(
                  paragraph.trim(),
                  textAlign: TextAlign.left,
                  style: GoogleFonts.roboto(
                    fontSize: 14.sp,
                    color: const Color(0xff4b5563),
                    height: 1.65,
                  ),
                ),
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
          color: AppTheme.primaryColor,
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
            Icon(icon, size: 16.sp, color: AppTheme.primaryColor),
            SizedBox(width: 6.w),
            Text(
              title,
              style: GoogleFonts.roboto(
                fontSize: 13.sp,
                fontWeight: FontWeight.w600,
                color: AppTheme.primaryColor,
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
                      color: color.withValues(alpha: 0.12),
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
                      foregroundColor: AppTheme.primaryColor,
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
                    color: AppTheme.primaryColor,
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
                    'No reviews yet. Be the first to share your experience.',
                    style: GoogleFonts.roboto(
                      fontSize: 12.sp,
                      color: Colors.grey[600],
                    ),
                    textAlign: TextAlign.center,
                  ),
                );
              }
              return Column(
                children: reviews.map((review) {
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
            Icon(Icons.place, size: 16.sp, color: AppTheme.primaryColor),
            SizedBox(width: 6.w),
            Text(
              'Structured Facilities',
              style: GoogleFonts.roboto(
                fontSize: 13.sp,
                fontWeight: FontWeight.w600,
                color: AppTheme.primaryColor,
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
                        foregroundColor: AppTheme.primaryColor,
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
    final reviewImages = _reviewImageUrls(review);

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
                review['user_name']?.toString() ?? 'Anonymous',
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
            review['comment']?.toString() ?? '',
            style: GoogleFonts.roboto(
              fontSize: 11.sp,
              color: Colors.grey[700],
            ),
          ),
          if (reviewImages.isNotEmpty) ...[
            SizedBox(height: 10.h),
            SizedBox(
              height: 88.w,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: reviewImages.length,
                separatorBuilder: (context, index) => SizedBox(width: 8.w),
                itemBuilder: (context, index) => GestureDetector(
                  onTap: () => _openImageGallery(
                    reviewImages,
                    index,
                    title: review['user_name']?.toString() ?? 'Review photos',
                  ),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(6.r),
                    child: CachedImageWidget(
                      imageUrl: reviewImages[index],
                      width: 88.w,
                      height: 88.w,
                      fit: BoxFit.cover,
                    ),
                  ),
                ),
              ),
            ),
          ],
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

  List<String> _reviewImageUrls(Map<String, dynamic> review) {
    final images = <String>{};
    final rawImages = review['images'];
    if (rawImages is List) {
      images.addAll(
        rawImages
            .where((image) => image != null)
            .map((image) => image.toString().trim())
            .where((image) => image.isNotEmpty),
      );
    }

    final rawMedia = review['media'];
    if (rawMedia is List) {
      for (final item in rawMedia.whereType<Map>()) {
        if (item['type']?.toString() == 'image') {
          final path = (item['url'] ?? item['path'])?.toString().trim();
          if (path != null && path.isNotEmpty) images.add(path);
        }
      }
    }

    final legacyImage =
        (review['image_url'] ?? review['image_path'])?.toString().trim();
    if (legacyImage != null && legacyImage.isNotEmpty) {
      images.add(legacyImage);
    }

    return images.toList();
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
      final isGoogleMapsAvailable =
          await MapLauncher.isMapAvailable(MapType.google);
      if (!isGoogleMapsAvailable) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Google Maps is not installed')),
        );
        return;
      }
      await MapLauncher.showDirections(
        mapType: MapType.google,
        destination: coords,
        destinationTitle: widget.spot.name,
      );
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
      final isGoogleMapsAvailable =
          await MapLauncher.isMapAvailable(MapType.google);
      if (!isGoogleMapsAvailable) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Google Maps is not installed')),
        );
        return;
      }

      final title = facility['name']?.toString() ?? 'Nearby Facility';
      final description = _facilityTypeLabel(
        facility['type']?.toString().toLowerCase() ?? '',
      );

      await MapLauncher.showMarker(
        mapType: MapType.google,
        coords: coords,
        title: title,
        description: description,
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

class _SpotImageGalleryPage extends StatefulWidget {
  final String spotName;
  final List<String> images;
  final int initialIndex;

  const _SpotImageGalleryPage({
    required this.spotName,
    required this.images,
    required this.initialIndex,
  });

  @override
  State<_SpotImageGalleryPage> createState() => _SpotImageGalleryPageState();
}

class _SpotImageGalleryPageState extends State<_SpotImageGalleryPage> {
  late final PageController _pageController;
  late int _currentIndex;

  @override
  void initState() {
    super.initState();
    _currentIndex = widget.initialIndex;
    _pageController = PageController(initialPage: widget.initialIndex);
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: Text(
          '${widget.spotName}  ${_currentIndex + 1}/${widget.images.length}',
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
      ),
      body: PageView.builder(
        controller: _pageController,
        itemCount: widget.images.length,
        onPageChanged: (index) => setState(() => _currentIndex = index),
        itemBuilder: (context, index) => Center(
          child: CachedImageWidget(
            imageUrl: widget.images[index],
            width: double.infinity,
            height: double.infinity,
            fit: BoxFit.contain,
          ),
        ),
      ),
    );
  }
}
