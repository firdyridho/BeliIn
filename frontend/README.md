# BeliIn Frontend

Frontend adalah aplikasi React yang mengonsumsi Laravel REST API.
Frontend tidak memiliki akses langsung ke database dan tidak menjalankan
aturan bisnis kritis.

## Stack awal

-   React + Vite
-   TypeScript direkomendasikan
-   React Router
-   TanStack Query untuk server state
-   Zustand atau Context untuk client state bila diperlukan
-   UI component system yang konsisten
-   Form validation dengan schema validation
-   API client terpusat

## Area aplikasi

1.  Buyer storefront: beranda, pencarian, detail produk, keranjang,
    checkout, pesanan, profil.
2.  Seller Center: onboarding seller, toko, produk, stok, pesanan,
    promosi, analitik, membership.
3.  Admin Panel: pengguna, verifikasi seller, moderasi, kategori,
    konfigurasi, laporan.
4.  AnterIn Panel: pengiriman, penugasan kurir, status pickup/delivery,
    bukti pengantaran.

Area-area tersebut dapat dimulai sebagai satu aplikasi React dengan
route/layout terpisah. Pisahkan menjadi beberapa deployment jika
kebutuhan tim dan skala sudah jelas.

## Aturan integrasi

-   Semua data bisnis berasal dari API.
-   Gunakan `Authorization: Bearer <token>` jika memakai token auth.
-   Gunakan `Idempotency-Key` untuk aksi yang dapat terkirim ulang
    seperti checkout dan pembayaran.
-   Jangan menyimpan secret payment provider di frontend.
-   Jangan mempercayai harga, diskon, ongkir, komisi, atau total yang
    dikirim client. Backend wajib menghitung ulang.
-   Tangani status `401`, `403`, `422`, `429`, dan `5xx` secara
    konsisten.
-   Gunakan pagination untuk daftar produk, pesanan, dan data admin.
-   Upload file melalui endpoint backend atau signed upload URL yang
    diterbitkan backend.

## Environment

Contoh variabel:

``` env
VITE_API_BASE_URL=https://api.beliin.com/api/v1
```

Jangan pernah menaruh private key atau secret di variabel `VITE_*`;
seluruh variabel tersebut dapat terekspos ke browser.
