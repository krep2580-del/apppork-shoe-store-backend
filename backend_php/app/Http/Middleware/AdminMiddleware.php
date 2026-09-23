<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * ตรวจสอบสิทธิ์เฉพาะผู้ใช้ที่เป็น Admin เท่านั้น
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('admin.login')->with('error', 'กรุณาเข้าสู่ระบบก่อนเข้าใช้งาน');
        }

        if (Auth::user()->role !== 'admin') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login')->with('error', 'บัญชีนี้ไม่มีสิทธิ์เข้าถึงระบบผู้ดูแล');
        }

        return $next($request);
    }
}
