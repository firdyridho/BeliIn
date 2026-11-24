# Entity Relationship Diagram (ERD)

## BeliIn --- Draft Logical Data Model

**Versi:** 1.0\
**Database:** MySQL InnoDB\
**Status:** Draft untuk ditinjau sebelum migration dibuat

ERD ini menggambarkan model logis. Tipe data, index, constraint, soft
delete, dan detail kolom perlu disesuaikan saat implementasi.

------------------------------------------------------------------------

## 1. Konvensi

-   Primary key disarankan `BIGINT UNSIGNED` atau UUID/ULID secara
    konsisten. Pilih satu strategi sebelum migration pertama.
-   Semua tabel operasional menggunakan `created_at` dan `updated_at`;
    tambahkan `deleted_at` hanya jika soft delete memang diperlukan.
-   Uang IDR disimpan sebagai integer rupiah (`BIGINT`) bila tidak ada
    pecahan. Jika provider mengenakan pecahan, gunakan `DECIMAL` sesuai
    kebutuhan.
-   Snapshot transaksi menyimpan data saat pembelian agar perubahan
    produk/toko tidak mengubah histori.
-   Status penting menggunakan enum aplikasi atau lookup table; hindari
    status bebas yang tidak tervalidasi.
-   Foreign key dan index wajib dirancang untuk query utama.

## 2. ERD ringkas

``` mermaid
erDiagram
    USERS ||--o{ USER_ADDRESSES : has
    USERS ||--o{ USER_ROLES : assigned
    ROLES ||--o{ USER_ROLES : contains
    ROLES ||--o{ ROLE_PERMISSIONS : grants
    PERMISSIONS ||--o{ ROLE_PERMISSIONS : included

    USERS ||--o| SELLERS : becomes
    SELLERS ||--o| STORES : owns
    SELLERS ||--o{ SELLER_VERIFICATIONS : submits
    STORES ||--o{ PRODUCTS : lists
    CATEGORIES ||--o{ PRODUCTS : classifies
    PRODUCTS ||--o{ PRODUCT_VARIANTS : has
    PRODUCTS ||--o{ PRODUCT_IMAGES : has
    PRODUCT_VARIANTS ||--o| INVENTORIES : stocked
    INVENTORIES ||--o{ INVENTORY_RESERVATIONS : reserves
    INVENTORIES ||--o{ INVENTORY_MOVEMENTS : records

    USERS ||--o{ CARTS : owns
    CARTS ||--o{ CART_ITEMS : contains
    PRODUCT_VARIANTS ||--o{ CART_ITEMS : selected

    USERS ||--o{ ORDERS : places
    ORDERS ||--o{ SELLER_ORDERS : splits
    STORES ||--o{ SELLER_ORDERS : fulfills
    SELLER_ORDERS ||--o{ ORDER_ITEMS : contains
    PRODUCT_VARIANTS ||--o{ ORDER_ITEMS : references
    ORDERS ||--o{ PAYMENTS : paid_by
    PAYMENTS ||--o{ PAYMENT_TRANSACTIONS : records

    SELLER_ORDERS ||--o{ SHIPMENTS : ships
    SHIPPING_PROVIDERS ||--o{ SHIPMENTS : handles
    SHIPMENTS ||--o{ SHIPMENT_TRACKING_EVENTS : tracks
    COURIERS ||--o{ COURIER_ASSIGNMENTS : assigned
    SHIPMENTS ||--o{ COURIER_ASSIGNMENTS : assigned_to

    SELLER_ORDERS ||--o{ RETURN_REQUESTS : may_return
    RETURN_REQUESTS ||--o{ REFUNDS : results_in
    USERS ||--o{ REVIEWS : writes
    ORDER_ITEMS ||--o| REVIEWS : reviewed

    SELLERS ||--o{ COMMISSION_SNAPSHOTS : charged
    ORDERS ||--o{ FINANCIAL_LEDGER_ENTRIES : creates
    SELLERS ||--o{ SETTLEMENTS : receives
```

## 3. Kelompok entitas dan kolom penting

### 3.1 Identity & access

**users** - id - name - email (unique, nullable jika mendukung
phone-first) - phone (unique, nullable) - password - email_verified_at -
phone_verified_at - status - last_login_at

**roles** - id, name, slug, description

**permissions** - id, name, slug, module

**user_roles** - user_id, role_id

**role_permissions** - role_id, permission_id

