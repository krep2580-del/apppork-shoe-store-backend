import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/app_user.dart';
import '../services/api_service.dart';

class AuthProvider extends ChangeNotifier {
  AppUser? _appUser;
  bool _isLoading = true; // start true for initial auto login check
  String? _errorMessage;

  AppUser? get appUser => _appUser;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  bool get isAuthenticated => _appUser != null;

  AuthProvider() {
    tryAutoLogin();
  }

  Future<void> tryAutoLogin() async {
    _isLoading = true;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      final userDataStr = prefs.getString('user_data');

      if (token != null && token.isNotEmpty) {
        final userData = await ApiService.checkTokenValid(token);
        if (userData != null && userData['role'] == 'admin') {
          _appUser = AppUser.fromMap(userData, userData['id'].toString());
          await prefs.setString('user_data', jsonEncode(userData));
        } else if (userDataStr != null) {
          final decoded = jsonDecode(userDataStr);
          if (decoded['role'] == 'admin') {
            _appUser = AppUser.fromMap(decoded, decoded['id'].toString());
            ApiService.setAuthToken(token);
          } else {
            await _clearStorage();
          }
        } else {
          await _clearStorage();
        }
      }
    } catch (e) {
      debugPrint('Admin auto login error: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> signInAdmin(String email, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await ApiService.login(email, password);
      if (res['status'] == 'success') {
        final userData = res['user'];
        final token = res['access_token'];
        final user = AppUser.fromMap(userData, userData['id'].toString());

        if (user.role != 'admin') {
          _errorMessage = 'บัญชีนี้ไม่มีสิทธิ์เข้าใช้งานระบบแอดมิน';
          ApiService.setAuthToken(null);
          _isLoading = false;
          notifyListeners();
          return false;
        }

        _appUser = user;
        await _saveStorage(token, userData);

        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = res['message'] ?? 'เข้าสู่ระบบไม่สำเร็จ';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      debugPrint('API Admin login error: $e');
      _errorMessage = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ กรุณาเปิด Laragon และ Laravel API Server';
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> signOut() async {
    _isLoading = true;
    notifyListeners();

    await ApiService.logout();
    await _clearStorage();
    _appUser = null;

    _isLoading = false;
    notifyListeners();
  }

  Future<void> _saveStorage(String token, Map<String, dynamic> userData) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
    await prefs.setString('user_data', jsonEncode(userData));
  }

  Future<void> _clearStorage() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('user_data');
  }
}
