# BeliIn Backend Core Modules Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a headless Laravel REST API covering the 7 core modules (Auth, Seller, Catalog, Inventory, Checkout/Order, Payment, Shipping) with decoupled mobile-ready architecture, strict separated status lifecycles, and atomic stock reservation.

**Architecture:** Modular Monolith in `app/Modules/` with clear domain boundaries (`Auth`, `Seller`, `Catalog`, `Inventory`, `Order`, `Payment`, `Shipping`), shared domain Enums, Actions, API Resources, and Form Requests. Pure JSON REST API without Blade or views.

**Tech Stack:** PHP 8.3, Laravel 11/12, MySQL 8 InnoDB (`beliin`), Laravel Sanctum (Bearer Token), PHPUnit.

**Spec:**
- `c:\laragon\www\Beliin\docs\PRD.md`
- `c:\laragon\www\Beliin\docs\ERD.md`
- `c:\laragon\www\Beliin\docs\API-CONTRACT.md`

## Global Constraints

- **Strict REST API Only:** Zero Blade templates, zero web-rendered HTML views. All endpoints reside under `/api/v1`.
- **Decoupled & Mobile-Ready:** Pure stateless Bearer token authentication via Laravel Sanctum. Standard JSON response envelope (`success`, `message`, `data`, `meta`, `error`). ISO 8601 UTC timestamps, integer IDR amounts.
- **Strict Status Separation:**
  - `orders.order_status` (`pending_payment`, `processing`, `completed`, `cancelled`)
  - `seller_orders.seller_status` (`pending`, `accepted`, `processing`, `ready_to_ship`, `shipped`, `delivered`, `completed`, `cancelled`)
  - `payments.status` (`pending`, `paid`, `failed`, `expired`, `refunded`)
  - `shipments.status` (`pending_pickup`, `picked_up`, `in_transit`, `out_for_delivery`, `delivered`, `failed`, `returned`)
  - Never combine or conflate these into a single status column.
- **Atomic Inventory & Hybrid Stock Reservation:** 15-minute stock reservation with `expires_at` created at checkout. Database transactions with `lockForUpdate()` to guarantee no overselling under concurrent load.
- **Idempotency & Snapshots:** Snapshot price, variant names, SKU, shipping address, and commission at time of order creation. Idempotent webhook processing.

## Review Focus

1. **Overselling race condition:** Simultaneous checkout attempts on a variant with stock = 1 must result in exactly 1 successful reservation and 1 rejected (409 Conflict / 422 Out of stock).
2. **Reservation expiration release:** Expired reservations must release reserved stock back to available quantity.
3. **Multi-seller order split:** Checkout containing items from multiple stores creates exactly 1 parent `order` and $N$ `seller_orders` with partitioned subtotal and shipping costs.
4. **Status isolation:** Updating payment or shipment status must update only their respective lifecycle tables and trigger decoupled events/actions, never overwriting other status fields directly.
5. **Webhook idempotency:** Replaying an identical payment transaction event must not duplicate financial ledger entries or re-trigger order transitions.

---

### Task 1: Project Scaffolding & Modular Monolith Setup

**Files:**
- Create/Configure: `composer.json`, `.env`, `config/sanctum.php`, `config/cors.php`, `app/Traits/ApiResponse.php`
- Modify: `bootstrap/app.php`, `routes/api.php`
- Test: `tests/Feature/HealthCheckTest.php`

**Interfaces:**
- Produces: Base API Response trait (`successResponse`, `errorResponse`), API route prefix `/api/v1`, Database connection to MySQL `beliin`.

- [ ] **Step 1: Write failing health check test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Scaffold Laravel & configure `.env` (DB `beliin`, Sanctum, JSON API routing)**
- [ ] **Step 4: Implement `ApiResponse` trait and register `/api/v1/health`**
- [ ] **Step 5: Run test to verify it passes**
- [ ] **Step 6: Commit**

---

### Task 2: Authentication & User Management Module

