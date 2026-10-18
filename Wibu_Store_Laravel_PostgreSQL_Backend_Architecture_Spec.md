# WIBU STORE — BACKEND ARCHITECTURE & IMPLEMENTATION SPECIFICATION

> **Version:** 1.0  
> **Updated:** 2026-10-17  
> **Status:** Implementation-ready specification (decisions, contracts and acceptance criteria; not application source code)  
> **Project:** Wibu Store — Anime Figure & Collectibles E-commerce  
> **Backend:** Laravel 13 · PHP 8.3+ · PostgreSQL · REST API  
> **Frontend consumers:** Astro Storefront + Astro Admin, both using Tailwind CSS 4

---

## 1. Mục tiêu và phạm vi

### 1.1 Mục tiêu

Thiết kế backend duy nhất phục vụ hai ứng dụng riêng biệt:

- **Storefront** (`http://localhost:4321`): xem figure, lọc sản phẩm, giỏ hàng, đặt mua, đặt trước, tra cứu đơn.
- **Admin** (`http://localhost:4322`): quản lý catalog, media, tồn kho, đơn hàng, pre-order, khách hàng, khuyến mãi, CMS, báo cáo và phân quyền.
- **API** (`http://localhost:8000`): quản lý nghiệp vụ, xác thực, chính sách truy cập, đồng bộ trạng thái và cung cấp hợp đồng dữ liệu cho hai frontend.

Hệ thống phải bảo toàn dữ liệu giao dịch, ngăn bán vượt tồn kho và hỗ trợ figure có nhiều biến thể (scale, edition, màu, SKU).

### 1.2 Phạm vi MVP (P0)

1. Catalog: sản phẩm, biến thể, ảnh, anime series, danh mục, nhà sản xuất.
2. Tìm kiếm, lọc, sắp xếp, phân trang.
3. Đăng ký/đăng nhập khách hàng; đăng nhập Admin; RBAC.
4. Giỏ hàng, checkout, đơn hàng và lịch sử trạng thái.
5. Tồn kho theo SKU, reservation và inventory movement ledger.
6. Pre-order có quota, tiền cọc, ngày dự kiến và phần thanh toán còn lại.
7. Thanh toán COD/chuyển khoản thủ công có xác nhận của nhân viên; kiến trúc sẵn sàng tích hợp payment gateway.
8. Quản lý hình ảnh trên S3-compatible storage.
9. Homepage CMS: Hero/Comic Cover, collection, category blocks, campaign và thứ tự sections.
10. Audit log cho các thao tác nhạy cảm và gửi email giao dịch qua queue.

### 1.3 Giai đoạn sau (P1/P2)

- Payment gateway với webhook và refund tự động.
- Coupon nâng cao, loyalty, wishlist, đánh giá, notification center.
- Đồng bộ vận chuyển, nhãn vận đơn, đối soát thanh toán.
- Full-text search chuyên dụng, inventory đa kho nâng cao, dashboard nâng cao.
- 2FA/MFA Admin, product import/export theo batch, phân quyền chi tiết hơn.

### 1.4 Ngoài phạm vi MVP

Marketplace đa người bán, đa tiền tệ, multi-tenant, livestream shopping, realtime chat, microservices và nền tảng thanh toán tự phát triển.

---

## 2. Quyết định kỹ thuật

| Hạng mục | Quyết định | Ghi chú |
|---|---|---|
| Architecture | **Modular Monolith** | Một Laravel API, tổ chức module theo nghiệp vụ |
| Runtime | **PHP 8.3+** | Yêu cầu tối thiểu theo Laravel 13 |
| Framework | **Laravel 13** | Pin major version và dependencies |
| Database | **PostgreSQL** | Nguồn dữ liệu có tính nhất quán duy nhất |
| ORM | **Eloquent** | Models, Relations, Scopes, Migrations |
| API | **REST, `/api/v1`** | JSON over HTTPS, API versioning |
| Auth first-party | **Laravel Sanctum session/cookie** | Không lưu Bearer token dài hạn trong localStorage |
| Authorization | **Policies + Roles/Permissions** | Default-deny cho API Admin |
| Cache | **Redis (khuyến nghị)** | Cache/session/locks/rate-limits; MVP có thể dùng database driver |
| Background Jobs | **Laravel Queue** | Database driver ban đầu; Redis khi cần scale |
| Object Storage | **S3-compatible** | MinIO ở dev, S3/R2/Wasabi tương thích ở prod |
| Search | **PostgreSQL indexes + search** | Nâng cấp nếu catalog đủ lớn |
| API docs | **OpenAPI 3.1** | Có version, schema, examples, errors |
| Tests | **Pest hoặc PHPUnit** | Feature, integration, concurrency |
| Developer environment | **Docker Compose** | API, PostgreSQL, Redis, MinIO, worker, scheduler |
| Deployment | **Nginx/FrankenPHP + PHP runtime** | App, worker và scheduler tách process |

**Quy tắc:** Không tạo hai backend riêng cho Storefront và Admin. Các API cùng dùng một business service layer, nhưng phân quyền và nhóm routes khác nhau.

---

## 3. Kiến trúc tổng quan

```text
                    ┌───────────────────────────────────┐
                    │         CLIENT APPLICATIONS       │
                    │                                   │
                    │ Storefront Astro :4321            │
                    │ Admin Astro      :4322            │
                    └───────────────┬───────────────────┘
                                    │ HTTPS / JSON
                                    ▼
                       ┌────────────────────────┐
                       │ Laravel API :8000      │
                       │ /api/v1                │
                       ├────────────────────────┤
                       │ Middleware             │
                       │ Auth / CSRF / RBAC     │
                       │ Validation / RateLimit │
                       ├────────────────────────┤
                       │ Domain Services        │
                       │ Catalog / Inventory    │
                       │ Orders / Pre-orders    │
                       │ Payments / CMS / Media │
                       └───┬────────┬────────┬──┘
                           │        │        │
                    ┌──────▼───┐ ┌──▼────┐ ┌─▼────────────┐
                    │PostgreSQL│ │ Redis │ │ S3-compatible│
                    └──────────┘ └───────┘ └──────────────┘
                           ▲        ▲
                           │        │
                           └── Laravel Queue Workers ──► Email/Webhooks
```

### 3.1 Layering

1. **HTTP:** routes, controllers, form requests, API resources, middleware.
2. **Application:** use cases, command handlers, DTOs, transaction boundaries.
3. **Domain:** business rules, statuses/state transitions, business exceptions.
4. **Persistence/Integrations:** Eloquent models, query objects, storage adapters, mail, payment provider adapters.

**Không để** toàn bộ logic checkout, cập nhật tồn kho, thanh toán trong controller hoặc Eloquent observers. Controller chỉ điều phối HTTP và gọi application service.

### 3.2 Giao tiếp frontend

- Trang public có thể được Astro render tĩnh hoặc SSR rồi lấy dữ liệu từ public API.
- Trang tài khoản và Admin phải bảo vệ cả **trang SSR** lẫn **API endpoint**.
- Browser có thể gọi API bằng credentialed requests (Sanctum), sau khi cấu hình chính xác CORS/CSRF/session.
- Nếu Astro SSR gọi API thay browser: forward thông tin xác thực có kiểm soát, không log cookies, không cache dữ liệu cá nhân chung.
- Không đưa S3 credentials, DB credentials hoặc APP_KEY vào biến môi trường public của Astro.

---

## 4. Repository và cấu trúc backend

### 4.1 Cấu trúc mono-repo khuyến nghị

