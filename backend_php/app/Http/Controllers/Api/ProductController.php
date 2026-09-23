<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * ดึงรายการสินค้าทั้งหมด (รองรับการค้นหาและกรองหมวดหมู่)
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // แสดงเฉพาะสินค้าที่ไม่ได้ถูกซ่อน (is_active != false)
        $query->where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        });

        // กรองตามหมวดหมู่
        if ($request->filled('category') && $request->category !== 'ทั้งหมด') {
            $query->where('category', $request->category);
        }

        // ค้นหาตามชื่อหรือรายละเอียดสินค้า
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ], 200);
    }

    /**
     * ดึงรายละเอียดสินค้าเดี่ยว
     */
    public function show($id)
    {
        $product = Product::where('id', $id)
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->first();

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลสินค้าที่ต้องการ',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $product,
        ], 200);
    }
}
