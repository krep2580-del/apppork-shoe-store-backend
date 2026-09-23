<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'method',
        'amount',
        'slip_path',
        'slip_hash',
        'status',
        'reject_reason',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'verified_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getStatusThaiAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'อนุมัติแล้ว',
            'rejected' => 'ปฏิเสธ',
            default => 'รอตรวจสอบ',
        };
    }
}
