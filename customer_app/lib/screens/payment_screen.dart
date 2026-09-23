import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../services/api_service.dart';

class PaymentScreen extends StatefulWidget {
  final String orderId;

  const PaymentScreen({
    super.key,
    required this.orderId,
  });

  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  Map<String, dynamic>? _paymentData;

  Timer? _countdownTimer;
  int _secondsRemaining = 0;

  Uint8List? _slipBytes;
  String? _slipFileName;
  bool _isUploading = false;
  bool _isCancelling = false;

  final ImagePicker _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _fetchPaymentInfo();
  }

  @override
  void dispose() {
    _countdownTimer?.cancel();
    super.dispose();
  }

  Future<void> _fetchPaymentInfo() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final res = await ApiService.getPaymentInfo(widget.orderId);
    if (!mounted) return;

    if (res['success'] == true) {
      final data = res['data'] as Map<String, dynamic>;
      setState(() {
        _paymentData = data;
        _secondsRemaining = (data['seconds_remaining'] as num?)?.toInt() ?? 0;
        _isLoading = false;
      });
      _startTimer();
    } else {
      setState(() {
        _errorMessage = res['message'] ?? 'ไม่สามารถดึงข้อมูลการชำระเงินได้';
        _isLoading = false;
      });
    }
  }

  void _startTimer() {
    _countdownTimer?.cancel();
    if (_secondsRemaining <= 0) return;

    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) {
        timer.cancel();
        return;
      }
      if (_secondsRemaining > 0) {
        setState(() {
          _secondsRemaining--;
        });
      } else {
        timer.cancel();
      }
    });
  }

  String _formatDuration(int totalSeconds) {
    if (totalSeconds <= 0) return '00:00:00';
    final hours = totalSeconds ~/ 3600;
    final minutes = (totalSeconds % 3600) ~/ 60;
    final seconds = totalSeconds % 60;
    final hStr = hours.toString().padLeft(2, '0');
    final mStr = minutes.toString().padLeft(2, '0');
    final sStr = seconds.toString().padLeft(2, '0');
    return '$hStr:$mStr:$sStr';
  }

  Future<void> _pickSlipImage(ImageSource source) async {
    try {
      final pickedFile = await _picker.pickImage(
        source: source,
        imageQuality: 80,
        maxWidth: 1600,
      );
      if (pickedFile != null) {
        final bytes = await pickedFile.readAsBytes();
        setState(() {
          _slipBytes = bytes;
          _slipFileName = pickedFile.name;
        });
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('ไม่สามารถเลือกรูปภาพได้: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _uploadSlip() async {
    if (_slipBytes == null || _slipFileName == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('กรุณาเลือกรูปภาพสลิปก่อนกดยืนยัน'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    if (_secondsRemaining <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('หมดเวลาชำระเงินแล้ว กรุณาติดต่อทางร้าน'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() {
      _isUploading = true;
    });

    final res = await ApiService.uploadPaymentSlip(
      widget.orderId,
      _slipBytes!,
      _slipFileName!,
    );
    if (!mounted) return;

    setState(() {
      _isUploading = false;
    });

    if (res['success'] == true) {
      setState(() {
        _slipBytes = null;
        _slipFileName = null;
      });

      await showDialog(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => AlertDialog(
          icon: const Icon(Icons.check_circle, color: Colors.green, size: 54),
          title: const Text('ส่งสลิปเรียบร้อย'),
          content: Text(res['message'] ?? 'ส่งสลิปหลักฐานการโอนเรียบร้อยแล้ว รอผู้ดูแลร้านตรวจสอบ'),
          actions: [
            FilledButton(
              onPressed: () {
                Navigator.pop(ctx);
                _fetchPaymentInfo();
              },
              child: const Text('ตกลง'),
            ),
          ],
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['message'] ?? 'เกิดข้อผิดพลาดในการส่งสลิป'),
          backgroundColor: Colors.red,
          duration: const Duration(seconds: 4),
        ),
      );
    }
  }

  Future<void> _cancelOrder() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('ยืนยันการยกเลิกคำสั่งซื้อ'),
        content: const Text('คุณต้องการยกเลิกคำสั่งซื้อนี้ใช่หรือไม่? หากยกเลิกแล้วจะไม่สามารถย้อนกลับได้'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('ไม่ยกเลิก'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('ยืนยันยกเลิก'),
          ),
        ],
      ),
    );

    if (confirm != true || !mounted) return;

    setState(() {
      _isCancelling = true;
    });

    final res = await ApiService.cancelOrder(widget.orderId);
    if (!mounted) return;

    setState(() {
      _isCancelling = false;
    });

    if (res['success'] == true) {
      await showDialog(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => AlertDialog(
          icon: const Icon(Icons.info, color: Colors.orange, size: 54),
          title: const Text('ยกเลิกสำเร็จ'),
          content: Text(res['message'] ?? 'ยกเลิกคำสั่งซื้อเรียบร้อยแล้ว'),
          actions: [
            FilledButton(
              onPressed: () {
                Navigator.pop(ctx);
                Navigator.pop(context); // กลับหน้ารายการ
              },
              child: const Text('กลับหน้ารายการ'),
            ),
          ],
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['message'] ?? 'ไม่สามารถยกเลิกคำสั่งซื้อได้'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  void _copyToClipboard(String text, String label) {
    Clipboard.setData(ClipboardData(text: text));
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('คัดลอก$labelแล้ว: $text'),
        duration: const Duration(seconds: 2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final currencyFormatter = NumberFormat.currency(locale: 'th_TH', symbol: '฿', decimalDigits: 2);

    return Scaffold(
      appBar: AppBar(
        title: Text('ชำระเงิน ออเดอร์ #${widget.orderId}'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'รีเฟรชข้อมูล',
            onPressed: _isLoading ? null : _fetchPaymentInfo,
          ),
        ],
      ),
      body: _buildBody(theme, currencyFormatter),
    );
  }

  Widget _buildBody(ThemeData theme, NumberFormat currencyFormatter) {
    if (_isLoading) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            CircularProgressIndicator(),
            SizedBox(height: 16),
            Text('กำลังโหลดข้อมูลการชำระเงิน...'),
          ],
        ),
      );
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, size: 64, color: Colors.red),
              const SizedBox(height: 16),
              Text(
                _errorMessage!,
                textAlign: TextAlign.center,
                style: theme.textTheme.titleMedium?.copyWith(color: Colors.red.shade700),
              ),
              const SizedBox(height: 24),
              FilledButton.icon(
                onPressed: _fetchPaymentInfo,
                icon: const Icon(Icons.refresh),
                label: const Text('ลองใหม่อีกครั้ง'),
              ),
            ],
          ),
        ),
      );
    }

    final data = _paymentData!;
    final totalPrice = (data['total_price'] as num?)?.toDouble() ?? 0.0;
    final paymentStatus = (data['payment_status'] as String?) ?? 'unpaid';
    final isCancelled = data['status'] == 'cancelled';
    final promptPay = data['promptpay'] as Map<String, dynamic>?;
    final bankTransfer = data['bank_transfer'] as Map<String, dynamic>?;
    final qrPayload = promptPay?['qr_payload'] as String?;
    final isExpired = _secondsRemaining <= 0 && paymentStatus != 'paid' && !isCancelled;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // กล่องสรุปสถานะและยอดเงิน
          Card(
            elevation: 2,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(20.0),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'ออเดอร์ #${widget.orderId}',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                      _buildPaymentStatusChip(paymentStatus, isCancelled),
                    ],
                  ),
                  const Divider(height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'ยอดเงินที่ต้องชำระ:',
                        style: TextStyle(fontSize: 15, color: Colors.black87),
                      ),
                      Text(
                        currencyFormatter.format(totalPrice),
                        style: theme.textTheme.headlineSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: theme.colorScheme.primary,
                        ),
                      ),
                    ],
                  ),
                  if (!isCancelled && paymentStatus != 'paid') ...[
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                      decoration: BoxDecoration(
                        color: isExpired ? Colors.red.shade50 : Colors.amber.shade50,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color: isExpired ? Colors.red.shade300 : Colors.amber.shade400,
                        ),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            isExpired ? Icons.timer_off : Icons.timer,
                            size: 20,
                            color: isExpired ? Colors.red.shade700 : Colors.orange.shade800,
                          ),
                          const SizedBox(width: 8),
                          Text(
                            isExpired
                                ? 'หมดเวลาชำระเงิน กรุณาติดต่อทางร้าน'
                                : 'เวลาชำระเงินคงเหลือ: ${_formatDuration(_secondsRemaining)}',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              color: isExpired ? Colors.red.shade800 : Colors.orange.shade900,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          // ถ้าชำระแล้ว หรือยกเลิกแล้ว
          if (paymentStatus == 'paid') ...[
            _buildInfoCard(
              icon: Icons.check_circle,
              color: Colors.green,
              title: 'ชำระเงินเรียบร้อยแล้ว',
              subtitle: 'ร้านค้าได้รับการชำระเงินแล้ว และกำลังเตรียมจัดส่งสินค้าให้คุณ',
            ),
          ] else if (paymentStatus == 'pending_verification') ...[
            _buildInfoCard(
              icon: Icons.hourglass_top,
              color: Colors.orange,
              title: 'อยู่ระหว่างตรวจสอบสลิป',
              subtitle: 'ร้านค้าได้รับหลักฐานการโอนเงินของคุณแล้ว เจ้าหน้าที่จะตรวจสอบยอดเงินและดำเนินการต่อไปโดยเร็ว',
            ),
          ] else if (isCancelled) ...[
            _buildInfoCard(
              icon: Icons.cancel,
              color: Colors.red,
              title: 'คำสั่งซื้อถูกยกเลิกแล้ว',
              subtitle: 'คำสั่งซื้อนี้ถูกยกเลิก ไม่สามารถดำเนินการชำระเงินได้',
            ),
          ] else ...[
            // ส่วน PromptPay QR
            if (qrPayload != null && qrPayload.isNotEmpty) ...[
              Card(
                elevation: 2,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                child: Padding(
                  padding: const EdgeInsets.all(20.0),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: const Color(0xFF003D6B),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: const Text(
                              'PromptPay',
                              style: TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.bold,
                                fontSize: 13,
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          const Text(
                            'สแกน QR เพื่อชำระเงิน',
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.grey.shade300, width: 2),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.06),
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: QrImageView(
                          data: qrPayload,
                          version: QrVersions.auto,
                          size: 200.0,
                          backgroundColor: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 12),
                      if (promptPay?['payee_name'] != null && (promptPay!['payee_name'] as String).isNotEmpty)
                        Text(
                          'ชื่อบัญชี: ${promptPay['payee_name']}',
                          style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                        ),
                      const SizedBox(height: 4),
                      Text(
                        'พร้อมเพย์: ${promptPay?['id'] ?? ''}',
                        style: TextStyle(fontSize: 12, color: theme.colorScheme.outline),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.red.shade50,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          '⚠️ ห้ามแก้ยอดเงินที่โอนโดยเด็ดขาด',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: Colors.red.shade800,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),
            ],

            // ส่วนโอนผ่านบัญชีธนาคาร
            if (bankTransfer != null && (bankTransfer['account_no'] as String?)?.isNotEmpty == true) ...[
              Card(
                elevation: 2,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                child: Padding(
                  padding: const EdgeInsets.all(20.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Row(
                        children: [
                          Icon(Icons.account_balance, color: Colors.blue),
                          SizedBox(width: 8),
                          Text(
                            'ข้อมูลโอนเงินผ่านบัญชีธนาคาร',
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                          ),
                        ],
                      ),
                      const Divider(height: 20),
                      _buildTransferRow(
                        label: 'ธนาคาร:',
                        value: bankTransfer['bank_name'] ?? '-',
                      ),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: _buildTransferRow(
                              label: 'เลขที่บัญชี:',
                              value: bankTransfer['account_no'] ?? '-',
                              isBold: true,
                            ),
                          ),
                          TextButton.icon(
                            onPressed: () => _copyToClipboard(bankTransfer['account_no'] ?? '', 'เลขที่บัญชี'),
                            icon: const Icon(Icons.copy, size: 16),
                            label: const Text('คัดลอก'),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      _buildTransferRow(
                        label: 'ชื่อบัญชี:',
                        value: bankTransfer['account_name'] ?? '-',
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),
            ],

            // ส่วนแนบสลิป
            Card(
              elevation: 2,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              child: Padding(
                padding: const EdgeInsets.all(20.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.receipt_long, color: Colors.teal),
                        SizedBox(width: 8),
                        Text(
                          'แนบสลิปหลักฐานการโอนเงิน',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                        ),
                      ],
                    ),
                    const Divider(height: 20),
                    if (_slipBytes != null) ...[
                      Center(
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: Stack(
                            children: [
                              Image.memory(
                                _slipBytes!,
                                height: 220,
                                fit: BoxFit.contain,
                              ),
                              Positioned(
                                top: 8,
                                right: 8,
                                child: CircleAvatar(
                                  backgroundColor: Colors.black54,
                                  child: IconButton(
                                    icon: const Icon(Icons.close, color: Colors.white, size: 18),
                                    onPressed: () => setState(() {
                                      _slipBytes = null;
                                      _slipFileName = null;
                                    }),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                    ],
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: isExpired || _isUploading
                                ? null
                                : () => _pickSlipImage(ImageSource.gallery),
                            icon: const Icon(Icons.photo_library),
                            label: const Text('เลือกจากคลังภาพ'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: isExpired || _isUploading
                                ? null
                                : () => _pickSlipImage(ImageSource.camera),
                            icon: const Icon(Icons.camera_alt),
                            label: const Text('ถ่ายรูป'),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: FilledButton.icon(
                        onPressed: isExpired || _slipBytes == null || _isUploading
                            ? null
                            : _uploadSlip,
                        icon: _isUploading
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                              )
                            : const Icon(Icons.cloud_upload),
                        label: Text(
                          _isUploading ? 'กำลังอัปโหลดสลิป...' : 'ยืนยันและส่งสลิป',
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),

            // ปุ่มยกเลิกคำสั่งซื้อ
            if (paymentStatus == 'unpaid' && !isCancelled) ...[
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.red,
                  side: const BorderSide(color: Colors.red),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
                onPressed: _isCancelling || _isUploading ? null : _cancelOrder,
                icon: _isCancelling
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(color: Colors.red, strokeWidth: 2),
                      )
                    : const Icon(Icons.cancel_outlined),
                label: const Text('ยกเลิกคำสั่งซื้อนี้'),
              ),
              const SizedBox(height: 24),
            ],
          ],
        ],
      ),
    );
  }

  Widget _buildTransferRow({required String label, required String value, bool isBold = false}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 90,
          child: Text(
            label,
            style: const TextStyle(color: Colors.grey, fontSize: 13),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: TextStyle(
              fontSize: 14,
              fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildInfoCard({
    required IconData icon,
    required Color color,
    required String title,
    required String subtitle,
  }) {
    return Card(
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          children: [
            Icon(icon, color: color, size: 54),
            const SizedBox(height: 12),
            Text(
              title,
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 18,
                color: color,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              subtitle,
              textAlign: TextAlign.center,
              style: const TextStyle(color: Colors.black87),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPaymentStatusChip(String paymentStatus, bool isCancelled) {
    Color color;
    String label;

    if (isCancelled) {
      color = Colors.red;
      label = 'ยกเลิกแล้ว';
    } else {
      switch (paymentStatus) {
        case 'paid':
          color = Colors.green;
          label = 'ชำระแล้ว';
          break;
        case 'pending_verification':
          color = Colors.orange;
          label = 'รอตรวจสลิป';
          break;
        case 'unpaid':
        default:
          label = 'ยังไม่ชำระ';
          color = Colors.redAccent;
          break;
      }
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.4)),
      ),
      child: Text(
        label,
        style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.bold),
      ),
    );
  }
}
