import 'package:flutter/foundation.dart';
import '../models/product.dart';
import '../services/api_service.dart';

class ProductProvider extends ChangeNotifier {
  List<Product> _products = [];
  bool _isLoading = false;
  bool _isUsingFallback = false;

  List<Product> get products => _products;
  bool get isLoading => _isLoading;
  bool get isUsingFallback => _isUsingFallback;

  ProductProvider() {
    fetchProducts();
  }

  Future<void> fetchProducts({String? category, String? search}) async {
    _isLoading = true;
    notifyListeners();

    try {
      final fetched = await ApiService.getProducts(category: category, search: search);
      if (fetched.isNotEmpty) {
        _products = fetched;
        _isUsingFallback = false;
      } else {
        // หากค้นหาไม่พบ
        _products = [];
        _isUsingFallback = false;
      }
    } catch (e) {
      debugPrint('Error fetching products from API: $e');
      _isUsingFallback = true;
      if (_products.isEmpty) {
        _loadSampleProducts();
      }
    }

    _isLoading = false;
    notifyListeners();
  }

  void _loadSampleProducts() {
    _products = [
      Product(
        id: '1',
        name: 'Nike Air Max 270',
        description: 'รองเท้าผ้าใบระดับพรีเมียม สวมใส่สบายด้วยเทคโนโลยี Air Max รองรับแรงกระแทกได้อย่างยอดเยี่ยม',
        price: 4900,
        category: 'รองเท้าผ้าใบ',
        sizes: ['39', '40', '41', '42', '43', '44'],
        colors: ['ดำ', 'ขาว', 'แดง'],
        imageUrl: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600',
        stock: 15,
        createdAt: DateTime.now().subtract(const Duration(days: 5)),
      ),
      Product(
        id: '2',
        name: 'Adidas Ultraboost Light',
        description: 'รองเท้าวิ่งน้ำหนักเบา ให้ความยืดหยุ่นและการคืนพลังงานสูงสุด เหมาะสำหรับการวิ่งและออกกำลังกาย',
        price: 5200,
        category: 'รองเท้ากีฬา',
        sizes: ['38', '39', '40', '41', '42'],
        colors: ['ดำ', 'ขาว', 'น้ำเงิน'],
        imageUrl: 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=600',
        stock: 20,
        createdAt: DateTime.now().subtract(const Duration(days: 4)),
      ),
      Product(
        id: '3',
        name: 'Clarks Classic Leather Oxford',
        description: 'รองเท้าหนังแท้ทรง Oxford ดีไซน์คลาสสิก เรียบหรู เหมาะสำหรับใส่ทำงานและงานเป็นทางการ',
        price: 3800,
        category: 'รองเท้าหนัง',
        sizes: ['40', '41', '42', '43'],
        colors: ['น้ำตาล', 'ดำ'],
        imageUrl: 'https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?w=600',
        stock: 8,
        createdAt: DateTime.now().subtract(const Duration(days: 3)),
      ),
      Product(
        id: '4',
        name: 'Birkenstock Comfort Sandal',
        description: 'รองเท้าแตะเพื่อสุขภาพ พื้นรองเท้าออกแบบตามสรีระเท้า สวมใส่สบายตลอดทั้งวัน',
        price: 2400,
        category: 'รองเท้าแตะ',
        sizes: ['37', '38', '39', '40', '41', '42'],
        colors: ['น้ำตาล', 'ดำ', 'เบจ'],
        imageUrl: 'https://images.unsplash.com/photo-1603808033192-082d6919d3e1?w=600',
        stock: 25,
        createdAt: DateTime.now().subtract(const Duration(days: 2)),
      ),
      Product(
        id: '5',
        name: 'Puma RS-X Triple White',
        description: 'รองเท้าสตรีทสไตล์ทรง Chunky ดีไซน์โดดเด่น แมตช์ได้กับทุกชุด',
        price: 3600,
        category: 'รองเท้าผ้าใบ',
        sizes: ['39', '40', '41', '42', '43'],
        colors: ['ขาว'],
        imageUrl: 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=600',
        stock: 12,
        createdAt: DateTime.now().subtract(const Duration(days: 1)),
      ),
    ];
  }
}
