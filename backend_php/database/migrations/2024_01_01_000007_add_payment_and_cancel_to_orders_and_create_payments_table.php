<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. ปรับปรุงตาราง orders
        if (Schema::hasTable('orders')) {
            // ปรับแก้ enum ของ status ใน MySQL ให้รองรับ 'cancelled'
            // ใช้ DB::statement เพื่อความแม่นยำและไม่ขึ้นกับ doctrine/dbal
            try {
                DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending', 'shipping', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
            } catch (\Throwable $e) {
                // Ignore if already modified or driver issue
            }

            $addedPaymentStatus = false;

            Schema::table('orders', function (Blueprint $table) use (&$addedPaymentStatus) {
                if (!Schema::hasColumn('orders', 'payment_status')) {
                    $table->enum('payment_status', ['unpaid', 'pending_verification', 'paid'])->default('unpaid')->after('status');
                    $addedPaymentStatus = true;
                }

                if (!Schema::hasColumn('orders', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable()->after('payment_status');
                }

                if (!Schema::hasColumn('orders', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable()->after('expires_at');
                }
            });

            // ออเดอร์เดิมที่มีอยู่ในระบบก่อนการมีระบบชำระเงิน ให้ถือว่า payment_status = 'paid'
            if ($addedPaymentStatus) {
                DB::table('orders')->update(['payment_status' => 'paid']);
            }
        }

        // 2. สร้างตาราง payments
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->string('method', 50)->default('promptpay');
                $table->decimal('amount', 10, 2)->default(0.00);
                $table->string('slip_path', 500);
                $table->string('slip_hash', 64)->index(); // SHA256 hash เพื่อตรวจสอบสลิปซ้ำ
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('reject_reason')->nullable();
                $table->unsignedBigInteger('verified_by')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
                $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::dropIfExists('payments');
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'paid_at')) {
                    $table->dropColumn('paid_at');
                }
                if (Schema::hasColumn('orders', 'expires_at')) {
                    $table->dropColumn('expires_at');
                }
                if (Schema::hasColumn('orders', 'payment_status')) {
                    $table->dropColumn('payment_status');
                }
            });

            try {
                DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending', 'shipping', 'completed') NOT NULL DEFAULT 'pending'");
            } catch (\Throwable $e) {
                // Ignore
            }
        }
    }
};