```text
wibu-store/
├── apps/
│   ├── storefront/                  # Astro :4321
│   └── admin/                       # Astro :4322
├── services/
│   └── api/                         # Laravel :8000
│       ├── app/
│       │   ├── Domain/
│       │   │   ├── Identity/
│       │   │   ├── Catalog/
│       │   │   ├── Inventory/
│       │   │   ├── Cart/
│       │   │   ├── Orders/
│       │   │   ├── Preorders/
│       │   │   ├── Payments/
│       │   │   ├── Promotions/
│       │   │   ├── Cms/
│       │   │   ├── Media/
│       │   │   └── Reporting/
│       │   ├── Http/
│       │   │   ├── Controllers/Api/V1/Public/
│       │   │   ├── Controllers/Api/V1/Customer/
│       │   │   ├── Controllers/Api/V1/Admin/
│       │   │   ├── Requests/
│       │   │   ├── Resources/
│       │   │   └── Middleware/
│       │   ├── Jobs/
│       │   ├── Policies/
│       │   └── Support/
│       ├── bootstrap/
│       ├── config/
│       ├── database/migrations/
│       ├── database/seeders/
│       ├── routes/api.php
│       ├── tests/Feature/
│       ├── tests/Unit/
│       └── .env.example
├── infra/
│   ├── compose.yaml
│   ├── docker/
│   └── nginx/
├── docs/
│   ├── api/openapi.yaml
│   ├── database/
│   └── architecture/
└── README.md
```

Có thể triển khai thành 3 repositories nếu quy trình của nhóm yêu cầu; hợp đồng API và cấu trúc module vẫn giữ nguyên.

### 4.2 Quy ước tổ chức module

Trong từng `Domain/<Module>` có thể có `Actions/`, `Services/`, `Models/`, `Enums/`, `DTOs/`, `Events/`, `Exceptions/` và `Queries/` tùy độ phức tạp. Không ép mọi module phải có tất cả các lớp.

- `Catalog`: cấu hình sản phẩm, SKU, phân loại, xuất bản.
- `Inventory`: số lượng vật lý, đặt giữ hàng, điều chỉnh kho, sổ phát sinh.
- `Orders`: checkout, order snapshots, shipping/fulfillment state.
- `Preorders`: chiến dịch đặt trước, quota, cọc, yêu cầu trả phần còn lại.
- `Payments`: payment intent/records, xác nhận, webhook/idempotency.
- `Cms`: nội dung publish theo revision.
- `Media`: upload, validate, file metadata, image variants.

---

## 5. Database conventions — PostgreSQL

### 5.1 Quy tắc chung

- Internal PK: `bigint` tự tăng cho quan hệ nội bộ.
- Public identifiers: UUID hoặc ULID không đoán được, tách khỏi PK nội bộ. Order có thêm `order_number` duy nhất, dễ đọc.
- Foreign keys phải có index phù hợp; quy tắc xóa rõ ràng (`RESTRICT`, `SET NULL` hoặc soft-delete).
- Timestamps: `created_at`, `updated_at` (`timestamptz`) và `deleted_at` khi cần soft-delete.
- Tiền Việt Nam: lưu **integer/bigint đơn vị VND**, ví dụ `8900000`; **không dùng float/double** để lưu tiền. Nếu mở đa tiền tệ, bổ sung currency code và minor-unit policy.
- Trạng thái: dùng `varchar` + validation + enum ở application layer (hoặc CHECK constraint khi ổn định).
- Thuộc tính linh hoạt: `jsonb`; không dùng JSONB thay cho cột tiền, tồn kho, khóa ngoại, SKU.
- Số lượng: `integer >= 0`, ràng buộc CHECK tại DB.
- `slug`, `sku`, `order_number`, external payment reference cần uniqueness đúng scope.
- Dữ liệu giao dịch: **không hard-delete** orders, payments, inventory movements, audit logs.
- Thay đổi schema thông qua migrations; seed demo tách seed production.

### 5.2 Danh mục bảng và quan hệ chủ đạo

| Module | Tables | Quan hệ cốt lõi |
|---|---|---|
| Identity | `users`, `roles`, `permissions`, `role_user`, `permission_role`, `user_addresses` | User nhiều roles, roles nhiều permissions |
| Catalog | `anime_series`, `manufacturers`, `categories`, `products`, `product_categories`, `product_variants` | Product thuộc series/brand, có nhiều SKU |
| Media | `media_assets`, `product_media` | Asset gắn product/variant và CMS |
| Inventory | `warehouses`, `inventory_levels`, `inventory_movements` | Stock theo `(warehouse_id, variant_id)` |
| Cart | `carts`, `cart_items` | User/guest có cart, chứa SKU |
| Orders | `orders`, `order_items`, `order_status_history`, `order_addresses` | Snapshot tại thời điểm đặt |
| Payments | `payments`, `payment_events` | Payment records theo order, events idempotent |
| Preorder | `preorder_campaigns`, `preorder_reservations` | Campaign gắn variant, reservation gắn order item |
| Promotion | `coupons`, `coupon_redemptions` | Coupon theo scope/điều kiện |
| CMS | `cms_pages`, `cms_page_revisions` | Draft/review/published revisions |
| Platform | `activity_logs`, `settings`, `jobs`, `failed_jobs` | Audit, runtime, operations |

### 5.3 Đặc tả các bảng catalog

**`anime_series`**: `id`, `name`, `slug UNIQUE`, `cover_media_id NULL`, `description`, `is_active`, timestamps.

**`manufacturers`**: `id`, `name`, `slug UNIQUE`, `website_url NULL`, `logo_media_id NULL`, timestamps.

**`categories`**: `id`, `parent_id NULL`, `name`, `slug UNIQUE`, `sort_order`, `is_active`, timestamps. Chống cycle trong cây danh mục tại service layer.

**`products`**:

| Column | Type | Quy định |
|---|---|---|
| `id` | bigint PK | Internal |
| `public_id` | uuid UNIQUE | ID public |
| `name` | varchar | Required |
| `slug` | varchar UNIQUE | Stable URL; thay đổi phải có redirect strategy |
| `anime_series_id` | FK nullable | Series |
| `manufacturer_id` | FK nullable | Hãng |
| `short_description` | text nullable | Mô tả ngắn |
| `description` | text nullable | Nội dung đã sanitize |
| `attributes` | jsonb | Đặc trưng riêng của figure |
| `status` | varchar | `draft / active / archived` |
| `seo_title`, `seo_description` | varchar nullable | SEO |
| `published_at` | timestamptz nullable | Publish |
| `created_at`, `updated_at`, `deleted_at` | timestamptz | Audit |

**`product_variants`**:

| Column | Type | Quy định |
|---|---|---|
| `id` | bigint PK | Internal |
| `product_id` | FK | Cascade không áp dụng khi có order refs |
| `sku` | varchar UNIQUE | SKU bất biến sau khi bán nếu có history |
| `label` | varchar | Ví dụ `1/7 Standard` |
| `attributes` | jsonb | Edition, version, màu |
| `price_amount` | bigint | `>= 0`, VND |
| `compare_at_amount` | bigint nullable | `>= price_amount` khi áp dụng |
| `weight_grams` | integer nullable | Logistics |
| `track_inventory` | boolean | Theo dõi kho |
| `status` | varchar | `active / inactive` |
| `created_at`, `updated_at` | timestamptz | Audit |

**`product_categories`**: composite unique `(product_id, category_id)`.

**Các attributes tham khảo:** `scale`, `height_mm`, `material`, `edition`, `official_license`, `included_accessories`. Cần JSON schema/validation per product type ở application layer.

### 5.4 Media & inventory schema

