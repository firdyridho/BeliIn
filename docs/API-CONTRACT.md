# API Contract

## BeliIn REST API --- v1

**Base URL production:** `https://api.beliin.com/api/v1`\
**Base URL development:** `http://localhost:8000/api/v1`\
**Backend:** Laravel API-only\
**Frontend client:** React

Kontrak ini adalah baseline untuk menyepakati bentuk komunikasi
frontend-backend. Detail endpoint dapat berkembang, tetapi perubahan
harus didokumentasikan dan tidak boleh merusak client tanpa
versioning/deprecation plan.

------------------------------------------------------------------------

## 1. Prinsip API

-   REST JSON melalui HTTPS.
-   Prefix versi `/api/v1`.
-   Backend adalah sumber kebenaran untuk aturan bisnis.
-   Response konsisten menggunakan envelope.
-   Pagination menggunakan `data`, `meta`, dan `links`.
-   Tanggal menggunakan ISO 8601 UTC.
-   Nilai uang menggunakan integer rupiah dan currency `IDR` pada fase
    awal.
-   ID dapat berupa string untuk mendukung BIGINT/ULID tanpa mengubah
    kontrak client.
-   Gunakan idempotency untuk operasi yang berpotensi diulang.
-   Jangan mengirim secret provider ke frontend.

## 2. Headers

Request umum:

``` http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <access_token>
```

Untuk operasi idempotent:

``` http
Idempotency-Key: <unique-client-generated-key>
```

Untuk upload multipart:

``` http
Content-Type: multipart/form-data
```

## 3. Format response

### Success

``` json
{
  "success": true,
  "message": "Products retrieved successfully",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 0,
    "last_page": 1
  }
}
```

### Single resource

``` json
{
  "success": true,
  "message": "Product retrieved successfully",
  "data": {
    "id": "prd_123",
    "name": "Contoh Produk"
  }
}
```

### Error

``` json
{
  "success": false,
  "message": "The given data was invalid.",
  "error": {
    "code": "VALIDATION_ERROR",
    "details": {
      "email": ["The email field is required."]
    }
  },
  "request_id": "req_abc123"
}
```

## 4. HTTP status conventions

  Status   Makna
  -------- ----------------------------------------------
  200      Request berhasil
  201      Resource berhasil dibuat
  202      Request diterima untuk diproses asynchronous
  204      Berhasil tanpa response body
  400      Request tidak valid secara umum
  401      Belum login/token tidak valid
  403      Tidak memiliki permission
  404      Resource tidak ditemukan
  409      Konflik state/stok/idempotency
  422      Validasi gagal
  429      Rate limit
  500      Kesalahan server
  503      Layanan sementara tidak tersedia

## 5. Auth endpoints

  -------------------------------------------------------------------------------
  Method            Endpoint                  Auth              Keterangan
  ----------------- ------------------------- ----------------- -----------------
  POST              `/auth/register`          Public            Membuat akun

  POST              `/auth/login`             Public            Login

  POST              `/auth/logout`            User              Logout/revoke
                                                                token

  GET               `/auth/me`                User              Profil user aktif

  POST              `/auth/email/verify`      User              Verifikasi
                                                                email/token

  POST              `/auth/otp/request`       Public/User       Meminta OTP
                                                                dengan rate limit

  POST              `/auth/otp/verify`        Public/User       Memvalidasi OTP

  POST              `/auth/password/forgot`   Public            Memulai reset
                                                                password

  POST              `/auth/password/reset`    Public            Reset password
  -------------------------------------------------------------------------------

### Register request

``` json
{
  "name": "Nama Pengguna",
  "email": "user@example.com",
  "phone": "08xxxxxxxxxx",
  "password": "strong-password",
  "password_confirmation": "strong-password"
}
```

### Login request

``` json
{
  "login": "user@example.com",
  "password": "strong-password",
  "device_name": "Chrome Desktop"
}
```

## 6. User & address endpoints

  Method   Endpoint                   Keterangan
  -------- -------------------------- ---------------
  GET      `/profile`                 Lihat profil
  PATCH    `/profile`                 Ubah profil
  GET      `/addresses`               Daftar alamat
  POST     `/addresses`               Tambah alamat
  GET      `/addresses/{addressId}`   Detail alamat
  PATCH    `/addresses/{addressId}`   Ubah alamat
  DELETE   `/addresses/{addressId}`   Hapus alamat

