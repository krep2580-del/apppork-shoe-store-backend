<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ยกเลิกคำสั่งซื้อที่เลยกำหนดเวลาชำระเงิน (unpaid) และคืนสต็อกสินค้า';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('กำลังตรวจสอบคำสั่งซื้อที่หมดอายุการชำระเงิน...');

        // ค้นหาออเดอร์ที่ยังไม่ชำระ และเลย expires_at แล้ว
        $expiredOrders = Order::where('payment_status', 'unpaid')
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('ไม่พบคำสั่งซื้อที่หมดอายุ');
            return 0;
        }

        $cancelledCount = 0;

        foreach ($expiredOrders as $candidate) {
            DB::transaction(function () use ($candidate, &$cancelledCount) {
                // ใช้ lockForUpdate และตรวจสอบสถานะซ้ำใน transaction เพื่อป้องกันการคืนสต็อกซ้ำ (Race Condition)
                $order = Order::where('id', $candidate->id)->lockForUpdate()->first();

                if (!$order) {
                    return;
                }

                // ตรวจสอบสถานะซ้ำอย่างเคร่งครัด
                if ($order->status === 'cancelled' || $order->payment_status !== 'unpaid') {
                    return;
                }

                // คืนสต็อกสินค้าตามรายการในคำสั่งซื้อ
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                    }
                }

                // อัปเดตสถานะออเดอร์เป็นยกเลิก
                $order->update([
                    'status' => 'cancelled',
                ]);

                $cancelledCount++;
                Log::info("ออเดอร์ #{$order->id} หมดอายุการชำระเงิน: ยกเลิกและคืนสต็อกเรียบร้อยแล้ว");
            });
        }

        $this->info("ดำเนินการยกเลิกคำสั่งซื้อที่หมดอายุและคืนสต็อกสำเร็จจำนวน {$cancelledCount} รายการ");
        return 0;
    }
}