**`media_assets`**: `id`, `object_key UNIQUE`, `disk`, `mime_type`, `file_size_bytes`, `width`, `height`, `checksum`, `alt_text`, `visibility` (`public/private`), `status` (`pending/ready/failed`), `uploaded_by`, timestamps.

**`product_media`**: `id`, `product_id`, `variant_id NULL`, `media_asset_id`, `sort_order`, `is_primary`; unique theo asset/scope và rule mỗi scope tối đa một primary.

**`warehouses`**: `id`, `code UNIQUE`, `name`, `is_active`.

**`inventory_levels`**: `id`, `warehouse_id`, `variant_id`, `on_hand`, `reserved`, `updated_at`, UNIQUE `(warehouse_id, variant_id)`, CHECK `on_hand >= 0`, `reserved >= 0`, `reserved <= on_hand`.

**`inventory_movements`**: `id`, `warehouse_id`, `variant_id`, `delta_on_hand`, `delta_reserved`, `type`, `reason`, `reference_type`, `reference_id`, `idempotency_key NULL`, `actor_user_id NULL`, `created_at`; immutable append-only. Cần unique key theo thao tác nghiệp vụ để chống thực thi hai lần.

**Định nghĩa khả dụng:** `available = on_hand - reserved`. Đặt mua thành công tăng `reserved`; khi xuất hàng giảm **cả** `reserved` và `on_hand`; khi hủy trước xuất hàng chỉ giảm `reserved`.

### 5.5 Cart, orders và payments schema

**`carts`**: `id`, `user_id NULL`, `guest_token_hash NULL`, `cart_type` (`regular/preorder`), `expires_at`, timestamps. Không để token khách vãng lai ở dạng plaintext trong DB.

**`cart_items`**: `id`, `cart_id`, `variant_id`, `preorder_campaign_id NULL`, `quantity`, timestamps; unique theo cart + variant + campaign.

**`orders`**:

| Column | Type | Quy định |
|---|---|---|
| `id` | bigint PK | Internal |
| `public_id` | uuid UNIQUE | Trả cho client |
| `order_number` | varchar UNIQUE | Ví dụ `WB-20261017-000123` |
| `user_id` | FK nullable | Guest checkout khả dụng |
| `checkout_type` | varchar | `regular / preorder` |
| `customer_name`, `email`, `phone` | varchar | Snapshot đặt hàng |
| `order_status` | varchar | Theo state machine |
| `payment_status` | varchar | Theo tổng tiền thực nhận |
| `fulfillment_status` | varchar | Trạng thái giao hàng riêng |
| `subtotal_amount` | bigint | Snapshot |
| `discount_amount` | bigint | Snapshot |
| `shipping_amount` | bigint | Snapshot |
| `total_amount` | bigint | Không âm |
| `paid_amount` | bigint | Có thể derive/cached từ payments; phải reconcile |
| `currency` | char(3) | Mặc định `VND` |
| `placed_at`, `expires_at` | timestamptz nullable | Lifecycle |
| `customer_note`, `internal_note` | text nullable | Internal không public |
| `created_at`, `updated_at` | timestamptz | Audit |

**`order_items`**: `id`, `order_id`, `variant_id NULL`, `preorder_campaign_id NULL`, `sku_snapshot`, `product_name_snapshot`, `variant_label_snapshot`, `unit_price_amount`, `quantity`, `line_total_amount`, `created_at`. Snapshot giữ dữ liệu ổn định dù product đổi tên/giá hoặc bị archive.

**`order_addresses`**: `order_id`, `type` (`shipping/billing`), `recipient_name`, `phone`, `line1`, `line2`, `ward`, `district`, `province`, `country`, `postal_code`; snapshot bất biến sau đặt hàng trừ thao tác chỉnh sửa có audit.

**`order_status_history`**: `order_id`, `from_status`, `to_status`, `reason`, `actor_user_id NULL`, `created_at`.

**`payments`**: `id`, `order_id`, `kind` (`full/deposit/balance/refund`), `method` (`cod/bank_transfer/gateway`), `provider NULL`, `provider_reference NULL`, `amount`, `status` (`pending/confirmed/failed/refunded`), `confirmed_at NULL`, `confirmed_by NULL`, `idempotency_key NULL`, timestamps. Unique index thích hợp theo `(provider, provider_reference)` khi giá trị khác NULL.

**`payment_events`**: `id`, `provider`, `event_id`, `payment_id NULL`, `payload_redacted` jsonb, `processing_status`, timestamps, UNIQUE `(provider, event_id)`.

**Đối soát:** Chỉ cộng `paid_amount` từ payments đã thực nhận/xác nhận (điều chỉnh refunds theo ledger). Không dùng `payment_status = paid` chỉ vì khách đã mở trang thành công của payment gateway.

### 5.6 Pre-order schema

**`preorder_campaigns`**:

- `id`, `variant_id`, `status` (`draft/open/closed/arrived/cancelled`).
- `opens_at`, `closes_at`, `expected_release_at`, `expected_arrival_at` (có thể NULL khi chưa chắc chắn).
- `quota` (`integer >= 0`), `reserved_quantity` (`0 <= reserved_quantity <= quota`).
- `deposit_per_unit_amount` (VND), `sale_price_per_unit_amount` (VND); CHECK `deposit <= sale_price`.
- `balance_due_days`, `terms_revision_id NULL`, `created_at`, `updated_at`.

**`preorder_reservations`**:

- `id`, `campaign_id`, `order_item_id`, `quantity`, `status` (`awaiting_deposit/deposit_paid/balance_due/completed/expired/cancelled`).
- `deposit_required_amount`, `balance_due_amount`, `deposit_deadline_at`, `balance_deadline_at NULL`.
- `deposit_paid_at NULL`, `created_at`, `updated_at`.

**Quy tắc:** Đặt trước dùng **quota của campaign**, không làm giảm `inventory_levels.on_hand`. Khi hàng thực về kho, mới ghi inventory receipt, sau đó kết nối fulfillment/reservation chính thức theo chính sách bán hàng. Không được tính quota pre-order là hàng sẵn có.

### 5.7 CMS, promotions và audit

**`cms_pages`**: `id`, `slug UNIQUE`, `title`, `current_published_revision_id NULL`, timestamps.

**`cms_page_revisions`**: `id`, `page_id`, `version_number`, `status` (`draft/in_review/published/archived`), `content` jsonb, `created_by`, `published_by NULL`, `published_at NULL`, timestamps, UNIQUE `(page_id, version_number)`.

Trang home có `content` được validate theo schema version, hỗ trợ 9 sections của storefront: `hero_comic`, `featured_categories`, `trending`, `featured_collection`, `preorder_spotlight`, `shop_by_anime`, `new_arrivals`, `collector_corner`, `footer_promo`.

**`coupons`**: `code UNIQUE`, `discount_type`, `discount_value`, `starts_at`, `ends_at`, `usage_limit`, `per_user_limit`, `is_active`, rules jsonb.

**`coupon_redemptions`**: `coupon_id`, `order_id`, `user_id NULL`, `discount_amount`, `created_at`; unique theo `(coupon_id, order_id)`.

**`activity_logs`**: `actor_user_id`, `action`, `subject_type`, `subject_id`, `before_redacted` jsonb, `after_redacted` jsonb, `request_id`, `ip_address` (retention policy), `created_at`. Tránh log bí mật và dữ liệu thanh toán nhạy cảm.

### 5.8 Index và hiệu năng database

Bắt buộc index theo luồng truy vấn thực tế:

- `products(status, published_at)`, `products(anime_series_id)`, `products(manufacturer_id)`.
- `product_variants(sku)` UNIQUE, `product_variants(product_id, status)`.
- `orders(order_number)` UNIQUE, `orders(user_id, created_at DESC)`, `orders(order_status, created_at DESC)`.
- `payments(order_id, status)`; `(provider, provider_reference)` unique partial khi không NULL.
- `preorder_campaigns(status, opens_at, closes_at)`; `preorder_reservations(campaign_id, status)`.
- `inventory_levels(warehouse_id, variant_id)` UNIQUE.
- Optional `GIN` cho `products.attributes` khi cần truy vấn JSONB; `pg_trgm`/search vector cho tên sản phẩm khi đo được nhu cầu.

**Không index toàn bộ JSONB hoặc từng cột một cách máy móc.** Theo dõi execution plan trước khi mở rộng index.

---

## 6. Business rules và state machines

### 6.1 Product status

`draft → active → archived`. Chỉ sản phẩm `active`, có SKU active, giá hợp lệ và ảnh được phép hiển thị mới xuất hiện trên storefront. Product được archive vẫn phải giữ order history.

### 6.2 Order status (MVP)

| Status | Ý nghĩa | Chuyển trạng thái hợp lệ |
|---|---|---|
| `pending` | Tạo đơn, đang chờ xác nhận/thanh toán | `confirmed / cancelled / expired` |
| `confirmed` | Đơn hợp lệ, có cam kết bán | `processing / cancelled` |
| `processing` | Đang chuẩn bị | `shipped / cancelled` tùy quy tắc |
| `shipped` | Đã bàn giao vận chuyển | `delivered / return_requested` |
| `delivered` | Đã giao | `completed / return_requested` |
| `completed` | Kết thúc | Chỉ quy trình hậu mãi riêng |
| `cancelled` | Đã hủy | Terminal cho luồng thường |
| `expired` | Hết hạn giữ hàng/đặt cọc | Terminal |

`payment_status` riêng: `unpaid / partially_paid / paid / refunded / partially_refunded`.

`fulfillment_status` riêng: `unfulfilled / preparing / shipped / delivered / returned`.

Không gộp payment, order và shipping thành một enum duy nhất.

### 6.3 Quy tắc giá

- Giá tính tại **server**, theo giá SKU/active campaign/coupon tại thời điểm checkout.
- Cart preview không đảm bảo giữ giá hay tồn kho; checkout phải xác minh lại.
- Lưu snapshot giá và giảm giá vào `order_items`/`orders`.
- Phí ship và chiết khấu do backend tính; frontend chỉ hiển thị.
- Tổng tiền: `subtotal - discount + shipping`, CHECK không âm; xử lý rounding theo VND integer.

### 6.4 Quy trình mua sản phẩm có sẵn

1. Client gửi variant IDs, quantities và thông tin checkout.
2. API kiểm tra sản phẩm còn active, số lượng, phương thức thanh toán, địa chỉ.
3. Transaction lock các inventory rows theo thứ tự ổn định để giảm deadlock.
4. Kiểm tra `available >= requested`; nếu không đủ trả `409 INSUFFICIENT_STOCK`.
5. Tạo order + items snapshots + payment record (nếu cần).
6. Tăng `reserved`, ghi inventory movements cùng transaction.
7. Commit và phát hành `OrderPlaced` **sau commit** để gửi email/notification.
8. Với chuyển khoản online/thủ công: expiry policy được cấu hình; khi hết hạn và chưa thanh toán thì release reservation qua transaction idempotent.
9. Với COD: giữ reservation cho đơn đã chấp nhận; giải phóng khi hủy.
10. Khi hàng thực sự xuất kho: giảm đồng thời `reserved` và `on_hand`, ghi movement.

**Chống oversell:** API không được `read available` rồi ghi sau bên ngoài transaction/row lock. Chỉ kiểm tra giỏ hàng tại client là không đủ.

### 6.5 Quy trình Pre-order / Deposit

1. Chỉ campaign `open` trong thời gian hiệu lực mới nhận đặt trước.
2. Transaction lock campaign, kiểm tra `quota - reserved_quantity >= quantity`.
3. Tạo order type `preorder`, order item + preorder reservation, trạng thái `awaiting_deposit`.
4. Tăng `reserved_quantity`; lưu deadline đặt cọc.
5. Ghi payment loại `deposit` với tổng cọc = `deposit_per_unit × quantity`.
6. Sau khi nhận tiền hợp lệ, xác nhận `deposit_paid`, giữ quota.
7. Quá hạn cọc: chuyển `expired`, giảm quota giữ chỗ đúng **một lần**.
8. Khi hàng sắp về/đã về: Admin kích hoạt `balance_due`, gửi thông báo số tiền còn lại và hạn thanh toán.
9. Xác nhận thanh toán đủ mới chuyển sang quy trình fulfillment.
10. Refund/cancel thực hiện theo chính sách công bố tại thời điểm order; mọi thay đổi có audit.

**MVP simplification:** Một checkout chỉ gồm hàng có sẵn **hoặc** một pre-order campaign; chưa hỗ trợ trộn nhiều campaign hoặc mixed regular/preorder trong cùng order. Điều này giúp chính sách phí ship, cọc và split shipment nhất quán.

### 6.6 Inventory receiving và reconciliation

- Admin ghi phiếu nhập hàng với quantity positive, reason/reference và user identity.
- Stock adjustments phải qua dedicated endpoint, không update trực tiếp `on_hand` tự do từ bảng sản phẩm.
- Mỗi thay đổi stock có 1 movement bất biến và 1 idempotency key/reference.
- Scheduled reconciliation kiểm tra chênh lệch tổng movement và inventory_levels; báo cáo sai lệch, không âm thầm tự sửa số liệu.

### 6.7 Payment processing

- MVP hỗ trợ `cod` và `bank_transfer`, xác nhận chuyển khoản **thủ công có phân quyền**.
- Kiểm tra amount, order, reference và đối chiếu tránh double confirmation.
- Sau khi tích hợp gateway: xác thực chữ ký webhook, nguồn sự kiện, chống replay, unique provider event ID, idempotent handlers.
- **Không** đánh dấu `paid` chỉ từ redirect URL/trạng thái browser.
- Refund là thao tác riêng có payment record và audit; không xóa bản ghi thanh toán cũ.

### 6.8 Coupon/khuyến mãi

Validate trên backend: thời gian, giá trị đơn tối thiểu, giới hạn số lần, đối tượng sản phẩm, tương thích pre-order, giá trị tối đa; apply trong transaction tránh vượt usage limit.

---

## 7. API contract và versioning

### 7.1 Quy ước chung

- Base path: `/api/v1`.
- REST resource names dạng plural, lowercase, kebab-case khi cần.
- Response JSON dùng `data`, `meta`, `links`; lỗi có `error.code`, `message`, `details`, `request_id`.
- Timestamp ISO 8601 UTC; frontend tự hiển thị timezone Việt Nam.
- Giá JSON: **integer VND**, không trả string đã format làm trường dữ liệu duy nhất.
- Paginate bằng page/page_size ở MVP; page size có max giới hạn server.
- Sort/filter dùng allowlist, không truyền raw SQL/order column.
- Protected POST checkout/transactions chấp nhận `Idempotency-Key` và trả lại kết quả cũ cho retry cùng request.
- Không expose internal IDs không cần thiết, auth secrets, unpublished data, cost price hay admin notes trên public API.

### 7.2 Mẫu response chuẩn (minh họa hợp đồng, không phải code triển khai)

```json
{
  "data": {
    "id": "7dd31465-93c2-4b41-9515-9d2c83b28b65",
    "name": "Gojo Satoru Unlimited Void 1/7",
    "price_amount": 8900000,
    "currency": "VND",
    "availability": "preorder"
  },
  "meta": { "request_id": "req_example" }
}
```

