import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../models/product.dart';
import '../providers/product_provider.dart';

class AddEditProductScreen extends StatefulWidget {
  final Product? product;

  const AddEditProductScreen({super.key, this.product});

  @override
  State<AddEditProductScreen> createState() => _AddEditProductScreenState();
}

class _AddEditProductScreenState extends State<AddEditProductScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _priceController = TextEditingController();
  final _stockController = TextEditingController();
  final _imageUrlController = TextEditingController();

  String _category = 'รองเท้าผ้าใบ';
  final List<String> _categories = [
    'รองเท้าผ้าใบ',
    'รองเท้าหนัง',
    'รองเท้ากีฬา',
    'รองเท้าแตะ',
  ];

  final List<String> _availableSizes = ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45'];
  final List<String> _selectedSizes = [];

  final List<String> _availableColors = ['ดำ', 'ขาว', 'แดง', 'น้ำเงิน', 'เทา', 'น้ำตาล', 'เบจ', 'เขียว'];
  final List<String> _selectedColors = [];

  dynamic _pickedImage; // File on mobile/desktop, Uint8List on web
  final ImagePicker _picker = ImagePicker();

  @override
  void initState() {
    super.initState() ;
    if (widget.product != null) {
      final p = widget.product!;
      _nameController.text = p.name;
      _descriptionController.text = p.description;
      _priceController.text = p.price.toStringAsFixed(0);
      _stockController.text = p.stock.toString();
      _imageUrlController.text = p.imageUrl;
      _category = _categories.contains(p.category) ? p.category : _categories.first;
      _selectedSizes.addAll(p.sizes);
      _selectedColors.addAll(p.colors);
    } else {
      _selectedSizes.addAll(['39', '40', '41', '42']);
      _selectedColors.addAll(['ดำ', 'ขาว']);
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _descriptionController.dispose();
    _priceController.dispose();
    _stockController.dispose();
    _imageUrlController.dispose();
    super.dispose();
  }

  Future<void> _pickImage() async {
    final XFile? image = await _picker.pickImage(source: ImageSource.gallery);
    if (image != null) {
      if (kIsWeb) {
        final bytes = await image.readAsBytes();
        setState(() {
          _pickedImage = bytes;
        });
      } else {
        setState(() {
          _pickedImage = File(image.path);
        });
      }
    }
  }

  void _saveProduct() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedSizes.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('กรุณาเลือกไซส์สินค้าอย่างน้อย 1 ไซส์')),
      );
      return;
    }
    if (_selectedColors.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('กรุณาเลือกสีสินค้าอย่างน้อย 1 สี')),
      );
      return;
    }

    final productProvider = Provider.of<ProductProvider>(context, listen: false);

    final product = Product(
      id: widget.product?.id ?? '',
      name: _nameController.text.trim(),
      description: _descriptionController.text.trim(),
      price: double.parse(_priceController.text.trim()),
      category: _category,
      sizes: _selectedSizes,
      colors: _selectedColors,
      imageUrl: _imageUrlController.text.trim(),
      stock: int.parse(_stockController.text.trim()),
      createdAt: widget.product?.createdAt ?? DateTime.now(),
    );

    bool success;
    if (widget.product != null) {
      success = await productProvider.updateProduct(product, imageFile: _pickedImage);
    } else {
      success = await productProvider.addProduct(product, imageFile: _pickedImage);
    }

    if (!mounted) return;

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(widget.product != null
              ? 'บันทึกการแก้ไขสินค้าเรียบร้อย'
              : 'เพิ่มสินค้าใหม่เรียบร้อยแล้ว'),
        ),
      );
      Navigator.pop(context);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isEditing = widget.product != null;

    return Scaffold(
      appBar: AppBar(
        title: Text(isEditing ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24.0),
        child: Center(
          child: Container(
            constraints: const BoxConstraints(maxWidth: 700),
            child: Card(
              elevation: 2,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'รูปภาพสินค้า',
                        style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 12),
                      Center(
                        child: Column(
                          children: [
                            Container(
                              width: 180,
                              height: 180,
                              decoration: BoxDecoration(
                                color: Colors.grey[100],
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: theme.colorScheme.outlineVariant),
                              ),
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(16),
                                child: _pickedImage != null
                                    ? (kIsWeb
                                        ? Image.memory(_pickedImage, fit: BoxFit.cover)
                                        : Image.file(_pickedImage, fit: BoxFit.cover))
                                    : (_imageUrlController.text.isNotEmpty
                                        ? CachedNetworkImage(
                                            imageUrl: _imageUrlController.text,
                                            fit: BoxFit.cover,
                                            errorWidget: (context, url, error) =>
                                                const Icon(Icons.image_not_supported, size: 48),
                                          )
                                        : const Icon(Icons.add_a_photo_outlined,
                                            size: 48, color: Colors.grey)),
                              ),
                            ),
                            const SizedBox(height: 12),
                            OutlinedButton.icon(
                              onPressed: _pickImage,
                              icon: const Icon(Icons.upload_file),
                              label: const Text('เลือกรูปภาพจากเครื่อง'),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _imageUrlController,
                        decoration: InputDecoration(
                          labelText: 'หรือกรอก URL รูปภาพ',
                          hintText: 'https://images.unsplash.com/...',
                          prefixIcon: const Icon(Icons.link),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onChanged: (val) {
                          setState(() {});
                        },
                      ),
                      const SizedBox(height: 24),
                      const Divider(),
                      const SizedBox(height: 16),
                      Text(
                        'ข้อมูลสินค้า',
                        style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _nameController,
                        decoration: InputDecoration(
                          labelText: 'ชื่อสินค้า *',
                          prefixIcon: const Icon(Icons.subtitles_outlined),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        validator: (val) => val == null || val.trim().isEmpty ? 'กรุณากรอกชื่อสินค้า' : null,
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: _priceController,
                              keyboardType: TextInputType.number,
                              decoration: InputDecoration(
                                labelText: 'ราคา (บาท) *',
                                prefixIcon: const Icon(Icons.sell_outlined),
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              ),
                              validator: (val) {
                                if (val == null || val.trim().isEmpty) return 'กรุณากรอกราคา';
                                if (double.tryParse(val.trim()) == null) return 'กรุณากรอกตัวเลข';
                                return null;
                              },
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: TextFormField(
                              controller: _stockController,
                              keyboardType: TextInputType.number,
                              decoration: InputDecoration(
                                labelText: 'จำนวนสต็อก *',
                                prefixIcon: const Icon(Icons.inventory_outlined),
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              ),
                              validator: (val) {
                                if (val == null || val.trim().isEmpty) return 'กรุณากรอกจำนวนสต็อก';
                                if (int.tryParse(val.trim()) == null) return 'กรุณากรอกตัวเลข';
                                return null;
                              },
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      DropdownButtonFormField<String>(
                        value: _category,
                        decoration: InputDecoration(
                          labelText: 'หมวดหมู่สินค้า',
                          prefixIcon: const Icon(Icons.category_outlined),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        items: _categories.map((cat) {
                          return DropdownMenuItem(value: cat, child: Text(cat));
                        }).toList(),
                        onChanged: (val) {
                          if (val != null) setState(() => _category = val);
                        },
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _descriptionController,
                        maxLines: 3,
                        decoration: InputDecoration(
                          labelText: 'คำอธิบายสินค้า',
                          prefixIcon: const Icon(Icons.description_outlined),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                      ),
                      const SizedBox(height: 24),
                      Text(
                        'ไซส์ที่มีให้เลือก *',
                        style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        children: _availableSizes.map((size) {
                          final isSelected = _selectedSizes.contains(size);
                          return FilterChip(
                            label: Text(size),
                            selected: isSelected,
                            onSelected: (selected) {
                              setState(() {
                                if (selected) {
                                  _selectedSizes.add(size);
                                } else {
                                  _selectedSizes.remove(size);
                                }
                              });
                            },
                          );
                        }).toList(),
                      ),
                      const SizedBox(height: 20),
                      Text(
                        'สีที่มีให้เลือก *',
                        style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        children: _availableColors.map((color) {
                          final isSelected = _selectedColors.contains(color);
                          return FilterChip(
                            label: Text(color),
                            selected: isSelected,
                            onSelected: (selected) {
                              setState(() {
                                if (selected) {
                                  _selectedColors.add(color);
                                } else {
                                  _selectedColors.remove(color);
                                }
                              });
                            },
                          );
                        }).toList(),
                      ),
                      const SizedBox(height: 32),
                      Consumer<ProductProvider>(
                        builder: (context, provider, child) {
                          return SizedBox(
                            width: double.infinity,
                            height: 50,
                            child: FilledButton.icon(
                              onPressed: provider.isLoading ? null : _saveProduct,
                              icon: provider.isLoading
                                  ? const SizedBox(
                                      width: 20,
                                      height: 20,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : const Icon(Icons.save),
                              label: Text(
                                isEditing ? 'บันทึกการแก้ไข' : 'เพิ่มสินค้าเข้าสู่ระบบ',
                                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                              ),
                            ),
                          );
                        },
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
