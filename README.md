# Shoe Store Dual Flutter Apps (Customer & Admin)

โปรเจกต์แอพขายรองเท้า Flutter ที่แยกเป็น 2 แอพพลิเคชันภายใต้ Firebase project เดียวกัน:

1. **`customer_app/`**: แอพสำหรับลูกค้า (เลือกดูสินค้า, กรองหมวดหมู่, ค้นหา, หน้ารายละเอียดสินค้า, ตะกร้าสินค้า, ยืนยันการสั่งซื้อ, ประวัติการสั่งซื้อ)
2. **`admin_app/`**: แอพสำหรับแอดมิน (แดชบอร์ดสรุปยอดขาย + กราฟ `fl_chart`, จัดการรายการสินค้า เพิ่ม/แก้ไข/ลบ, จัดการและเปลี่ยนสถานะออเดอร์)

---

## 📁 โครงสร้างโปรเจกต์

```
c:\apppork\
├── customer_app/                # แอพฝั่งลูกค้า
│   ├── lib/
│   │   ├── models/             # Product, CartItem, Order, AppUser
│   │   ├── providers/          # AuthProvider, ProductProvider, CartProvider, OrderProvider
│   │   ├── screens/            # AuthScreen, MainNavigation, HomeScreen, ProductDetail, Cart, Checkout, OrderHistory, Profile, Search
│   │   └── main.dart
│   └── pubspec.yaml
│
├── admin_app/                   # แอพฝั่งแอดมิน (Dashboard)
│   ├── lib/
│   │   ├── models/             # Product, CartItem, Order, AppUser
│   │   ├── providers/          # AuthProvider, ProductProvider, OrderProvider
│   │   ├── screens/            # AdminLogin, AdminMain, DashboardScreen, ProductList, AddEditProduct, OrderManagement
│   │   └── main.dart
│   └── pubspec.yaml
│
└── firestore.rules              # กฎความปลอดภัย Firestore Security Rules
```

---

## 🚀 วิธีการรันโปรเจกต์

### 1. การรัน Customer App (ฝั่งลูกค้า)
```bash
cd customer_app
flutter run
```
*หรือเปิดในอุปกรณ์/อีมูเลเตอร์โดยตรง*
*มีปุ่ม **"ทดลองใช้งาน (Demo Customer)"** ในหน้าแรกสำหรับทดสอบ UI ทันที*

### 2. การรัน Admin App (ฝั่งแอดมิน)
```bash
cd admin_app
flutter run
```
*มีปุ่ม **"เข้าใช้งานในโหมด Demo Admin"** ในหน้า Login สำหรับทดสอบแดชบอร์ด จัดการสินค้า และออเดอร์ทันที*
*มีปุ่ม **"ใส่ข้อมูลตัวอย่าง (Seed Data)"** ในหน้าแดชบอร์ดเพื่อสร้างสินค้าและออเดอร์ทดลองทันที*

---

## 🔐 Firebase Security Rules

ไฟล์ `firestore.rules` มีโครงสร้างดังนี้:
- `products`: อ่านได้ทุกคน (`read: true`), เพิ่ม/แก้ไข/ลบได้เฉพาะแอดมิน (`role == 'admin'`)
- `orders`: ผู้ใช้ทั่วไปอ่าน/เขียนได้เฉพาะของตัวเอง (`userId == request.auth.uid`), แอดมินจัดการได้ทั้งหมด
- `users`: อ่าน/แก้ไขได้เฉพาะของตัวเอง, แอดมินจัดการได้ทั้งหมด

---

## 🛠️ Tech Stack & Packages
- **Flutter** (Material 3 Design)
- **State Management**: Provider
- **Firebase**: Firebase Core, Firebase Auth, Cloud Firestore, Firebase Storage
- **UI & Helpers**: `cached_network_image`, `image_picker`, `fl_chart`, `intl`
