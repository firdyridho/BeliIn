# Product Requirements Document (PRD)

## BeliIn --- Marketplace Multi-Seller

**Versi:** 1.0\
**Status:** Draft baseline hasil brainstorming\
**Produk:** BeliIn\
**Ekosistem logistik:** AnterIn

------------------------------------------------------------------------

## 1. Ringkasan Produk

BeliIn adalah marketplace general-purpose yang mempertemukan pembeli dan
seller dalam satu platform. BeliIn mendukung berbagai kategori produk
fisik, checkout multi-seller, beragam metode pembayaran, serta pilihan
pengiriman melalui AnterIn dan ekspedisi eksternal.

Backend BeliIn dibangun sebagai Laravel REST API saja. Frontend
dikembangkan terpisah menggunakan React dan berkomunikasi dengan API.
Backend tidak bertanggung jawab untuk merender halaman web.

## 2. Visi dan tujuan

### Visi

Membangun marketplace yang mudah digunakan, fleksibel untuk seller,
transparan bagi pembeli, dan dapat berkembang bersama layanan logistik
internal.

### Tujuan produk

1.  Memudahkan pembeli menemukan dan membeli produk dari banyak toko.
2.  Memungkinkan satu checkout menghasilkan pesanan terpisah per toko.
3.  Memberikan seller alat untuk mengelola toko, produk, stok, dan
    pesanan.
4.  Mengintegrasikan AnterIn dan ekspedisi eksternal melalui sistem
    provider yang konsisten.
5.  Menjaga pencatatan pembayaran, komisi, refund, dan settlement secara
    akurat.
6.  Menyediakan monetisasi melalui komisi, iklan, membership opsional,
    biaya layanan, dan layanan logistik.

## 3. Ruang lingkup

### In scope

-   Produk fisik dari berbagai kategori.
-   Akun buyer dan seller.
-   Satu akun seller hanya memiliki satu toko pada model awal.
-   Seller onboarding dan verifikasi hybrid.
-   Katalog, kategori, produk, varian, foto, stok.
-   Keranjang dan checkout multi-seller.
-   Pembayaran melalui payment provider yang dipilih.
-   Pengiriman AnterIn dan ekspedisi eksternal.
-   Tracking pengiriman.
-   Retur, refund, dan sengketa.
-   Rating dan ulasan transaksi terverifikasi.
-   Komisi, biaya layanan, membership opsional, iklan.
-   Panel buyer, seller, admin, dan operasional AnterIn melalui frontend
    terpisah/area aplikasi.

### Out of scope untuk fase awal

-   Produk digital dan layanan.
-   Dompet/e-money BeliIn sendiri.
-   Sistem payment gateway milik BeliIn.
-   Affiliate, live commerce, loyalty/rewards tingkat lanjut.
-   Aplikasi mobile native khusus (dapat dipertimbangkan setelah MVP).
-   Microservices.

## 4. Target pengguna dan aktor

  -----------------------------------------------------------------------
  Aktor                               Kebutuhan utama
  ----------------------------------- -----------------------------------
  Guest                               Menjelajahi dan mencari produk

  Buyer                               Membeli, membayar, melacak,
                                      mengajukan retur, memberi ulasan

  Seller                              Membuka toko, mengelola
                                      katalog/stok/pesanan

  Admin                               Mengelola operasional dan
                                      konfigurasi marketplace

  Finance                             Mengatur kebijakan keuangan,
                                      rekonsiliasi, laporan, aturan
                                      komisi

  Moderator                           Menangani laporan dan moderasi
                                      konten

  AnterIn Operator                    Mengelola pengiriman dan penugasan
                                      kurir

  Courier                             Melihat tugas dan memperbarui
                                      status pengiriman
  -----------------------------------------------------------------------

Satu user dapat menjadi buyer sekaligus seller. Hak akses ditentukan
oleh role dan permission.

## 5. Model bisnis dan monetisasi

1.  **Komisi transaksi:** dapat berbeda berdasarkan kategori atau
    kesepakatan seller. Aturan komisi harus memiliki versi dan tanggal
    efektif. Nilai komisi transaksi disimpan sebagai snapshot.
