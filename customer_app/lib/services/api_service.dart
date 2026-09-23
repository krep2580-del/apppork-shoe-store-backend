import 'dart:convert';
import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';
import '../models/product.dart';
import '../models/order.dart';
import '../models/cart_item.dart';

class ApiService {
  // อ่านจาก --dart-define=API_BASE_URL ถ้าไม่ระบุใช้ http://10.0.2.2:8000/api เป็นค่าเริ่มต้น
  static const String _envBaseUrl = String.fromEnvironment('API_BASE_URL');

  static String get baseUrl {
    if (_envBaseUrl.isNotEmpty) {
      return _envBaseUrl;
    }
    if (kIsWeb) {
      return 'http://localhost:8000/api';
    }
    return 'http://10.0.2.2:8000/api';
  }

  // กำหนด Timeout สำหรับทุก Request (8 วินาที)
  static const Duration timeoutDuration = Duration(seconds: 8);

  // ใช้ flutter_secure_storage สำหรับเก็บ Auth Token
  static const FlutterSecureStorage _storage = FlutterSecureStorage();
  static const String _tokenKey = 'auth_token';
  static const String _userDataKey = 'user_data';

  static String? _authToken;
  static String? get authToken => _authToken;
  static void setAuthToken(String? token) => _authToken = token;

  // Callback เมื่อพบ HTTP 401 Unauthorized เพื่อให้ออกจากระบบทันที
  static VoidCallback? onUnauthorized;

  static Map<String, String> get _headers {
    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (_authToken != null && _authToken!.isNotEmpty) {
      headers['Authorization'] = 'Bearer $_authToken';
    }
    return headers;
  }

  // ==========================================
  // STORAGE HELPERS
  // ==========================================
  static Future<void> saveAuthData(String token, Map<String, dynamic> userData) async {
    _authToken = token;
    await _storage.write(key: _tokenKey, value: token);
    await _storage.write(key: _userDataKey, value: jsonEncode(userData));
  }

  static Future<String?> getSavedToken() async {
    return await _storage.read(key: _tokenKey);
  }

  static Future<String?> getSavedUserData() async {
    return await _storage.read(key: _userDataKey);
  }