**Error contract:** `400 BAD_REQUEST`, `401 UNAUTHENTICATED`, `403 FORBIDDEN`, `404 NOT_FOUND`, `409 INSUFFICIENT_STOCK / INVALID_STATE`, `422 VALIDATION_ERROR`, `429 RATE_LIMITED`, `500 INTERNAL_ERROR`. Không trả stack trace cho client.

### 7.3 Public catalog API

| Method | Endpoint | Chức năng |
|---|---|---|
| GET | `/api/v1/products` | Listing: search, series, category, price, sort, page |
| GET | `/api/v1/products/{slug}` | Chi tiết product + variants + gallery + availability |
| GET | `/api/v1/categories` | Tree/list categories |
| GET | `/api/v1/anime-series` | Lọc theo anime/franchise |
| GET | `/api/v1/manufacturers` | Hãng sản xuất |
| GET | `/api/v1/preorders` | Active campaigns |
| GET | `/api/v1/preorders/{id}` | Campaign detail/quota/status |
| GET | `/api/v1/pages/home` | CMS home published revision |
| GET | `/api/v1/collections/{slug}` | Collection được publish |
| GET | `/api/v1/health` | Health đơn giản, không lộ hạ tầng |

### 7.4 Authentication & customer API

| Method | Endpoint | Chức năng |
|---|---|---|
| GET | `/sanctum/csrf-cookie` | Khởi tạo CSRF cho session auth |
| POST | `/api/v1/auth/register` | Đăng ký |
| POST | `/api/v1/auth/login` | Đăng nhập, rotate session |
| POST | `/api/v1/auth/logout` | Logout/invalidate session |
| GET | `/api/v1/me` | Hồ sơ & permissions hữu dụng cho UI |
| PATCH | `/api/v1/me` | Sửa hồ sơ |
| GET | `/api/v1/me/orders` | Đơn hàng của user |
| GET/POST | `/api/v1/me/addresses` | Quản lý địa chỉ |
| PATCH/DELETE | `/api/v1/me/addresses/{id}` | Sửa/xóa địa chỉ |
| POST | `/api/v1/auth/forgot-password` | Gửi reset link |
| POST | `/api/v1/auth/reset-password` | Đặt lại mật khẩu |

Email verification và throttling theo chính sách, có thể yêu cầu xác minh email trước một số tác vụ có rủi ro.

### 7.5 Cart / checkout / order API

| Method | Endpoint | Chức năng |
|---|---|---|
| GET | `/api/v1/cart` | Xem current cart (user hoặc guest) |
| POST | `/api/v1/cart/items` | Thêm SKU/campaign |
| PATCH | `/api/v1/cart/items/{id}` | Cập nhật quantity |
| DELETE | `/api/v1/cart/items/{id}` | Bỏ item |
| POST | `/api/v1/checkout/preview` | Tính lại tổng tiền, phí ship, coupon, stock |
| POST | `/api/v1/orders` | Checkout, transaction + idempotency |
| GET | `/api/v1/orders/{public_id}` | Chỉ owner hoặc guest proof hợp lệ |
| GET | `/api/v1/orders/{public_id}/payments` | Xem trạng thái thanh toán được phép |
| POST | `/api/v1/orders/{public_id}/cancel` | Yêu cầu hủy theo policy |
| POST | `/api/v1/orders/lookup` | Tra đơn guest bằng order number + proof/OTP |

**Guest order security:** Không cho phép truy vấn thông tin khách hàng chỉ với `order_number` dễ đoán. Dùng secret lookup token ngẫu nhiên (hash trong DB), xác nhận email/OTP, hoặc session guest hợp lệ. Rate-limit mạnh.

### 7.6 Admin API — Dashboard & Catalog

Tất cả endpoints `/api/v1/admin/*` yêu cầu auth + permission cụ thể.

| Method | Endpoint | Chức năng |
|---|---|---|
| GET | `/api/v1/admin/dashboard/overview` | KPIs & alerts |
| GET/POST | `/api/v1/admin/products` | List/Create |
| GET/PATCH | `/api/v1/admin/products/{id}` | Detail/Update |
| POST | `/api/v1/admin/products/{id}/publish` | Publish có validation |
| POST | `/api/v1/admin/products/{id}/archive` | Archive, không hard-delete |
| GET/POST | `/api/v1/admin/products/{id}/variants` | Quản lý SKU |
| PATCH | `/api/v1/admin/variants/{id}` | Sửa biến thể |
| GET/POST/PATCH | `/api/v1/admin/categories[/{id}]` | Categories |
| GET/POST/PATCH | `/api/v1/admin/anime-series[/{id}]` | Franchise |
| GET/POST/PATCH | `/api/v1/admin/manufacturers[/{id}]` | Manufacturer |

### 7.7 Admin API — Operations

| Method | Endpoint | Chức năng |
|---|---|---|
| GET | `/api/v1/admin/orders` | Filter, sort, export restricted |
| GET | `/api/v1/admin/orders/{id}` | Order detail/timeline |
| POST | `/api/v1/admin/orders/{id}/transition` | State change có policy |
| GET | `/api/v1/admin/inventory` | On hand/reserved/available |
| POST | `/api/v1/admin/inventory/adjustments` | Adjust stock có lý do |
| GET | `/api/v1/admin/inventory/movements` | Ledger |
| GET/POST | `/api/v1/admin/preorder-campaigns` | List/Create |
| GET/PATCH | `/api/v1/admin/preorder-campaigns/{id}` | Detail/Update |
| POST | `/api/v1/admin/preorder-campaigns/{id}/open` | Activate campaign |
| POST | `/api/v1/admin/preorder-campaigns/{id}/close` | Close campaign |
| GET | `/api/v1/admin/preorder-reservations` | Theo dõi đặt cọc |
| POST | `/api/v1/admin/payments/{id}/confirm` | Confirm manual payment |
| POST | `/api/v1/admin/payments/{id}/reject` | Reject with reason |
| GET | `/api/v1/admin/customers` | List/search, PII controlled |
| GET | `/api/v1/admin/reports/sales` | Báo cáo doanh thu |

### 7.8 Admin API — CMS, Media, Security

| Method | Endpoint | Chức năng |
|---|---|---|
| GET/POST | `/api/v1/admin/media` | List / khởi tạo upload |
| POST | `/api/v1/admin/media/{id}/complete` | Finalize/validate upload |
| DELETE | `/api/v1/admin/media/{id}` | Chỉ xóa khi không tham chiếu |
| GET | `/api/v1/admin/pages/{slug}/revisions` | Lịch sử nội dung |
| POST | `/api/v1/admin/pages/{slug}/revisions` | Tạo draft revision |
| PATCH | `/api/v1/admin/pages/{slug}/revisions/{id}` | Edit draft |
| POST | `/api/v1/admin/pages/{slug}/revisions/{id}/publish` | Publish revision atomically |
| GET/POST | `/api/v1/admin/coupons` | List/Create |
| PATCH | `/api/v1/admin/coupons/{id}` | Update |
| GET | `/api/v1/admin/activity-logs` | Audit restricted |
| GET | `/api/v1/admin/users` | Quản trị nhân sự |
| PUT | `/api/v1/admin/users/{id}/roles` | Cập nhật roles có protection |
| GET/PATCH | `/api/v1/admin/settings` | Cấu hình cửa hàng |

**Webhooks:** `POST /api/v1/webhooks/payments/{provider}` dùng signature verification, provider event ID unique và replay protection; không áp dụng session CSRF cho machine-to-machine webhook, nhưng phải bảo vệ bằng cơ chế chữ ký của provider.

### 7.9 Query filtering và pagination