2.  **Iklan:** sponsored product, banner, atau promosi kategori. Iklan
    harus diberi label yang jelas dan memiliki pencatatan impresi/klik.
3.  **Membership:** opsional. Paket Basic gratis, paket Pro/Business
    berbayar dengan fitur tambahan. Fitur dasar berjualan tidak boleh
    dikunci di balik membership.
4.  **Biaya layanan:** ditampilkan transparan sebelum pembeli membayar.
5.  **Logistik:** pendapatan layanan AnterIn dipisahkan dari biaya
    ekspedisi pihak ketiga dan biaya operasional kurir.

Biaya, persentase, dan harga paket belum ditetapkan. Semua perhitungan
final dilakukan backend.

## 6. Aturan bisnis utama

### 6.1 Akun dan toko

-   Satu akun hanya dapat memiliki satu toko pada model awal.
-   Buyer dapat mengajukan pembukaan toko menggunakan akun yang sama.
-   Seller harus melengkapi profil dan dokumen yang diminta.
-   Verifikasi bersifat hybrid: pemeriksaan otomatis untuk
    kelengkapan/validasi dasar dan pemeriksaan manual untuk kasus
    tertentu.
-   Seller yang belum memenuhi status aktif tidak dapat mempublikasikan
    produk atau menerima pesanan.

### 6.2 Katalog

-   Mendukung semua kategori produk fisik.
-   Kategori dan atribut produk dikelola secara dinamis.
-   Produk dapat memiliki varian seperti ukuran/warna.
-   Harga, stok, berat, dimensi, kondisi, dan status publikasi
    divalidasi backend.
-   Produk terlarang atau melanggar kebijakan dapat ditolak atau
    diturunkan.

### 6.3 Stok dan reservasi hybrid

-   Menambahkan produk ke cart tidak mengurangi stok.
-   Pada tahap checkout, backend memeriksa dan mereservasi stok
    sementara.
-   Reservasi memiliki `expires_at`.
-   Pembayaran sukses mengonversi reservasi menjadi pengurangan stok
    terjual.
-   Pembayaran gagal/kedaluwarsa melepaskan reservasi.
-   Pembatalan setelah pembayaran diproses melalui alur order/refund dan
    inventory movement yang sesuai.
-   Gunakan transaksi database dan locking/atomic stock update untuk
    mencegah overselling.
-   Durasi reservasi awal yang disarankan: 15 menit, dapat dikonfigurasi
    berdasarkan payment method.

### 6.4 Checkout multi-seller

-   Pembeli melakukan satu checkout.
-   Sistem membuat satu parent `order`.
-   Sistem membuat satu `seller_order` untuk setiap toko yang terlibat.
-   Setiap seller order dapat memiliki shipment dan status pemenuhan
    sendiri.
-   Pembayaran parent order tidak berarti dana otomatis dibayarkan
    langsung ke semua seller.
-   Alokasi dana, komisi, refund, dan settlement dicatat secara
    terpisah.

### 6.5 Pengiriman

-   Mendukung AnterIn dan ekspedisi eksternal.
-   Buyer memilih dari opsi yang tersedia berdasarkan alamat, cakupan,
    berat/dimensi, dan jenis layanan.
-   Setiap seller order dapat memiliki shipment sendiri.
-   Provider diintegrasikan melalui adapter agar penambahan ekspedisi
    tidak mengubah core order flow.
-   Tracking disimpan sebagai riwayat event, bukan hanya satu kolom
    status.
-   Bukti pickup/delivery dapat berupa foto, OTP, tanda tangan, atau
    metode yang disepakati.

### 6.6 Pembatalan

-   Aturan pembatalan bergantung pada status order dan shipment.
-   Pembatalan sebelum seller memproses dapat lebih sederhana.
-   Setelah diproses/dikirim, pembatalan mengikuti proses retur atau
    intervensi operasional.
-   Semua pembatalan harus memiliki actor, reason, timestamp, dan dampak
    stok/keuangan yang tercatat.

### 6.7 Retur dan refund hybrid

-   Kebijakan minimum BeliIn berlaku untuk semua seller.
-   Seller boleh memberikan kebijakan tambahan yang lebih menguntungkan
    pembeli.
