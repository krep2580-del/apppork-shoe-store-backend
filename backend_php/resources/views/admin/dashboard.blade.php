@extends('layouts.admin')

@section('title', 'แดชบอร์ดสรุปยอดขาย')
@section('header', 'ภาพรวมระบบร้านค้า (Dashboard)')

@section('content')
<div class="row g-4 mb-4">
    <!-- ยอดขายรวม -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card bg-white p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">ยอดขายรวมทั้งหมด</span>
                    <h3 class="fw-bold mt-2 mb-0 text-primary">฿{{ number_format($totalRevenue, 2) }}</h3>
                </div>
                <div class="p-3 bg-primary-subtle text-primary rounded-4">
                    <i class="bi bi-currency-dollar fs-3"></i>
                </div>
            </div>
            <div class="mt-3 text-muted small">
                <span class="text-success fw-bold"><i class="bi bi-arrow-up-short"></i> ยอดขายวันนี้</span>: ฿{{ number_format($todayRevenue, 2) }}
            </div>
        </div>
    </div>

    <!-- ออเดอร์วันนี้ -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card bg-white p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">ออเดอร์ในวันนี้</span>
                    <h3 class="fw-bold mt-2 mb-0 text-success">{{ number_format($todayOrdersCount) }}</h3>
                </div>
                <div class="p-3 bg-success-subtle text-success rounded-4">
                    <i class="bi bi-cart-check fs-3"></i>
                </div>
            </div>
            <div class="mt-3 text-muted small">
                รายการสั่งซื้อเข้ามาวันนี้
            </div>
        </div>
    </div>

    <!-- จำนวนสต็อกสินค้ารวม -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card bg-white p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">สินค้าคงเหลือรวม</span>
                    <h3 class="fw-bold mt-2 mb-0 text-warning">{{ number_format($totalStock) }} <span class="fs-6 fw-normal text-muted">ชิ้น</span></h3>
                </div>
                <div class="p-3 bg-warning-subtle text-warning rounded-4">
                    <i class="bi bi-box-seam fs-3"></i>
                </div>
            </div>
            <div class="mt-3 text-muted small">
                จากสินค้าทั้งหมด {{ $totalProducts }} รายการ
            </div>
        </div>
    </div>

    <!-- จำนวนลูกค้าทั้งหมด -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card bg-white p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">สมาชิกลูกค้า</span>
                    <h3 class="fw-bold mt-2 mb-0 text-info">{{ number_format($totalCustomers) }} <span class="fs-6 fw-normal text-muted">คน</span></h3>
                </div>
                <div class="p-3 bg-info-subtle text-info rounded-4">
                    <i class="bi bi-people fs-3"></i>
                </div>
            </div>
            <div class="mt-3 text-muted small">
                บัญชีลูกค้าที่ลงทะเบียนในระบบ
            </div>
        </div>
    </div>
</div>

<!-- Sales Chart & Recent Orders -->
<div class="row g-4">
    <!-- กราฟยอดขาย 7 วันย้อนหลัง -->
    <div class="col-12 col-lg-8">
        <div class="card table-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-graph-up text-primary me-2"></i> สถิติยอดขาย 7 วันย้อนหลัง</h5>
                    <small class="text-muted">กราฟแสดงยอดขายจริงในรอบสัปดาห์</small>
                </div>
            </div>
            <div style="position: relative; height: 320px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ออเดอร์ล่าสุด -->
    <div class="col-12 col-lg-4">
        <div class="card table-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-clock-history text-secondary me-2"></i> ออเดอร์ล่าสุด</h5>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-link text-decoration-none">ดูทั้งหมด</a>
            </div>

            @if ($recentOrders->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    ยังไม่มีรายการคำสั่งซื้อ
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($recentOrders as $order)
                        <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center border-bottom">
                            <div>
                                <div class="fw-semibold">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="text-decoration-none text-dark">
                                        #{{ $order->id }} - {{ $order->user->name ?? 'ลูกค้า' }}
                                    </a>
                                </div>
                                <small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-primary">฿{{ number_format($order->total_price, 2) }}</div>
                                @if ($order->status == 'completed')
                                    <span class="badge badge-completed rounded-pill px-2 py-1">สำเร็จ</span>
                                @elseif ($order->status == 'shipping')
                                    <span class="badge badge-shipping rounded-pill px-2 py-1">กำลังส่ง</span>
                                @else
                                    <span class="badge badge-pending rounded-pill px-2 py-1">รอดำเนินการ</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(30, 58, 138, 0.25)');
    gradient.addColorStop(1, 'rgba(30, 58, 138, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'ยอดขาย (บาท)',
                data: @json($chartValues),
                borderColor: '#1E3A8A',
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.35,
                fill: true,
                pointBackgroundColor: '#1E3A8A',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ยอดขาย: ฿' + Number(context.raw).toLocaleString('th-TH');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '฿' + Number(value).toLocaleString();
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>
@endsection
