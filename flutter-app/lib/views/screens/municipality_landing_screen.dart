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
  final GlobalKey _municipalitySectionKey = GlobalKey();
  String _searchQuery = '';

  void _scrollToMunicipalities() {
    final sectionContext = _municipalitySectionKey.currentContext;
    if (sectionContext == null) return;
    Scrollable.ensureVisible(
      sectionContext,
      duration: const Duration(milliseconds: 450),
      curve: Curves.easeOutCubic,
    );
  }

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
        SliverToBoxAdapter(
          child: _buildHero(),
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
        SliverToBoxAdapter(
          child: _buildHero(
            imageUrl:
                municipalities.isEmpty ? null : municipalities.first.imageUrl,
          ),
        ),
        SliverToBoxAdapter(
          key: _municipalitySectionKey,
          child: Padding(
            padding: EdgeInsets.fromLTRB(16.w, 24.h, 16.w, 0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Explore by municipality',
                  style: GoogleFonts.outfit(
                    fontSize: 21.sp,
                    fontWeight: FontWeight.w700,
                    color: AppTheme.textPrimary,
                  ),
                ),
                SizedBox(height: 4.h),
                Text(
                  'Find places, local favorites, and routes across the district.',
                  style: GoogleFonts.roboto(
                    fontSize: 13.sp,
                    color: AppTheme.textSecondary,
                  ),
                ),
              ],
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
                      crossAxisCount:
                          MediaQuery.sizeOf(context).width >= 600 ? 3 : 2,
                      crossAxisSpacing: 12.w,
                      mainAxisSpacing: 12.w,
                      childAspectRatio: 0.76,
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

  Widget _buildHero({String? imageUrl}) {
    return SizedBox(
      height: 330.h,
      child: Stack(
        fit: StackFit.expand,
        children: [
          if (imageUrl != null && imageUrl.isNotEmpty)
            CachedImageWidget(imageUrl: imageUrl, fit: BoxFit.cover)
          else
            Container(color: AppTheme.primaryColor),
          const DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [Color(0x660D3B3E), Color(0xF20D3B3E)],
              ),
            ),
          ),
          SafeArea(
            bottom: false,
            child: Padding(
              padding: EdgeInsets.fromLTRB(20.w, 22.h, 20.w, 24.h),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.location_on,
                          color: const Color(0xFF69D3B6), size: 20.sp),
                      SizedBox(width: 8.w),
                      Text(
                        'PANGASINAN 2ND DISTRICT',
                        style: GoogleFonts.roboto(
                          color: Colors.white,
                          fontSize: 11.sp,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                  const Spacer(),
                  Text(
                    'YOUR LOCAL TOURISM GUIDE',
                    style: GoogleFonts.roboto(
                      color: const Color(0xFF8DE0C8),
                      fontSize: 11.sp,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  SizedBox(height: 8.h),
                  Text(
                    'Discover the\nhidden gems.',
                    style: GoogleFonts.outfit(
                      color: Colors.white,
                      fontSize: 34.sp,
                      height: 1.04,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  SizedBox(height: 8.h),
                  Text(
                    'Eight municipalities. A hundred ways to explore.',
                    style: GoogleFonts.roboto(
                      color: Colors.white.withOpacity(0.88),
                      fontSize: 13.sp,
                    ),
                  ),
                  SizedBox(height: 16.h),
                  FilledButton.icon(
                    onPressed: _scrollToMunicipalities,
                    icon: const Icon(Icons.explore_outlined, size: 18),
                    label: const Text('Explore places'),
                    style: FilledButton.styleFrom(
                      backgroundColor: AppTheme.accentColor,
                      foregroundColor: Colors.white,
                      padding: EdgeInsets.symmetric(
                          horizontal: 16.w, vertical: 11.h),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSearchBar() {
    return Container(
      padding: EdgeInsets.all(14.w),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(8.r),
        gradient: const LinearGradient(
          colors: [
            Colors.white,
            Color(0xFFF7F8F3),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        border: Border.all(color: const Color(0xFFE1EBE6)),
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
            borderRadius: BorderRadius.circular(8.r),
            borderSide: BorderSide.none,
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(8.r),
            borderSide: BorderSide(color: Colors.grey.shade300),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(8.r),
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
          borderRadius: BorderRadius.circular(8.r),
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