Public filters MVP: `q`, `category`, `anime_series`, `manufacturer`, `min_price`, `max_price`, `availability`, `sort`, `page`, `per_page`. Sort allowlist: `newest`, `price_asc`, `price_desc`, `name_asc`, `featured`. Giới hạn `per_page` (ví dụ max 48); rate limit đối với search.

---

## 8. Authentication & authorization

### 8.1 Session-first Auth với Sanctum

- Chọn **Sanctum stateful session/cookie** cho storefront và admin first-party cùng root domain.
- Dev origins: `http://localhost:4321`, `http://localhost:4322`, API `http://localhost:8000`. **Sử dụng nhất quán `localhost`**, không trộn `127.0.0.1` tùy tiện vì khác host/cookie scope.
- `SANCTUM_STATEFUL_DOMAINS` bao gồm origin host+port frontend và API theo config thực tế; configure credentialed CORS cho origins được chỉ định, **không** wildcard khi credentials bật.
- Thực hiện CSRF handshake qua `/sanctum/csrf-cookie`; mọi request ghi có CSRF và session cookie hợp lệ.
- Production origins gợi ý: `https://wibustore.vn`, `https://admin.wibustore.vn`, `https://api.wibustore.vn`.
- Session cookie production `Secure`, `HttpOnly` đối với session, `SameSite=Lax` (đánh giá lại theo deployment); domain phù hợp với subdomains nếu cần. Cookie XSRF phải đọc được theo cơ chế HTTP client đã chọn.
- Login regenerate session ID; logout invalidate/revoke session và rotate CSRF token.
- SSR Astro không được dùng shared/static cache cho private content và phải forward cookies an toàn khi gọi Laravel server-to-server.

**Lưu ý:** Sanctum stateful mode dành cho frontend cùng site/root domain; nếu sau này frontend nằm ở một domain hoàn toàn khác, cần thay đổi thiết kế auth thay vì cố bật CORS bừa bãi.

### 8.2 Roles và permissions mặc định

| Role | Quyền chính |
|---|---|
| `super_admin` | Tất cả + role management + critical settings |
| `store_manager` | Catalog, Orders, Preorders, CMS, Reports; không quản lý super_admin |
| `catalog_editor` | Catalog, categories, manufacturers, product media |
| `order_staff` | Orders, shipment statuses, customer support visibility |
| `inventory_staff` | Inventory view + adjustments theo policy |
| `content_editor` | CMS draft/review, publish theo phân quyền |
| `customer` | Tài khoản và đơn hàng của chính mình |

Permission strings minh họa: `products.read`, `products.write`, `products.publish`, `inventory.adjust`, `orders.transition`, `payments.confirm`, `preorders.manage`, `cms.publish`, `users.manage`, `reports.read`, `settings.manage`.

**Quy tắc:** Admin navigation có thể ẩn theo permission nhưng backend **luôn** xác thực lại bằng Policies/Gates. Staff không được cấp quyền quản lý tài khoản cao hơn mình. Audit mọi thay đổi quyền.

### 8.3 Security checklist

- Password hashing mặc định Laravel; password reset qua single-use token.
- Login/admin routes có throttling, CSRF, server-side validation.
- Cấu hình HTTPS/HSTS production, secret rotation, security headers.
- Validate ownership của mọi resource: chống IDOR/BOLA.
- Output encoding, HTML sanitizer cho CMS, CSP phù hợp.
- Form upload validate MIME thực tế, size và định dạng; không tin file extension.
- PII minimization; không log full session cookie, credentials, payment secrets.
- Customer export và admin CSV theo permission/retention policy.
- Không expose `.env`, `/storage` private, debug endpoints, database hay Redis ra Internet.
- Security review cho guest order lookup, inventory adjustment, refund, role change và signed URLs.

---

## 9. S3-compatible Media Storage

### 9.1 Kiến trúc lưu trữ

- Metadata ở PostgreSQL (`media_assets`), file binary ở S3-compatible object storage.
- **Dev:** MinIO local; **Prod:** S3/Cloudflare R2/Wasabi hoặc dịch vụ tương thích.
- Không đưa access key/secret key cho browser.
- Sử dụng **presigned PUT/POST URLs** (khi phù hợp) hoặc upload qua Laravel; finalize endpoint kiểm tra file thực sự tồn tại/metadata trước khi phát hành media.
- Public product images có thể dùng bucket/public read qua CDN được kiểm soát; tài liệu nhạy cảm luôn private với signed GET URLs.
- Xây image variants (`thumb`, `card`, `detail`, `hero`) qua job để giảm dung lượng frontend.
- Chặn upload SVG không được sanitize nếu không thật sự cần; format ảnh ưu tiên WebP/AVIF khi pipeline hỗ trợ.
- Object key dạng namespaced UUID, không dùng tên file gốc làm đường dẫn tin cậy.

### 9.2 Environment variables đề nghị

Các biến dưới đây dùng trong `.env.example`; **giá trị là placeholder, không phải secret thực tế**.

```dotenv
APP_NAME="Wibu Store API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=vi
APP_TIMEZONE=UTC

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=wibu_store
DB_USERNAME=wibu_app
DB_PASSWORD=change_me_local_only

SESSION_DRIVER=redis
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=false
SANCTUM_STATEFUL_DOMAINS=localhost:4321,localhost:4322,localhost:8000

CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=local_minio_access_key
AWS_SECRET_ACCESS_KEY=local_minio_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=wibu-store-media
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true

STOREFRONT_URL=http://localhost:4321
ADMIN_URL=http://localhost:4322
CORS_ALLOWED_ORIGINS=http://localhost:4321,http://localhost:4322

MAIL_MAILER=log
LOG_CHANNEL=stack
```

**Bổ sung:** `APP_KEY` phải được Laravel generate và giữ bí mật; không commit `.env`. Config S3 tùy provider và public URL/CDN domain sẽ bổ sung theo deployment; `AWS_ENDPOINT` nội bộ Docker **không phải** URL cho browser.

### 9.3 MinIO endpoint caveat

Khi ký presigned URL bằng hostname `minio:9000` (chỉ có container biết), browser tại host sẽ không truy cập được. Giải pháp: ký URL với hostname public/browser-reachable thống nhất (reverse proxy như `storage.localhost`/domain HTTPS) hoặc dùng upload proxy; không thay hostname trong URL đã ký vì sẽ làm sai signature. Xem xét cấu hình CORS bucket theo origins cần thiết.

---

## 10. Docker / local development

### 10.1 Services

| Service | Container | Dev Port | Public exposure |
|---|---|---|---|
| Storefront | Astro | `4321` | Local dev |
| Admin | Astro | `4322` | Local dev |
| Laravel API | PHP + web server | `8000` | Local dev |
| PostgreSQL | PostgreSQL | `5432` (optional bind localhost) | Never public in prod |
| Redis | Redis | `6379` (optional bind localhost) | Never public in prod |
| MinIO API | MinIO | `9000` (optional bind localhost) | Private/proxy policies |
| MinIO Console | MinIO | `9001` (optional bind localhost) | Dev only |
| Worker | Laravel Queue | none | Internal process |
| Scheduler | Laravel Schedule | none | Internal process |
| Mail testing | Mailpit (optional) | `8025` | Dev only |

### 10.2 Compose conventions

- Shared internal network; volume persistent cho PostgreSQL, Redis (nếu cần), MinIO.
- API chờ PostgreSQL healthy trước migration; worker/scheduler chờ app config và DB ready.
- DB hostname **`postgres`** bên trong Compose, không phải `localhost`.
- Phân biệt `localhost` hostname browser và internal Docker DNS.
- Không tự chạy `migrate:fresh` trên môi trường có dữ liệu người dùng.
- Có scripts/documented workflow: bootstrap, migrate, seed-demo, test, format, queue-restart, backup-restore.