**Files:**
- Create:
  - `app/Modules/Auth/Models/User.php`
  - `app/Modules/Auth/Models/UserAddress.php`
  - `app/Modules/Auth/Models/Role.php`
  - `app/Modules/Auth/Models/Permission.php`
  - `app/Modules/Auth/Http/Controllers/AuthController.php`
  - `app/Modules/Auth/Http/Controllers/AddressController.php`
  - `app/Modules/Auth/Http/Requests/RegisterRequest.php`
  - `app/Modules/Auth/Http/Requests/LoginRequest.php`
  - `app/Modules/Auth/Http/Requests/AddressRequest.php`
  - `app/Modules/Auth/Http/Resources/UserResource.php`
  - `app/Modules/Auth/Http/Resources/AddressResource.php`
  - `database/migrations/*_create_users_table.php`
  - `database/migrations/*_create_roles_permissions_tables.php`
  - `database/migrations/*_create_user_addresses_table.php`
- Test: `tests/Feature/AuthTest.php`, `tests/Feature/AddressTest.php`

**Interfaces:**
- Consumes: `ApiResponse` trait, Sanctum HasApiTokens.
- Produces: Bearer token auth, authenticated buyer/seller user identity, shipping address management.

- [ ] **Step 1: Write failing Auth tests (register, login, me, logout, address CRUD)**
- [ ] **Step 2: Run tests to verify failure**
- [ ] **Step 3: Implement migrations, Models, Enums, Requests, Resources, and Controllers**
- [ ] **Step 4: Register `/api/v1/auth/*` and `/api/v1/addresses/*` routes**
- [ ] **Step 5: Run tests to verify all pass**
- [ ] **Step 6: Commit**

---

### Task 3: Seller & Store Onboarding Module

**Files:**
- Create:
  - `app/Modules/Seller/Enums/SellerStatus.php`
  - `app/Modules/Seller/Models/Seller.php`
  - `app/Modules/Seller/Models/Store.php`
  - `app/Modules/Seller/Models/SellerVerification.php`
  - `app/Modules/Seller/Http/Controllers/SellerApplicationController.php`
  - `app/Modules/Seller/Http/Controllers/SellerStoreController.php`
  - `app/Modules/Seller/Http/Controllers/AdminSellerReviewController.php`
  - `app/Modules/Seller/Http/Requests/SellerApplicationRequest.php`
  - `app/Modules/Seller/Http/Requests/StoreUpdateRequest.php`
  - `app/Modules/Seller/Http/Resources/SellerResource.php`
  - `app/Modules/Seller/Http/Resources/StoreResource.php`
  - `database/migrations/*_create_sellers_table.php`
  - `database/migrations/*_create_stores_table.php`
  - `database/migrations/*_create_seller_verifications_table.php`
- Test: `tests/Feature/SellerOnboardingTest.php`

**Interfaces:**
- Consumes: `User` model, Auth middleware.
- Produces: `Store` identity for products and multi-seller fulfillment.

- [ ] **Step 1: Write failing Seller onboarding and store management tests**
- [ ] **Step 2: Run tests to verify failure**
- [ ] **Step 3: Implement migrations, Enums, Models, Policies, Requests, Resources, and Controllers**
- [ ] **Step 4: Register `/api/v1/seller/*` and `/api/v1/admin/seller-applications/*` routes**
- [ ] **Step 5: Run tests to verify all pass**
- [ ] **Step 6: Commit**

---

### Task 4: Catalog Module (Categories, Products, Multi-Variants, Images)

**Files:**
- Create:
  - `app/Modules/Catalog/Models/Category.php`
  - `app/Modules/Catalog/Models/Product.php`
  - `app/Modules/Catalog/Models/ProductVariant.php`
  - `app/Modules/Catalog/Models/ProductImage.php`
  - `app/Modules/Catalog/Http/Controllers/PublicCatalogController.php`
  - `app/Modules/Catalog/Http/Controllers/SellerProductController.php`
  - `app/Modules/Catalog/Http/Requests/StoreProductRequest.php`
  - `app/Modules/Catalog/Http/Requests/UpdateProductRequest.php`
  - `app/Modules/Catalog/Http/Resources/CategoryResource.php`
  - `app/Modules/Catalog/Http/Resources/ProductResource.php`
  - `app/Modules/Catalog/Http/Resources/ProductDetailResource.php`
  - `database/migrations/*_create_catalog_tables.php`
- Test: `tests/Feature/CatalogTest.php`

**Interfaces:**
- Consumes: `Store` model.
- Produces: Active product variants with SKU, dimensions, weight, price, and media for inventory and checkout.

