@extends('layouts.admin')

@section('title', 'รายละเอียดออเดอร์ #' . $order->id)
@section('header', 'รายละเอียดคำสั่งซื้อ #' . $order->id)

@section('content')
<!-- กล่องแจ้งเตือนผลลัพธ์ -->
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-4">
    <!-- ส่วนซ้าย: รายการสินค้า & ประวัติการชำระเงิน -->
    <div class="col-12 col-lg-8">
        <!-- 1. รายการสินค้าในออเดอร์ -->
        <div class="card table-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2"></i> รายการสินค้าที่สั่งซื้อ</h5>
                <span class="text-muted small">รวม {{ $order->items->sum('quantity') }} ชิ้น</span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 64px;">รูปภาพ</th>
                            <th>ชื่อสินค้า</th>
                            <th>ตัวเลือก (ไซส์/สี)</th>
                            <th class="text-center">จำนวน</th>
                            <th class="text-end">ราคาต่อหน่วย</th>
                            <th class="text-end">ราคารวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>
                                    <img src="{{ $item->image_url }}" alt="{{ $item->product_name }}" class="rounded-3 object-fit-cover shadow-sm" width="50" height="50" onerror="this.src='https://placehold.co/100x100?text=Shoe'">
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $item->product_name }}</div>
                                    <small class="text-muted">รหัสสินค้า: #{{ $item->product_id ?? '-' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">ไซส์: {{ $item->size ?: '-' }}</span>
                                    <span class="badge bg-light text-dark border">สี: {{ $item->color ?: '-' }}</span>
                                </td>
                                <td class="text-center fw-semibold">
                                    {{ $item->quantity }}
                                </td>
                                <td class="text-end">
                                    ฿{{ number_format($item->price, 2) }}
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    ฿{{ number_format($item->price * $item->quantity, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="5" class="text-end fw-bold fs-6">ยอดสุทธิทั้งสิ้น:</td>
                            <td class="text-end fw-bold fs-5 text-primary">฿{{ number_format($order->total_price, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- 2. ส่วนตรวจสอบสลิปหลักฐานการโอนเงิน (ถ้ามีสลิป) -->
        @php
            $latestPayment = $order->payments->first();
        @endphp

        @if ($latestPayment)
            <div class="card table-card p-4 mb-4 border-2 {{ $latestPayment->status == 'pending' ? 'border-warning' : ($latestPayment->status == 'approved' ? 'border-success' : 'border-danger') }}">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-receipt me-2"></i> ตรวจสอบสลิปหลักฐานการโอน (ล่าสุด)
                    </h5>
                    @if ($latestPayment->status == 'pending')
                        <span class="badge bg-warning text-dark px-3 py-2 fs-6 fw-bold">
                            <i class="bi bi-hourglass-split me-1"></i> รอตรวจสอบสลิป
                        </span>
                    @elseif ($latestPayment->status == 'approved')
                        <span class="badge bg-success px-3 py-2 fs-6 fw-bold">
                            <i class="bi bi-check-circle-fill me-1"></i> อนุมัติแล้ว
                        </span>
                    @else
                        <span class="badge bg-danger px-3 py-2 fs-6 fw-bold">
                            <i class="bi bi-x-circle-fill me-1"></i> ปฏิเสธสลิปแล้ว
                        </span>
                    @endif
                </div>

                <div class="row g-4 align-items-center">
                    <!-- รูปสลิป (ดึงผ่าน Private Disk 'slips' โดยแอดมินเท่านั้น) -->
                    <div class="col-12 col-md-5 text-center">
                        <div class="position-relative border rounded-3 p-2 bg-light shadow-sm d-inline-block">
                            <img src="{{ route('admin.payments.slip', $latestPayment->id) }}" alt="สลิปการโอนเงิน" class="img-fluid rounded-2" style="max-height: 380px; object-fit: contain;">
                            <div class="mt-2">
                                <a href="{{ route('admin.payments.slip', $latestPayment->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-arrows-fullscreen me-1"></i> เปิดดูรูปภาพขนาดเต็ม
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ข้อมูลสำคัญประกอบการตรวจสลิป (แสดงยอดและเลขออเดอร์ให้เด่น) -->
                    <div class="col-12 col-md-7">
                        <div class="bg-light rounded-3 p-3 mb-3 border">
                            <div class="text-muted small">เลขออเดอร์ที่ต้องตรวจสอบ:</div>
                            <div class="fs-4 fw-bold text-dark">#{{ $order->id }}</div>

                            <div class="text-muted small mt-2">ยอดเงินที่ต้องได้รับจริง (จากระบบ):</div>
                            <div class="fs-2 fw-bold text-success">
                                ฿{{ number_format($order->total_price, 2) }}
                            </div>

                            <hr class="my-2">

                            <div class="row g-2 small text-muted">
                                <div class="col-6">
                                    <span>เวลาที่ส่งสลิป:</span>
                                    <strong class="d-block text-dark">{{ $latestPayment->created_at->format('d/m/Y H:i:s') }}</strong>
                                </div>
                                <div class="col-6">
                                    <span>ช่องทางชำระเงิน:</span>
                                    <strong class="d-block text-dark">{{ strtoupper($latestPayment->method) }}</strong>
                                </div>
                                <div class="col-12 mt-1">
                                    <span>SHA-256 Hash ของสลิป:</span>
                                    <code class="d-block text-break" style="font-size: 0.75rem;">{{ $latestPayment->slip_hash }}</code>
                                </div>
                            </div>
                        </div>

                        <!-- ผลการตรวจสอบก่อนหน้า (กรณีอนุมัติหรือปฏิเสธไปแล้ว) -->
                        @if ($latestPayment->status == 'approved')
                            <div class="alert alert-success d-flex align-items-center gap-2 mb-0">
                                <i class="bi bi-check-circle-fill fs-4"></i>
                                <div>
                                    <strong>อนุมัติการชำระเงินแล้ว</strong>
                                    <div class="small">ผู้ตรวจสอบ: {{ $latestPayment->verifier->name ?? 'แอดมิน' }} ({{ $latestPayment->verified_at ? $latestPayment->verified_at->format('d/m/Y H:i:s') : '-' }})</div>
                                </div>
                            </div>
                        @elseif ($latestPayment->status == 'rejected')
                            <div class="alert alert-danger d-flex align-items-start gap-2 mb-0">
                                <i class="bi bi-x-circle-fill fs-4 mt-1"></i>
                                <div>
                                    <strong>สลิปนี้ถูกปฏิเสธ</strong>
                                    <div class="small mt-1">เหตุผล: <em>{{ $latestPayment->reject_reason }}</em></div>
                                    <div class="small text-muted mt-1">ผู้ตรวจสอบ: {{ $latestPayment->verifier->name ?? 'แอดมิน' }} ({{ $latestPayment->verified_at ? $latestPayment->verified_at->format('d/m/Y H:i:s') : '-' }})</div>
                                </div>
                            </div>
                        @endif

                        <!-- ฟอร์มการอนุมัติ / ปฏิเสธ (สำหรับสลิปที่สถานะ pending เท่านั้น) -->
                        @if ($latestPayment->status == 'pending' && $order->payment_status == 'pending_verification')
                            <div class="border-top pt-3 mt-3">
                                <!-- ฟอร์มอนุมัติสลิป -->
                                <form action="{{ route('admin.payments.approve', $latestPayment->id) }}" method="POST" class="mb-3">
                                    @csrf
                                    <!-- บังคับติ๊กช่องยืนยันยอดเงินตรง -->
                                    <div class="form-check mb-3 p-3 bg-white rounded-3 border border-success">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" name="confirm_verified" id="confirmVerified" value="1" required>
                                        <label class="form-check-label fw-bold text-success" for="confirmVerified">
                                            <i class="bi bi-shield-check me-1"></i> ตรวจยอดเงินเข้าบัญชีตรงกับออเดอร์แล้ว (฿{{ number_format($order->total_price, 2) }})
                                        </label>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-success fw-bold px-4 py-2 flex-grow-1" onclick="return confirm('ยืนยันว่าได้รับเงินยอด ฿{{ number_format($order->total_price, 2) }} เข้าบัญชีเรียบร้อยแล้ว?')">
                                            <i class="bi bi-check2-circle me-1"></i> ยืนยันการชำระเงิน (อนุมัติสลิป)
                                        </button>
                                        
                                        <!-- ปุ่มเปิด Modal ปฏิเสธสลิป -->
                                        <button type="button" class="btn btn-outline-danger px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                            <i class="bi bi-x-circle me-1"></i> ปฏิเสธสลิป
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <!-- กรณีไม่มีสลิปการโอน -->
            <div class="card table-card p-4 mb-4 text-center text-muted">
                <i class="bi bi-receipt-cutoff fs-1 d-block mb-2"></i>
                <h6>ยังไม่มีการแนบสลิปหลักฐานการโอนเงินสำหรับออเดอร์นี้</h6>
                <small>สถานะการชำระเงินปัจจุบัน: {{ $order->payment_status_thai }}</small>
            </div>
        @endif

        <!-- 3. ประวัติการแนบสลิปทั้งหมดของออเดอร์นี้ -->
        @if ($order->payments->count() > 1)
            <div class="card table-card p-4 mb-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i> ประวัติสลิปทั้งหมดในออเดอร์นี้ ({{ $order->payments->count() }} รายการ)</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ลำดับ</th>
                                <th>เวลาที่ส่ง</th>
                                <th>สถานะ</th>
                                <th>เหตุผล (กรณีปฏิเสธ)</th>
                                <th>ผู้ตรวจ</th>
                                <th class="text-end">สลิป</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->payments as $index => $historyPayment)
                                <tr>
                                    <td>#{{ $historyPayment->id }}</td>
                                    <td><small>{{ $historyPayment->created_at->format('d/m/Y H:i') }}</small></td>
                                    <td>
                                        @if ($historyPayment->status == 'approved')
                                            <span class="badge bg-success">อนุมัติแล้ว</span>
                                        @elseif ($historyPayment->status == 'rejected')
                                            <span class="badge bg-danger">ปฏิเสธ</span>
                                        @else
                                            <span class="badge bg-warning text-dark">รอตรวจ</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $historyPayment->reject_reason ?: '-' }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $historyPayment->verifier->name ?? '-' }}</small>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.payments.slip', $historyPayment->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2">
                                            ดูสลิป
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="mb-4">
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> ย้อนกลับไปรายการออเดอร์
            </a>
        </div>
    </div>

    <!-- ส่วนขวา: ข้อมูลลูกค้า & สถานะจัดส่ง -->
    <div class="col-12 col-lg-4">
        <!-- 1. สถานะการชำระเงิน -->
        <div class="card table-card p-4 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-cash-coin me-2"></i> ข้อมูลการชำระเงิน</h6>

            <div class="mb-3">
                <small class="text-muted d-block">สถานะชำระเงิน</small>
                <div>
                    @if ($order->payment_status == 'paid')
                        <span class="badge bg-success fs-6 px-3 py-1">
                            <i class="bi bi-check-circle-fill me-1"></i> ชำระเงินแล้ว
                        </span>
                    @elseif ($order->payment_status == 'pending_verification')
                        <span class="badge bg-danger fs-6 px-3 py-1">
                            <i class="bi bi-receipt me-1"></i> รอตรวจสลิป
                        </span>
                    @else
                        <span class="badge bg-warning text-dark fs-6 px-3 py-1">
                            <i class="bi bi-clock me-1"></i> ยังไม่ชำระเงิน
                        </span>
                    @endif
                </div>
            </div>

            <div class="mb-2">
                <small class="text-muted d-block">เวลาหมดอายุการชำระเงิน</small>
                <div class="small {{ $order->isExpired() ? 'text-danger fw-bold' : 'text-dark' }}">
                    {{ $order->expires_at ? $order->expires_at->format('d/m/Y H:i:s') : 'ไม่จำกัด' }}
                    @if ($order->isExpired())
                        <span class="badge bg-danger ms-1">หมดเวลาแล้ว</span>
                    @endif
                </div>
            </div>

            @if ($order->paid_at)
                <div class="mb-2">
                    <small class="text-muted d-block">เวลาที่ชำระเงินสำเร็จ</small>
                    <div class="small text-success fw-bold">
                        {{ $order->paid_at->format('d/m/Y H:i:s') }}
                    </div>
                </div>
            @endif
        </div>

        <!-- 2. จัดการขั้นตอนจัดส่ง -->
        <div class="card table-card p-4 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-truck me-2"></i> สถานะการจัดส่งสินค้า</h6>
            
            <form action="{{ route('admin.orders.status', $order->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small text-muted">เปลี่ยนขั้นตอนคำสั่งซื้อ:</label>
                    <select name="status" class="form-select" {{ $order->status == 'cancelled' ? 'disabled' : '' }}>
                        <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>⏳ รอดำเนินการ (Pending)</option>
                        
                        <!-- ป้องกันห้ามเปลี่ยนเป็น shipping หรือ completed ถ้ายังไม่ได้ชำระเงิน -->
                        <option value="shipping" {{ $order->status == 'shipping' ? 'selected' : '' }} {{ $order->payment_status != 'paid' ? 'disabled' : '' }}>
                            🚚 กำลังจัดส่ง (Shipping) {{ $order->payment_status != 'paid' ? '[ต้องชำระเงินก่อน]' : '' }}
                        </option>
                        <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }} {{ $order->payment_status != 'paid' ? 'disabled' : '' }}>
                            ✅ สำเร็จเรียบร้อย (Completed) {{ $order->payment_status != 'paid' ? '[ต้องชำระเงินก่อน]' : '' }}
                        </option>
                        <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>
                            ❌ ยกเลิกคำสั่งซื้อ (Cancelled)
                        </option>
                    </select>

                    @if ($order->payment_status != 'paid' && $order->status != 'cancelled')
                        <div class="form-text text-danger small mt-1">
                            <i class="bi bi-info-circle me-1"></i> เซิร์ฟเวอร์บังคับ: ออเดอร์ต้องได้รับการอนุมัติการชำระเงิน (Paid) ก่อน จึงจะสามารถเปลี่ยนเป็นจัดส่งหรือสำเร็จได้
                        </div>
                    @endif
                </div>

                @if ($order->status != 'cancelled')
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-arrow-repeat me-1"></i> อัปเดตสถานะจัดส่ง
                    </button>
                @else
                    <div class="alert alert-secondary py-2 px-3 small mb-0 text-center">
                        คำสั่งซื้อนี้ถูกยกเลิกแล้ว
                    </div>
                @endif
            </form>
        </div>

        <!-- 3. ข้อมูลลูกค้าและที่อยู่จัดส่ง -->
        <div class="card table-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-person me-2"></i> ข้อมูลลูกค้า & ที่อยู่จัดส่ง</h6>
            
            <div class="mb-3">
                <small class="text-muted d-block">ชื่อลูกค้า</small>
                <div class="fw-bold text-dark">{{ $order->user->name ?? 'ไม่ระบุชื่อ' }}</div>
            </div>

            <div class="mb-3">
                <small class="text-muted d-block">อีเมลติดต่อ</small>
                <div>{{ $order->user->email ?? '-' }}</div>
            </div>

            <div class="mb-3">
                <small class="text-muted d-block">วันที่ทำรายการสั่งซื้อ</small>
                <div>{{ $order->created_at ? $order->created_at->format('d/m/Y H:i:s') : '-' }}</div>
            </div>

            <div class="pt-3 border-top">
                <small class="text-muted d-block mb-1"><i class="bi bi-geo-alt text-danger me-1"></i> ที่อยู่สำหรับจัดส่งสินค้า</small>
                <div class="p-3 bg-light rounded-3 text-dark small text-break">
                    {{ $order->shipping_address ?: 'ไม่มีข้อมูลที่อยู่' }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal ปฏิเสธสลิป -->
@if ($latestPayment && $latestPayment->status == 'pending')
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.payments.reject', $latestPayment->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel"><i class="bi bi-exclamation-triangle me-2"></i> ปฏิเสธสลิปการโอนเงิน</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        เมื่อปฏิเสธสลิป สถานะออเดอร์จะถูกปรับกลับเป็น <strong>"ยังไม่ชำระเงิน"</strong> และระบบจะ<strong>ขยายเวลาหมดอายุให้อีก {{ round(config('payment.retry_minutes', 720) / 60, 1) }} ชั่วโมง ({{ config('payment.retry_minutes', 720) }} นาที)</strong> เพื่อให้ลูกค้าสามารถถ่ายสลิปใหม่และส่งเข้ามาตรวจสอบได้
                    </p>

                    <div class="mb-3">
                        <label for="rejectReason" class="form-label fw-bold">ระบุเหตุผลในการปฏิเสธสลิป <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectReason" name="reject_reason" rows="3" required maxlength="500" placeholder="เช่น ยอดเงินในสลิปไม่ตรงกับยอดคำสั่งซื้อ, วันเวลาในสลิปไม่ถูกต้อง, ภาพสลิปเบลอหรือไม่ชัดเจน..."></textarea>
                        <div class="form-text small">ข้อความนี้จะแสดงให้ลูกค้าเห็นในหน้าประวัติการสั่งซื้อบนแอป (จำกัดไม่เกิน 500 ตัวอักษร)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger fw-bold">
                        <i class="bi bi-x-circle me-1"></i> ยืนยันปฏิเสธสลิป
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