### 10.3 Môi trường

| Environment | Đặc điểm |
|---|---|
| Local | Docker Compose, demo data, MinIO, email log/Mailpit |
| CI | Database test riêng, migrations + feature tests |
| Staging | HTTPS, PostgreSQL staging, storage bucket staging, payment sandbox |
| Production | Managed PostgreSQL/S3 hoặc storage đáng tin cậy, backups, monitoring |

---

## 11. Caching, queues, events và scheduler

### 11.1 Cache policy

- Cache public catalog queries (ngắn hạn) và CMS published revision; invalidate khi publish/change.
- Không cache order/user/payment data ở cache public/shared.
- Key bao gồm query params, locale, visibility/publication version.
- Dùng Redis nếu đã có; tránh cache stampede cho các danh mục phổ biến.

### 11.2 Jobs và events

| Event / Trigger | Job | Rule |
|---|---|---|
| `OrderPlaced` | SendOrderConfirmation | Chỉ sau commit |
| `PaymentConfirmed` | SendPaymentReceipt | Idempotent |
| `PreorderDepositDeadline` | SendDepositReminder | Không gửi lại bừa bãi |
| `PreorderBalanceDue` | SendBalancePaymentRequest | Có deadline rõ |
| `MediaUploaded` | GenerateImageVariants | Retry-safe |
| `CmsPublished` | InvalidateCmsCache | Published revision only |
| `InventoryLow` | NotifyStoreManager | Debounce/rate limit |

Set `after_commit=true` cho queue config phù hợp (hoặc dispatch after commit) để worker không đọc order chưa được commit. Có retry/backoff, dead-letter/failed_jobs và alert.

### 11.3 Scheduled tasks

- Expire unpaid checkout reservations.
- Expire pre-order deposit reservations.
- Send overdue balance reminders theo policy.
- Reconcile stock/movements và payments.
- Purge expired guest carts, temporary uploads, old sessions và logs theo retention policy.
- Daily summary report và backup verification.

**Tất cả scheduler jobs phải idempotent.** Khi chạy song song nhiều worker/scheduler không gây double release stock hoặc double payments.

---

## 12. CMS contract với Anime Pop / Comic storefront

### 12.1 Lý do chọn versioned JSONB

Storefront light theme có Hero Comic Cover Collage, stickers, halftone graphic, collection cards và nhiều promotional sections. Phần backend phải cho phép Admin chỉnh **nội dung và bố cục trong phạm vi schema đã thiết kế**, nhưng không cho phép chèn JS/CSS tùy tiện.

### 12.2 Schema fields gợi ý cho `hero_comic`

| Field | Kiểu | Mô tả |
|---|---|---|
| `schema_version` | integer | Version của cấu trúc |
| `enabled` | boolean | Bật/tắt section |
| `headline` | string | Hàng chữ chính |
| `eyebrow` | string | `NEW DROP` / `LIMITED EDITION` |
| `description` | string | Copy marketing |
| `featured_product_id` | UUID | Figure đang giới thiệu |
| `media_asset_id` | bigint/UUID ref | Figure PNG nền trong |
| `background_variant` | enum | Palette preset, không tự nhập arbitrary CSS |
| `comic_stickers` | structured array | Sticker preset + vị trí hợp lệ |
| `cta_label` | string | Text CTA |
| `cta_target` | internal route reference | Link internal được validate |
| `sort_order` | integer | Thứ tự |

### 12.3 CMS publishing workflow

Draft → Preview → Review (optional) → Publish → Cache invalidation. Storefront chỉ đọc `published` revision; có rollback về revision trước. Media phải ready, các product referenced phải published hoặc có fallback. Preview phải được bảo vệ bằng token/server auth và không index SEO.

### 12.4 Trách nhiệm Admin

- Builder chỉ cho phép cấu hình sections nằm trong schema.
- Không cho viết HTML script trực tiếp vào `content`.
- Hiển thị cảnh báo khi hero trỏ tới figure `archived`, hết hàng hoặc hết thời gian pre-order.
- Có lịch sử thay đổi nội dung, tác giả và thời điểm publish.

---

## 13. Reporting và analytics

### 13.1 Dashboard MVP

- Doanh thu đã nhận và doanh thu đơn hàng (phân biệt rõ), theo ngày/tháng.
- Tổng đơn mới, pending payment, đang chuẩn bị, chờ xử lý.
- Pre-order đang mở, sắp hết hạn cọc, outstanding balances.
- SKU sắp hết hàng, products chưa có ảnh, campaign sắp đóng.

### 13.2 Quy tắc báo cáo

- KPI phải xác định rõ nguồn và timestamp (payment `confirmed_at` vs order `placed_at`).
- Không tính pre-order đặt cọc là **toàn bộ doanh thu nhận tiền**.
- Chỉ tính refund/cancellation theo chính sách kế toán định nghĩa; version báo cáo nếu chính sách thay đổi.
- Query aggregation, materialized views hoặc reporting tables chỉ thêm khi cần; source of truth vẫn ở orders/payments.

---

## 14. Observability & reliability

### 14.1 Logging

- Structured logs: `timestamp`, `level`, `request_id`, `actor_id`, `order_id`, `operation`, `duration_ms`.
- Audit log riêng, append-only cho thao tác có ảnh hưởng tiền/tồn kho/phân quyền.
- Không ghi session IDs, raw cookies, credentials, full payment payload nhạy cảm.

### 14.2 Monitoring

- API request rate, latency P50/P95/P99, error rates.
- Queue lag, failed jobs, scheduler health.
- PostgreSQL slow queries, connections, storage usage.
- S3 upload failure rate, media transformation failures.
- Stock anomaly (`reserved > on_hand`, duplicate reference), failed payment reconciliation.

### 14.3 Backup & disaster recovery

- Automated PostgreSQL backups + point-in-time recovery nếu nhà cung cấp hỗ trợ.
- Object storage lifecycle/versioning theo nhu cầu, backup hoặc replication phù hợp.
- Test restore định kỳ; lưu tài liệu recovery, owner và thời gian khôi phục mục tiêu.
- Migration trong production phải backward-compatible khi deploy rolling; rollback phải có kế hoạch.

---

## 15. Testing strategy

### 15.1 Unit tests

- Giá/discount/shipping calculations.
- State transition guards và permission rules.
- Pre-order deposit/balance calculations.
- Coupon qualification, expiry.

### 15.2 Integration/feature tests

- Auth login/logout, CSRF, unauthenticated/forbidden.
- Catalog CRUD và published visibility.
- Cart → checkout → order creation → stock reservation.
- Release inventory khi cancel/expire; shipment deduction.
- Payment confirmation idempotency; webhook signature/replay.
- Pre-order quota, deposit deadline, balance due.
- CMS draft/publish/rollback; media references.
- Guest order access không lộ thông tin người khác.

### 15.3 Concurrency tests bắt buộc

1. **Còn 1 sản phẩm, 2 khách checkout đồng thời:** chỉ một thành công; không âm tồn kho.
2. **Quota pre-order còn 1, 2 khách cùng reserve:** chỉ một thành công.
3. **Webhook/payment confirm lặp lại:** chỉ ghi nhận tiền một lần.
4. **Scheduler expiry và payment confirmation đồng thời:** kết quả phải nhất quán.
5. **Order cancellation gọi lặp:** reservation chỉ release một lần.
6. **Multiple SKUs checkout cùng lúc:** có lock ordering và deadlock retry được kiểm soát.

