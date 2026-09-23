import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../providers/order_provider.dart';
import '../models/order.dart';

class OrderManagementScreen extends StatefulWidget {
  const OrderManagementScreen({super.key});

  @override
  State<OrderManagementScreen> createState() => _OrderManagementScreenState();
}

class _OrderManagementScreenState extends State<OrderManagementScreen> {
  String _selectedStatusFilter = 'ทั้งหมด';

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final currencyFormatter = NumberFormat.currency(locale: 'th_TH', symbol: '฿', decimalDigits: 0);
    final dateFormatter = DateFormat('dd/MM/yyyy HH:mm');

    return Consumer<OrderProvider>(
      builder: (context, orderProvider, child) {
        final filteredOrders = orderProvider.orders.where((o) {
          if (_selectedStatusFilter == 'ทั้งหมด') return true;
          if (_selectedStatusFilter == 'รอดำเนินการ') return o.status == 'pending';
          if (_selectedStatusFilter == 'กำลังจัดส่ง') return o.status == 'shipping';
          if (_selectedStatusFilter == 'สำเร็จ') return o.status == 'completed';
          return true;
        }).toList();

        return Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'จัดการออเดอร์',
                        style: theme.textTheme.headlineSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        'ตรวจสอบรายการสั่งซื้อและอัปเดตสถานะการจัดส่ง',
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: theme.colorScheme.outline,
                        ),
                      ),
                    ],
                  ),
                  SegmentedButton<String>(
                    segments: const [
                      ButtonSegment(value: 'ทั้งหมด', label: Text('ทั้งหมด')),
                      ButtonSegment(value: 'รอดำเนินการ', label: Text('รอดำเนินการ')),
                      ButtonSegment(value: 'กำลังจัดส่ง', label: Text('กำลังจัดส่ง')),
                      ButtonSegment(value: 'สำเร็จ', label: Text('สำเร็จ')),
                    ],
                    selected: {_selectedStatusFilter},
                    onSelectionChanged: (Set<String> newSelection) {
                      setState(() {
                        _selectedStatusFilter = newSelection.first;
                      });
                    },
                  ),
                ],
              ),
              const SizedBox(height: 24),
              Expanded(
                child: filteredOrders.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.shopping_bag_outlined,
                                size: 64, color: theme.colorScheme.outline),
                            const SizedBox(height: 16),
                            Text(
                              'ไม่พบบริการสั่งซื้อในหมวดนี้',
                              style: theme.textTheme.titleMedium,
                            ),
                          ],
                        ),
                      )
                    : ListView.builder(
                        itemCount: filteredOrders.length,
                        itemBuilder: (context, index) {
                          final order = filteredOrders[index];
                          return Card(
                            elevation: 2,
                            margin: const EdgeInsets.only(bottom: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                            child: Padding(
                              padding: const EdgeInsets.all(20.0),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Row(
                                        children: [
                                          Icon(Icons.receipt_long,
                                              color: theme.colorScheme.primary),
                                          const SizedBox(width: 8),
                                          Text(
                                            'คำสั่งซื้อ #${order.id}',
                                            style: const TextStyle(
                                              fontWeight: FontWeight.bold,
                                              fontSize: 16,
                                            ),
                                          ),
                                          const SizedBox(width: 12),
                                          Text(
                                            dateFormatter.format(order.createdAt),
                                            style: TextStyle(
                                              color: theme.colorScheme.outline,
                                              fontSize: 13,
                                            ),
                                          ),
                                        ],
                                      ),
                                      _buildStatusDropdown(context, order, orderProvider),
                                    ],
                                  ),
                                  const Divider(height: 24),
                                  Text(
                                    'ลูกค้า: ${order.userEmail.isNotEmpty ? order.userEmail : order.userId}',
                                    style: const TextStyle(fontWeight: FontWeight.w600),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    'ที่อยู่จัดส่ง: ${order.shippingAddress}',
                                    style: TextStyle(color: theme.colorScheme.onSurfaceVariant),
                                  ),
                                  const SizedBox(height: 16),
                                  const Text(
                                    'รายการสินค้า:',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                    ),
                                  ),
                                  const SizedBox(height: 8),
                                  ...order.items.map((item) {
                                    return Container(
                                      margin: const EdgeInsets.only(bottom: 8),
                                      padding: const EdgeInsets.all(8),
                                      decoration: BoxDecoration(
                                        color: theme.colorScheme.surfaceContainerLow,
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Row(
                                        children: [
                                          ClipRRect(
                                            borderRadius: BorderRadius.circular(6),
                                            child: Container(
                                              width: 44,
                                              height: 44,
                                              color: Colors.grey[200],
                                              child: item.imageUrl.isNotEmpty
                                                  ? CachedNetworkImage(
                                                      imageUrl: item.imageUrl,
                                                      fit: BoxFit.cover,
                                                      errorWidget: (context, url, error) =>
                                                          const Icon(Icons.image),
                                                    )
                                                  : const Icon(Icons.image),
                                            ),
                                          ),
                                          const SizedBox(width: 12),
                                          Expanded(
                                            child: Column(
                                              crossAxisAlignment: CrossAxisAlignment.start,
                                              children: [
                                                Text(
                                                  item.productName.isNotEmpty
                                                      ? item.productName
                                                      : item.productId,
                                                  style: const TextStyle(
                                                    fontWeight: FontWeight.bold,
                                                  ),
                                                ),
                                                Text(
                                                  'ไซส์: ${item.size} | สี: ${item.color} | จำนวน: ${item.quantity}',
                                                  style: TextStyle(
                                                    fontSize: 12,
                                                    color: theme.colorScheme.outline,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                          Text(
                                            currencyFormatter.format(item.totalPrice),
                                            style: const TextStyle(
                                              fontWeight: FontWeight.w600,
                                            ),
                                          ),
                                        ],
                                      ),
                                    );
                                  }),
                                  const Divider(height: 24),
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      const Text(
                                        'ราคารวมสุทธิ:',
                                        style: TextStyle(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 16,
                                        ),
                                      ),
                                      Text(
                                        currencyFormatter.format(order.totalPrice),
                                        style: theme.textTheme.headlineSmall?.copyWith(
                                          fontWeight: FontWeight.bold,
                                          color: theme.colorScheme.primary,
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildStatusDropdown(
      BuildContext context, Order order, OrderProvider provider) {
    Color chipColor;
    switch (order.status) {
      case 'shipping':
        chipColor = Colors.blue;
        break;
      case 'completed':
        chipColor = Colors.green;
        break;
      case 'pending':
      default:
        chipColor = Colors.orange;
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      decoration: BoxDecoration(
        color: chipColor.withOpacity(0.15),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: chipColor.withOpacity(0.5)),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: order.status,
          isDense: true,
          style: TextStyle(color: chipColor, fontWeight: FontWeight.bold),
          items: const [
            DropdownMenuItem(
              value: 'pending',
              child: Text('⏳ รอดำเนินการ'),
            ),
            DropdownMenuItem(
              value: 'shipping',
              child: Text('🚚 กำลังจัดส่ง'),
            ),
            DropdownMenuItem(
              value: 'completed',
              child: Text('✅ สำเร็จ'),
            ),
          ],
          onChanged: (newStatus) {
            if (newStatus != null) {
              provider.updateOrderStatus(order.id, newStatus);
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text('อัปเดตสถานะออเดอร์ #${order.id} เรียบร้อยแล้ว'),
                ),
              );
            }
          },
        ),
      ),
    );
  }
}
