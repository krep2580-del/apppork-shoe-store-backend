import 'package:flutter/foundation.dart';
import '../models/order.dart';
import '../models/cart_item.dart';
import '../services/api_service.dart';

class OrderProvider extends ChangeNotifier {
  List<Order> _userOrders = [];
  bool _isLoading = false;
  String? _errorMessage;
  double? _lastConfirmedTotal;
  String? _lastCreatedOrderId;

  List<Order> get userOrders => _userOrders;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  double? get lastConfirmedTotal => _lastConfirmedTotal;
  String? get lastCreatedOrderId => _lastCreatedOrderId;

  Future<void> fetchUserOrders(String userId) async {
    _isLoading = true;
    notifyListeners();

    try {
      final fetched = await ApiService.getUserOrders();
      _userOrders = fetched;
    } catch (e) {
      debugPrint('API fetch orders error: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  /// สร้างคำสั่งซื้อ
  /// เงื่อนไข: ส่งเฉพาะ product_id, quantity, size, color, shipping_address (ห้ามส่งราคา)
  /// และคำนวณราคาจริงจากเซิร์ฟเวอร์ ห้าม fallback มาเป็นสำเร็จเมื่อเกิด error
  Future<bool> createOrder({
    required String userId,
    required String userEmail,
    required List<CartItem> items,
    required String shippingAddress,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    _lastConfirmedTotal = null;
    _lastCreatedOrderId = null;
    notifyListeners();

    try {
      final res = await ApiService.createOrder(
        items: items,
        shippingAddress: shippingAddress,
      );

      if (res['success'] == true) {
        final data = res['data'];
        final serverOrderId = data != null ? data['id'].toString() : 'ord_${DateTime.now().millisecondsSinceEpoch}';
        final serverTotal = data != null ? ((data['total_price'] as num?)?.toDouble() ?? 0.0) : 0.0;
        _lastConfirmedTotal = serverTotal;
        _lastCreatedOrderId = serverOrderId;

        final newOrder = Order(
          id: serverOrderId,
          userId: userId,
          userEmail: userEmail,
          items: items,
          totalPrice: serverTotal,
          status: 'pending',
          shippingAddress: shippingAddress,
          createdAt: DateTime.now(),
        );

        _userOrders.insert(0, newOrder);
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        // บันทึก Error จาก Server และห้าม fallback เด็ดขาด
        _errorMessage = res['message'] ?? 'สั่งซื้อสินค้าไม่สำเร็จ';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      debugPrint('OrderProvider createOrder exception: $e');
      _errorMessage = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์เพื่อสั่งซื้อได้: $e';
      _isLoading = false;
      notifyListeners();
      return false; // แก้ไขไม่ให้คืนค่า true ใน catch
    }
  }
}
