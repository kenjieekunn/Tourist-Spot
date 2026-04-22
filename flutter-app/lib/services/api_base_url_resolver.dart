import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:tourist_spot_app/config/constants/api_constants.dart';

class ApiBaseUrlResolver {
  static const String _prefsKey = 'api_base_url';

  static Future<String> resolve({bool forceDiscover = false}) async {
    if (ApiConstants.envBaseUrl.isNotEmpty) return ApiConstants.envBaseUrl;
    if (kIsWeb) return ApiConstants.webLocalhostUrl;
    if (Platform.isAndroid && ApiConstants.useAndroidEmulator) {
      return ApiConstants.androidEmulatorUrl;
    }

    final prefs = await SharedPreferences.getInstance();
    if (!forceDiscover) {
      final cached = prefs.getString(_prefsKey);
      if (cached != null && await _isReachable(cached)) return cached;
    }

    final candidates = <String>[];
    String? prefix;
    final deviceIp = await _getDeviceIpv4();
    if (deviceIp != null) {
      final parts = deviceIp.split('.');
      if (parts.length == 4) {
        prefix = '${parts[0]}.${parts[1]}.${parts[2]}.';
        final commonHosts = <int>[
          1,
          2,
          10,
          100,
          101,
          102,
          188,
          200,
          254,
        ];
        for (final host in commonHosts) {
          candidates.add(
              'http://$prefix$host/tourist-spot-system/admin-system/public/api/v1/');
        }
      }
    }

    // Always include the configured local network URL as a fallback candidate.
    candidates.add(ApiConstants.localNetworkUrl);

    for (final candidate in candidates.toSet()) {
      if (await _isReachable(candidate)) {
        await prefs.setString(_prefsKey, candidate);
        return candidate;
      }
    }

    if (prefix != null) {
      final discovered = await _scanSubnet(prefix);
      if (discovered != null) {
        await prefs.setString(_prefsKey, discovered);
        return discovered;
      }
    }

    return ApiConstants.localNetworkUrl;
  }

  static Future<bool> _isReachable(String baseUrl) async {
    try {
      final dio = Dio(BaseOptions(
        baseUrl: baseUrl,
        connectTimeout: const Duration(milliseconds: 700),
        receiveTimeout: const Duration(milliseconds: 700),
        headers: {
          'Accept': 'application/json',
        },
      ));
      final response = await dio.get('municipalities');
      return response.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  static Future<String?> _scanSubnet(String prefix) async {
    const batchSize = 25;
    final hosts = List<int>.generate(254, (index) => index + 1);
    for (var i = 0; i < hosts.length; i += batchSize) {
      final batch = hosts.sublist(
          i, (i + batchSize) > hosts.length ? hosts.length : i + batchSize);
      final futures = batch.map((host) async {
        final candidate =
            'http://$prefix$host/tourist-spot-system/admin-system/public/api/v1/';
        if (await _isReachable(candidate)) return candidate;
        return null;
      }).toList();
      final results = await Future.wait(futures);
      for (final result in results) {
        if (result != null) return result;
      }
    }
    return null;
  }

  static Future<String?> _getDeviceIpv4() async {
    try {
      final interfaces = await NetworkInterface.list(
        includeLoopback: false,
        type: InternetAddressType.IPv4,
      );
      for (final iface in interfaces) {
        for (final addr in iface.addresses) {
          if (!addr.isLoopback) return addr.address;
        }
      }
    } catch (_) {
      return null;
    }
    return null;
  }
}
