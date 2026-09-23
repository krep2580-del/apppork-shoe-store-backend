@extends('layouts.admin')

@section('title', 'แก้ไขสินค้า: ' . $product->name)
@section('header', 'แก้ไขข้อมูลสินค้า')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-9">
        <div class="card table-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <h5 class="fw-bold mb-0">แก้ไขสินค้า: {{ $product->name }}</h5>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
                </a>
            </div>

            <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <!-- ชื่อสินค้า -->
                    <div class="col-12 col-md-8">
                        <label for="name" class="form-label fw-semibold">ชื่อสินค้า <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- หมวดหมู่ -->
                    <div class="col-12 col-md-4">
                        <label for="category" class="form-label fw-semibold">หมวดหมู่ <span class="text-danger">*</span></label>
                        <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}" {{ old('category', $product->category) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- ราคา -->
                    <div class="col-12 col-md-6">
                        <label for="price" class="form-label fw-semibold">ราคา (บาท) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">฿</span>
                            <input type="number" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $product->price) }}" required>
                        </div>
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- จำนวนสต็อก -->
                    <div class="col-12 col-md-6">
                        <label for="stock" class="form-label fw-semibold">จำนวนสต็อก (ชิ้น) <span class="text-danger">*</span></label>
                        <input type="number" min="0" class="form-control @error('stock') is-invalid @enderror" id="stock" name="stock" value="{{ old('stock', $product->stock) }}" required>
                        @error('stock')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- ไซส์รองเท้า -->
                    <div class="col-12 col-md-6">
                        <label for="sizes" class="form-label fw-semibold">ไซส์ที่มี (คั่นด้วยเครื่องหมายจุลภาค) <span class="text-danger">*</span></label>
                        @php
                            $sizesString = is_array($product->sizes) ? implode(', ', $product->sizes) : $product->sizes;
                        @endphp
                        <input type="text" class="form-control @error('sizes') is-invalid @enderror" id="sizes" name="sizes" value="{{ old('sizes', $sizesString) }}" required>
                        <small class="text-muted">เช่น <code>39, 40, 41, 42, 43</code></small>
                        @error('sizes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- สีรองเท้า -->
                    <div class="col-12 col-md-6">
                        <label for="colors" class="form-label fw-semibold">สีที่มี (คั่นด้วยเครื่องหมายจุลภาค) <span class="text-danger">*</span></label>
                        @php
                            $colorsString = is_array($product->colors) ? implode(', ', $product->colors) : $product->colors;
                        @endphp
                        <input type="text" class="form-control @error('colors') is-invalid @enderror" id="colors" name="colors" value="{{ old('colors', $colorsString) }}" required>
                        <small class="text-muted">เช่น <code>ดำ, ขาว, แดง</code></small>
                        @error('colors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- รายละเอียดสินค้า -->
                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold">รายละเอียดสินค้า</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- ส่วนรูปภาพ -->
                    <div class="col-12 pt-3 border-top">
                        <h6 class="fw-bold mb-3"><i class="bi bi-image me-1"></i> จัดการรูปภาพสินค้า</h6>
                        
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">รูปภาพปัจจุบัน</label>
                                <div>
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded-3 border shadow-sm object-fit-cover" style="max-height: 140px; max-width: 200px;" onerror="this.src='https://placehold.co/100x100?text=No+Image'">
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="image_file" class="form-label fw-semibold">อัปโหลดรูปภาพใหม่เพื่อเปลี่ยน</label>
                                <input type="file" class="form-control @error('image_file') is-invalid @enderror" id="image_file" name="image_file" accept=".jpg,.jpeg,.png,.webp" onchange="previewNewImage(this)">
                                <small class="text-muted d-block mt-1">รองรับ: <code>jpg, jpeg, png, webp</code> (ไม่เกิน 2MB)</small>
                                @error('image_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <div class="mt-3">
                                    <label for="custom_image_url" class="form-label fw-semibold">หรือเปลี่ยนเป็น Image URL</label>
                                    <input type="url" class="form-control @error('custom_image_url') is-invalid @enderror" id="custom_image_url" name="custom_image_url" value="{{ old('custom_image_url', str_starts_with($product->getRawOriginal('image_url') ?? '', 'http') ? $product->getRawOriginal('image_url') : '') }}" placeholder="https://...">
                                    @error('custom_image_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Preview Box for new image -->
                            <div class="col-12">
                                <div id="previewContainer" class="d-none mt-2">
                                    <label class="form-label fw-semibold small text-primary">รูปภาพใหม่ที่เลือก:</label>
                                    <div>
                                        <img id="imagePreview" src="#" alt="Preview" class="rounded-3 border shadow-sm object-fit-cover" style="max-height: 150px; max-width: 220px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ปุ่มบันทึก -->
                    <div class="col-12 text-end mt-4 pt-3 border-top">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-light px-4 me-2">ยกเลิก</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> บันทึกการแก้ไข
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function previewNewImage(input) {
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');

        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (file.size > 2 * 1024 * 1024) {
                alert('ขนาดไฟล์เกิน 2MB กรุณาเลือกรูปภาพใหม่');
                input.value = '';
                previewContainer.classList.add('d-none');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                previewContainer.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.classList.add('d-none');
        }
    }
</script>
@endsection