**user_addresses** - id, user_id, recipient_name, phone, address_line,
province, city, district, village, postal_code, latitude, longitude,
is_default

### 3.2 Seller & store

**sellers** - id, user_id (unique) - status:
draft/pending/under_review/verified/rejected/suspended -
verification_level - verified_at

**seller_verifications** - id, seller_id, document_type, document_path,
status, reviewer_id, review_note, submitted_at, reviewed_at

**stores** - id, seller_id (unique) - name, slug (unique), description,
logo_path, banner_path, status, rating_summary

**store_members** - id, store_id, user_id, role_id, status

**membership_plans** - id, name, price_amount, billing_period,
features_json, is_active

**membership_subscriptions** - id, store_id, membership_plan_id, status,
starts_at, ends_at, provider_reference

### 3.3 Catalog

**categories** - id, parent_id, name, slug, description, status,
sort_order

**products** - id, store_id, category_id - name, slug, description -
condition, status - weight_gram, length_mm, width_mm, height_mm -
published_at

**product_variants** - id, product_id, sku, variant_name,
attributes_json, price_amount, compare_at_price_amount, status

**product_images** - id, product_id, variant_id nullable, file_path,
sort_order, alt_text

**product_attributes** - id, category_id, name, slug, data_type,
is_filterable, is_required

### 3.4 Inventory

**inventories** - id, product_variant_id (unique), available_quantity,
reserved_quantity, sold_quantity, low_stock_threshold

**inventory_reservations** - id, inventory_id, order_id nullable,
checkout_reference, quantity, status, expires_at, converted_at,
released_at

**inventory_movements** - id, inventory_id, order_item_id nullable,
movement_type, quantity_delta, reference_type, reference_id, note,
actor_id

Catatan: tentukan satu definisi stok yang konsisten. Contoh:
`available_quantity` adalah stok yang dapat dijual setelah reservasi,
sedangkan `reserved_quantity` adalah stok yang sedang ditahan. Semua
perubahan harus melalui service transaksi, bukan update bebas dari
controller.

### 3.5 Cart & order

**carts** - id, user_id, status, expires_at

**cart_items** - id, cart_id, product_variant_id, quantity

**orders** (parent checkout) - id, order_number, buyer_id -
subtotal_amount, discount_amount, shipping_amount, service_fee_amount,
total_amount - currency - payment_status, order_status -
shipping_address_snapshot_json - placed_at, cancelled_at

**seller_orders** - id, order_id, store_id, seller_order_number -
subtotal_amount, discount_amount, shipping_amount, seller_status -
accepted_at, shipped_at, completed_at, cancelled_at

**order_items** - id, seller_order_id, product_id, product_variant_id -
product_name_snapshot, variant_snapshot_json, sku_snapshot -
unit_price_amount, quantity, discount_amount, line_total_amount -
commission_rate_snapshot, commission_amount_snapshot -
weight_snapshot_gram

**payments** - id, order_id, provider, provider_payment_id,
payment_method - amount, currency, status, expires_at, paid_at

**payment_transactions** - id, payment_id, provider_transaction_id,
event_type, amount, status, raw_payload_path, processed_at - Unique
provider event/reference key untuk idempotency.

### 3.6 Shipping & AnterIn

**shipping_providers** - id, code, name, provider_type
(internal/external), is_active, config_reference

**shipping_services** - id, shipping_provider_id, service_code,
service_name, estimated_min_days, estimated_max_days, is_active

**shipments** - id, seller_order_id, shipping_provider_id,
shipping_service_id - tracking_number, status - origin_snapshot_json,
destination_snapshot_json - shipping_cost_amount, buyer_shipping_amount,
provider_cost_amount - shipped_at, delivered_at

**shipment_tracking_events** - id, shipment_id, event_code,
event_description, location, event_at, source

**couriers** - id, user_id nullable, name, phone, status,
operational_area_id

**courier_assignments** - id, shipment_id, courier_id, assigned_by,
status, assigned_at, completed_at

**delivery_proofs** - id, shipment_id, courier_assignment_id,
proof_type, file_path nullable, recipient_name, notes, captured_at

### 3.7 Return, refund & review

**return_requests** - id, seller_order_id, buyer_id, reason_code,
description, status - evidence_json, requested_at, reviewed_at,
resolution

**return_items** - id, return_request_id, order_item_id, quantity,
condition_received, resolution

