import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:tourist_spot_app/config/theme/app_theme.dart';
import 'package:tourist_spot_app/controllers/app_providers.dart';
import 'package:tourist_spot_app/views/widgets/cached_image_widget.dart';

class TourismLandingScreen extends ConsumerWidget {
  const TourismLandingScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final municipalities = ref.watch(municipalitiesProvider).asData?.value;
    final heroImageUrl = municipalities
        ?.where((municipality) => (municipality.imageUrl ?? '').isNotEmpty)
        .firstOrNull
        ?.imageUrl;

    return Scaffold(
      body: Stack(
        fit: StackFit.expand,
        children: [
          if (heroImageUrl != null)
            CachedImageWidget(imageUrl: heroImageUrl, fit: BoxFit.cover)
          else
            const ColoredBox(color: AppTheme.primaryColor),
          const DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Color(0x660D3B3E),
                  Color(0xB30D3B3E),
                  Color(0xF20D3B3E),
                ],
                stops: [0, 0.42, 1],
              ),
            ),
          ),
          SafeArea(
            child: LayoutBuilder(
              builder: (context, constraints) => SingleChildScrollView(
                child: ConstrainedBox(
                  constraints: BoxConstraints(minHeight: constraints.maxHeight),
                  child: Padding(
                    padding: EdgeInsets.fromLTRB(24.w, 20.h, 24.w, 28.h),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(
                              Icons.location_on,
                              color: const Color(0xFF69D3B6),
                              size: 24.sp,
                            ),
                            SizedBox(width: 8.w),
                            Expanded(
                              child: Text(
                                'M-TOUR  /  PANGASINAN',
                                style: GoogleFonts.roboto(
                                  color: Colors.white,
                                  fontSize: 12.sp,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                          ],
                        ),
                        SizedBox(height: 28.h),
                        Container(
                          padding: EdgeInsets.symmetric(
                            horizontal: 11.w,
                            vertical: 7.h,
                          ),
                          decoration: BoxDecoration(
                            border: Border.all(color: const Color(0xFF8DE0C8)),
                            borderRadius: BorderRadius.circular(24.r),
                          ),
                          child: Text(
                            'YOUR LOCAL TOURISM GUIDE',
                            style: GoogleFonts.roboto(
                              color: const Color(0xFFB5F0DE),
                              fontSize: 10.sp,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                        SizedBox(height: 16.h),
                        Text(
                          'Discover the\nhidden gems.',
                          style: GoogleFonts.outfit(
                            color: Colors.white,
                            fontSize: 43.sp,
                            height: 1.02,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        SizedBox(height: 14.h),
                        Text(
                          'Find places worth the journey across the eight municipalities of Pangasinan’s 2nd District.',
                          style: GoogleFonts.roboto(
                            color: Colors.white.withValues(alpha: 0.9),
                            fontSize: 15.sp,
                            height: 1.5,
                          ),
                        ),
                        SizedBox(height: 26.h),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton.icon(
                            onPressed: () => Navigator.of(context)
                                .pushNamed('/municipalities'),
                            icon: const Icon(Icons.explore_outlined),
                            label: const Text('Explore municipalities'),
                            style: FilledButton.styleFrom(
                              backgroundColor: AppTheme.accentColor,
                              foregroundColor: Colors.white,
                              padding: EdgeInsets.symmetric(vertical: 15.h),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(8.r),
                              ),
                            ),
                          ),
                        ),
                        SizedBox(height: 24.h),
                        const Divider(color: Color(0x66FFFFFF), height: 1),
                        SizedBox(height: 16.h),
                        Row(
                          children: [
                            const _LandingHighlight(
                              icon: Icons.map_outlined,
                              label: 'Local places',
                            ),
                            SizedBox(width: 20.w),
                            const _LandingHighlight(
                              icon: Icons.verified_outlined,
                              label: 'Verified spots',
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _LandingHighlight extends StatelessWidget {
  const _LandingHighlight({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 17.sp, color: const Color(0xFF8DE0C8)),
        SizedBox(width: 6.w),
        Text(
          label,
          style: GoogleFonts.roboto(
            color: Colors.white,
            fontSize: 12.sp,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }
}
