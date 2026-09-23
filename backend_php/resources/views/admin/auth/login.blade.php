<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบผู้ดูแลหลังบ้าน - Shoe Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1E3A8A 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 440px;
            padding: 40px 32px;
        }
        .btn-primary {
            background-color: #1E3A8A;
            border-color: #1E3A8A;
        }
        .btn-primary:hover {
            background-color: #172554;
            border-color: #172554;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="d-inline-flex p-3 rounded-circle bg-primary-subtle text-primary mb-3">
                <i class="bi bi-shield-lock-fill fs-2"></i>
            </div>
            <h4 class="fw-bold mb-1">ระบบผู้ดูแลหลังบ้าน</h4>
            <p class="text-muted small">Shoe Store Admin Panel</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 small mb-3">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3 small mb-3">
                <i class="bi bi-exclamation-circle-fill me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('admin.login.submit') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label fw-semibold small">อีเมลแอดมิน</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', app()->environment('local', 'testing') ? 'admin@shoestore.com' : '') }}" placeholder="admin@shoestore.com" required autofocus>
                </div>
                @error('email')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold small">รหัสผ่าน</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" value="{{ app()->environment('local', 'testing') ? 'password123' : '' }}" placeholder="รหัสผ่าน" required>
                </div>
                @error('password')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label small" for="remember">จดจำการเข้าสู่ระบบ</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-3 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i> เข้าสู่ระบบ
            </button>

            @if (app()->environment('local', 'testing'))
            <div class="text-center text-muted small bg-light p-3 rounded-3">
                <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i> บัญชีแอดมินสำหรับทดสอบ (Local):</div>
                <div>อีเมล: <code>admin@shoestore.com</code></div>
                <div>รหัสผ่าน: <code>password123</code></div>
            </div>
            @endif
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
