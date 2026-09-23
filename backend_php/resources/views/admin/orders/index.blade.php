@extends('layouts.admin')

@section('title', 'จัดการคำสั่งซื้อ')
@section('header', 'รายการคำสั่งซื้อ')

@section('content')
<div class="card table-card p-4">
    <!-- Filter Tabs -->
    <div class="d-flex flex-wrap gap-2 mb-4 pb-3 border-bottom align-items-center">
        <a href="{{ route('admin.orders.index', ['filter' => 'all']) }}" class="btn {{ (!request('filter') || request('filter') == 'all') ? 'btn-primary' : 'btn-outline-secondary' }} btn-sm rounded-pill px-3">
            ทั้งหมด ({{ $statusCounts['all'] }})
        </a>

        <!-- แท็บรอตรวจสลิป เด่นชัด -->
        <a href="{{ route('admin.orders.index', ['filter' => 'pending_verification']) }}" class="btn {{ request('filter') == 'pending_verification' ? 'btn-danger fw-bold' : 'btn-outline-danger' }} btn-sm rounded-pill px-3 position-relative">
            <i class="bi bi-receipt me-1"></i> รอตรวจสลิป
            <span class="badge {{ request('filter') == 'pending_verification' ? 'bg-white text-danger' : 'bg-danger text-white' }} rounded-pill ms-1">
                {{ $statusCounts['pending_verification'] }}
            </span>
        </a>

        <a href="{{ route('admin.orders.index', ['filter' => 'unpaid']) }}" class="btn {{ request('filter') == 'unpaid' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' }} btn-sm rounded-pill px-3">
            <i class="bi bi-hourglass me-1"></i> ยังไม่ชำระ ({{ $statusCounts['unpaid'] }})
        </a>

        <a href="{{ route('admin.orders.index', ['filter' => 'paid']) }}" class="btn {{ request('filter') == 'paid' ? 'btn-success fw-bold' : 'btn-outline-secondary' }} btn-sm rounded-pill px-3">
            <i class="bi bi-check2-circle me-1"></i> ชำระแล้ว ({{ $statusCounts['paid'] }})
        </a>

        <a href="{{ route('admin.orders.index', ['filter' => 'cancelled']) }}" class="btn {{ request('filter') == 'cancelled' ? 'btn-secondary text-white fw-bold' : 'btn-outline-secondary' }} btn-sm rounded-pill px-3">
            <i class="bi bi-x-circle me-1"></i> ยกเลิกแล้ว ({{ $statusCounts['cancelled'] }})
        </a>
    </div>

    @if (request('filter') == 'pending_verification')
        <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center gap-2 small">
            <i class="bi bi-info-circle-fill fs-5"></i>
            <span>กำลังแสดงรายการออเดอร์ที่รอตรวจสลิป โดย<strong>เรียงออเดอร์ที่รอนานที่สุดขึ้นก่อน</strong> เพื่อให้ตรวจสอบตามลำดับคิว</span>
        </div>
    @endif

    <!-- Orders Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 90px;">รหัส</th>
                    <th>ลูกค้า</th>
                    <th>สินค้า</th>
                    <th>ยอดรวม</th>
                    <th>การชำระเงิน</th>
                    <th>สถานะจัดส่ง</th>
                    <th>วันที่สั่งซื้อ</th>
                    <th class="text-end" style="width: 170px;">ดำเนินการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr class="{{ $order->payment_status == 'pending_verification' ? 'table-warning bg-opacity-25' : '' }}">
                        <td class="fw-bold">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="text-decoration-none">
                                #{{ $order->id }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $order->user->name ?? 'ลูกค้า' }}</div>
                            <small class="text-muted">{{ $order->user->email ?? '-' }}</small>
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $order->items->pluck('product_name')->implode(', ') }} ({{ $order->items->sum('quantity') }} ชิ้น)
                            </small>
                        </td>
                        <td class="fw-bold text-primary">
                            ฿{{ number_format($order->total_price, 2) }}
                        </td>
                        <td>
                            @if ($order->payment_status == 'paid')
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                    <i class="bi bi-check-circle-fill me-1"></i> ชำระแล้ว
                                </span>
                            @elseif ($order->payment_status == 'pending_verification')
                                <span class="badge bg-danger rounded-pill px-2 py-1 animate__animated animate__pulse">
                                    <i class="bi bi-receipt me-1"></i> รอตรวจสลิป
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-1">
                                    <i class="bi bi-clock me-1"></i> ยังไม่ชำระ
                                </span>
                            @endif
                        </td>
                        <td>
                            @if ($order->status == 'completed')
                                <span class="badge badge-completed rounded-pill px-3 py-1">
                                    <i class="bi bi-check-circle-fill me-1"></i> สำเร็จ
                                </span>
                            @elseif ($order->status == 'shipping')
                                <span class="badge badge-shipping rounded-pill px-3 py-1">
                                    <i class="bi bi-truck me-1"></i> กำลังส่ง
                                </span>
                            @elseif ($order->status == 'cancelled')
                                <span class="badge bg-secondary rounded-pill px-3 py-1 text-white">
                                    <i class="bi bi-x-circle me-1"></i> ยกเลิก
                                </span>
                            @else
                                <span class="badge badge-pending rounded-pill px-3 py-1">
                                    <i class="bi bi-hourglass-split me-1"></i> รอดำเนินการ
                                </span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '-' }}</small>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end align-items-center gap-1">
                                @if ($order->payment_status == 'pending_verification')
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-danger fw-bold" title="ตรวจสอบสลิป">
                                        <i class="bi bi-receipt me-1"></i> ตรวจสลิป
                                    </a>
                                @else
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-outline-primary" title="ดูรายละเอียด">
                                        <i class="bi bi-eye me-1"></i> รายละเอียด
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            ไม่พบรายการคำสั่งซื้อในหมวดนี้
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-end mt-4">
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
