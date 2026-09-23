<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Services\PromptPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * แปลง Model คำสั่งซื้อเป็น Array มาตรฐานสำหรับส่งให้แอป
     */
    private function formatOrderResponse(Order $order): array
    {
        $latestPayment = $order->relationLoaded('latestPayment') ? $order->latestPayment : $order->latestPayment()->first();
        $rejectReason = ($latestPayment && $latestPayment->status === 'rejected') ? $latestPayment->reject_reason : null;

        return [
            'id' => (string) $order->id,
            'user_id' => (string) $order->user_id,
            'user_email' => $order->user ? $order->user->email : '',
            'total_price' => (float) $order->total_price,
            'status' => $order->status,
            'status_thai' => $order->status_thai,
            'payment_status' => $order->payment_status,
            'payment_status_thai' => $order->payment_status_thai,
            'expires_at' => $order->expires_at ? $order->expires_at->toIso8601String() : null,
            'paid_at' => $order->paid_at ? $order->paid_at->toIso8601String() : null,
            'is_expired' => $order->isExpired(),
            'reject_reason' => $rejectReason,
            'shipping_address' => $order->shipping_address,
            'created_at' => $order->created_at ? $order->created_at->toIso8601String() : null,
            'items' => $order->items->map(function ($item) {
                return [
                    'product_id' => (string) $item->product_id,
                    'product_name' => $item->product_name,
                    'price' => (float) $item->price,
                    'quantity' => (int) $item->quantity,
                    'size' => $item->size ?? '',
                    'color' => $item->color ?? '',
                    'image_url' => $item->image_url ?? '',
                ];
            }),
        ];
    }

    /**
     * ดึงประวัติคำสั่งซื้อเฉพาะของลูกค้าคนนั้นๆ
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::where('user_id', $user->id)
            ->with(['items', 'user:id,name,email', 'latestPayment'])
            ->latest()
            ->get()
            ->map(fn($order) => $this->formatOrderResponse($order));

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ], 200);
    }

    /**
     * ดึงรายละเอียดคำสั่งซื้อเดี่ยวของลูกค้า
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['items', 'user:id,name,email', 'latestPayment'])
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลคำสั่งซื้อที่ระบุ',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->formatOrderResponse($order),
        ], 200);
    }

    /**
     * สร้างคำสั่งซื้อใหม่
     * เงื่อนไข:
     * 1) เซิร์ฟเวอร์ดึงราคาจากตาราง products จริงและคำนวณ total_price เอง (ห้ามเชื่อราคาจาก request)
     * 2) ตัดสต็อกสินค้าภายใน Database Transaction พร้อม lockForUpdate
     * 3) ตั้งค่า payment_status = unpaid และ expires_at = now() + PAYMENT_HOLD_HOURS
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_address' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'shipping_address.required' => 'กรุณาระบุที่อยู่จัดส่ง',
            'items.required' => 'ไม่มีรายการสินค้าในคำสั่งซื้อ',
            'items.min' => 'ต้องมีสินค้าอย่างน้อย 1 รายการ',
            'items.*.product_id.exists' => 'ไม่พบสินค้ารหัสที่ระบุในระบบ',
            'items.*.quantity.min' => 'จำนวนสินค้าต้องอย่างน้อย 1 ชิ้น',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        try {
            $order = DB::transaction(function () use ($request, $user) {
                $calculatedTotalPrice = 0.0;
                $itemsToCreate = [];

                foreach ($request->items as $itemData) {
                    $productId = $itemData['product_id'];
                    $quantity = (int) $itemData['quantity'];

                    // ล็อกแถวข้อมูลสินค้าเพื่อตรวจสอบและตัดสต็อกอย่างปลอดภัย (Concurrency Safe)
                    $product = Product::where('id', $productId)->lockForUpdate()->first();

                    if (!$product) {
                        throw new \Exception("ไม่พบข้อมูลสินค้า ID: {$productId}");
                    }

                    // ปฏิเสธการสั่งซื้อสินค้าที่ถูกปิดการขาย/ซ่อนไว้
                    if ($product->is_active === false) {
                        throw new \Exception("สินค้า \"{$product->name}\" ถูกระงับการจำหน่าย ไม่สามารถสั่งซื้อได้");
                    }

                    if ($product->stock < $quantity) {
                        throw new \Exception("สินค้า \"{$product->name}\" มีสต็อกไม่เพียงพอ (คงเหลือ {$product->stock} ชิ้น)");
                    }

                    // ตัดสต็อกสินค้าจริง
                    $product->decrement('stock', $quantity);

                    // คำนวณราคาจริงจากตารางสินค้า
                    $unitPrice = (float) $product->price;
                    $lineTotal = $unitPrice * $quantity;
                    $calculatedTotalPrice += $lineTotal;

                    $itemsToCreate[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'price' => $unitPrice,
                        'quantity' => $quantity,
                        'size' => $itemData['size'] ?? '',
                        'color' => $itemData['color'] ?? '',
                        'image_url' => $product->getRawOriginal('image_url') ?? $product->image_url,
                    ];
                }

                // กำหนดเวลาหมดอายุคำสั่งซื้อจาก config/payment.php (ค่าเริ่มต้น 1440 นาที)
                $holdMinutes = (int) config('payment.hold_minutes', 1440);
                $expiresAt = now()->addMinutes($holdMinutes);

                // สร้างหัวบิลคำสั่งซื้อด้วยยอดเงินที่คำนวณจากระบบ
                $createdOrder = Order::create([
                    'user_id' => $user->id,
                    'total_price' => $calculatedTotalPrice,
                    'shipping_address' => trim($request->shipping_address),
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'expires_at' => $expiresAt,
                    'paid_at' => null,
                ]);

                // บันทึกรายการสินค้าในคำสั่งซื้อ
                foreach ($itemsToCreate as $item) {
                    $createdOrder->items()->create($item);
                }

                return $createdOrder;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'บันทึกคำสั่งซื้อสำเร็จ กรุณาชำระเงินตามเวลาที่กำหนด',
                'data' => $this->formatOrderResponse($order),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ดึงข้อมูลการชำระเงิน (PromptPay QR Payload และข้อมูลบัญชีธนาคาร)
     * เฉพาะเจ้าของออเดอร์เท่านั้น
     */
    public function getPaymentInfo(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->with('latestPayment')
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลคำสั่งซื้อที่ระบุ',
            ], 404);
        }

        // ดึงค่าจาก config/payment.php
        $promptpayId = config('payment.promptpay_id');
        $payeeName = config('payment.payee_name', '');
        $bankName = config('payment.bank_name', '');
        $bankAccountNo = config('payment.bank_account_no', '');

        // หากร้านค้ายังไม่ได้ตั้งค่า PromptPay หรือเป็น placeholder ให้แจ้ง error ภาษาไทยทันที (ไม่ส่ง QR ปลอม)
        if (!PromptPayService::isValidPromptPayId($promptpayId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'ทางร้านยังไม่ได้ตั้งค่าการรับชำระเงิน กรุณาติดต่อผู้ดูแลร้านค้า',
            ], 503);
        }

        try {
            // สร้าง QR Payload สำหรับยอดเงินจริงของออเดอร์นี้
            $qrPayload = PromptPayService::generatePayload($promptpayId, (float) $order->total_price);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่สามารถสร้างรหัส PromptPay QR ได้: ' . $e->getMessage(),
            ], 500);
        }

        $latestPayment = $order->latestPayment;
        $rejectReason = ($latestPayment && $latestPayment->status === 'rejected') ? $latestPayment->reject_reason : null;

        // คำนวณจำนวนวินาทีที่เหลือก่อนหมดอายุตามเวลาเซิร์ฟเวอร์
        $secondsRemaining = ($order->expires_at && !$order->isExpired())
            ? max(0, (int) now()->diffInSeconds($order->expires_at, false))
            : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'order_id' => (string) $order->id,
                'total_price' => (float) $order->total_price,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'expires_at' => $order->expires_at ? $order->expires_at->toIso8601String() : null,
                'seconds_remaining' => $secondsRemaining,
                'is_expired' => $order->isExpired(),
                'reject_reason' => $rejectReason,
                'promptpay' => [
                    'id' => $promptpayId,
                    'payee_name' => $payeeName,
                    'qr_payload' => $qrPayload,
                ],
                'bank_transfer' => [
                    'bank_name' => $bankName,
                    'account_no' => $bankAccountNo,
                    'account_name' => $payeeName,
                ],
            ],
        ], 200);
    }

    /**
     * แนบสลิปหลักฐานการชำระเงิน (Multipart)
     * มีการล็อกแถวข้อมูลด้วย lockForUpdate, ตรวจสอบเนื้อไฟล์จริง, ตรวจสอบ SHA256 ป้องกันสลิปซ้ำ
     * และจัดเก็บใน Private Storage ('slips')
     */
    public function uploadSlip(Request $request, $id)
    {
        $user = $request->user();

        // ตรวจสอบไฟล์ในเบื้องต้น
        $file = $request->file('slip_file') ?? $request->file('slip');
        if (!$file || !$file->isValid()) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาแนบไฟล์รูปภาพสลิปหลักฐานการโอนเงิน',
            ], 422);
        }

        // ตรวจสอบขนาดไฟล์ไม่เกิน 5MB (5,242,880 bytes)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return response()->json([
                'status' => 'error',
                'message' => 'ขนาดไฟล์สลิปต้องไม่เกิน 5MB',
            ], 422);
        }

        // ตรวจสอบชนิดไฟล์จากเนื้อไฟล์จริง (MIME Header Detection)
        $realPath = $file->getRealPath();
        $imageInfo = @getimagesize($realPath);
        if (!$imageInfo) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไฟล์ที่แนบไม่ใช่ไฟล์รูปภาพที่ถูกต้อง',
            ], 422);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($imageInfo['mime'], $allowedMimes, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'รองรับเฉพาะไฟล์รูปภาพประเภท JPG, PNG หรือ WebP เท่านั้น',
            ], 422);
        }

        // คำนวณ SHA-256 Checksum ของไฟล์สลิป
        $slipHash = hash_file('sha256', $realPath);

        // ตรวจสอบความซ้ำซ้อนของสลิปในทั้งระบบ (ออเดอร์ไหนก็ตาม)
        $duplicateSlip = Payment::where('slip_hash', $slipHash)->first();
        if ($duplicateSlip) {
            return response()->json([
                'status' => 'error',
                'message' => 'สลิปนี้เคยถูกส่งเข้าระบบแล้ว ไม่สามารถใช้ซ้ำได้',
            ], 422);
        }

        try {
            $payment = DB::transaction(function () use ($id, $user, $file, $imageInfo, $slipHash) {
                // ล็อกแถวคำสั่งซื้อเพื่อป้องกันคำขอส่งซ้อนกันพร้อมกัน (Concurrency Lock)
                $order = Order::where('id', $id)->where('user_id', $user->id)->lockForUpdate()->first();

                if (!$order) {
                    throw new \Exception('ไม่พบคำสั่งซื้อที่ระบุ');
                }

                if ($order->status === 'cancelled') {
                    throw new \Exception('คำสั่งซื้อนี้ถูกยกเลิกแล้ว ไม่สามารถแนบสลิปได้ กรุณาติดต่อทางร้าน');
                }

                if ($order->isExpired()) {
                    throw new \Exception('คำสั่งซื้อนี้หมดเวลาชำระเงินแล้ว ไม่สามารถแนบสลิปได้ กรุณาติดต่อทางร้าน');
                }

                if ($order->payment_status === 'paid') {
                    throw new \Exception('คำสั่งซื้อนี้ได้รับการชำระเงินเรียบร้อยแล้ว ไม่จำเป็นต้องส่งสลิปอีก');
                }

                if ($order->payment_status === 'pending_verification') {
                    throw new \Exception('สลิปของคำสั่งซื้อนี้กำลังอยู่ระหว่างรอเจ้าหน้าที่ตรวจสอบ กรุณารอสักครู่');
                }

                // สุ่มชื่อไฟล์ใหม่เพื่อความปลอดภัย
                $ext = match ($imageInfo['mime']) {
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };
                $fileName = 'slip_' . $order->id . '_' . time() . '_' . uniqid() . '.' . $ext;

                // บันทึกไฟล์ลง Private Disk ('slips') -> storage/app/slips/
                $file->storeAs('', $fileName, 'slips');

                // บันทึกข้อมูลลงตาราง payments โดยยอดเงิน amount ต้องนำมาจาก order.total_price เสมอ
                $newPayment = Payment::create([
                    'order_id' => $order->id,
                    'method' => 'promptpay',
                    'amount' => (float) $order->total_price,
                    'slip_path' => $fileName,
                    'slip_hash' => $slipHash,
                    'status' => 'pending',
                ]);

                // อัปเดตสถานะออเดอร์เป็น 'pending_verification'
                $order->update([
                    'payment_status' => 'pending_verification',
                ]);

                return $newPayment;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'แนบสลิปหลักฐานการโอนเงินเรียบร้อยแล้ว เจ้าหน้าที่จะดำเนินการตรวจสอบโดยเร็ว',
                'data' => [
                    'payment_id' => (string) $payment->id,
                    'order_id' => (string) $payment->order_id,
                    'amount' => (float) $payment->amount,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at->toIso8601String(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ลูกค้ายกเลิกคำสั่งซื้อ
     * เงื่อนไข: ยกเลิกได้เฉพาะตอน unpaid และคืนสต็อกสินค้าใน Transaction
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();

        try {
            DB::transaction(function () use ($id, $user) {
                $order = Order::where('id', $id)->where('user_id', $user->id)->lockForUpdate()->first();

                if (!$order) {
                    throw new \Exception('ไม่พบคำสั่งซื้อที่ระบุ');
                }

                if ($order->status === 'cancelled') {
                    throw new \Exception('คำสั่งซื้อนี้ถูกยกเลิกไปแล้ว');
                }

                if ($order->payment_status !== 'unpaid') {
                    throw new \Exception('สามารถยกเลิกได้เฉพาะคำสั่งซื้อที่ยังไม่ได้ชำระเงินเท่านั้น');
                }

                // คืนสต็อกสินค้าตามรายการในคำสั่งซื้อ
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                    }
                }

                // อัปเดตสถานะเป็นยกเลิก
                $order->update([
                    'status' => 'cancelled',
                ]);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'ยกเลิกคำสั่งซื้อและคืนสต็อกสินค้าเรียบร้อยแล้ว',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
