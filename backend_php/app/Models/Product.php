<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category',
        'stock',
        'sizes',
        'colors',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'stock' => 'integer',
        'sizes' => 'array',
        'colors' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * คืนค่า image_url เป็น Full URL
     * หากเป็น relative path จะสร้าง URL เต็มจาก host ของ request ที่เข้ามาโดยอัตโนมัติ
     * (ทำให้ emulator 10.0.2.2, มือถือจริงผ่าน LAN IP, และ localhost เห็นรูปได้ถูกต้อง)
     */
    public function getImageUrlAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // หากเป็น URL ภายนอกอยู่แล้ว (เช่น Unsplash) คืนค่าเดิม
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        // ใช้ host จาก request ที่เข้ามาจริง หรือ fallback ไปที่ config app.url
        $requestRoot = request() ? request()->root() : null;
        $baseUrl = !empty($requestRoot) ? rtrim($requestRoot, '/') : rtrim(config('app.url', 'http://localhost:8000'), '/');

        $cleanPath = ltrim($value, '/');
        if (!str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = 'storage/' . $cleanPath;
        }

        return $baseUrl . '/' . $cleanPath;
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
