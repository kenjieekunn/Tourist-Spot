import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:tourist_spot_app/models/municipality_model.dart';
import 'package:tourist_spot_app/controllers/app_providers.dart';

class MunicipalityLandingScreen extends ConsumerWidget {
  const MunicipalityLandingScreen({Key? key}) : super(key: key);
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final municipalitiesAsync = ref.watch(municipalitiesProvider);

    return Scaffold(
      body: municipalitiesAsync.when(
        loading: () => _buildLoadingState(),
        error: (error, stackTrace) => _buildErrorState(error, context, ref),
        data: (municipalities) =>
            _buildMunicipalitiesList(municipalities, context, ref),
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
            'Loading Municipalities...',
            style: GoogleFonts.roboto(fontSize: 16.sp),
          ),
          SizedBox(height: 20.h),
          const CircularProgressIndicator(
            valueColor: AlwaysStoppedAnimation<Color>(Color(0xFFFF6B35)),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorState(Object error, BuildContext context, WidgetRef ref) {
    return CustomScrollView(
      slivers: [
        SliverAppBar(
          pinned: true,
          elevation: 0,
          backgroundColor: const Color(0xFFFF6B35),
          expandedHeight: 200.h,
          flexibleSpace: FlexibleSpaceBar(
            background: Container(
              decoration: const BoxDecoration(
                color: Color(0xFFFF6B35),
              ),
              child: Stack(
                children: [
                  Opacity(
                    opacity: 0.1,
                    child: Icon(
                      Icons.location_on,
                      size: 200.sp,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
            ),
            title: Text(
              'Explore the 2nd District of Pangasinan',
              style: GoogleFonts.roboto(
                fontSize: 18.sp,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: EdgeInsets.all(16.w),
            child: Container(
              padding: EdgeInsets.all(16.w),
              decoration: BoxDecoration(
                color: Colors.orange.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12.r),
                border: Border.all(
                  color: Colors.orange,
                  width: 1.5,
                ),
              ),
              child: Column(
                children: [
                  Row(
                    children: [
                      Icon(
                        Icons.wifi_off,
                        color: Colors.orange,
                        size: 24.sp,
                      ),
                      SizedBox(width: 12.w),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Connection Issue',
                              style: GoogleFonts.roboto(
                                fontSize: 14.sp,
                                fontWeight: FontWeight.bold,
                                color: Colors.orange,
                              ),
                            ),
                            SizedBox(height: 4.h),
                            Text(
                              'Unable to connect to server.\nPlease check your API base URL and server status.',
                              style: GoogleFonts.roboto(
                                fontSize: 12.sp,
                                color: Colors.grey[700],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 12.h),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () {
                        ref.invalidate(municipalitiesProvider);
                      },
                      icon: const Icon(Icons.refresh),
                      label:
                          Text('Retry Connection', style: GoogleFonts.roboto()),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.orange,
                        padding: EdgeInsets.symmetric(vertical: 12.h),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildMunicipalitiesList(
      List<Municipality> municipalities, BuildContext context, WidgetRef ref) {
    return CustomScrollView(
      slivers: [
        SliverAppBar(
          pinned: true,
          elevation: 0,
          backgroundColor: const Color(0xFFFF6B35),
          expandedHeight: 200.h,
          flexibleSpace: FlexibleSpaceBar(
            background: Container(
              decoration: const BoxDecoration(
                color: Color(0xFFFF6B35),
              ),
              child: Stack(
                children: [
                  Opacity(
                    opacity: 0.1,
                    child: Icon(
                      Icons.location_on,
                      size: 200.sp,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
            ),
            title: Text(
              'Explore 2nd District of Pangasinan',
              style: GoogleFonts.roboto(
                fontSize: 18.sp,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ),
        SliverPadding(
          padding: EdgeInsets.all(16.w),
          sliver: SliverList(
            delegate: SliverChildListDelegate(
              [
                Padding(
                  padding: EdgeInsets.only(bottom: 12.h),
                  child: Text(
                    'Municipalities',
                    style: GoogleFonts.roboto(
                      fontSize: 16.sp,
                      fontWeight: FontWeight.bold,
                      color: Colors.grey[800],
                    ),
                  ),
                ),
                GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    crossAxisSpacing: 12.w,
                    mainAxisSpacing: 12.w,
                    childAspectRatio: 0.8,
                  ),
                  itemCount: municipalities.length,
                  itemBuilder: (context, index) {
                    final municipality = municipalities[index];
                    return _buildMunicipalityCard(
                      municipality,
                      context,
                      ref,
                    );
                  },
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildMunicipalityCard(
    Municipality municipality,
    BuildContext context,
    WidgetRef ref,
  ) {
    return GestureDetector(
      onTap: () {
        ref.read(selectedMunicipalityProvider.notifier).state = municipality;
        Navigator.of(context).pushNamed(
          '/tourist-spots-list',
          arguments: municipality,
        );
      },
      child: Card(
        elevation: 4,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12.r),
        ),
        child: Stack(
          children: [
            // Background image
            Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12.r),
                color: Colors.grey[300],
              ),
              child: (municipality.imageUrl ?? '').isNotEmpty
                  ? Image.network(
                      municipality.imageUrl!,
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) {
                        return _buildPlaceholder();
                      },
                    )
                  : _buildPlaceholder(),
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
                      municipality.name,
                      style: GoogleFonts.roboto(
                        fontSize: 14.sp,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    SizedBox(height: 4.h),
                    Row(
                      children: [
                        Icon(
                          Icons.location_on,
                          size: 12.sp,
                          color: Colors.white70,
                        ),
                        SizedBox(width: 4.w),
                        if (municipality.touristSpotsCount != null)
                          Text(
                            '${municipality.touristSpotsCount} Spots',
                            style: GoogleFonts.roboto(
                              fontSize: 10.sp,
                              color: Colors.white70,
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

  Widget _buildPlaceholder() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.grey[300],
        gradient: LinearGradient(
          colors: [Colors.grey[300]!, Colors.grey[400]!],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Center(
        child: Icon(
          Icons.image_not_supported,
          color: Colors.grey[600],
          size: 50.sp,
        ),
      ),
    );
  }
}
