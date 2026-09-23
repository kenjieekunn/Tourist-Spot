import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:tourist_spot_app/controllers/app_providers.dart';
import 'package:tourist_spot_app/config/theme/app_theme.dart';
import 'package:tourist_spot_app/models/municipality_model.dart';
import 'package:tourist_spot_app/views/widgets/cached_image_widget.dart';

class MunicipalityLandingScreen extends ConsumerStatefulWidget {
  const MunicipalityLandingScreen({super.key});

  @override
  ConsumerState<MunicipalityLandingScreen> createState() =>
      _MunicipalityLandingScreenState();
}

class _MunicipalityLandingScreenState
    extends ConsumerState<MunicipalityLandingScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final municipalitiesAsync = ref.watch(municipalitiesProvider);

    return Scaffold(
      body: municipalitiesAsync.when(
        loading: _buildLoadingState,
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
            color: AppTheme.primaryColor,
          ),
          SizedBox(height: 20.h),
          Text(
            'Loading Municipalities...',
            style: GoogleFonts.roboto(fontSize: 16.sp),
          ),
          SizedBox(height: 20.h),
          const CircularProgressIndicator(
            valueColor: AlwaysStoppedAnimation<Color>(AppTheme.primaryColor),
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
          backgroundColor: AppTheme.primaryColor,
          expandedHeight: 200.h,
          flexibleSpace: FlexibleSpaceBar(
            background: Container(
              decoration: const BoxDecoration(
                color: AppTheme.primaryColor,
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
    List<Municipality> municipalities,
    BuildContext context,
    WidgetRef ref,
  ) {
    final filteredMunicipalities = _filterMunicipalities(municipalities);

    return CustomScrollView(
      slivers: [
        SliverAppBar(
          pinned: true,
          elevation: 0,
          backgroundColor: AppTheme.primaryColor,
          expandedHeight: 200.h,
          flexibleSpace: FlexibleSpaceBar(
            background: Container(
              decoration: const BoxDecoration(
                color: AppTheme.primaryColor,
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
                Text(
                  'Municipalities',
                  style: GoogleFonts.roboto(
                    fontSize: 16.sp,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey[800],
                  ),
                ),
                SizedBox(height: 12.h),
                _buildSearchBar(),
                SizedBox(height: 10.h),
                Text(
                  'Showing ${filteredMunicipalities.length} of ${municipalities.length} municipalities',
                  style: GoogleFonts.roboto(
                    fontSize: 12.sp,
                    color: Colors.grey[700],
                    fontWeight: FontWeight.w500,
                  ),
                ),
                SizedBox(height: 16.h),
                if (filteredMunicipalities.isEmpty)
                  _buildEmptyState()
                else
                  GridView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      crossAxisSpacing: 12.w,
                      mainAxisSpacing: 12.w,
                      childAspectRatio: 0.8,
                    ),
                    itemCount: filteredMunicipalities.length,
                    itemBuilder: (context, index) {
                      final municipality = filteredMunicipalities[index];
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
          hintText: 'Search municipalities',
          prefixIcon: const Icon(Icons.search),
          suffixIcon: _searchQuery.isEmpty
              ? null
              : IconButton(
                  icon: const Icon(Icons.clear),
                  onPressed: () {
                    _searchController.clear();
                    setState(() {
                      _searchQuery = '';
                    });
                  },
                ),
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

  List<Municipality> _filterMunicipalities(List<Municipality> municipalities) {
    final query = _searchQuery.trim().toLowerCase();
    if (query.isEmpty) return municipalities;

    return municipalities.where((municipality) {
      return municipality.name.toLowerCase().contains(query) ||
          (municipality.description ?? '').toLowerCase().contains(query);
    }).toList();
  }

  Widget _buildEmptyState() {
    return Padding(
      padding: EdgeInsets.only(top: 18.h, bottom: 8.h),
      child: Column(
        children: [
          Icon(
            Icons.filter_alt_off,
            size: 54.sp,
            color: Colors.grey[500],
          ),
          SizedBox(height: 12.h),
          Text(
            'No municipalities match your search.',
            style: GoogleFonts.roboto(
              fontSize: 14.sp,
              fontWeight: FontWeight.w600,
              color: Colors.grey[700],
            ),
            textAlign: TextAlign.center,
          ),
          SizedBox(height: 6.h),
          Text(
            'Try a different keyword or clear the search bar.',
            style: GoogleFonts.roboto(
              fontSize: 12.sp,
              color: Colors.grey[600],
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
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
            Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12.r),
                color: Colors.grey[300],
              ),
              child: (municipality.imageUrl ?? '').isNotEmpty
                  ? CachedImageWidget(
                      imageUrl: municipality.imageUrl!,
                      fit: BoxFit.cover,
                      borderRadius: BorderRadius.circular(12.r),
                      placeholderBuilder: _buildPlaceholder,
                    )
                  : _buildPlaceholder(),
            ),
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
                        Expanded(
                          child: Text(
                            '${municipality.touristSpotsCount ?? 0} Spots',
                            style: GoogleFonts.roboto(
                              fontSize: 10.sp,
                              color: Colors.white70,
                            ),
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
