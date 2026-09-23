@extends('layouts.admin')

@section('title', 'เพิ่มสินค้าใหม่')
@section('header', 'เพิ่มสินค้าใหม่')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-9">
        <div class="card table-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <h5 class="fw-bold mb-0">ข้อมูลสินค้าใหม่</h5>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
                </a>
            </div>

            <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <!-- ชื่อสินค้า -->
                    <div class="col-12 col-md-8">
                        <label for="name" class="form-label fw-semibold">ชื่อสินค้า <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="เช่น Nike Air Max 90" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- หมวดหมู่ -->
                    <div class="col-12 col-md-4">
                        <label for="category" class="form-label fw-semibold">หมวดหมู่ <span class="text-danger">*</span></label>
                        <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                            <option value="">-- เลือกหมวดหมู่ --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
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
                            <input type="number" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price') }}" placeholder="0.00" required>
                        </div>
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- จำนวนสต็อก -->
                    <div class="col-12 col-md-6">
                        <label for="stock" class="form-label fw-semibold">จำนวนสต็อก (ชิ้น) <span class="text-danger">*</span></label>
                        <input type="number" min="0" class="form-control @error('stock') is-invalid @enderror" id="stock" name="stock" value="{{ old('stock', 10) }}" required>
                        @error('stock')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- ไซส์รองเท้า -->
                    <div class="col-12 col-md-6">
                        <label for="sizes" class="form-label fw-semibold">ไซส์ที่มี (คั่นด้วยเครื่องหมายจุลภาค) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('sizes') is-invalid @enderror" id="sizes" name="sizes" value="{{ old('sizes', '38, 39, 40, 41, 42, 43') }}" placeholder="38, 39, 40, 41, 42, 43" required>
                        <small class="text-muted">ตัวอย่าง: <code>39, 40, 41, 42, 43, 44</code></small>
                        @error('sizes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- สีรองเท้า -->
                    <div class="col-12 col-md-6">
                        <label for="colors" class="form-label fw-semibold">สีที่มี (คั่นด้วยเครื่องหมายจุลภาค) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('colors') is-invalid @enderror" id="colors" name="colors" value="{{ old('colors', 'ดำ, ขาว, แดง') }}" placeholder="ดำ, ขาว, แดง" required>
                        <small class="text-muted">ตัวอย่าง: <code>ดำ, ขาว, น้ำเงิน</code></small>
                        @error('colors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- รายละเอียดสินค้า -->
                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold">รายละเอียดสินค้า</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="ระบุรายละเอียด วัสดุ คุณสมบัติความนุ่มสบาย...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- ส่วนอัปโหลดรูปภาพ -->
                    <div class="col-12 pt-3 border-top">
                        <h6 class="fw-bold mb-3"><i class="bi bi-image me-1"></i> รูปภาพสินค้า</h6>
                        
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="image_file" class="form-label fw-semibold">อัปโหลดรูปภาพจากคอมพิวเตอร์</label>
                                <input type="file" class="form-control @error('image_file') is-invalid @enderror" id="image_file" name="image_file" accept=".jpg,.jpeg,.png,.webp" onchange="previewImage(this)">
                                <small class="text-muted d-block mt-1">รองรับ: <code>jpg, jpeg, png, webp</code> (ขนาดไฟล์ไม่เกิน 2MB)</small>
                                @error('image_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="custom_image_url" class="form-label fw-semibold">หรือระบุ URL รูปภาพโดยตรง</label>
                                <input type="url" class="form-control @error('custom_image_url') is-invalid @enderror" id="custom_image_url" name="custom_image_url" value="{{ old('custom_image_url') }}" placeholder="https://images.unsplash.com/...">
                                <small class="text-muted d-block mt-1">หากไม่อัปโหลดไฟล์ สามารถใส่ลิงก์รูปภาพจากอินเทอร์เน็ตได้</small>
                                @error('custom_image_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Preview Box -->
                            <div class="col-12">
                                <div id="previewContainer" class="d-none mt-2">
                                    <label class="form-label fw-semibold small text-muted">ตัวอย่างรูปภาพ:</label>
                                    <div>
                                        <img id="imagePreview" src="#" alt="Preview" class="rounded-3 border shadow-sm object-fit-cover" style="max-height: 180px; max-width: 250px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ปุ่มบันทึก -->
                    <div class="col-12 text-end mt-4 pt-3 border-top">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-light px-4 me-2">ยกเลิก</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> บันทึกข้อมูลสินค้า
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
    function previewImage(input) {
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