## 7. Public catalog endpoints

  Method   Endpoint                    Keterangan
  -------- --------------------------- ---------------------------
  GET      `/categories`               Daftar kategori
  GET      `/categories/{slug}`        Detail kategori
  GET      `/products`                 List/search/filter produk
  GET      `/products/{slug}`          Detail produk
  GET      `/stores/{slug}`            Profil toko publik
  GET      `/stores/{slug}/products`   Produk toko

Query contoh:

``` http
GET /products?q=sepatu&category=fashion&min_price=50000&max_price=500000&sort=price_asc&page=1&per_page=20
```

Parameter filter/sort harus di-whitelist backend.

## 8. Seller onboarding & store endpoints

  ---------------------------------------------------------------------------------------
  Method            Endpoint                          Auth/Permission   Keterangan
  ----------------- --------------------------------- ----------------- -----------------
  POST              `/seller/applications`            User              Ajukan seller

  GET               `/seller/application`             Seller applicant  Status pengajuan

  POST              `/seller/application/documents`   Seller applicant  Upload dokumen

  GET               `/seller/store`                   Seller            Detail toko
                                                                        sendiri

  PATCH             `/seller/store`                   Seller            Ubah toko

  GET               `/seller/dashboard`               Seller            Ringkasan
                                                                        dashboard
  ---------------------------------------------------------------------------------------

Admin: \| Method \| Endpoint \| Permission \| Keterangan \|
\|---\|---\|---\|---\| \| GET \| `/admin/seller-applications` \|
seller.review \| Daftar pengajuan \| \| GET \|
`/admin/seller-applications/{id}` \| seller.review \| Detail \| \| POST
\| `/admin/seller-applications/{id}/approve` \| seller.review \| Setujui
\| \| POST \| `/admin/seller-applications/{id}/reject` \| seller.review
\| Tolak dengan alasan \|

## 9. Seller catalog & inventory endpoints

  ------------------------------------------------------------------------------------------------
  Method            Endpoint                                  Permission         Keterangan
  ----------------- ----------------------------------------- ------------------ -----------------
  GET               `/seller/products`                        product.view       Produk toko

  POST              `/seller/products`                        product.create     Buat produk

  GET               `/seller/products/{productId}`            product.view       Detail

  PATCH             `/seller/products/{productId}`            product.update     Ubah

  DELETE            `/seller/products/{productId}`            product.delete     Arsip/hapus
                                                                                 sesuai aturan

  POST              `/seller/products/{productId}/images`     product.update     Upload gambar

  POST              `/seller/products/{productId}/variants`   product.update     Tambah varian

  PATCH             `/seller/variants/{variantId}`            product.update     Ubah varian

  GET               `/seller/inventory`                       inventory.view     Daftar stok

  PATCH             `/seller/inventory/{variantId}`           inventory.update   Penyesuaian stok
  ------------------------------------------------------------------------------------------------

Backend harus memastikan seller hanya dapat mengakses produk/store
miliknya.

## 10. Cart & checkout endpoints

  Method   Endpoint                 Auth    Keterangan
  -------- ------------------------ ------- ----------------------------------------
  GET      `/cart`                  Buyer   Lihat cart
  POST     `/cart/items`            Buyer   Tambah item
  PATCH    `/cart/items/{itemId}`   Buyer   Ubah quantity
  DELETE   `/cart/items/{itemId}`   Buyer   Hapus item
  POST     `/checkout/preview`      Buyer   Hitung ulang estimasi
  POST     `/checkout`              Buyer   Buat order + seller orders + reservasi

### Checkout preview request

``` json
{
  "address_id": "addr_123",
  "items": [
    {
      "variant_id": "var_123",
      "quantity": 2
    }
  ],
  "voucher_codes": ["HEMAT10"],
  "shipping_selections": [
    {
      "store_id": "store_1",
      "provider_code": "anterin",
      "service_code": "same_day"
    }
  ]
}
```

### Checkout request

``` json
{
  "address_id": "addr_123",
  "items": [
    {
      "variant_id": "var_123",
      "quantity": 2
    }
  ],
  "voucher_codes": ["HEMAT10"],
  "shipping_selections": [
    {
      "store_id": "store_1",
      "provider_code": "anterin",
      "service_code": "same_day"
    }
  ],
  "notes": "Mohon dikemas dengan aman"
}
```

Header wajib:

``` http
Idempotency-Key: checkout-unique-key
```

### Checkout response (contoh)

``` json
{
  "success": true,
  "message": "Checkout created",
  "data": {
    "order_id": "ord_123",
    "order_number": "BL-2026-000123",
    "payment_status": "pending",
    "expires_at": "2026-11-22T10:15:00Z",
    "totals": {
      "subtotal_amount": 250000,
      "discount_amount": 10000,
      "shipping_amount": 15000,
      "service_fee_amount": 2000,
      "total_amount": 257000,
      "currency": "IDR"
    },
    "seller_orders": [
      {
        "id": "sord_1",
        "store_name": "Toko Contoh",
        "seller_status": "awaiting_payment",
        "shipment_id": "shp_1"
      }
    ]
  }
}
```