-   Alasan retur, bukti, batas waktu, dan kondisi barang dicatat.
-   Sengketa yang tidak selesai antara buyer dan seller dapat
    dieskalasikan ke moderator/admin.
-   Refund diproses sesuai hasil keputusan dan kemampuan payment
    provider.
-   Refund tidak boleh hanya mengubah status order; harus menghasilkan
    catatan transaksi/ledger.

### 6.8 Rating

-   Semua pengguna dapat melihat ulasan.
-   Rating transaksi produk/toko hanya dapat diberikan oleh buyer dengan
    transaksi valid/verified purchase.
-   Satu item order hanya memiliki satu ulasan utama.
-   Seller dapat membalas ulasan.
-   Moderator dapat menyembunyikan ulasan yang melanggar kebijakan
    dengan alasan dan audit log.

### 6.9 Membership

-   Membership bersifat opsional.
-   Basic gratis untuk kemampuan berjualan dasar.
-   Pro/Business memberikan fitur tambahan.
-   Status subscription dan periode aktif diperiksa backend.

### 6.10 Keuangan

-   Aturan komisi memiliki versi dan tanggal efektif.
-   Transaksi menyimpan snapshot komisi yang digunakan saat transaksi
    terjadi.
-   Financial ledger bersifat append-only.
-   Koreksi dilakukan dengan entri reversal/adjustment, bukan mengedit
    histori.
-   Payout/settlement mengikuti integrasi payment provider, aturan
    hukum, dan hasil rekonsiliasi.
-   Finance mengelola kebijakan dan rekonsiliasi; operasi pencairan
    rutin sebaiknya otomatis jika provider mendukung.

## 7. Alur utama pengguna

### 7.1 Buyer

1.  Menjelajahi atau mencari produk.
2.  Membuka detail produk dan memilih varian.
3.  Menambahkan ke keranjang.
4.  Memilih alamat dan opsi pengiriman.
5.  Backend memvalidasi harga, promo, ongkir, dan stok.
6.  Backend membuat order dan seller orders serta reservasi stok.
7.  Buyer menyelesaikan pembayaran.
8.  Payment webhook diverifikasi backend.
9.  Order diproses seller dan shipment dibuat/diperbarui.
10. Buyer melacak pengiriman.
11. Setelah diterima, buyer dapat memberi ulasan.
12. Jika ada masalah, buyer dapat mengajukan retur.

### 7.2 Seller

1.  Registrasi/login.
2.  Mengajukan pembukaan toko.
3.  Melengkapi profil dan dokumen.
4.  Menunggu verifikasi.
5.  Setelah aktif, mengelola produk, varian, dan stok.
6.  Menerima seller order.
7.  Memproses dan menyerahkan paket ke provider.
8.  Memantau pesanan, retur, dan performa toko.

### 7.3 AnterIn

1.  Shipment AnterIn dibuat.
2.  Operator memeriksa area, jadwal, dan kapasitas.
3.  Kurir ditugaskan.
4.  Kurir melakukan pickup.
5.  Status pengiriman diperbarui.
6.  Kurir mengantar paket.
7.  Bukti pengantaran disimpan.
8.  Shipment ditandai delivered.

## 8. Functional requirements

  -----------------------------------------------------------------------
  ID                      Fitur                   Requirement
  ----------------------- ----------------------- -----------------------
  FR-01                   Authentication          Register, login,
                                                  logout, OTP/reset
                                                  sesuai kebijakan

  FR-02                   RBAC                    Role dan permission
                                                  granular

  FR-03                   Seller onboarding       Pengajuan toko dan
                                                  verifikasi

  FR-04                   Store                   Profil dan pengaturan
                                                  toko

  FR-05                   Catalog                 CRUD produk, kategori,
                                                  varian, gambar

  FR-06                   Inventory               Stok, reservasi,
                                                  movement

  FR-07                   Search                  Pencarian, filter,
                                                  sort, pagination

  FR-08                   Cart                    Cart dan cart items

  FR-09                   Checkout                Validasi dan split
                                                  seller order

  FR-10                   Payment                 Payment intent,
                                                  callback/webhook,
                                                  status

  FR-11                   Shipping                Rate, shipment,
                                                  tracking provider

  FR-12                   AnterIn                 Pickup, courier
                                                  assignment, proof of
                                                  delivery

  FR-13                   Order                   Buyer/seller order
                                                  management

  FR-14                   Return                  Return request, review,
                                                  dispute, refund

  FR-15                   Review                  Verified purchase
                                                  review dan reply

  FR-16                   Finance                 Commission, ledger,
                                                  settlement,
                                                  reconciliation

  FR-17                   Promotion               Voucher dan promo

  FR-18                   Advertisement           Campaign, sponsored
                                                  placement, event
                                                  tracking

  FR-19                   Membership              Plans dan subscription

  FR-20                   Notification            In-app/email/push
                                                  notification

  FR-21                   Moderation              Reports, takedown,
                                                  seller/product
                                                  moderation

  FR-22                   Analytics               Dashboard sesuai
                                                  permission
  -----------------------------------------------------------------------

