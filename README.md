# BeliIn — Multi-Seller Marketplace Platform

BeliIn adalah platform marketplace general-purpose multi-seller yang menghubungkan pembeli dan penjual dalam satu ekosistem terpadu dengan arsitektur decoupled (Laravel REST API Backend + React TypeScript Frontend) dan terintegrasi dengan layanan logistik internal AnterIn.

---

## 📁 Struktur Monorepo

```
BeliIn/
├── backend/            # Laravel 11 REST API (Modular Monolith)
│   ├── app/            # Domain logic (Auth, Catalog, Order, Payment, Shipping, dll.)
│   ├── database/       # Migrations & Seeders
│   ├── routes/api.php  # REST API endpoints
│   └── tests/          # Feature & Unit tests
├── frontend/           # Modern React + TypeScript + Vite + Tailwind CSS
│   ├── src/            # Storefront, Seller Portal, Cart, Checkout, & Order Tracking
│   └── public/         # Static assets
└── docs/               # Dokumentasi Teknis & Arsitektur
    ├── PRD.md          # Product Requirements Document
    ├── ERD.md          # Entity Relationship Diagram & Schema Rules
    ├── API-CONTRACT.md # Spesifikasi API Contract & Envelope Format
    └── beliin_postman_collection.json # Koleksi Postman API
```

---

## 🌿 Branching Strategy

- **`main`**: Branch produksi utama (Production / Stable Release).
- **`staging`**: Branch integrasi dan pre-production testing sebelum rilis ke `main`.

---

## 🚀 Memulai Pengembangan

### 1. Backend (Laravel REST API)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
Backend API berjalan di `http://localhost:8000` (API base: `http://localhost:8000/api/v1`).

### 2. Frontend (React + Vite)

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```
Frontend berjalan di `http://localhost:5173`.

---

## 🛠️ Fitur Utama

- **Multi-Seller Cart & Split Checkout**: Pembeli dapat memesan dari berbagai toko dalam satu transaksi dengan snapshot harga, promo, dan ongkir terisolasi.
- **Atomic Stock Reservation**: Reservasi stok berbasis row-locking dan idempotensi untuk mencegah overselling.
- **Idempotent Payment & Ledger**: Integrasi webhook pembayaran dengan signature verification dan pencatatan double-entry ledger.
- **AnterIn Logistics Integration**: Perhitungan tarif real-time, fulfillment pengiriman, dan pelacakan status bertahap.
- **Modern Responsive UI**: Pengalaman belanja bebas slop dengan filter katalog cepat, drawer keranjang responsif, dan pelacakan pesanan visual.

---

## 📄 Dokumentasi

Silakan baca dokumen arsitektur dan spesifikasi pada folder `docs/`:
- [PRD.md](docs/PRD.md)
- [ERD.md](docs/ERD.md)
- [API-CONTRACT.md](docs/API-CONTRACT.md)
