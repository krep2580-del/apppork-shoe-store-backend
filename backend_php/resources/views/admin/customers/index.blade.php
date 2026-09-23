@extends('layouts.admin')

@section('title', 'รายชื่อลูกค้า')
@section('header', 'ข้อมูลสมาชิกลูกค้า')

@section('content')
<div class="card table-card p-4">
    <!-- Search Bar -->
    <div class="row g-3 justify-content-between align-items-center mb-4">
        <div class="col-12 col-md-6">
            <form action="{{ route('admin.customers.index') }}" method="GET">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาชื่อ หรืออีเมลลูกค้า..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-secondary">ค้นหา</button>
                </div>
            </form>
        </div>
        <div class="col-12 col-md-6 text-md-end text-muted small">
            พบสมาชิกลูกค้าทั้งหมด {{ $customers->total() }} คน
        </div>
    </div>

    <!-- Customers Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 80px;">รหัส</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>อีเมล</th>
                    <th class="text-center">จำนวนออเดอร์</th>
                    <th>วันที่ลงทะเบียน</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td class="fw-bold text-muted">#{{ $customer->id }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                    {{ mb_substr($customer->name, 0, 1) }}
                                </div>
                                <div class="fw-bold text-dark">{{ $customer->name }}</div>
                            </div>
                        </td>
                        <td>{{ $customer->email }}</td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-3 py-2">
                                <i class="bi bi-bag-check me-1 text-primary"></i> {{ $customer->orders_count }} ออเดอร์
                            </span>
                        </td>
                        <td>
                            <small class="text-muted">{{ $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : '-' }}</small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-2"></i>
                            ไม่พบรายชื่อลูกค้า
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4 d-flex justify-content-between align-items-center">
        <small class="text-muted">แสดงทั้งหมด {{ $customers->total() }} รายการ</small>
        {{ $customers->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
