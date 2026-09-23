import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../models/product.dart';
import '../models/order.dart';

class ApiService {
  static String get baseUrl {
    if (kIsWeb) {
      return 'http://localhost/shoe_store_api/public/api';
    } else if (defaultTargetPlatform == TargetPlatform.android) {
      return 'http://10.0.2.2/shoe_store_api/public/api'; // Android Emulator
    }
    return 'http://localhost/shoe_store_api/public/api';
  }

  static String? _authToken;
  static String? get authToken => _authToken;
  static void setAuthToken(String? token) => _authToken = token;

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

  // AUTH
  static Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: _headers,
      body: jsonEncode({'email': email, 'password': password}),
    );
    final data = jsonDecode(response.body);
    if (response.statusCode == 200 && data['status'] == 'success') {
      _authToken = data['access_token'];
    }
    return data;
  }

  static Future<Map<String, dynamic>?> checkTokenValid(String token) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/me'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          _authToken = token;
          return data['user'];
        }
      }
    } catch (e) {
      debugPrint('Check token error: $e');
    }
    return null;
  }

  static Future<void> logout() async {
    if (_authToken != null) {
      try {
        await http.post(
          Uri.parse('$baseUrl/logout'),
          headers: _headers,
        );
      } catch (e) {
        debugPrint('Logout API error: $e');
      }
    }
    _authToken = null;
  }

  // PRODUCTS
  static Future<List<Product>> getProducts() async {
    final response = await http.get(
      Uri.parse('$baseUrl/products'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      final List list = body['data'] ?? [];
      return list.map((item) => Product.fromMap(item, item['id'].toString())).toList();
    }
    return [];
  }

  static Future<bool> addProduct(Product product, dynamic imageFile) async {
    final request = http.MultipartRequest('POST', Uri.parse('$baseUrl/products'));

    if (_authToken != null) {
      request.headers['Authorization'] = 'Bearer $_authToken';
    }
    request.headers['Accept'] = 'application/json';

    request.fields['name'] = product.name;
    request.fields['description'] = product.description;
    request.fields['price'] = product.price.toString();
    request.fields['category'] = product.category;
    request.fields['stock'] = product.stock.toString();
    request.fields['sizes'] = jsonEncode(product.sizes);
    request.fields['colors'] = jsonEncode(product.colors);
    request.fields['image_url'] = product.imageUrl;

    if (imageFile != null) {
      if (kIsWeb && imageFile is Uint8List) {
        request.files.add(http.MultipartFile.fromBytes(
          'image',
          imageFile,
          filename: 'product.jpg',
        ));
      } else if (imageFile.path != null) {
        request.files.add(await http.MultipartFile.fromPath('image', imageFile.path));
      }
    }

    final streamedResponse = await request.send();
    return streamedResponse.statusCode == 200 || streamedResponse.statusCode == 201;
  }

  static Future<bool> updateProduct(Product product, dynamic imageFile) async {
    final response = await http.put(
      Uri.parse('$baseUrl/products/${product.id}'),
      headers: _headers,
      body: jsonEncode({
        'name': product.name,
        'description': product.description,
        'price': product.price,
        'category': product.category,
        'stock': product.stock,
        'sizes': product.sizes,
        'colors': product.colors,
        'image_url': product.imageUrl,
      }),
    );

    return response.statusCode == 200;
  }

  static Future<bool> deleteProduct(String productId) async {
    final response = await http.delete(
      Uri.parse('$baseUrl/products/$productId'),
      headers: _headers,
    );
    return response.statusCode == 200;
  }

  // ORDERS
  static Future<List<Order>> getOrders() async {
    final response = await http.get(
      Uri.parse('$baseUrl/orders'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      final List list = body['data'] ?? [];
      return list.map((item) => Order.fromMap(item, item['id'].toString())).toList();
    }
    return [];
  }

  static Future<bool> updateOrderStatus(String orderId, String status) async {
    final response = await http.put(
      Uri.parse('$baseUrl/orders/$orderId/status'),
      headers: _headers,
      body: jsonEncode({'status': status}),
    );
    return response.statusCode == 200;
  }
}
