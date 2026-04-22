import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:tourist_spot_app/models/municipality_model.dart';
import 'package:tourist_spot_app/models/tourist_spot_model.dart';
import 'package:tourist_spot_app/controllers/app_providers.dart';

class TouristSpotsListScreen extends ConsumerWidget {
  final Municipality municipality;

  const TouristSpotsListScreen({
    Key? key,
    required this.municipality,
  }) : super(key: key);

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    // **REAL-TIME**: Auto-refresh every 15s + manual pull-to-refresh
    final spotsAsync = ref.watch(
      touristSpotsByMunicipalityStreamProvider(municipality.id),
    );

    final now = DateTime.now();
    final timeFormatter = DateFormat('HH:mm');

    return Scaffold(
      appBar: AppBar(
        title: Text(
          municipality.name,
          style: GoogleFonts.roboto(
            fontSize: 18.sp,
            fontWeight: FontWeight.bold,
          ),
        ),
        elevation: 0,
        backgroundColor: const Color(0xFFFF6B35),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          // Manual refresh button
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => ref.invalidate(
                touristSpotsByMunicipalityStreamProvider(municipality.id)),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(
            touristSpotsByMunicipalityStreamProvider(municipality.id).future),
        child: spotsAsync.when(
          data: (spots) =>
              _buildSpotsList(spots, context, timeFormatter.format(now), ref),
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
            color: const Color(0xFFFF6B35),
          ),
          SizedBox(height: 20.h),
          Text(
            'Loading Tourist Spots...',
            style: GoogleFonts.roboto(fontSize: 16.sp),
          ),
          SizedBox(height: 20.h),
          const CircularProgressIndicator(
            valueColor: AlwaysStoppedAnimation<Color>(Color(0xFFFF6B35)),
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
                                municipality.id as int)),
                        icon: const Icon(Icons.refresh),
                        label: Text('Retry', style: GoogleFonts.roboto()),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFFFF6B35),
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
      String lastUpdated, WidgetRef ref) {
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
              'Last checked: $lastUpdated',
              style:
                  GoogleFonts.roboto(fontSize: 12.sp, color: Colors.grey[600]),
            ),
          ],
        ),
      );
    }

    return CustomScrollView(
      slivers: [
        SliverToBoxAdapter(
          child: Padding(
            padding: EdgeInsets.all(12.w),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Found ${spots.length} spots',
                  style: GoogleFonts.roboto(
                      fontSize: 14.sp, fontWeight: FontWeight.w600),
                ),
                Text(
                  'Updated: $lastUpdated',
                  style: GoogleFonts.roboto(
                      fontSize: 12.sp, color: Colors.grey[600]),
                ),
              ],
            ),
          ),
        ),
        SliverList(
          delegate: SliverChildBuilderDelegate(
            (context, index) => _buildSpotCard(spots[index], context),
            childCount: spots.length,
          ),
        ),
      ],
    );
  }

  Widget _buildSpotCard(TouristSpot spot, BuildContext context) {
    return GestureDetector(
      onTap: () {
        Navigator.of(context).pushNamed('/spot-detail', arguments: spot);
      },
      child: Card(
        margin: EdgeInsets.symmetric(horizontal: 12.w, vertical: 8.h),
        elevation: 3,
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(12.r)),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Image
            Container(
              height: 180.h,
              width: double.infinity,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.vertical(top: Radius.circular(12.r)),
                color: Colors.grey[300],
              ),
              child: _buildImageWidget(spot),
            ),
            // Content
            Padding(
              padding: EdgeInsets.all(12.w),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Title
                  Text(
                    spot.name,
                    style: GoogleFonts.roboto(
                        fontSize: 16.sp, fontWeight: FontWeight.bold),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  SizedBox(height: 8.h),
                  // Description
                  Text(
                    spot.description,
                    style: GoogleFonts.roboto(
                        fontSize: 12.sp, color: Colors.grey[600]),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  SizedBox(height: 10.h),
                  // Rating and Info Row
                  Row(
                    children: [
                      // Rating
                      if (spot.averageRating != null)
                        Row(
                          children: [
                            Icon(Icons.star, size: 16.sp, color: Colors.amber),
                            SizedBox(width: 4.w),
                            Text(
                              '${spot.averageRating?.toStringAsFixed(1) ?? 'N/A'} (${spot.reviewsCount ?? 0})',
                              style: GoogleFonts.roboto(
                                  fontSize: 12.sp, color: Colors.grey[700]),
                            ),
                          ],
                        ),
                      const Spacer(),
                      Icon(Icons.place, size: 14.sp, color: Colors.grey),
                      SizedBox(width: 4.w),
                      Expanded(
                        child: Text(
                          spot.address,
                          style: GoogleFonts.roboto(
                              fontSize: 12.sp, color: Colors.grey),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 12.h),
                  // Action Buttons
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: () {
                            Navigator.of(context)
                                .pushNamed('/spot-detail', arguments: spot);
                          },
                          icon: const Icon(Icons.info_outline, size: 18),
                          label: const Text('Details'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFFFF6B35),
                            foregroundColor: Colors.white,
                            padding: EdgeInsets.symmetric(vertical: 10.h),
                          ),
                        ),
                      ),
                      SizedBox(width: 8.w),
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: () {
                            Navigator.of(context)
                                .pushNamed('/spot-map', arguments: spot);
                          },
                          icon: const Icon(Icons.map, size: 18),
                          label: const Text('Navigate'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.blue,
                            foregroundColor: Colors.white,
                            padding: EdgeInsets.symmetric(vertical: 10.h),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildImageWidget(TouristSpot spot) {
    final imageUrl = spot.imageUrl ?? '';
    if (imageUrl.isNotEmpty) {
      return Image.network(
        imageUrl,
        fit: BoxFit.cover,
        errorBuilder: (context, error, stackTrace) => _buildImagePlaceholder(),
        loadingBuilder: (context, child, loadingProgress) {
          if (loadingProgress == null) return child;
          return Center(
              child: CircularProgressIndicator(
            value: loadingProgress.expectedTotalBytes != null
                ? loadingProgress.cumulativeBytesLoaded /
                    loadingProgress.expectedTotalBytes!
                : null,
          ));
        },
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
