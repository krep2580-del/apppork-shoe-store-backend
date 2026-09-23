<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [
            [
                'id' => 1,
                'user_id' => 2,
                'total_price' => 4900.00,
                'shipping_address' => '123/45 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110',
                'status' => 'completed',
                'created_at' => now()->subDays(2),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'total_price' => 5200.00,
                'shipping_address' => '123/45 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110',
                'status' => 'shipping',
                'created_at' => now()->subDays(1),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'user_id' => 2,
                'total_price' => 3600.00,
                'shipping_address' => '123/45 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($orders as $order) {
            DB::table('orders')->updateOrInsert(['id' => $order['id']], $order);
        }

        $items = [
            [
                'id' => 1,
                'order_id' => 1,
                'product_id' => 1,
                'product_name' => 'Nike Air Max 270',
                'price' => 4900.00,
                'quantity' => 1,
                'size' => '42',
                'color' => 'ดำ',
                'image_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600',
                'created_at' => now()->subDays(2),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'order_id' => 2,
                'product_id' => 2,
                'product_name' => 'Adidas Ultraboost Light',
                'price' => 5200.00,
                'quantity' => 1,
                'size' => '40',
                'color' => 'ขาว',
                'image_url' => 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=600',
                'created_at' => now()->subDays(1),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'order_id' => 3,
                'product_id' => 5,
                'product_name' => 'Puma RS-X Triple White',
                'price' => 3600.00,
                'quantity' => 1,
                'size' => '41',
                'color' => 'ขาว',
                'image_url' => 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=600',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($items as $item) {
            DB::table('order_items')->updateOrInsert(['id' => $item['id']], $item);
        }
    }
}
