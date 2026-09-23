import 'package:cloud_firestore/cloud_firestore.dart';
import 'cart_item.dart';

class Order {
  final String id;
  final String userId;
  final String userEmail;
  final List<CartItem> items;
  final double totalPrice;
  final String status; // 'pending', 'shipping', 'completed'
  final String shippingAddress;
  final DateTime createdAt;

  Order({
    required this.id,
    required this.userId,
    this.userEmail = '',
    required this.items,
    required this.totalPrice,
    required this.status,
    required this.shippingAddress,
    required this.createdAt,
  });

  String get statusThai {
    switch (status) {
      case 'shipping':
        return 'กำลังจัดส่ง';
      case 'completed':
        return 'สำเร็จ';
      case 'pending':
      default:
        return 'รอดำเนินการ';
    }
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
      'shippingAddress': shippingAddress,
      'shipping_address': shippingAddress,
      'createdAt': Timestamp.fromDate(createdAt),
    };
  }

  factory Order.fromMap(Map<String, dynamic> map, String docId) {
    final itemsList = (map['items'] as List<dynamic>?)
            ?.map((item) => CartItem.fromMap(item as Map<String, dynamic>))
            .toList() ??
        [];

    final userMap = map['user'] is Map<String, dynamic> ? map['user'] as Map<String, dynamic> : null;
    final email = map['user_email'] ?? map['userEmail'] ?? userMap?['email'] ?? '';

    return Order(
      id: docId,
      userId: (map['user_id'] ?? map['userId'] ?? '').toString(),
      userEmail: email,
      items: itemsList,
      totalPrice: (map['total_price'] ?? map['totalPrice'] as num?)?.toDouble() ?? 0.0,
      status: map['status'] ?? 'pending',
      shippingAddress: map['shipping_address'] ?? map['shippingAddress'] ?? '',
      createdAt: map['created_at'] != null
          ? (DateTime.tryParse(map['created_at'].toString()) ?? DateTime.now())
          : (map['createdAt'] is Timestamp
              ? (map['createdAt'] as Timestamp).toDate()
              : (map['createdAt'] is String
                  ? DateTime.tryParse(map['createdAt']) ?? DateTime.now()
                  : DateTime.now())),
    );
  }

  factory Order.fromFirestore(DocumentSnapshot doc) {
    final data = doc.data() as Map<String, dynamic>? ?? {};
    return Order.fromMap(data, doc.id);
  }
}
