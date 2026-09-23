<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ยอดขายรวม (นับเฉพาะออเดอร์ที่ payment_status = paid)
        $totalRevenue = Order::where('payment_status', 'paid')->sum('total_price');

        // จำนวนออเดอร์วันนี้
        $todayOrdersCount = Order::whereDate('created_at', Carbon::today())->count();

        // ยอดขายวันนี้ (นับเฉพาะที่ paid)
        $todayRevenue = Order::whereDate('created_at', Carbon::today())
            ->where('payment_status', 'paid')
            ->sum('total_price');

        // จำนวนสต็อกสินค้ารวม
        $totalStock = Product::sum('stock');
        $totalProducts = Product::count();

        // จำนวนลูกค้าทั้งหมด
        $totalCustomers = User::where('role', 'customer')->count();

        // สถิติยอดขาย 7 วันย้อนหลัง (นับเฉพาะออเดอร์ที่ paid)
        $chartLabels = [];
        $chartValues = [];

        $thaiMonths = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
            5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
            9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
        ];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayRevenue = Order::whereDate('created_at', $date)
                ->where('payment_status', 'paid')
                ->sum('total_price');

            $monthThai = $thaiMonths[$date->month];
            $chartLabels[] = $date->day . ' ' . $monthThai;
            $chartValues[] = (float) $dayRevenue;
        }

        // ออเดอร์ล่าสุด 6 รายการ
        $recentOrders = Order::with(['user', 'items'])
            ->latest()
            ->take(6)
            ->get();

        return view('admin.dashboard', compact(
            'totalRevenue',
            'todayOrdersCount',
            'todayRevenue',
            'totalStock',
            'totalProducts',
            'totalCustomers',
            'chartLabels',
            'chartValues',
            'recentOrders'
        ));
    }
}
