import 'dart:convert';
import 'package:flutter/foundation.dart';
import '../models/app_user.dart';
import '../services/api_service.dart';

class AuthProvider extends ChangeNotifier {
  AppUser? _appUser;
  bool _isLoading = true; // เริ่มต้นเป็น true เพื่อตรวจสอบ auto login
  String? _errorMessage;

  AppUser? get appUser => _appUser;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  bool get isAuthenticated => _appUser != null;

  AuthProvider() {
    // ผูก Callback เมื่อ API เจอ 401 ให้ล็อกเอาต์อัตโนมัติ
    ApiService.onUnauthorized = () {
      signOut();
    };
    tryAutoLogin();
  }

  Future<void> tryAutoLogin() async {
    _isLoading = true;
    notifyListeners();

    try {
      final token = await ApiService.getSavedToken();
      final userDataStr = await ApiService.getSavedUserData();

      if (token != null && token.isNotEmpty) {
        final userData = await ApiService.checkTokenValid(token);
        if (userData != null) {
          _appUser = AppUser.fromMap(userData, userData['id'].toString());
        } else if (userDataStr != null) {
          // หากออฟไลน์ชั่วคราวแต่มีข้อมูลเดิม
          final decoded = jsonDecode(userDataStr);
          _appUser = AppUser.fromMap(decoded, decoded['id'].toString());
          ApiService.setAuthToken(token);
        } else {
          await ApiService.clearAuthData();
        }
      }
    } catch (e) {
      debugPrint('Auto login error: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> signIn(String email, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await ApiService.login(email, password);
      if (res['status'] == 'success') {
        final userData = res['user'];
        _appUser = AppUser.fromMap(userData, userData['id'].toString());
        _errorMessage = null;
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        // หากได้ 403 (บล็อกแอดมิน) หรือรหัสผ่านผิด ให้แสดงข้อความจากเซิร์ฟเวอร์
        _errorMessage = res['message'] ?? 'เข้าสู่ระบบไม่สำเร็จ';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      debugPrint('API Login error: $e');
      _errorMessage = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้';
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> signUp(String name, String email, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await ApiService.register(name, email, password);
      if (res['status'] == 'success') {
        final userData = res['user'];
        _appUser = AppUser.fromMap(userData, userData['id'].toString());
        _errorMessage = null;
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = res['message'] ?? 'สมัครสมาชิกไม่สำเร็จ';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      debugPrint('API Register error: $e');
      _errorMessage = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้';
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> updateUserName(String newName) async {
    if (_appUser == null) return;
    _appUser = AppUser(
      uid: _appUser!.uid,
      email: _appUser!.email,
      name: newName,
      role: _appUser!.role,
    );
    notifyListeners();
  }

  Future<void> signOut() async {
    _isLoading = true;
    notifyListeners();

    await ApiService.logout();
    _appUser = null;
    _errorMessage = null;

    _isLoading = false;
    notifyListeners();
  }
}