**refunds** - id, return_request_id nullable, order_id, payment_id,
amount, status, provider_refund_id, processed_at

**reviews** - id, order_item_id (unique), buyer_id, product_id,
store_id - rating, review_text, status, verified_purchase, created_at

**review_replies** - id, review_id, seller_id, reply_text, created_at

### 3.8 Finance

**commission_rules** - id, name, category_id nullable, seller_id
nullable - rate_basis_points, fixed_amount, priority, effective_from,
effective_until, status

**commission_snapshots** - id, order_item_id, seller_id, rule_id
nullable, rate_basis_points, commission_amount, snapshot_json

**financial_ledger_entries** - id, account_owner_type, account_owner_id,
order_id nullable, seller_order_id nullable - entry_type, direction,
amount, currency, reference_type, reference_id - idempotency_key
(unique), created_at

**settlements** - id, seller_id, amount, status, period_start,
period_end - provider_reference, initiated_at, completed_at,
failure_reason

**financial_adjustments** - id, ledger_entry_id, adjustment_type,
amount, reason, created_by

Ledger harus append-only. `financial_adjustments` membuat koreksi yang
merujuk entri asli; jangan mengubah/menghapus entri historis.

### 3.9 Promotions, ads & engagement

**vouchers** - id, code, discount_type, discount_value,
max_discount_amount, min_order_amount, starts_at, ends_at, usage_limit,
status

**voucher_redemptions** - id, voucher_id, user_id, order_id,
discount_amount, redeemed_at

**ad_campaigns** - id, advertiser_store_id nullable, campaign_type,
budget_amount, starts_at, ends_at, status

**ad_events** - id, ad_campaign_id, product_id nullable, user_id
nullable, event_type, occurred_at, attribution_reference

**notifications** - id, user_id, type, title, body, data_json, read_at

**reports** - id, reporter_id, target_type, target_id, reason, status,
assigned_to, resolution_note

## 4. Relasi transaksi yang wajib dipahami

-   `orders` adalah satu checkout dari buyer.
-   `seller_orders` adalah pecahan tanggung jawab masing-masing toko.
-   `order_items` terhubung ke `seller_orders`, bukan langsung ke parent
    order.
-   `payments` pada rancangan awal terhubung ke parent `orders`.
-   `shipments` terhubung ke `seller_orders`; satu seller order dapat
    memiliki satu atau lebih shipment jika split shipment didukung.
-   `return_requests` sebaiknya dapat mengembalikan sebagian item.
    Karena itu disediakan `return_items`.
-   `reviews` terhubung ke `order_items` untuk memastikan verified
    purchase.

## 5. Catatan konsistensi stok

Implementasi perlu menetapkan rumus stok dengan jelas. Salah satu
pendekatan: - `on_hand_quantity`: stok fisik seller. -
`reserved_quantity`: stok yang sedang ditahan untuk checkout. -
`available_quantity = on_hand_quantity - reserved_quantity`.

Jika memakai kolom terdenormalisasi, update dilakukan atomik dalam
transaction. Jangan menyimpan nilai yang saling bertentangan tanpa
invariant dan test.

## 6. Indeks dan constraint penting

-   Unique: users.email, users.phone (sesuai kebijakan), stores.slug,
    product_variants.sku (scope seller/store), orders.order_number,
    seller_orders.seller_order_number.
-   Index: products(store_id, status), products(category_id, status),
    product_variants(product_id), orders(buyer_id, created_at),
    seller_orders(store_id, seller_status, created_at),
    shipments(tracking_number), inventory_reservations(status,
    expires_at).
-   Foreign keys dengan kebijakan delete yang aman; jangan
    cascade-delete histori transaksi/keuangan.
-   Unique idempotency key untuk checkout/payment webhook/refund/ledger
    event.
-   Index full-text/search lebih baik dikelola search engine ketika
    Meilisearch digunakan.

## 7. Hal yang perlu diputuskan sebelum migration

1.  BIGINT vs UUID/ULID untuk primary key.
2.  Apakah satu seller order boleh memiliki beberapa shipment sejak MVP.
3.  Apakah retur dapat parsial per item (rancangan ini mendukung).
4.  Struktur akun ledger double-entry dan chart of accounts final.
5.  Bentuk snapshot alamat, harga, pajak, komisi, dan ongkir.
6.  Kebijakan penghapusan/retensi data dan dokumen identitas.