Semua nominal pada response merupakan hasil kalkulasi backend. Client
tidak boleh menentukan total final. Reservasi stok dibuat dalam
transaksi yang sama dengan pembuatan order atau melalui workflow yang
memiliki kompensasi aman.

## 11. Payment endpoints

  -------------------------------------------------------------------------------------------
  Method            Endpoint                              Auth              Keterangan
  ----------------- ------------------------------------- ----------------- -----------------
  POST              `/orders/{orderId}/payment-intents`   Buyer             Membuat instruksi
                                                                            pembayaran

  GET               `/orders/{orderId}/payment`           Buyer             Status pembayaran

  POST              `/webhooks/payments/{provider}`       Provider          Webhook provider
                                                          signature         

  GET               `/orders/{orderId}`                   Buyer             Detail order
  -------------------------------------------------------------------------------------------

Webhook: - Verifikasi signature dari provider. - Validasi nominal,
currency, merchant reference, dan status. - Simpan event provider. -
Proses secara idempotent. - Jangan percaya status dari browser/client. -
Konversi reservasi menjadi stok terjual setelah payment success
terverifikasi. - Lepaskan reservasi jika payment expired/failed sesuai
aturan.

## 12. Order endpoints

Buyer: \| Method \| Endpoint \| Keterangan \| \|---\|---\|---\| \| GET
\| `/orders` \| Daftar parent orders \| \| GET \| `/orders/{orderId}` \|
Detail checkout \| \| POST \| `/orders/{orderId}/cancel` \| Ajukan
pembatalan \|

Seller: \| Method \| Endpoint \| Keterangan \| \|---\|---\|---\| \| GET
\| `/seller/orders` \| Daftar seller orders \| \| GET \|
`/seller/orders/{sellerOrderId}` \| Detail \| \| POST \|
`/seller/orders/{sellerOrderId}/accept` \| Terima pesanan \| \| POST \|
`/seller/orders/{sellerOrderId}/reject` \| Tolak sesuai aturan \| \|
POST \| `/seller/orders/{sellerOrderId}/process` \| Mulai proses \| \|
POST \| `/seller/orders/{sellerOrderId}/ready-to-ship` \| Siap dikirim
\| \| POST \| `/seller/orders/{sellerOrderId}/complete` \| Tandai
selesai jika diizinkan \|

## 13. Shipping & AnterIn endpoints

Buyer: \| Method \| Endpoint \| Keterangan \| \|---\|---\|---\| \| POST
\| `/shipping/rates` \| Mencari opsi tarif \| \| GET \|
`/shipments/{shipmentId}/tracking` \| Riwayat tracking \|

Seller: \| Method \| Endpoint \| Keterangan \| \|---\|---\|---\| \| GET
\| `/seller/shipments` \| Shipment toko \| \| POST \|
`/seller/shipments/{shipmentId}/handover` \| Konfirmasi serah paket \|

AnterIn operator/courier: \| Method \| Endpoint \| Keterangan \|
\|---\|---\|---\| \| GET \| `/anterin/shipments` \| Daftar shipment \|
\| POST \| `/anterin/shipments/{shipmentId}/assign` \| Assign kurir \|
\| GET \| `/courier/tasks` \| Tugas kurir \| \| POST \|
`/courier/tasks/{assignmentId}/pickup` \| Konfirmasi pickup \| \| POST
\| `/courier/tasks/{assignmentId}/status` \| Update status \| \| POST \|
`/courier/tasks/{assignmentId}/proof` \| Upload bukti delivery \|

Provider webhook: \| Method \| Endpoint \| Keterangan \|
\|---\|---\|---\| \| POST \| `/webhooks/shipping/{provider}` \| Update
tracking dari provider \|

