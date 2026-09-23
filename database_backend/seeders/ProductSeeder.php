<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'id' => 1,
                'name' => 'Nike Air Max 270',
                'description' => 'รองเท้าผ้าใบระดับพรีเมียม สวมใส่สบายด้วยเทคโนโลยี Air Max รองรับแรงกระแทกได้อย่างยอดเยี่ยม',
                'price' => 4900.00,
                'category' => 'รองเท้าผ้าใบ',
                'stock' => 15,
                'sizes' => json_encode(['39', '40', '41', '42', '43', '44']),
                'colors' => json_encode(['ดำ', 'ขาว', 'แดง']),
                'image_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Adidas Ultraboost Light',
                'description' => 'รองเท้าวิ่งน้ำหนักเบา ให้ความยืดหยุ่นและการคืนพลังงานสูงสุด เหมาะสำหรับการวิ่งและออกกำลังกาย',
                'price' => 5200.00,
                'category' => 'รองเท้ากีฬา',
                'stock' => 20,
                'sizes' => json_encode(['38', '39', '40', '41', '42']),
                'colors' => json_encode(['ดำ', 'ขาว', 'น้ำเงิน']),
                'image_url' => 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=600',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Clarks Classic Leather Oxford',
                'description' => 'รองเท้าหนังแท้ทรง Oxford ดีไซน์คลาสสิก เรียบหรู เหมาะสำหรับใส่ทำงานและงานเป็นทางการ',
                'price' => 3800.00,
                'category' => 'รองเท้าหนัง',
                'stock' => 8,
                'sizes' => json_encode(['40', '41', '42', '43']),
                'colors' => json_encode(['น้ำตาล', 'ดำ']),
                'image_url' => 'https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?w=600',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Birkenstock Comfort Sandal',
                'description' => 'รองเท้าแตะเพื่อสุขภาพ พื้นรองเท้าออกแบบตามสรีระเท้า สวมใส่สบายตลอดทั้งวัน',
                'price' => 2400.00,
                'category' => 'รองเท้าแตะ',
                'stock' => 25,
                'sizes' => json_encode(['37', '38', '39', '40', '41', '42']),
                'colors' => json_encode(['น้ำตาล', 'ดำ', 'เบจ']),
                'image_url' => 'https://images.unsplash.com/photo-1603808033192-082d6919d3e1?w=600',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => 'Puma RS-X Triple White',
                'description' => 'รองเท้าสตรีทสไตล์ทรง Chunky ดีไซน์โดดเด่น แมตช์ได้กับทุกชุด',
                'price' => 3600.00,
                'category' => 'รองเท้าผ้าใบ',
                'stock' => 12,
                'sizes' => json_encode(['39', '40', '41', '42', '43']),
                'colors' => json_encode(['ขาว']),
                'image_url' => 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=600',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(['id' => $product['id']], $product);
        }
    }
}
