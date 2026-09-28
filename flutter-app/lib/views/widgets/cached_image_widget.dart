import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:tourist_spot_app/config/theme/app_theme.dart';
import 'package:tourist_spot_app/services/api_base_url_resolver.dart';

class CachedImageWidget extends StatefulWidget {
  final String imageUrl;
  final BoxFit fit;
  final double? width;
  final double? height;
  final BorderRadius? borderRadius;
  final Widget Function()? placeholderBuilder;

  const CachedImageWidget({
    super.key,
    required this.imageUrl,
    this.fit = BoxFit.cover,
    this.width,
    this.height,
    this.borderRadius,
    this.placeholderBuilder,
  });

  @override
  State<CachedImageWidget> createState() => _CachedImageWidgetState();
}

class _CachedImageWidgetState extends State<CachedImageWidget> {
  @override
  Widget build(BuildContext context) {
    final imageWidget = CachedNetworkImage(
      imageUrl: _resolveImageUrl(widget.imageUrl),
      fit: widget.fit,
      placeholder: (context, url) => _buildLoadingWidget(),
      errorWidget: (context, url, error) => _buildPlaceholder(),
    );
    return SizedBox(
      width: widget.width,
      height: widget.height,
      child: widget.borderRadius == null
          ? imageWidget
          : ClipRRect(borderRadius: widget.borderRadius!, child: imageWidget),
    );
  }

  String _resolveImageUrl(String rawUrl) {
    final value = rawUrl.trim();
    final parsed = Uri.tryParse(value);
    final activeBase = Uri.tryParse(ApiBaseUrlResolver.activeBaseUrl);
    if (parsed == null || activeBase == null) return value;

    final serverOrigin = Uri(
      scheme: activeBase.scheme,
      host: activeBase.host,
      port: activeBase.hasPort ? activeBase.port : null,
    );
    if (parsed.hasScheme) {
      if (parsed.host == 'localhost' || parsed.host == '127.0.0.1') {
        return _normalizeStoragePath(
          serverOrigin.replace(
            path: parsed.path,
            query: parsed.hasQuery ? parsed.query : null,
          ),
        ).toString();
      }
      return _normalizeStoragePath(parsed).toString();
    }

    final path = value.startsWith('/') || value.startsWith('storage/')
        ? value.startsWith('/')
            ? value
            : '/$value'
        : '/storage/$value';
    return _normalizeStoragePath(serverOrigin.resolve(path)).toString();
  }

  Uri _normalizeStoragePath(Uri uri) {
    var path = uri.path;
    while (path.contains('/storage/storage/')) {
      path = path.replaceAll('/storage/storage/', '/storage/');
    }
    return path == uri.path ? uri : uri.replace(path: path);
  }

  Widget _buildLoadingWidget() {
    return const Center(
      child: CircularProgressIndicator(
        valueColor: AlwaysStoppedAnimation<Color>(AppTheme.primaryColor),
      ),
    );
  }

  Widget _buildPlaceholder() {
    if (widget.placeholderBuilder != null) {
      return widget.placeholderBuilder!();
    }
    return Container(
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
}
