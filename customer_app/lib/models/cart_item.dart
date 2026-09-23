class CartItem {
  final String productId;
  final String size;
  final String color;
  int quantity;
  final String productName;
  final double price;
  final String imageUrl;

  CartItem({
    required this.productId,
    required this.size,
    required this.color,
    required this.quantity,
    this.productName = '',
    this.price = 0.0,
    this.imageUrl = '',
  });

  double get totalPrice => price * quantity;

  Map<String, dynamic> toMap() {
    return {
      'productId': productId,
      'product_id': productId,
      'size': size,
      'color': color,
      'quantity': quantity,
      'productName': productName,
      'product_name': productName,
      'price': price,
      'imageUrl': imageUrl,
      'image_url': imageUrl,
    };
  }

  factory CartItem.fromMap(Map<String, dynamic> map) {
    return CartItem(
      productId: (map['product_id'] ?? map['productId'] ?? '').toString(),
      size: map['size'] ?? '',
      color: map['color'] ?? '',
      quantity: (map['quantity'] as num?)?.toInt() ?? 1,
      productName: map['product_name'] ?? map['productName'] ?? '',
      price: (map['price'] as num?)?.toDouble() ?? 0.0,
      imageUrl: map['image_url'] ?? map['imageUrl'] ?? '',
    );
  }

  CartItem copyWith({
    String? productId,
    String? size,
    String? color,
    int? quantity,
    String? productName,
    double? price,
    String? imageUrl,
  }) {
    return CartItem(
      productId: productId ?? this.productId,
      size: size ?? this.size,
      color: color ?? this.color,
      quantity: quantity ?? this.quantity,
      productName: productName ?? this.productName,
      price: price ?? this.price,
      imageUrl: imageUrl ?? this.imageUrl,
    );
  }
}
