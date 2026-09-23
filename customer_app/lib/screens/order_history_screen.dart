import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../providers/order_provider.dart';
import '../providers/auth_provider.dart';
import '../models/order.dart';
import 'payment_screen.dart';

class OrderHistoryScreen extends StatefulWidget {
  const OrderHistoryScreen({super.key});

  @override
  State<OrderHistoryScreen> createState() => _OrderHistoryScreenState();
}

class _OrderHistoryScreenState extends State<OrderHistoryScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _refreshOrders();
    });
  }

  Future<void> _refreshOrders() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    if (authProvider.appUser != null) {
      await Provider.of<OrderProvider>(context, listen: false)
          .fetchUserOrders(authProvider.appUser!.uid);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final currencyFormatter = NumberFormat.currency(locale: 'th_TH', symbol: '฿', decimalDigits: 0);
    final dateFormatter = DateFormat('dd/MM/yyyy HH:mm');

    return Scaffold(
      appBar: AppBar(
        title: const Text('ประวัติการสั่งซื้อ'),
      ),
      body: Consumer<OrderProvider>(
        builder: (context, orderProvider, child) {
          if (orderProvider.isLoading && orderProvider.userOrders.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }

          if (orderProvider.userOrders.isEmpty) {
            return RefreshIndicator(
              onRefresh: _refreshOrders,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: [
                  SizedBox(
                    height: MediaQuery.of(context).size.height * 0.7,
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.history_outlined, size: 64, color: theme.colorScheme.outline),
                          const SizedBox(height: 16),
                          Text(
                            'ยังไม่มีประวัติการสั่งซื้อ',
                            style: theme.textTheme.titleMedium,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'เมื่อคุณกดสั่งซื้อรองเท้า รายการคำสั่งซื้อจะแสดงที่นี่',
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: theme.colorScheme.outline,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: _refreshOrders,
            child: ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: orderProvider.userOrders.length,
              itemBuilder: (context, index) {
                final order = orderProvider.userOrders[index];
                final isUnpaidOrRejected = order.status != 'cancelled' &&
                    (order.paymentStatus == 'unpaid' ||
                        (order.rejectReason != null && order.rejectReason!.trim().isNotEmpty));

                return Card(
                  elevation: 2,
                  margin: const EdgeInsets.only(bottom: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              'ออเดอร์ #${order.id}',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                            ),
                            Wrap(
                              spacing: 6,
                              children: [
                                _buildPaymentBadge(order),
                                _buildStatusBadge(order.status),
                              ],
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          dateFormatter.format(order.createdAt),
                          style: TextStyle(fontSize: 12, color: theme.colorScheme.outline),
                        ),
                        const Divider(height: 20),

                        // รายการสินค้า
                        ...order.items.map((item) {
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 8.0),
                            child: Row(
                              children: [
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(6),
                                  child: Container(
                                    width: 40,
                                    height: 40,
                                    color: Colors.grey[100],
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
                                        item.productName,
                                        style: const TextStyle(fontWeight: FontWeight.w600),
                                      ),
                                      Text(
                                        'EU ${item.size} | ${item.color} | x${item.quantity}',
                                        style: TextStyle(
                                          fontSize: 11,
                                          color: theme.colorScheme.outline,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                Text(
                                  currencyFormatter.format(item.totalPrice),
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                              ],
                            ),
                          );
                        }),
                        const Divider(height: 20),

                        // ราคารวม
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'ราคารวมสุทธิ:',
                              style: TextStyle(fontWeight: FontWeight.bold),
                            ),
                            Text(
                              currencyFormatter.format(order.totalPrice),
                              style: theme.textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.bold,
                                color: theme.colorScheme.primary,
                              ),
                            ),
                          ],
                        ),

                        // แสดงเหตุผลที่ปฏิเสธสลิป (ถ้ามี)
                        if (order.rejectReason != null && order.rejectReason!.trim().isNotEmpty) ...[
                          const SizedBox(height: 12),
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: Colors.red.shade50,
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: Colors.red.shade200),
                            ),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Icon(Icons.warning_amber_rounded, color: Colors.red, size: 20),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const Text(
                                        'สลิปถูกปฏิเสธ:',
                                        style: TextStyle(
                                          fontWeight: FontWeight.bold,
                                          color: Colors.red,
                                          fontSize: 13,
                                        ),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        order.rejectReason!,
                                        style: TextStyle(color: Colors.red.shade900, fontSize: 13),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],

                        // ปุ่มชำระเงิน / แนบสลิปใหม่
                        if (isUnpaidOrRejected) ...[
                          const SizedBox(height: 14),
                          SizedBox(
                            width: double.infinity,
                            child: FilledButton.icon(
                              style: FilledButton.styleFrom(
                                backgroundColor: order.rejectReason != null && order.rejectReason!.trim().isNotEmpty
                                    ? Colors.orange.shade800
                                    : theme.colorScheme.primary,
                              ),
                              onPressed: () async {
                                await Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) => PaymentScreen(orderId: order.id),
                                  ),
                                );
                                _refreshOrders();
                              },
                              icon: const Icon(Icons.payment, size: 18),
                              label: Text(
                                order.rejectReason != null && order.rejectReason!.trim().isNotEmpty
                                    ? 'แนบสลิปใหม่'
                                    : 'ชำระเงิน / แนบสลิป',
                                style: const TextStyle(fontWeight: FontWeight.bold),
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }

  Widget _buildPaymentBadge(Order order) {
    Color color;
    String label = order.paymentStatusThai;

    if (order.status == 'cancelled') {
      color = Colors.grey;
    } else {
      switch (order.paymentStatus) {
        case 'paid':
          color = Colors.green;
          break;
        case 'pending_verification':
          color = Colors.orange;
          break;
        case 'unpaid':
        default:
          if (order.rejectReason != null && order.rejectReason!.trim().isNotEmpty) {
            color = Colors.deepOrange;
          } else {
            color = Colors.red;
          }
          break;
      }
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withValues(alpha: 0.4)),
      ),
      child: Text(
        label,
        style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.bold),
      ),
    );
  }

  Widget _buildStatusBadge(String status) {
    Color color;
    String label;

    switch (status) {
      case 'shipping':
        color = Colors.blue;
        label = '🚚 กำลังจัดส่ง';
        break;
      case 'completed':
        color = Colors.green;
        label = '✅ สำเร็จ';
        break;
      case 'cancelled':
        color = Colors.grey;
        label = '❌ ยกเลิก';
        break;
      case 'pending':
      default:
        color = Colors.amber.shade800;
        label = '⏳ รอดำเนินการ';
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withValues(alpha: 0.4)),
      ),
      child: Text(
        label,
        style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.bold),
      ),
    );
  }
}