- [ ] **Step 1: Write failing Catalog tests (public browse/filter, seller CRUD for product/variants)**
- [ ] **Step 2: Run tests to verify failure**
- [ ] **Step 3: Implement migrations, Models, Requests, Resources, Controllers**
- [ ] **Step 4: Register `/api/v1/categories/*`, `/api/v1/products/*`, and `/api/v1/seller/products/*` routes**
- [ ] **Step 5: Run tests to verify all pass**
- [ ] **Step 6: Commit**

---

### Task 5: Inventory & Atomic Stock Reservation Module

**Files:**
- Create:
  - `app/Modules/Inventory/Models/Inventory.php`
  - `app/Modules/Inventory/Models/InventoryReservation.php`
  - `app/Modules/Inventory/Models/InventoryMovement.php`
  - `app/Modules/Inventory/Services/InventoryService.php`
  - `app/Modules/Inventory/Http/Controllers/SellerInventoryController.php`
  - `app/Modules/Inventory/Http/Requests/StockAdjustmentRequest.php`
  - `app/Modules/Inventory/Http/Resources/InventoryResource.php`
  - `database/migrations/*_create_inventory_tables.php`
- Test: `tests/Feature/InventoryTest.php`

**Interfaces:**
- Consumes: `ProductVariant` model.
- Produces:
  - `reserveStock(int $variantId, int $quantity, string $checkoutRef, int $durationMinutes = 15): InventoryReservation`
  - `commitReservation(InventoryReservation $reservation, int $orderItemId): void`
  - `releaseReservation(InventoryReservation $reservation): void`
  - `adjustStock(int $variantId, int $delta, string $reason, int $actorId): Inventory`

- [ ] **Step 1: Write failing Inventory tests (stock creation, atomic reservation, overselling prevention, release)**
- [ ] **Step 2: Run tests to verify failure**
- [ ] **Step 3: Implement migrations, Models, Services with `lockForUpdate()`, Controllers**
- [ ] **Step 4: Register `/api/v1/seller/inventory/*` routes**
- [ ] **Step 5: Run tests to verify all pass**
- [ ] **Step 6: Commit**

---

### Task 6: Cart & Multi-Seller Checkout Module

**Files:**
- Create:
  - `app/Modules/Order/Enums/OrderStatus.php`
  - `app/Modules/Order/Enums/SellerOrderStatus.php`
  - `app/Modules/Order/Models/Cart.php`
  - `app/Modules/Order/Models/CartItem.php`
  - `app/Modules/Order/Models/Order.php`
  - `app/Modules/Order/Models/SellerOrder.php`
  - `app/Modules/Order/Models/OrderItem.php`
  - `app/Modules/Order/Services/CheckoutService.php`
  - `app/Modules/Order/Http/Controllers/CartController.php`
  - `app/Modules/Order/Http/Controllers/CheckoutController.php`
  - `app/Modules/Order/Http/Controllers/OrderController.php`
  - `app/Modules/Order/Http/Controllers/SellerOrderController.php`
  - `app/Modules/Order/Http/Requests/AddToCartRequest.php`
  - `app/Modules/Order/Http/Requests/CheckoutRequest.php`
  - `app/Modules/Order/Http/Resources/OrderResource.php`
  - `app/Modules/Order/Http/Resources/SellerOrderResource.php`
  - `database/migrations/*_create_orders_and_carts_tables.php`
- Test: `tests/Feature/CheckoutTest.php`

**Interfaces:**
- Consumes: `User`, `UserAddress`, `Store`, `ProductVariant`, `InventoryService`.
- Produces: Parent `Order` with `order_status = pending_payment`, child `SellerOrder`s with `seller_status = pending`, snapshots, and active stock reservations.

- [ ] **Step 1: Write failing Cart & Multi-Seller Checkout tests (cart management, multi-store order split, snapshot verification)**
- [ ] **Step 2: Run tests to verify failure**
- [ ] **Step 3: Implement migrations, Enums, Models, Services, Controllers**
- [ ] **Step 4: Register `/api/v1/cart/*`, `/api/v1/checkout/*`, `/api/v1/orders/*`, and `/api/v1/seller/orders/*` routes**
- [ ] **Step 5: Run tests to verify all pass**
- [ ] **Step 6: Commit**

---

### Task 7: Payment Module & Webhook Processing