## 14. Return, refund & review endpoints

  -------------------------------------------------------------------------------------------------
  Method            Endpoint                                   Auth               Keterangan
  ----------------- ------------------------------------------ ------------------ -----------------
  POST              `/seller-orders/{sellerOrderId}/returns`   Buyer              Buat pengajuan
                                                                                  retur

  GET               `/returns`                                 Buyer/Seller       Daftar retur
                                                                                  sesuai akses

  GET               `/returns/{returnId}`                      Pihak terkait      Detail retur

  POST              `/returns/{returnId}/approve`              Seller/Moderator   Setujui
                                                               sesuai aturan      

  POST              `/returns/{returnId}/reject`               Seller/Moderator   Tolak dengan
                                                               sesuai aturan      alasan

  POST              `/returns/{returnId}/evidence`             Pihak terkait      Tambah bukti

  POST              `/orders/{orderId}/confirm-received`       Buyer              Konfirmasi
                                                                                  diterima

  POST              `/order-items/{orderItemId}/reviews`       Verified buyer     Buat ulasan

  POST              `/reviews/{reviewId}/reply`                Seller             Balas ulasan
  -------------------------------------------------------------------------------------------------

Refund dibuat melalui proses internal yang terotorisasi; jangan
menyediakan endpoint publik untuk mengubah status refund sesuka client.

## 15. Admin & finance endpoints

Contoh area endpoint: - `/admin/users` - `/admin/stores` -
`/admin/products` - `/admin/categories` - `/admin/reports` -
`/admin/moderation` - `/admin/configurations` -
`/admin/commission-rules` - `/admin/financial-reports` -
`/admin/settlements` - `/admin/audit-logs`

Finance dapat mengelola aturan komisi/biaya, rekonsiliasi, dan laporan
sesuai permission. Pencairan dana sebaiknya dijalankan melalui workflow
yang terkontrol dan provider, dengan approval/audit sesuai kebijakan.

## 16. Promotion, ads, membership, chat & notifications

Contoh endpoint: - `GET/POST/PATCH /seller/vouchers` -
`GET /vouchers/{code}/validate` -
`GET/POST/PATCH /seller/ad-campaigns` - `GET /seller/membership/plans` -
`POST /seller/membership/subscriptions` -
`GET /seller/membership/subscription` - `GET/POST /conversations` -
`GET/POST /conversations/{conversationId}/messages` -
`GET /notifications` - `POST /notifications/{notificationId}/read`

Fitur yang belum masuk MVP dapat ditambahkan pada versi API berikutnya
tanpa harus dibangun sekaligus.

## 17. Pagination, filtering, sorting

Contoh:

``` http
GET /seller/orders?status=processing&page=1&per_page=20&sort=-created_at
```

Response list menggunakan:

``` json
{
  "success": true,
  "message": "Orders retrieved",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 0,
    "last_page": 1
  },
  "links": {
    "first": null,
    "last": null,
    "prev": null,
    "next": null
  }
}
```

## 18. State machine utama

Jangan gabungkan semua status ke satu kolom.

-   Payment: `pending`, `paid`, `failed`, `expired`,
    `partially_refunded`, `refunded`.
-   Parent order: `pending_payment`, `paid`, `partially_fulfilled`,
    `completed`, `cancelled`.
-   Seller order: `awaiting_payment`, `new`, `accepted`, `processing`,
    `ready_to_ship`, `shipped`, `delivered`, `completed`, `cancelled`,
    `return_requested`, `returned`.
-   Shipment: `pending`, `awaiting_pickup`, `picked_up`, `in_transit`,
    `out_for_delivery`, `delivered`, `failed`, `returned`.
-   Return: `requested`, `under_review`, `approved`, `rejected`,
    `item_in_return_transit`, `received`, `resolved`, `disputed`.
-   Settlement: `pending`, `processing`, `paid`, `failed`, `reversed`.

Transisi harus divalidasi backend berdasarkan aktor, permission,
kondisi, dan histori status.

## 19. API security & operational requirements

-   Sanctum/token auth atau mekanisme yang dipilih harus dikonfigurasi
    aman untuk domain frontend.
-   Rate limit login, OTP, search, checkout, payment, dan endpoint
    upload.
-   CORS hanya mengizinkan origin resmi.
-   Validasi object ownership untuk mencegah IDOR.
-   Webhook memakai signature dan replay protection.
-   Audit log untuk aksi admin/finance/moderator.
-   Idempotency key untuk checkout, payment callback, refund, dan
    ledger.
-   Jangan mengirim raw provider secrets atau data sensitif yang tidak
    dibutuhkan.
-   Semua endpoint admin memerlukan permission eksplisit.
-   Gunakan request ID/correlation ID untuk tracing.

## 20. API versioning & change management

-   Perubahan backward-compatible dapat ditambahkan pada `/v1`.
-   Perubahan breaking harus menggunakan versi baru atau rencana
    deprecation yang jelas.
-   OpenAPI/Swagger menjadi sumber dokumentasi teknis.
-   Frontend dan backend menyepakati kontrak sebelum implementasi fitur.
-   Setiap endpoint wajib memiliki request schema, response schema,
    permission, error cases, dan test.
