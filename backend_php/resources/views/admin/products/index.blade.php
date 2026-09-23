@extends('layouts.admin')

@section('title', 'จัดการรายการสินค้า')
@section('header', 'รายการสินค้าทั้งหมด')

@section('content')
<div class="card table-card p-4">
    <!-- Status Filter Tabs -->
    <div class="d-flex flex-wrap gap-2 mb-4 pb-3 border-bottom">
        <a href="{{ route('admin.products.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}" class="btn {{ (!request('status') || request('status') == 'all') ? 'btn-primary' : 'btn-outline-secondary' }} btn-sm rounded-pill px-3">
            ทั้งหมด ({{ $statusCounts['all'] }})
        </a>
        <a href="{{ route('admin.products.index', array_merge(request()->except('status', 'page'), ['status' => 'active'])) }}" class="btn {{ request('status') == 'active' ? 'btn-success fw-bold' : 'btn-outline-secondary' }} btn-sm rounded-pill px-3">
            <i class="bi bi-check-circle me-1"></i> เปิดขายอยู่ ({{ $statusCounts['active'] }})
        </a>
        <a href="{{ route('admin.products.index', array_merge(request()->except('status', 'page'), ['status' => 'hidden'])) }}" class="btn {{ request('status') == 'hidden' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' }} btn-sm rounded-pill px-3">
            <i class="bi bi-eye-slash me-1"></i> ซ่อนอยู่ ({{ $statusCounts['hidden'] }})
        </a>
    </div>

    <!-- Header Controls: Search, Filter, Add button -->
    <div class="row g-3 justify-content-between align-items-center mb-4">
        <div class="col-12 col-md-8">
            <form action="{{ route('admin.products.index') }}" method="GET" class="row g-2">
                @if (request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="col-12 col-sm-6 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="ค้นหาชื่อสินค้า..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-8 col-sm-4 col-md-4">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 col-sm-2 col-md-3">
                    <button type="submit" class="btn btn-secondary w-100">ค้นหา</button>
                </div>
            </form>
        </div>
        <div class="col-12 col-md-4 text-md-end">
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary px-3 rounded-pill">
                <i class="bi bi-plus-circle me-1"></i> เพิ่มสินค้าใหม่
            </a>
        </div>
    </div>

    <!-- Products Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">รูปภาพ</th>
                    <th>ชื่อสินค้า</th>
                    <th>หมวดหมู่</th>
                    <th>ราคา</th>
                    <th>สต็อก</th>
                    <th>สถานะ</th>
                    <th>ไซส์ / สี</th>
                    <th class="text-end" style="width: 170px;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr class="{{ $product->is_active === false ? 'table-secondary opacity-75' : '' }}">
                        <td>
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded-3 object-fit-cover shadow-sm" width="56" height="56" onerror="this.src='https://placehold.co/100x100?text=No+Image'">
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $product->name }}</div>
                            <small class="text-muted text-truncate d-inline-block" style="max-width: 230px;">{{ $product->description }}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $product->category }}</span>
                        </td>
                        <td class="fw-bold text-primary">
                            ฿{{ number_format($product->price, 2) }}
                        </td>
                        <td>
                            @if ($product->stock <= 5)
                                <span class="badge bg-danger-subtle text-danger fw-semibold px-2 py-1">{{ $product->stock }} ชิ้น</span>
                            @else
                                <span class="badge bg-success-subtle text-success fw-semibold px-2 py-1">{{ $product->stock }} ชิ้น</span>
                            @endif
                        </td>
                        <td>
                            @if ($product->is_active === false)
                                <span class="badge bg-secondary text-white px-2 py-1"><i class="bi bi-eye-slash me-1"></i> ซ่อนอยู่</span>
                            @else
                                <span class="badge bg-success text-white px-2 py-1"><i class="bi bi-check me-1"></i> เปิดขาย</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted d-block">ไซส์: {{ is_array($product->sizes) ? implode(', ', $product->sizes) : $product->sizes }}</small>
                            <small class="text-muted d-block">สี: {{ is_array($product->colors) ? implode(', ', $product->colors) : $product->colors }}</small>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end align-items-center gap-1">
                                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-sm btn-outline-primary" title="แก้ไข">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                @if ($product->is_active === false)
                                    <!-- ปุ่มกู้คืนสินค้า -->
                                    <form action="{{ route('admin.products.restore', $product->id) }}" method="POST" class="d-inline m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="กู้คืนเพื่อเปิดขาย">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> กู้คืน
                                        </button>
                                    </form>
                                @else
                                    <!-- ปุ่มซ่อนสินค้า (Soft Delete) -->
                                    <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('คุณต้องการซ่อนสินค้า \"{{ $product->name }}\" ออกจากแอปใช่หรือไม่?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-warning text-dark" title="ซ่อนสินค้า">
                                            <i class="bi bi-eye-slash me-1"></i> ซ่อน
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            ไม่พบรายการสินค้า
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4 d-flex justify-content-between align-items-center">
        <small class="text-muted">แสดงทั้งหมด {{ $products->total() }} รายการ</small>
        {{ $products->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
