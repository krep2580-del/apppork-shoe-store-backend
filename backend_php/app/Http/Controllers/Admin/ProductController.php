<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('category') && $request->category !== 'ทั้งหมด') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'hidden') {
                $query->where('is_active', false);
            } elseif ($request->status === 'active') {
                $query->where(function ($q) {
                    $q->where('is_active', true)->orWhereNull('is_active');
                });
            }
        }

        $products = $query->latest()->paginate(10)->withQueryString();

        $categories = ['ทั้งหมด', 'รองเท้าผ้าใบ', 'รองเท้ากีฬา', 'รองเท้าหนัง', 'รองเท้าแตะ'];

        $statusCounts = [
            'all' => Product::count(),
            'active' => Product::where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })->count(),
            'hidden' => Product::where('is_active', false)->count(),
        ];

        return view('admin.products.index', compact('products', 'categories', 'statusCounts'));
    }

    public function create()
    {
        $categories = ['รองเท้าผ้าใบ', 'รองเท้ากีฬา', 'รองเท้าหนัง', 'รองเท้าแตะ'];
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'sizes' => 'required|string',
            'colors' => 'required|string',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // จำกัด jpg, png, webp ไม่เกิน 2MB
            'custom_image_url' => 'nullable|url|max:500',
        ], [
            'name.required' => 'กรุณากรอกชื่อสินค้า',
            'category.required' => 'กรุณาเลือกหมวดหมู่สินค้า',
            'price.required' => 'กรุณาระบุราคาสินค้า',
            'price.numeric' => 'ราคาสินค้าต้องเป็นตัวเลข',
            'stock.required' => 'กรุณาระบุจำนวนสต็อก',
            'stock.integer' => 'จำนวนสต็อกต้องเป็นจำนวนเต็ม',
            'sizes.required' => 'กรุณาระบุขนาดไซส์ (คั่นด้วยจุลภาค เช่น 39, 40, 41)',
            'colors.required' => 'กรุณาระบุสี (คั่นด้วยจุลภาค เช่น ดำ, ขาว, แดง)',
            'image_file.image' => 'ไฟล์ที่อัปโหลดต้องเป็นรูปภาพเท่านั้น',
            'image_file.mimes' => 'รองรับเฉพาะไฟล์รูปภาพประเภท jpeg, png, jpg, webp เท่านั้น',
            'image_file.max' => 'ขนาดไฟล์รูปภาพต้องไม่เกิน 2MB (2,048 KB)',
            'custom_image_url.url' => 'รูปแบบ URL ของรูปภาพไม่ถูกต้อง',
        ]);

        $imageUrl = null;

        // ตรวจสอบการอัปโหลดไฟล์รูปภาพ
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            // เปลี่ยนชื่อไฟล์เป็นชื่อสุ่มไม่ให้ซ้ำกัน
            $extension = $file->getClientOriginalExtension();
            $fileName = 'product_' . time() . '_' . uniqid() . '.' . $extension;
            $file->storeAs('products', $fileName, 'public');
            // เก็บแบบ relative path ในฐานข้อมูล
            $imageUrl = 'products/' . $fileName;
        } elseif ($request->filled('custom_image_url')) {
            $imageUrl = trim($request->custom_image_url);
        } else {
            $imageUrl = 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600';
        }

        // แปลง comma-separated เป็น array
        $sizesArray = array_values(array_filter(array_map('trim', explode(',', $request->sizes))));
        $colorsArray = array_values(array_filter(array_map('trim', explode(',', $request->colors))));

        Product::create([
            'name' => trim($request->name),
            'category' => $request->category,
            'price' => (float) $request->price,
            'stock' => (int) $request->stock,
            'description' => $request->description,
            'sizes' => $sizesArray,
            'colors' => $colorsArray,
            'image_url' => $imageUrl,
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'เพิ่มสินค้าใหม่เรียบร้อยแล้ว');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $categories = ['รองเท้าผ้าใบ', 'รองเท้ากีฬา', 'รองเท้าหนัง', 'รองเท้าแตะ'];
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'sizes' => 'required|string',
            'colors' => 'required|string',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'custom_image_url' => 'nullable|url|max:500',
        ], [
            'name.required' => 'กรุณากรอกชื่อสินค้า',
            'price.required' => 'กรุณาระบุราคาสินค้า',
            'stock.required' => 'กรุณาระบุจำนวนสต็อก',
            'image_file.mimes' => 'รองรับเฉพาะไฟล์รูปภาพประเภท jpeg, png, jpg, webp เท่านั้น',
            'image_file.max' => 'ขนาดไฟล์รูปภาพต้องไม่เกิน 2MB (2,048 KB)',
        ]);

        $rawImageUrl = $product->getRawOriginal('image_url');

        if ($request->hasFile('image_file')) {
            // ลบรูปภาพเก่าออกหากเป็นไฟล์ใน storage
            if ($rawImageUrl && !str_starts_with($rawImageUrl, 'http') && Storage::disk('public')->exists($rawImageUrl)) {
                Storage::disk('public')->delete($rawImageUrl);
            }

            $file = $request->file('image_file');
            $extension = $file->getClientOriginalExtension();
            $fileName = 'product_' . time() . '_' . uniqid() . '.' . $extension;
            $file->storeAs('products', $fileName, 'public');
            $rawImageUrl = 'products/' . $fileName;
        } elseif ($request->filled('custom_image_url')) {
            $rawImageUrl = trim($request->custom_image_url);
        }

        $sizesArray = array_values(array_filter(array_map('trim', explode(',', $request->sizes))));
        $colorsArray = array_values(array_filter(array_map('trim', explode(',', $request->colors))));

        $product->update([
            'name' => trim($request->name),
            'category' => $request->category,
            'price' => (float) $request->price,
            'stock' => (int) $request->stock,
            'description' => $request->description,
            'sizes' => $sizesArray,
            'colors' => $colorsArray,
            'image_url' => $rawImageUrl,
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'บันทึกการแก้ไขสินค้าเรียบร้อยแล้ว');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // เปลี่ยนเป็นการซ่อนสินค้า (Soft delete) เพื่อไม่ให้ประวัติออเดอร์เสียหาย
        $product->update(['is_active' => false]);

        return redirect()->route('admin.products.index')
            ->with('success', "ซ่อนสินค้า \"{$product->name}\" เรียบร้อยแล้ว (แอปจะไม่แสดงสินค้านี้ แต่ประวัติการสั่งซื้อยังคงอยู่)");
    }

    public function restore($id)
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => true]);

        return redirect()->route('admin.products.index')
            ->with('success', "เปิดขายสินค้า \"{$product->name}\" เรียบร้อยแล้ว");
    }
}
