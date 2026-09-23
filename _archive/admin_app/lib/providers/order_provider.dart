import 'package:flutter/foundation.dart';
import '../models/order.dart';
import '../models/cart_item.dart';
import '../services/api_service.dart';

class OrderProvider extends ChangeNotifier {
  List<Order> _orders = [];
  bool _isLoading = false;

  List<Order> get orders => _orders;
  bool get isLoading => _isLoading;

  double get totalRevenue {
    return _orders
        .where((o) => o.status == 'completed' || o.status == 'shipping')
        .fold(0.0, (previousValue, o) => previousValue + o.totalPrice);
  }

  int get todayOrdersCount {
    final now = DateTime.now();
    return _orders.where((o) {
      return o.createdAt.year == now.year &&
          o.createdAt.month == now.month &&
          o.createdAt.day == now.day;
    }).length;
  }

  OrderProvider() {
    fetchOrders();
  }

  Future<void> fetchOrders() async {
    _isLoading = true;
    notifyListeners();

    try {
      final fetched = await ApiService.getOrders();
      if (fetched.isNotEmpty) {
        _orders = fetched;
      } else if (_orders.isEmpty) {
        seedSampleOrders();
      }
    } catch (e) {
      debugPrint('API fetch orders error: $e');
      if (_orders.isEmpty) {
        seedSampleOrders();
      }
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> updateOrderStatus(String orderId, String newStatus) async {
    try {
      await ApiService.updateOrderStatus(orderId, newStatus);
    } catch (e) {
      debugPrint('Error updating order status via API: $e');
    }

    final index = _orders.indexWhere((o) => o.id == orderId);
    if (index != -1) {
      final old = _orders[index];
      _orders[index] = Order(
        id: old.id,
        userId: old.userId,
        userEmail: old.userEmail,
        items: old.items,
        totalPrice: old.totalPrice,
        status: newStatus,
        shippingAddress: old.shippingAddress,
        createdAt: old.createdAt,
      );
      notifyListeners();
    }
    return true;
  }

  void seedSampleOrders() {
    _orders = [
      Order(
        id: 'ord_101',
        userId: 'usr_001',
        userEmail: 'somchai@gmail.com',
        items: [
          CartItem(
            productId: '1',
            productName: 'Nike Air Max 270',
            price: 4900,
            quantity: 1,
            size: '42',
            color: 'ดำ',
            imageUrl: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600',
          )
        ],
        totalPrice: 4900,
        status: 'completed',
        shippingAddress: '123/45 ถนนสุขุมวิท เขตวัฒนา กรุงเทพฯ 10110',
        createdAt: DateTime.now().subtract(const Duration(hours: 3)),
      ),
      Order(
        id: 'ord_102',
        userId: 'usr_002',
        userEmail: 'maneec@gmail.com',
        items: [
          CartItem(
            productId: '2',
            productName: 'Adidas Ultraboost Light',
            price: 5200,
            quantity: 1,
            size: '39',
            color: 'ขาว',
            imageUrl: 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=600',
          ),
          CartItem(
            productId: '4',
            productName: 'Birkenstock Comfort Sandal',
            price: 2400,
            quantity: 1,
            size: '38',
            color: 'น้ำตาล',
            imageUrl: 'https://images.unsplash.com/photo-1603808033192-082d6919d3e1?w=600',
          ),
        ],
        totalPrice: 7600,
        status: 'shipping',
        shippingAddress: '88/9 หมู่ 5 ต.สุเทพ อ.เมือง จ.เชียงใหม่ 50200',
        createdAt: DateTime.now().subtract(const Duration(hours: 8)),
      ),
      Order(
        id: 'ord_103',
        userId: 'usr_003',
        userEmail: 'ananda@gmail.com',
        items: [
          CartItem(
            productId: '5',
            productName: 'Puma RS-X Triple White',
            price: 3600,
            quantity: 2,
            size: '41',
            color: 'ขาว',
            imageUrl: 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=600',
          ),
        ],
        totalPrice: 7200,
        status: 'pending',
        shippingAddress: '456 ถนนมิตรภาพ อ.เมือง จ.ขอนแก่น 40000',
        createdAt: DateTime.now().subtract(const Duration(hours: 20)),
      ),
    ];
    notifyListeners();
  }
}