**Files:**
- Create:
  - `app/Modules/Payment/Enums/PaymentStatus.php`
  - `app/Modules/Payment/Models/Payment.php`
  - `app/Modules/Payment/Models/PaymentTransaction.php`
  - `app/Modules/Payment/Models/FinancialLedgerEntry.php`
  - `app/Modules/Payment/Contracts/PaymentGatewayInterface.php`
  - `app/Modules/Payment/Services/PaymentService.php`
  - `app/Modules/Payment/Services/SimulatedPaymentGateway.php`
  - `app/Modules/Payment/Http/Controllers/PaymentController.php`
  - `app/Modules/Payment/Http/Controllers/PaymentWebhookController.php`
  - `app/Modules/Payment/Http/Resources/PaymentResource.php`
  - `database/migrations/*_create_payments_and_ledger_tables.php`
- Test: `tests/Feature/PaymentTest.php`

**Interfaces:**
- Consumes: Parent `Order` model, `InventoryService` commit/release.
- Produces:
  - `payments.status` lifecycle (`pending` -> `paid`/`failed`/`expired`)
  - Order progression upon paid (`order_status = processing`, seller_orders advance to `accepted`, inventory committed)
  - Append-only `FinancialLedgerEntry`

- [ ] **Step 1: Write failing Payment and Webhook tests (payment initiation, signature check, idempotent webhook, ledger entries)**
- [ ] **Step 2: Run tests to verify failure**
- [ ] **Step 3: Implement migrations, Enums, Models, Gateway contract & simulator, Webhook handler**
- [ ] **Step 4: Register `/api/v1/payments/*` and `/api/v1/webhooks/payment/{provider}` routes**
- [ ] **Step 5: Run tests to verify all pass**
- [ ] **Step 6: Commit**

---

### Task 8: Shipping & AnterIn Logistics Module

**Files:**
- Create:
  - `app/Modules/Shipping/Enums/ShipmentStatus.php`
  - `app/Modules/Shipping/Models/ShippingProvider.php`
  - `app/Modules/Shipping/Models/ShippingService.php`
  - `app/Modules/Shipping/Models/Shipment.php`
  - `app/Modules/Shipping/Models/ShipmentTrackingEvent.php`
  - `app/Modules/Shipping/Contracts/ShippingProviderInterface.php`
  - `app/Modules/Shipping/Services/AnterInShippingProvider.php`
  - `app/Modules/Shipping/Services/ShippingManager.php`
  - `app/Modules/Shipping/Http/Controllers/ShippingRateController.php`
  - `app/Modules/Shipping/Http/Controllers/ShipmentController.php`
  - `app/Modules/Shipping/Http/Resources/ShipmentResource.php`
  - `database/migrations/*_create_shipping_tables.php`
- Test: `tests/Feature/ShippingTest.php`

**Interfaces:**
- Consumes: `SellerOrder`, store origin address, buyer destination address.
- Produces: `shipments.status` lifecycle, tracking events timeline, rate calculation.

- [ ] **Step 1: Write failing Shipping tests (rate quotes, shipment creation, tracking update, status transition)**
- [ ] **Step 2: Run tests to verify failure**
- [ ] **Step 3: Implement migrations, Enums, Models, AnterIn provider, Controllers**
- [ ] **Step 4: Register `/api/v1/shipping/*` and `/api/v1/seller/orders/{id}/ship` routes**
- [ ] **Step 5: Run tests to verify all pass**
- [ ] **Step 6: Commit**

---

### Task 9: Seeders, Verification & Integration Verification

**Files:**
- Create:
  - `database/seeders/DatabaseSeeder.php`
  - `database/seeders/RolePermissionSeeder.php`
  - `database/seeders/ShippingProviderSeeder.php`
  - `database/seeders/CategorySeeder.php`
  - `database/seeders/DemoMarketplaceSeeder.php`
- Test: `tests/Feature/EndToEndPurchaseFlowTest.php`

- [ ] **Step 1: Write End-to-End flow test (Register -> Store Setup -> Catalog -> Cart -> Checkout -> Payment -> Shipping Tracking)**
- [ ] **Step 2: Implement seeders with dummy logos and demo data**
- [ ] **Step 3: Run full test suite and confirm 100% PASS**
- [ ] **Step 4: Commit and finalize**
