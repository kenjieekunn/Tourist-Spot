import 'dart:io';
import 'package:dio/dio.dart';
import 'package:path_provider/path_provider.dart';

class ImageCacheService {
  static const String _cacheFolder = 'tourist_spot_images';
  late Directory _cacheDir;
  late Dio _dio;

  ImageCacheService() {
    _dio = Dio();
  }

  Future<void> initialize() async {
    final appDir = await getApplicationDocumentsDirectory();
    _cacheDir = Directory('${appDir.path}/$_cacheFolder');
    if (!_cacheDir.existsSync()) {
      _cacheDir.createSync(recursive: true);
    }
  }

  /// Generate a cache file name from URL
  String _getCacheFileName(String url) {
    return url.replaceAll(RegExp(r'[^a-zA-Z0-9]'), '_');
  }

  /// Get cached image file
  File? getCachedImageFile(String url) {
    try {
      final fileName = _getCacheFileName(url);
      final file = File('${_cacheDir.path}/$fileName');
      if (file.existsSync()) {
        return file;
      }
    } catch (e) {
      print('Error getting cached image: $e');
    }
    return null;
  }

  /// Download and cache image
  Future<File?> downloadAndCacheImage(String url) async {
    try {
      final fileName = _getCacheFileName(url);
      final file = File('${_cacheDir.path}/$fileName');

      // If already cached, return it
      if (file.existsSync()) {
        return file;
      }

      // Download the image
      final response = await _dio.get<List<int>>(
        url,
        options: Options(responseType: ResponseType.bytes),
      );

      // Save to cache
      await file.writeAsBytes(response.data!);
      return file;
    } catch (e) {
      print('Error downloading and caching image: $e');
      return null;
    }
  }

  /// Get image file (cached or download)
  Future<File?> getImageFile(String url) async {
    // Check if already cached
    final cachedFile = getCachedImageFile(url);
    if (cachedFile != null) {
      return cachedFile;
    }

    // Try to download and cache
    return await downloadAndCacheImage(url);
  }

  /// Clear all cached images
  Future<void> clearCache() async {
    try {
      if (_cacheDir.existsSync()) {
        _cacheDir.deleteSync(recursive: true);
        _cacheDir.createSync(recursive: true);
      }
    } catch (e) {
      print('Error clearing cache: $e');
    }
  }

  /// Get cache size in MB
  Future<double> getCacheSize() async {
    try {
      double size = 0;
      if (_cacheDir.existsSync()) {
        _cacheDir.listSync().forEach((file) {
          if (file is File) {
            size += file.lengthSync();
          }
        });
      }
      return size / (1024 * 1024); // Convert to MB
    } catch (e) {
      print('Error getting cache size: $e');
      return 0;
    }
  }
}
