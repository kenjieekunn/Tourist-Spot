import 'dart:convert';
import 'package:web_socket_channel/web_socket_channel.dart';
import 'package:flutter/foundation.dart';
import '../config/constants/api_constants.dart';

class ReverbService extends ChangeNotifier {
  WebSocketChannel? _channel;
  bool _connected = false;
  Function(Map<String, dynamic>)? onSpotChanged;

  void connect() {
    try {
      final url = ApiConstants.reverbUrl;
      _channel = WebSocketChannel.connect(Uri.parse(url));
      _connected = true;
      notifyListeners();

      _channel!.stream.listen(
        (message) {
          try {
            final data = json.decode(message);
            if (data is Map && data['event'] == 'tourist-spot.changed') {
              onSpotChanged?.call(data['data'] as Map<String, dynamic>);
            }
          } catch (e) {
            debugPrint('Reverb message parse error: $e');
          }
        },
        onError: (error) {
          debugPrint('Reverb error: $error');
          _connected = false;
          notifyListeners();
        },
        onDone: () {
          debugPrint('Reverb disconnected');
          _connected = false;
          notifyListeners();
        },
      );

      // Subscribe to tourist-spots channel
      _subscribe('tourist-spots');
    } catch (e) {
      debugPrint('Reverb connect error: $e');
    }
  }

  void _subscribe(String channel) {
    if (_channel != null) {
      _channel!.sink.add(json.encode({
        'command': 'subscribe',
        'identifier': json.encode({'channel': channel}),
      }));
    }
  }

  bool get connected => _connected;

  @override
  void dispose() {
    _channel?.sink.close();
    super.dispose();
  }
}