### 15.4 Quality gates

- CI chạy composer validation, lint/static analysis, unit + feature tests, PostgreSQL migrations từ DB trống.
- OpenAPI lint/contract tests giữa backend và clients.
- Không merge nếu fail critical stock/payment/security tests.
- Staging smoke tests cho login, catalog, checkout và upload trước release.

---

## 16. CI/CD và deployment

### 16.1 Pipeline

1. Validate dependencies và code style.
2. Run tests trên PostgreSQL test database.
3. Build immutable image.
4. Security/dependency scan.
5. Deploy Staging → run smoke tests.
6. Backup/check migration plan → deploy Production.
7. Run approved migrations → rollout worker/scheduler → health checks.

### 16.2 Production checklist

- `APP_ENV=production`, `APP_DEBUG=false`, production `APP_KEY` riêng.
- PostgreSQL và Redis không truy cập trực tiếp từ Internet.
- HTTPS cho API, storefront, admin; CSRF/session/cookie đúng domain.
- Secrets được quản lý bằng platform secrets manager, không trong git/image.
- Storage bucket policies ít quyền nhất, upload validations, lifecycle.
- Worker/scheduler chạy độc lập, có process restart và health alerts.
- Rate limiting, abuse protection, logs và backup đã kiểm thử.
- Payment provider hoạt động sandbox trước production; callback signature verified.
- Public routes chỉ thấy published records.

---

## 17. Development roadmap

| Phase | Nội dung | Kết quả nghiệm thu |
|---|---|---|
| **0. Foundation** | Laravel, Docker, PostgreSQL, Redis, MinIO, env, OpenAPI skeleton | Health route, migration, upload smoke |
| **1. Identity + RBAC** | Auth, session Sanctum, roles/permissions, CSRF/CORS | 4321 và 4322 login đúng quyền |
| **2. Catalog + Media** | Products, variants, series, categories, media, public listing | Storefront render figure từ API |
| **3. Inventory + Cart** | Stock ledger, reservation, guest/member cart | Stock consistency tests pass |
| **4. Orders + Payments** | Checkout, COD/manual transfer, order admin | End-to-end purchase pass |
| **5. Pre-orders** | Campaign quota, deposit, payment balance, expiry | Preorder concurrency tests pass |
| **6. CMS + Reporting** | Homepage sections, revisions, basic KPI | Admin publish cập nhật storefront |
| **7. Hardening** | Security, monitoring, backup, API docs, staging | Production readiness checklist pass |

**Ưu tiên:** Foundation → Identity → Catalog → Inventory → Orders → Pre-order → CMS. Không bắt đầu bằng checkout UI khi chưa có invariant tồn kho.

---

## 18. Acceptance criteria tổng thể (Definition of Done)

### Hạ tầng

- [ ] Storefront chạy `:4321`, Admin chạy `:4322`, API chạy `:8000`.
- [ ] PostgreSQL và MinIO hoạt động trong Docker Compose; không có secrets commit vào Git.
- [ ] `.env.example` rõ các biến DB, cookie, Redis và S3-compatible.
- [ ] Có migrations, seed demo và môi trường test cô lập.

### Catalog & CMS

- [ ] Admin tạo figure có anime series, manufacturer, SKU, giá và media.
- [ ] Product `draft` không hiển thị ở API public; `active` hiển thị đầy đủ.
- [ ] Storefront nhận Hero Comic Cover từ CMS published revision.
- [ ] CMS có preview, publish, rollback, audit và validation.

### Checkout & payments

- [ ] Cart preview được tính trên backend.
- [ ] Checkout giữ stock nhất quán khi nhiều khách mua cùng lúc.
- [ ] Mỗi order có snapshot giá/line item/customer address.
- [ ] Retry request/webhook không tạo double order hoặc double payment.
- [ ] COD/chuyển khoản thủ công không tự xác nhận tiền từ UI.
- [ ] Cancel/expire release reservation đúng một lần.

### Pre-order

- [ ] Quota không vượt giới hạn kể cả khi request đồng thời.
- [ ] Tiền cọc, phần còn lại và hạn thanh toán có trạng thái độc lập.
- [ ] Không coi quota pre-order là hàng physical on-hand.
- [ ] Có email notification và manual operation với audit.

### Security & operations

- [ ] API Admin luôn kiểm tra RBAC; khách không truy cập đơn của người khác.
- [ ] Cookie, CSRF, CORS hoạt động trên localhost multi-port và domain production.
- [ ] S3 upload có type/size validation và URL policy rõ ràng.
- [ ] Queue jobs sau DB commit; có retry, failed-jobs, observability.
- [ ] Có backup/restore test, CI tests và API OpenAPI.

---

## 19. Những quyết định cần xác nhận trước khi viết code

| Vấn đề | Mặc định tài liệu | Cần chốt khi triển khai |
|---|---|---|
| Phương thức thanh toán | COD + chuyển khoản thủ công | Có tích hợp VNPay/MoMo/PayOS không? |
| Vận chuyển | Phí ship đơn giản + Admin cập nhật | Nhà vận chuyển/API cụ thể |
| Guest checkout | Có, với lookup proof bảo mật | Muốn bắt buộc đăng nhập hay không? |
| Kho hàng | 1 warehouse mặc định, schema hỗ trợ nhiều kho | Có bao nhiêu địa điểm chứa hàng? |
| Pre-order | Một campaign mỗi checkout | Đặt cọc cố định/%, hoàn cọc theo policy nào? |
| Search | PostgreSQL | Quy mô SKU dự kiến; search tiếng Việt có dấu/không dấu |
| Object storage | MinIO dev, S3-compatible prod | Nhà cung cấp và public CDN domain |
| CMS | Versioned JSONB sections | Những sections nào cần editor tự do? |
| Thuế/hóa đơn | Chưa tự động xử lý | Thuế, hóa đơn và quy định địa phương |

Các mặc định đủ để bắt đầu foundation/catalog. Không triển khai payment gateway production, đối soát doanh thu hoặc refund policy trước khi quyết định cụ thể được chấp thuận.

---

## 20. Tài liệu tham chiếu kỹ thuật

- Laravel 13 Installation / Requirements: https://laravel.com/docs/13.x/installation
- Laravel 13 Sanctum: https://laravel.com/docs/13.x/sanctum
- Laravel 13 Database & Transactions: https://laravel.com/docs/13.x/database
- Laravel 13 Authorization: https://laravel.com/docs/13.x/authorization
- Laravel 13 File Storage/S3: https://laravel.com/docs/13.x/filesystem
- Laravel 13 Queues: https://laravel.com/docs/13.x/queues
- PostgreSQL documentation: https://www.postgresql.org/docs/current/
- OpenAPI Specification: https://spec.openapis.org/oas/latest.html

---

## 21. Khuyến nghị chốt kiến trúc

Triển khai **một Laravel 13 REST API** cho **hai Astro applications**, sử dụng **PostgreSQL làm source of truth**, **Eloquent và migrations** cho schema, **Sanctum session** cho auth first-party, **Policies/RBAC** cho Admin và **S3-compatible storage** cho toàn bộ media. Các hành vi liên quan tới tiền, tồn kho, quota và trạng thái đơn được bảo vệ bằng **DB transactions + row locks + idempotency**. Đưa Redis vào hạ tầng ngay nếu thuận tiện; không xây microservices khi chưa có nhu cầu vận hành cụ thể.

Tài liệu này là chuẩn phối hợp giữa backend, frontend storefront và frontend admin. Trong quá trình triển khai, **OpenAPI contract + migrations + automated tests** phải trở thành các hiện vật sống, cập nhật cùng mọi thay đổi nghiệp vụ.
