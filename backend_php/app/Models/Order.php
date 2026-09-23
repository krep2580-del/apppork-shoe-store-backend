<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_price',
        'shipping_address',
        'status',
        'payment_status',
        'expires_at',
        'paid_at',
    ];

    protected $casts = [
        'total_price' => 'float',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function getStatusThaiAttribute(): string
    {
        return match ($this->status) {
            'shipping' => 'กำลังจัดส่ง',
            'completed' => 'สำเร็จ',
            'cancelled' => 'ยกเลิกแล้ว',
            default => 'รอดำเนินการ',
        };
    }

    public function getPaymentStatusThaiAttribute(): string
    {
        return match ($this->payment_status) {
            'pending_verification' => 'รอตรวจสอบสลิป',
            'paid' => 'ชำระแล้ว',
            default => 'ยังไม่ชำระ',
        };
    }

    public function isExpired(): bool
    {
        return $this->payment_status === 'unpaid' && $this->expires_at && $this->expires_at->isPast();
    }
}
