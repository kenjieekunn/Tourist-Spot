import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:tourist_spot_app/config/routes/app_routes.dart';
import 'package:tourist_spot_app/config/theme/app_theme.dart';

void main() {
  runApp(
    const ProviderScope(
      child: TouristSpotApp(),
    ),
  );
}

class TouristSpotApp extends StatelessWidget {
  const TouristSpotApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ScreenUtilInit(
      designSize: const Size(375, 812),
      minTextAdapt: true,
      splitScreenMode: true,
      builder: (context, child) {
        return MaterialApp(
          title: 'M-Tour',
          theme: AppTheme.lightTheme,
          darkTheme: AppTheme.darkTheme,
          themeMode: ThemeMode.light,
          home: child,
          onGenerateRoute: AppRoutes.generateRoute,
          debugShowCheckedModeBanner: false,
        );
      },
      child: const SplashScreen(),
    );
  }
}

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _navigateToHome();
  }

  void _navigateToHome() {
    Future.delayed(const Duration(seconds: 2), () {
      if (mounted) {
        Navigator.of(context).pushReplacementNamed('/home');
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Image.asset(
              'assets/images/mtour_logo.png',
              width: 90.w,
              height: 90.w,
              fit: BoxFit.contain,
              errorBuilder: (context, error, stackTrace) {
                return Icon(
                  Icons.location_on,
                  size: 80.sp,
                  color: const Color(0xFFFF6B35),
                );
              },
            ),
            SizedBox(height: 20.h),
            Text(
              'M-Tour',
              style: GoogleFonts.roboto(
                fontSize: 28.sp,
                fontWeight: FontWeight.bold,
                color: const Color(0xFFFF6B35),
              ),
            ),
            SizedBox(height: 10.h),
            Text(
              'Tourist Guide',
              style: GoogleFonts.roboto(
                fontSize: 14.sp,
                color: Colors.grey[600],
                fontWeight: FontWeight.w500,
              ),
            ),
            SizedBox(height: 40.h),
            SizedBox(
              width: 100.w,
              height: 3.h,
              child: ClipRRect(
                borderRadius: BorderRadius.circular(10.r),
                child: LinearProgressIndicator(
                  backgroundColor: Colors.grey[300],
                  valueColor: const AlwaysStoppedAnimation<Color>(
                    Color(0xFFFF6B35),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