## 9. Non-functional requirements

-   **Security:** HTTPS, password hashing, rate limiting, permission
    checks, secure file upload, webhook signature verification.
-   **Consistency:** checkout, stok, payment, refund, dan ledger harus
    transactional/idempotent.
-   **Performance:** pagination, caching untuk data publik, indexing
    database, queue untuk pekerjaan lambat.
-   **Availability:** health checks, logging, monitoring, backup, dan
    prosedur restore.
-   **Scalability:** modular monolith dengan queue dan search service;
    scale horizontal jika diperlukan.
-   **Maintainability:** API versioning, automated tests, OpenAPI,
    coding standards.
-   **Privacy:** akses data pribadi dibatasi sesuai kebutuhan dan
    kebijakan privasi.
-   **Auditability:** aksi sensitif memiliki actor, timestamp, reason,
    before/after bila relevan.

Target angka SLA/performance belum ditetapkan dan harus ditentukan
setelah estimasi beban.

## 10. Prioritas rilis

### MVP

-   Auth dan profil.
-   Seller onboarding/verifikasi dasar.
-   Store dan katalog.
-   Inventory dan reservasi.
-   Cart dan multi-seller checkout.
-   Payment gateway pertama.
-   Satu atau lebih integrasi pengiriman yang benar-benar tersedia,
    termasuk alur AnterIn awal jika operasional siap.
-   Order tracking.
-   Admin dasar.
-   Refund/retur dasar.
-   Rating verified purchase.

### Phase 2

-   Iklan dan sponsored products.
-   Membership Pro/Business.
-   Chat buyer-seller.
-   Analitik lanjutan.
-   Automasi settlement yang didukung provider.
-   Integrasi ekspedisi tambahan.

### Later

-   Affiliate, live commerce, rewards/loyalty.
-   Rekomendasi personalisasi.
-   Aplikasi mobile native.
-   Ekspansi layanan AnterIn.

## 11. Acceptance criteria MVP

-   Buyer dapat mendaftar/login dan mengelola alamat.
-   Seller dapat mengajukan toko dan hanya seller aktif dapat
    menerbitkan produk.
-   Produk mendukung varian dan stok.
-   Satu cart dapat berisi produk dari beberapa toko.
-   Checkout menghasilkan satu parent order dan beberapa seller orders.
-   Stok tidak oversell pada pembelian bersamaan.
-   Pembayaran hanya dianggap sukses setelah status diverifikasi
    backend/provider.
-   Setiap seller order memiliki shipment dan tracking yang sesuai.
-   Buyer dapat melihat status pesanan.
-   Retur/refund tercatat dan dapat diaudit.
-   Ulasan hanya dapat dibuat dari transaksi valid.
-   Backend berjalan sebagai API-only dan frontend dapat
    dikembangkan/deploy terpisah.

## 12. Keputusan yang masih terbuka

-   Payment gateway dan metode pembayaran final.
-   Dukungan COD dan kebijakan operasionalnya.
-   Mekanisme settlement dan jadwal pencairan seller.
-   Durasi reservasi per metode pembayaran.
-   Daftar provider ekspedisi dan aggregator.
-   Tarif komisi, biaya layanan, tarif iklan, dan harga membership.
-   SLA pengiriman dan customer service.
-   Kebijakan legal, pajak, privasi, barang terlarang, dan penyelesaian
    sengketa.
