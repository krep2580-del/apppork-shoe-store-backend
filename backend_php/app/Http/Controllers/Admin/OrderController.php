<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    /**
     * หน้ารายการคำสั่งซื้อทั้งหมด พร้อมแท็บตัวกรองและป้ายรอตรวจสลิป
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items', 'latestPayment']);

        $filter = $request->query('filter', 'all');

        if ($filter === 'pending_verification') {
            // เรียงตามเวลาที่ส่งสลิปล่าสุดของรายการ pending (payments.created_at) เก่าสุดก่อน
            $query->where('payment_status', 'pending_verification')
                  ->addSelect([
                      'orders.*',
                      'latest_pending_slip_at' => Payment::select('created_at')
                          ->whereColumn('payments.order_id', 'orders.id')
                          ->where('status', 'pending')
                          ->latest()
                          ->limit(1)
                  ])
                  ->orderBy('latest_pending_slip_at', 'asc');
        } elseif ($filter === 'unpaid') {
            $query->where('payment_status', 'unpaid')
                  ->where('status', '!=', 'cancelled')
                  ->latest();
        } elseif ($filter === 'paid') {
            $query->where('payment_status', 'paid')
                  ->latest();
        } elseif ($filter === 'cancelled') {
            $query->where('status', 'cancelled')
                  ->latest();
        } elseif ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status)->latest();
        } else {
            $query->latest();
        }

        $orders = $query->paginate(10)->withQueryString();

        $pendingVerificationCount = Order::where('payment_status', 'pending_verification')->count();

        $statusCounts = [
            'all' => Order::count(),
            'pending_verification' => $pendingVerificationCount,
            'unpaid' => Order::where('payment_status', 'unpaid')->where('status', '!=', 'cancelled')->count(),
            'paid' => Order::where('payment_status', 'paid')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];

        return view('admin.orders.index', compact('orders', 'statusCounts', 'filter', 'pendingVerificationCount'));
    }

    /**
     * หน้ารายละเอียดคำสั่งซื้อ
     */
    public function show($id)
    {
        $order = Order::with(['user', 'items', 'payments.verifier'])->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }

    /**
     * เปลี่ยนสถานะขั้นตอนจัดส่งของออเดอร์ (pending / shipping / completed / cancelled)
     * เงื่อนไข: เซิร์ฟเวอร์บังคับห้ามเปลี่ยนเป็น shipping หรือ completed ถ้ายังไม่ได้ชำระเงิน (payment_status != paid)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,shipping,completed,cancelled',
        ], [
            'status.required' => 'กรุณาเลือกสถานะคำสั่งซื้อ',
            'status.in' => 'สถานะคำสั่งซื้อไม่ถูกต้อง',
        ]);

        $order = Order::findOrFail($id);

        // เซิร์ฟเวอร์บังคับตรวจ: ห้ามเปลี่ยนเป็น shipping หรือ completed ถ้า payment_status ไม่ใช่ paid
        if (in_array($request->status, ['shipping', 'completed'], true) && $order->payment_status !== 'paid') {
            return back()->with('error', 'ไม่สามารถเปลี่ยนสถานะเป็น "' . ($request->status === 'shipping' ? 'กำลังจัดส่ง' : 'สำเร็จ') . '" ได้ เนื่องจากออเดอร์นี้ยังไม่ได้ชำระเงิน (Payment Status ต้องเป็น Paid เท่านั้น)');
        }

        // หากแอดมินยกเลิกออเดอร์ และออเดอร์ยังไม่เคยถูกยกเลิกมาก่อน ให้คืนสต็อกสินค้าใน Transaction
        if ($request->status === 'cancelled' && $order->status !== 'cancelled') {
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                    }
                }
                $order->status = 'cancelled';
                $order->save();
            });

            return back()->with('success', "ยกเลิกคำสั่งซื้อ #{$order->id} และคืนสต็อกสินค้าเรียบร้อยแล้ว");
        }

        $order->status = $request->status;
        $order->save();

        $statusThai = match ($request->status) {
            'shipping' => 'กำลังจัดส่ง',
            'completed' => 'สำเร็จ',
            'cancelled' => 'ยกเลิกแล้ว',
            default => 'รอดำเนินการ',
        };

        return back()->with('success', "เปลี่ยนสถานะคำสั่งซื้อ #{$order->id} เป็น \"{$statusThai}\" เรียบร้อยแล้ว");
    }

    /**
     * ดูไฟล์รูปภาพสลิปจาก Private Disk ('slips')
     * ปลอดภัย: ต้องผ่าน Middleware แอดมินเท่านั้น พร้อม Header ป้องกันการ Cache สาธารณะ
     */
    public function viewSlip($id)
    {
        $payment = Payment::findOrFail($id);

        if (!Storage::disk('slips')->exists($payment->slip_path)) {
            abort(404, 'ไม่พบไฟล์สลิปในระบบจัดเก็บส่วนตัว');
        }

        $filePath = Storage::disk('slips')->path($payment->slip_path);
        $mimeType = @mime_content_type($filePath) ?: 'image/jpeg';

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * ยืนยันการชำระเงิน (อนุมัติสลิป)
     * เงื่อนไข:
     * 1) ทำใน Transaction พร้อม lockForUpdate ทั้ง payment และ order
     * 2) บังคับติ๊กช่องยืนยันตรวจยอดเงินเข้าบัญชีตรงกับออเดอร์แล้ว
     * 3) ป้องกันกดซ้ำ / สองแท็บพร้อมกัน
     */
    public function approvePayment(Request $request, $id)
    {
        $request->validate([
            'confirm_verified' => 'required|accepted',
        ], [
            'confirm_verified.required' => 'กรุณาติ๊กยืนยันว่าได้ตรวจสอบยอดเงินเข้าบัญชีตรงกับออเดอร์แล้ว',
            'confirm_verified.accepted' => 'กรุณาติ๊กยืนยันว่าได้ตรวจสอบยอดเงินเข้าบัญชีตรงกับออเดอร์แล้ว',
        ]);

        try {
            DB::transaction(function () use ($id) {
                // ล็อกแถวข้อมูล payment และ order ป้องกัน race condition
                $payment = Payment::where('id', $id)->lockForUpdate()->firstOrFail();
                $order = Order::where('id', $payment->order_id)->lockForUpdate()->firstOrFail();

                // ตรวจสอบเงื่อนไขอย่างเคร่งครัด
                if ($payment->status !== 'pending' || $order->payment_status !== 'pending_verification') {
                    throw new \Exception('รายการชำระเงินนี้ได้รับการตรวจสอบไปแล้ว หรือไม่ได้อยู่ในสถานะรอตรวจสอบ');
                }

                if ($order->status === 'cancelled') {
                    throw new \Exception('คำสั่งซื้อนี้ถูกยกเลิกแล้ว ไม่สามารถอนุมัติการชำระเงินได้');
                }

                $now = now();

                $payment->update([
                    'status' => 'approved',
                    'verified_by' => auth()->id(),
                    'verified_at' => $now,
                ]);

                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => $now,
                ]);
            });

            return back()->with('success', 'อนุมัติการชำระเงินและปรับสถานะออเดอร์เป็น "ชำระแล้ว" เรียบร้อยแล้ว');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * ปฏิเสธสลิปหลักฐานการโอน
     * เงื่อนไข:
     * 1) บังคับระบุเหตุผล (จำกัดไม่เกิน 500 ตัวอักษร)
     * 2) ต่อเวลา expires_at เป็น ตอนนี้ + PAYMENT_RETRY_HOURS เพื่อให้ลูกค้ามีเวลาส่งสลิปใหม่
     * 3) ปรับสถานะออเดอร์กลับเป็น 'unpaid'
     */
    public function rejectPayment(Request $request, $id)
    {
        $request->validate([
            'reject_reason' => 'required|string|max:500',
        ], [
            'reject_reason.required' => 'กรุณาระบุเหตุผลในการปฏิเสธสลิป',
            'reject_reason.max' => 'เหตุผลในการปฏิเสธต้องมีความยาวไม่เกิน 500 ตัวอักษร',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $payment = Payment::where('id', $id)->lockForUpdate()->firstOrFail();
                $order = Order::where('id', $payment->order_id)->lockForUpdate()->firstOrFail();

                if ($payment->status !== 'pending' || $order->payment_status !== 'pending_verification') {
                    throw new \Exception('รายการชำระเงินนี้ได้รับการตรวจสอบไปแล้ว หรือไม่ได้อยู่ในสถานะรอตรวจสอบ');
                }

                if ($order->status === 'cancelled') {
                    throw new \Exception('คำสั่งซื้อนี้ถูกยกเลิกแล้ว ไม่สามารถปฏิเสธสลิปได้');
                }

                $now = now();
                $retryMinutes = (int) config('payment.retry_minutes', 720);
                $newExpiresAt = $now->copy()->addMinutes($retryMinutes);

                $payment->update([
                    'status' => 'rejected',
                    'reject_reason' => trim($request->reject_reason),
                    'verified_by' => auth()->id(),
                    'verified_at' => $now,
                ]);

                // ปรับสถานะออเดอร์กลับเป็น unpaid เพื่อให้ลูกค้าส่งสลิปใหม่ได้ พร้อมขยายเวลาหมดอายุ
                $order->update([
                    'payment_status' => 'unpaid',
                    'expires_at' => $newExpiresAt,
                ]);
            });

            return back()->with('success', 'ปฏิเสธสลิปและขยายเวลาให้ลูกค้าส่งสลิปใหม่เรียบร้อยแล้ว');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
