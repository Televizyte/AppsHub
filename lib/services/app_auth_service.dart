import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../app_config.dart';

class AppUser {
  final int id;
  final String name;
  final String email;

  const AppUser({required this.id, required this.name, required this.email});

  factory AppUser.fromJson(Map<String, dynamic> json) => AppUser(
        id: (json['id'] as num?)?.toInt() ?? 0,
        name: (json['name'] ?? '').toString(),
        email: (json['email'] ?? '').toString(),
      );

  Map<String, dynamic> toJson() => {'id': id, 'name': name, 'email': email};
}

class AppAuthState extends ChangeNotifier {
  AppAuthState._();

  static final AppAuthState instance = AppAuthState._();

  static const _tokenKey = 'app_user_token';
  static const _userKey = 'app_user_json';

  String? _token;
  AppUser? _user;
  bool _loaded = false;

  bool get isLoaded => _loaded;
  bool get isSignedIn => (_token ?? '').isNotEmpty && _user != null;
  String? get token => _token;
  AppUser? get user => _user;

  Future<void> load() async {
    if (_loaded) return;
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(_tokenKey);
    final raw = prefs.getString(_userKey);
    if (raw != null && raw.isNotEmpty) {
      try {
        _user = AppUser.fromJson(jsonDecode(raw) as Map<String, dynamic>);
      } catch (_) {
        _user = null;
      }
    }
    _loaded = true;
    notifyListeners();
  }

  Future<void> setSession(String token, AppUser user) async {
    _token = token;
    _user = user;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
    await prefs.setString(_userKey, jsonEncode(user.toJson()));
    _loaded = true;
    notifyListeners();
  }

  Future<void> clear() async {
    _token = null;
    _user = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userKey);
    _loaded = true;
    notifyListeners();
  }
}

class AppAuthService {
  AppAuthService._();

  static final AppAuthService instance = AppAuthService._();

  String get _base =>
      '${AppConfig.apiBaseUrl}/api/v1/apps/${AppConfig.appSlug}/auth';

  Map<String, String> _headers({bool authenticated = false}) => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-APP-TOKEN': AppConfig.appToken,
        if (authenticated && AppAuthState.instance.token != null)
          'Authorization': 'Bearer ${AppAuthState.instance.token}',
      };

  Future<AppUser> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$_base/login'),
      headers: _headers(),
      body: jsonEncode({'email': email.trim(), 'password': password}),
    );
    final payload = _decode(response);
    final user = AppUser.fromJson(payload['user'] as Map<String, dynamic>);
    await AppAuthState.instance.setSession(payload['token'].toString(), user);
    return user;
  }

  Future<AppUser> register(String name, String email, String password) async {
    final response = await http.post(
      Uri.parse('$_base/register'),
      headers: _headers(),
      body: jsonEncode({
        'name': name.trim(),
        'email': email.trim(),
        'password': password,
      }),
    );
    final payload = _decode(response);
    final user = AppUser.fromJson(payload['user'] as Map<String, dynamic>);
    await AppAuthState.instance.setSession(payload['token'].toString(), user);
    return user;
  }

  Future<void> logout() async {
    try {
      await http.post(Uri.parse('$_base/logout'), headers: _headers(authenticated: true));
    } finally {
      await AppAuthState.instance.clear();
    }
  }

  Future<void> deleteAccount(String password) async {
    final request = http.Request('DELETE', Uri.parse('$_base/account'))
      ..headers.addAll(_headers(authenticated: true))
      ..body = jsonEncode({'password': password, 'confirmation': true});
    final streamed = await request.send();
    final response = await http.Response.fromStream(streamed);
    _decode(response);
    await AppAuthState.instance.clear();
  }

  String publicDeletionUrl() =>
      '${AppConfig.apiBaseUrl}/account-deletion/${AppConfig.appSlug}';

  Map<String, dynamic> _decode(http.Response response) {
    Map<String, dynamic> payload = {};
    try {
      payload = jsonDecode(response.body) as Map<String, dynamic>;
    } catch (_) {}

    if (response.statusCode < 200 || response.statusCode >= 300) {
      final errors = payload['errors'];
      String message = (payload['message'] ?? 'Request failed.').toString();
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) message = first.first.toString();
      }
      throw AppAuthException(message, response.statusCode);
    }

    return payload;
  }
}

class AppAuthException implements Exception {
  final String message;
  final int statusCode;
  const AppAuthException(this.message, this.statusCode);
  @override
  String toString() => message;
}