  static Future<void> clearAuthData() async {
    _authToken = null;
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _userDataKey);
  }

  // ==========================================
  // AUTH API
  // ==========================================
  static Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await http
          .post(
            Uri.parse('$baseUrl/login'),
            headers: _headers,
            body: jsonEncode({'email': email.trim(), 'password': password}),
          )
          .timeout(timeoutDuration);

      final data = jsonDecode(response.body) as Map<String, dynamic>;

      if (response.statusCode == 200 && data['status'] == 'success') {
        _authToken = data['access_token'];
        await saveAuthData(_authToken!, data['user']);
        return data;
      } else if (response.statusCode == 403) {
        // แอดมินถูกบล็อก แสดงข้อความแจ้งเตือนจากเซิร์ฟเวอร์
        return {
          'status': 'error',
          'message': data['message'] ?? 'บัญชีผู้ดูแลระบบไม่สามารถเข้าสู่ระบบผ่านแอปพลิเคชันได้',
        };
      } else if (response.statusCode == 429) {
        return {
          'status': 'error',
          'message': 'มีการพยายามเข้าสู่ระบบถี่เกินไป กรุณารอสักครู่แล้วลองใหม่',
        };
      } else {
        return {
          'status': 'error',
          'message': data['message'] ?? 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
        };
      }
    } on TimeoutException {
      return {
        'status': 'error',
        'message': 'หมดเวลาเชื่อมต่อเซิร์ฟเวอร์ กรุณาตรวจสอบว่า Backend Server กำลังทำงานอยู่',
      };
    } catch (e) {
      debugPrint('ApiService login error: $e');
      return {
        'status': 'error',
        'message': 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ ($e)',
      };
    }
  }

  static Future<Map<String, dynamic>> register(String name, String email, String password) async {
    try {
      // ส่งเฉพาะ name, email, password (ห้ามส่ง role เด็ดขาด)
      final response = await http
          .post(
            Uri.parse('$baseUrl/register'),
            headers: _headers,
            body: jsonEncode({
              'name': name.trim(),
              'email': email.trim(),
              'password': password,
            }),
          )
          .timeout(timeoutDuration);

      final data = jsonDecode(response.body) as Map<String, dynamic>;

      if ((response.statusCode == 200 || response.statusCode == 201) && data['status'] == 'success') {
        _authToken = data['access_token'];
        await saveAuthData(_authToken!, data['user']);
        return data;
      } else if (response.statusCode == 429) {
        return {
          'status': 'error',
          'message': 'มีการทำรายการถี่เกินไป กรุณารอสักครู่',
        };
      } else {
        return {
          'status': 'error',
          'message': data['message'] ?? 'สมัครสมาชิกไม่สำเร็จ',
        };
      }
    } on TimeoutException {
      return {
        'status': 'error',
        'message': 'หมดเวลาเชื่อมต่อเซิร์ฟเวอร์ กรุณาตรวจสอบการเชื่อมต่อ',
      };
    } catch (e) {
      debugPrint('ApiService register error: $e');
      return {
        'status': 'error',
        'message': 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ ($e)',
      };
    }
  }

  static Future<Map<String, dynamic>?> checkTokenValid(String token) async {
    try {
      final response = await http
          .get(
            Uri.parse('$baseUrl/me'),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'Authorization': 'Bearer $token',
            },
          )
          .timeout(timeoutDuration);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          _authToken = token;
          return data['user'];
        }
      } else if (response.statusCode == 401) {
        // Token หมดอายุหรือไม่ถูกต้อง
        await clearAuthData();
        onUnauthorized?.call();
      }
    } catch (e) {
      debugPrint('Check token error: $e');
    }
    return null;
  }

  static Future<void> logout() async {
    if (_authToken != null) {
      try {
        await http
            .post(
              Uri.parse('$baseUrl/logout'),
              headers: _headers,
            )
            .timeout(const Duration(seconds: 4));
      } catch (e) {
        debugPrint('Logout API notice: $e');
      }
    }
    await clearAuthData();
  }

  // ==========================================
  // PRODUCTS API
  // ==========================================
  static Future<List<Product>> getProducts({String? category, String? search}) async {
    String query = '';
    if (category != null && category != 'ทั้งหมด') {
      query += 'category=${Uri.encodeComponent(category)}&';
    }
    if (search != null && search.isNotEmpty) {
      query += 'search=${Uri.encodeComponent(search)}&';
    }

    final response = await http
        .get(
          Uri.parse('$baseUrl/products?$query'),
          headers: _headers,
        )
        .timeout(timeoutDuration);

    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      final List list = body['data'] ?? [];
      return list.map((item) => Product.fromMap(item, item['id'].toString())).toList();
    } else if (response.statusCode == 401) {
      onUnauthorized?.call();
    }
    throw Exception('Server returned code: ${response.statusCode}');
  }

  // ==========================================
  // ORDERS API
  // ==========================================
  static Future<Map<String, dynamic>> createOrder({
    required List<CartItem> items,
    required String shippingAddress,
  }) async {
    try {
      // ส่งเฉพาะ product_id, quantity, size, color, shipping_address (ห้ามส่งราคา)
      final bodyPayload = jsonEncode({
        'shipping_address': shippingAddress,
        'items': items.map((i) => {
          'product_id': int.tryParse(i.productId) ?? i.productId,
          'quantity': i.quantity,
          'size': i.size,
          'color': i.color,
        }).toList(),
      });

      final response = await http
          .post(
            Uri.parse('$baseUrl/orders'),
            headers: _headers,
            body: bodyPayload,
          )
          .timeout(timeoutDuration);

      final data = jsonDecode(response.body) as Map<String, dynamic>;

      if (response.statusCode == 200 || response.statusCode == 201) {
        return {
          'success': true,
          'data': data['data'],
          'message': data['message'] ?? 'สั่งซื้อสำเร็จ',
        };
      } else if (response.statusCode == 401) {
        onUnauthorized?.call();
        return {
          'success': false,
          'message': 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'บันทึกคำสั่งซื้อไม่สำเร็จ',
        };
      }
    } on TimeoutException {
      return {
        'success': false,
        'message': 'หมดเวลาเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง',
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'เกิดข้อผิดพลาดในการเชื่อมต่อ: $e',
      };
    }
  }

  static Future<List<Order>> getUserOrders() async {
    final response = await http
        .get(
          Uri.parse('$baseUrl/orders'),
          headers: _headers,
        )
        .timeout(timeoutDuration);

    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      final List list = body['data'] ?? [];
      return list.map((item) => Order.fromMap(item, item['id'].toString())).toList();
    } else if (response.statusCode == 401) {
      onUnauthorized?.call();
    }
    throw Exception('Failed to fetch user orders: ${response.statusCode}');
  }

  // ==========================================
  // PAYMENT API
  // ==========================================
  static Future<Map<String, dynamic>> getPaymentInfo(String orderId) async {
    try {
      final response = await http
          .get(
            Uri.parse('$baseUrl/orders/$orderId/payment-info'),
            headers: _headers,
          )
          .timeout(timeoutDuration);

      final data = jsonDecode(response.body) as Map<String, dynamic>;

      if (response.statusCode == 200 && data['status'] == 'success') {
        return {
          'success': true,
          'data': data['data'],
        };
      } else if (response.statusCode == 401) {
        onUnauthorized?.call();
        return {
          'success': false,
          'message': 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่',
        };
      } else if (response.statusCode == 503) {
        return {
          'success': false,
          'message': data['message'] ?? 'ระบบยังไม่พร้อมรับชำระเงิน กรุณาติดต่อทางร้าน',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'ไม่สามารถดึงข้อมูลการชำระเงินได้',
        };
      }
    } on TimeoutException {
      return {
        'success': false,
        'message': 'หมดเวลาเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง',
      };
    } catch (e) {
      debugPrint('ApiService getPaymentInfo error: $e');
      return {
        'success': false,
        'message': 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ ($e)',
      };
    }
  }

  static Future<Map<String, dynamic>> uploadPaymentSlip(
    String orderId,
    Uint8List fileBytes,
    String fileName,
  ) async {
    try {
      final uri = Uri.parse('$baseUrl/orders/$orderId/payment-slip');
      final request = http.MultipartRequest('POST', uri);

      if (_authToken != null && _authToken!.isNotEmpty) {
        request.headers['Authorization'] = 'Bearer $_authToken';
      }
      request.headers['Accept'] = 'application/json';

      final lowerName = fileName.toLowerCase();
      MediaType contentType;
      if (lowerName.endsWith('.png')) {
        contentType = MediaType('image', 'png');
      } else if (lowerName.endsWith('.webp')) {
        contentType = MediaType('image', 'webp');
      } else {
        contentType = MediaType('image', 'jpeg');
      }

      request.files.add(
        http.MultipartFile.fromBytes(
          'slip_file',
          fileBytes,
          filename: fileName,
          contentType: contentType,
        ),
      );

      final streamedResponse = await request.send().timeout(const Duration(seconds: 30));
      final response = await http.Response.fromStream(streamedResponse);

      Map<String, dynamic> data = {};
      try {
        data = jsonDecode(response.body) as Map<String, dynamic>;
      } catch (_) {}

      if ((response.statusCode == 200 || response.statusCode == 201) && data['status'] == 'success') {
        return {
          'success': true,
          'message': data['message'] ?? 'ส่งสลิปหลักฐานการโอนเรียบร้อยแล้ว รอผู้ดูแลร้านตรวจสอบ',
          'data': data['data'],
        };
      } else if (response.statusCode == 401) {
        onUnauthorized?.call();
        return {
          'success': false,
          'message': 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่',
        };
      } else if (response.statusCode == 422) {
        return {
          'success': false,
          'message': data['message'] ?? 'ข้อมูลสลิปไม่ถูกต้อง หรือสลิปนี้เคยถูกส่งแล้ว',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'ไม่สามารถส่งสลิปได้ (รหัสข้อผิดพลาด: ${response.statusCode})',
        };
      }
    } on TimeoutException {
      return {
        'success': false,
        'message': 'หมดเวลาการอัปโหลดไฟล์ กรุณาลองใหม่อีกครั้ง',
      };
    } catch (e) {
      debugPrint('ApiService uploadPaymentSlip error: $e');
      return {
        'success': false,
        'message': 'เกิดข้อผิดพลาดในการอัปโหลดสลิป: $e',
      };
    }
  }

  static Future<Map<String, dynamic>> cancelOrder(String orderId) async {
    try {
      final response = await http
          .post(
            Uri.parse('$baseUrl/orders/$orderId/cancel'),
            headers: _headers,
          )
          .timeout(timeoutDuration);

      final data = jsonDecode(response.body) as Map<String, dynamic>;

      if (response.statusCode == 200 && data['status'] == 'success') {
        return {
          'success': true,
          'message': data['message'] ?? 'ยกเลิกคำสั่งซื้อเรียบร้อยแล้ว',
        };
      } else if (response.statusCode == 401) {
        onUnauthorized?.call();
        return {
          'success': false,
          'message': 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'ไม่สามารถยกเลิกคำสั่งซื้อได้',
        };
      }
    } on TimeoutException {
      return {
        'success': false,
        'message': 'หมดเวลาเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง',
      };
    } catch (e) {
      debugPrint('ApiService cancelOrder error: $e');
      return {
        'success': false,
        'message': 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ ($e)',
      };
    }
  }
}
