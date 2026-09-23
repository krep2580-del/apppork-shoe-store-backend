import 'cart_item.dart';

class Order {
  final String id;
  final String userId;
  final String userEmail;
  final List<CartItem> items;
  final double totalPrice;
  final String status; // 'pending', 'shipping', 'completed', 'cancelled'
  final String paymentStatus; // 'unpaid', 'pending_verification', 'paid'
  final DateTime? expiresAt;
  final DateTime? paidAt;
  final String? rejectReason;
  final int secondsRemaining;
  final String shippingAddress;
  final DateTime createdAt;

  Order({
    required this.id,
    required this.userId,
    this.userEmail = '',
    required this.items,
    required this.totalPrice,
    required this.status,
    this.paymentStatus = 'unpaid',
    this.expiresAt,
    this.paidAt,
    this.rejectReason,
    this.secondsRemaining = 0,
    required this.shippingAddress,
    required this.createdAt,
  });

  String get statusThai {
    switch (status) {
      case 'shipping':
        return 'กำลังจัดส่ง';
      case 'completed':
        return 'สำเร็จ';
      case 'cancelled':
        return 'ยกเลิกแล้ว';
      case 'pending':
      default:
        return 'รอดำเนินการ';
    }
  }

  String get paymentStatusThai {
    if (status == 'cancelled') {
      return 'ยกเลิกแล้ว';
    }
    switch (paymentStatus) {
      case 'paid':
        return 'ชำระแล้ว';
      case 'pending_verification':
        return 'รอตรวจสอบสลิป';
      case 'unpaid':
      default:
        if (rejectReason != null && rejectReason!.trim().isNotEmpty) {
          return 'สลิปถูกปฏิเสธ';
        }
        return 'ยังไม่ชำระ';
    }
  }

  bool get isExpired {
    if (paymentStatus == 'paid' || status == 'cancelled') {
      return false;
    }
    if (expiresAt == null) return false;
    return DateTime.now().isAfter(expiresAt!);
  }

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'userId': userId,
      'user_id': userId,
      'userEmail': userEmail,
      'user_email': userEmail,
      'items': items.map((item) => item.toMap()).toList(),
      'totalPrice': totalPrice,
      'total_price': totalPrice,
      'status': status,
      'payment_status': paymentStatus,
      'expires_at': expiresAt?.toIso8601String(),
      'paid_at': paidAt?.toIso8601String(),
      'reject_reason': rejectReason,
      'seconds_remaining': secondsRemaining,
      'shippingAddress': shippingAddress,
      'shipping_address': shippingAddress,
      'created_at': createdAt.toIso8601String(),
    };
  }

  factory Order.fromMap(Map<String, dynamic> map, String docId) {
    final itemsList = (map['items'] as List<dynamic>?)
            ?.map((item) => CartItem.fromMap(item as Map<String, dynamic>))
            .toList() ??
        [];

    final userMap = map['user'] is Map<String, dynamic> ? map['user'] as Map<String, dynamic> : null;
    final email = map['user_email'] ?? map['userEmail'] ?? userMap?['email'] ?? '';

    DateTime? parseDate(dynamic val) {
      if (val == null) return null;
      return DateTime.tryParse(val.toString());
    }

    final expires = parseDate(map['expires_at'] ?? map['expiresAt']);
    final paid = parseDate(map['paid_at'] ?? map['paidAt']);

    return Order(
      id: docId,
      userId: (map['user_id'] ?? map['userId'] ?? '').toString(),
      userEmail: email,
      items: itemsList,
      totalPrice: (map['total_price'] ?? map['totalPrice'] as num?)?.toDouble() ?? 0.0,
      status: (map['status'] ?? 'pending').toString(),
      paymentStatus: (map['payment_status'] ?? map['paymentStatus'] ?? 'unpaid').toString(),
      expiresAt: expires,
      paidAt: paid,
      rejectReason: map['reject_reason']?.toString(),
      secondsRemaining: (map['seconds_remaining'] as num?)?.toInt() ?? 0,
      shippingAddress: map['shipping_address'] ?? map['shippingAddress'] ?? '',
      createdAt: map['created_at'] != null
          ? (DateTime.tryParse(map['created_at'].toString()) ?? DateTime.now())
          : DateTime.now(),
    );
  }
}
