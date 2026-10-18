# WIBU STORE ADMIN — UI/UX & FRONTEND IMPLEMENTATION SPECIFICATION

> **Phiên bản:** 1.0 — 17/10/2026  
> **Sản phẩm:** Wibu Store — hệ thống quản trị cửa hàng figure/anime collectibles  
> **Frontend:** Astro + Tailwind CSS 4 + TypeScript  
> **Ứng dụng:** **Admin độc lập**, sử dụng **port 4322** khi phát triển  
> **Storefront hiện có:** Astro + Tailwind CSS, port 4321  
> **Art direction:** **Anime Pop / Comic (restrained) × Clean Operations Dashboard**  
> **Theme:** Light mặc định, chưa yêu cầu dark mode  
> **Loại tài liệu:** Thiết kế, bố cục, luồng thao tác, kiến trúc frontend và acceptance criteria; **không chứa code triển khai**.

---

## 0. Tóm tắt quyết định kiến trúc

| Hạng mục | Quyết định | Ghi chú |
|---|---|---|
| Storefront | `apps/storefront` — Astro + Tailwind CSS | Giữ cổng phát triển `http://localhost:4321` |
| Admin | **`apps/admin` — Astro + Tailwind CSS** | **Chạy tại `http://localhost:4322`** |
| Tổ chức source | Một monorepo với 2 Astro app độc lập | Có thể dùng 2 repo nếu đội phát triển yêu cầu; mặc định khuyến nghị monorepo |
| Shared layer | Brand tokens, type contracts, API conventions, utility không chứa logic bí mật | Không share toàn bộ marketing components sang admin |
| Rendering admin | Astro **SSR / on-demand rendering** | Xác thực và kiểm quyền trước khi trả HTML; dùng Node adapter khi deploy trên Node |
| Tương tác | Astro components + client-side islands cho bảng/form/phức tạp | Không biến toàn bộ admin thành SPA nếu không cần |
| API nghiệp vụ | Một backend/API dùng chung cho storefront và admin | Ví dụ Laravel API nếu dự án chọn Laravel; không bắt buộc trong phạm vi UI |
| Auth | Phiên đăng nhập an toàn, role/permission do backend kiểm tra | Frontend chỉ hiển thị quyền, không thay backend quyết định |
| Media | Media library dùng cơ chế tải lên có kiểm soát | S3-compatible nếu backend hỗ trợ; không đưa khóa S3 vào browser |
| Production | Host/subdomain riêng cho admin | Ví dụ `admin.<ten-mien-cua-ban>`; không xem port 4322 là lớp bảo mật |

**Mục tiêu:** Admin có ngôn ngữ thị giác cùng thương hiệu Wibu Store nhưng tối ưu cho thao tác nghiệp vụ lặp lại. Marketing mang chất comic; dashboard ưu tiên đọc số liệu, quản lý dữ liệu, tốc độ và độ chính xác.

**Ngoài phạm vi:** Tài liệu không định nghĩa database thực tế, không triển khai Laravel/API, không viết code, không khẳng định đã có tích hợp thanh toán/S3/đơn vị vận chuyển. Những phần này được nêu dưới dạng yêu cầu hợp đồng tích hợp.

---

## 1. Nguyên tắc UX và phạm vi nghiệp vụ

### 1.1. Nguyên tắc cốt lõi

1. **Operational first:** Mọi hành động quản trị phải dễ tìm, có trạng thái rõ và khó thao tác nhầm.
2. **Progressive disclosure:** Chỉ hiển thị trường nâng cao khi cần; không dồn mọi thuộc tính sản phẩm vào một form duy nhất.
3. **Consistent status:** Trạng thái đơn hàng, thanh toán, đặt cọc, tồn kho và xuất bản là **các trục độc lập**.
4. **Preview before publish:** Nội dung của storefront phải xem trước được trước khi công khai.
5. **Traceability:** Mọi thay đổi quan trọng có người thực hiện, thời điểm, giá trị trước/sau và lý do khi cần.
6. **Accessibility:** Keyboard-first, tương phản đủ, không dựa riêng vào màu sắc để báo trạng thái.
7. **Responsive but desktop-primary:** Desktop là mặt bằng thao tác chính; tablet/mobile vẫn xem được và xử lý tác vụ ưu tiên.
8. **Brand without clutter:** Màu đỏ, vàng, graphic comic chỉ làm dấu nhấn; không dùng halftone dưới bảng, form hoặc biểu đồ.

### 1.2. Phân nhóm chức năng

| Nhóm | Phân hệ | Ưu tiên |
|---|---|---|
| Tổng quan | Dashboard, activity feed, cảnh báo vận hành | MVP |
| Thương mại | Đơn hàng, chi tiết đơn, fulfillment, hoàn/hủy | MVP (phụ thuộc API) |
| Sản phẩm | Sản phẩm, biến thể, danh mục, series anime, thương hiệu | MVP |
| Tồn kho | Tồn kho, điều chỉnh tồn, lịch sử giao dịch tồn | MVP |
| Pre-order | Đợt đặt trước, lịch phát hành, cọc, theo dõi giao hàng | MVP nếu cửa hàng nhận pre-order |
| Nội dung | CMS trang chủ, banner/hero, collections, media | MVP |
| Khuyến mãi | Mã giảm giá, chương trình, lịch hiển thị | Giai đoạn 2 |
| Khách hàng | Hồ sơ khách, lịch sử đơn, ghi chú hỗ trợ | Giai đoạn 2; bản tối thiểu trong MVP |
| Đánh giá | Duyệt review, báo cáo vi phạm | Giai đoạn 2 |
| Phân tích | Doanh thu, funnel, hàng bán chạy, báo cáo | Dashboard cơ bản MVP; nâng cao GĐ2 |
| Hệ thống | Nhân sự/phân quyền, cấu hình cửa hàng, audit log | MVP cho auth/roles; nâng cao GĐ2 |

### 1.3. Đối tượng sử dụng

- **Owner / Super Admin:** Toàn quyền hợp lệ, cấu hình và quản lý phân quyền.
- **Store Manager:** Giám sát đơn hàng, tồn kho, sản phẩm và vận hành.
- **Catalog Editor:** Tạo/sửa sản phẩm, series, media, nội dung CMS được phân quyền.
- **Fulfillment Staff:** Xử lý đơn, trạng thái giao hàng, xác nhận đóng gói.
- **Customer Support:** Tra cứu đơn/khách, cập nhật ghi chú và yêu cầu hỗ trợ.
- **Analyst / Read-only:** Xem báo cáo được cấp quyền, không thay đổi dữ liệu.

Các vai trò trên là **gợi ý UI**; backend là nguồn chuẩn cho tập quyền thực tế.

---

## 2. Định hướng thiết kế tổng thể

### 2.1. Concept: “Collector Operations Studio”

Admin giống một **studio vận hành cửa hàng đồ sưu tầm**: sạch, nhanh, dễ quét thông tin, hơi hướng anime thông qua thương hiệu và điểm nhấn graphic. Không thiết kế giống landing page comic toàn màn hình.

**Phân bổ đặc trưng hình ảnh:**

- **85–90%:** dashboard sạch, bảng dữ liệu, form, navigation, cards nền trắng.
- **10–15%:** brand red, comic yellow, sticker label, micro-graphic tại welcome/empty state và campaign preview.
- **Không dùng:** nền speed lines ở bảng, figure phủ lên thông tin đơn hàng, animation giật trên form.

### 2.2. Quan hệ với design system storefront v2

Kế thừa đúng các token gốc đã thống nhất:

| Token / vai trò | HEX | Ứng dụng trong admin |
|---|---|---|
| Store canvas | `#FFFDF7` | Màn hình đăng nhập, preview thương hiệu |
| Admin canvas | `#F7F5F0` | Nền vùng nội dung dashboard |
| Surface | `#FFFFFF` | Card, table, form, popover, drawer |
| Primary ink | `#17141F` | Text chính, heading |
| Secondary text | `#534F5C` | Mô tả, thông số phụ |
| Muted text | `#6A6572` | Placeholder, nhãn phụ |
| Neutral border | `#D8D4D0` | Đường viền component |
| Brand red | `#D6284F` | Primary CTA, active context, điểm nhấn thương hiệu |
| Hover red | `#AF1E40` | Hover CTA; có label rõ |
| Comic yellow | `#FFD44A` | Warning/marketing callout có chọn lọc, không thay thế semantic warning |
| Cobalt blue | `#3258D7` | Thao tác phụ, biểu đồ, link nhấn |
| Success | `#147A4B` | Trạng thái hoàn tất / sẵn sàng |
| Warning | `#9A5A00` | Cảnh báo, cần xử lý |
| Danger | `#B42318` | Huỷ, xoá, lỗi, rủi ro |

**Token bổ sung cho admin (đề xuất):**

| Token | HEX / giá trị | Mục đích |
|---|---|---|
| `admin.sidebar.bg` | `#FFFFFF` | Sidebar sáng, chuyên nghiệp |
| `admin.sidebar.activeBg` | `#FFF0F3` | Menu active có sắc hồng rất nhạt |
| `admin.sidebar.activeText` | `#A61B3F` | Nhận diện vị trí đang mở |
| `admin.hover` | `#F7F5F0` | Hover row và control trung tính |
| `admin.focusRing` | `#3258D7` | Focus keyboard dễ nhận biết |
| `admin.infoBg` | `#EDF3FF` | Thông báo thông tin |
| `admin.successBg` | `#E7F5ED` | Badge success |
| `admin.warningBg` | `#FFF3DA` | Badge warning |
| `admin.dangerBg` | `#FDEDEC` | Badge danger |
| `admin.skeleton` | `#EAE7E4` | Loading skeleton |

**Không sử dụng màu thương hiệu để suy đoán tình trạng nghiệp vụ:** Đỏ brand trên nút CTA khác đỏ danger của hành động xoá; trạng thái phải có nhãn/icon rõ.

### 2.3. Typography

| Vai trò | Font | Quy chuẩn |
|---|---|---|
| Admin UI, heading, form, table | **Be Vietnam Pro** | **Font mặc định** cho toàn bộ dashboard, hỗ trợ tiếng Việt |
| Campaign preview / graphic label | **Anton** | Chỉ trong preview marketing hoặc nhãn minh họa ngắn, không dùng bảng/form |
| Tên file, SKU, order ID, technical metadata | System mono hoặc font monospace hệ thống | Dùng tiết chế để đọc mã định danh |

| Level | Desktop | Mobile / tablet | Weight |
|---|---:|---:|---|
| Page title | 26–30 px | 22–26 px | 750–800 |
| Section title | 18–22 px | 18–20 px | 700 |
| Card KPI | 26–34 px | 24–28 px | 750–800 |
| Body / form value | 14–15 px | 14–15 px | 400–500 |
| Input / button | 14 px | 14–15 px | 600–700 |
| Table heading | 12–13 px | 12–13 px | 700 |
| Table body | 13–14 px | 13–14 px | 400–600 |
| Hint / metadata | 12–13 px | 12–13 px | 400–500 |

Giữ chiều cao dòng khoảng 1.4–1.6 cho body; giá tiền và số liệu sử dụng tabular numerals khi khả dụng.

### 2.4. Khoảng cách, hình khối, bóng

| Quy chuẩn | Giá trị đề xuất |
|---|---|
| Base spacing | 4 px |
| Spacing scale | 4 / 8 / 12 / 16 / 20 / 24 / 32 / 40 / 48 / 64 px |
| Content padding desktop | 28–36 px |
| Content padding tablet | 20–24 px |
| Content padding mobile | 16 px |
| Card padding | 20–24 px; mobile 16 px |
| Control height | 40–44 px; form quan trọng 44–48 px |
| Radius card | 14–16 px |
| Radius button/input | 8–10 px |
| Border | 1 px neutral; focus ring rõ |
| Shadow | Soft, tinh tế cho popover/modal; không áp dụng comic offset vào bảng |
| Table row | 52–64 px, tùy có thumbnail hay không |
| Sticky header | 64–72 px |
| Sidebar width | 248–264 px mở; 72–80 px thu gọn |

### 2.5. Icon, trạng thái, mật độ

- Icon line thống nhất 18–20 px; actions nguy hiểm không chỉ dùng icon.
- Table density mặc định **comfortable**; cho phép chuyển **compact** đối với người xử lý nhiều đơn.
- Tooltip có nhãn chỉ cho tính năng bổ trợ; tác vụ cốt lõi cần text hiển thị.
- Badges dùng văn bản như `Chờ xác nhận`, `Đã thanh toán`, `Sắp hết hàng`, không chỉ mã màu.
- Biểu đồ đơn giản, ít gradient, màu nhất quán với semantic tokens.

---

## 3. Information Architecture & Navigation

### 3.1. Sidebar đề xuất

**TỔNG QUAN**
- Dashboard
- Việc cần xử lý

**BÁN HÀNG**
- Đơn hàng
- Pre-order
- Khách hàng

**SẢN PHẨM**
- Tất cả sản phẩm
- Danh mục / Dòng figure
- Anime Series / Nhân vật
- Thương hiệu / Nhà sản xuất
- Tồn kho

**NỘI DUNG & MARKETING**
- Nội dung trang chủ
- Bộ sưu tập
- Media Library
- Khuyến mãi
- Đánh giá

**BÁO CÁO**
- Tổng quan kinh doanh
- Báo cáo sản phẩm
- Báo cáo tồn kho

**HỆ THỐNG**
- Nhân sự & phân quyền
- Cấu hình cửa hàng
- Nhật ký hoạt động

Ẩn menu không có quyền; các nhóm mở/đóng được, ghi nhớ trạng thái sidebar theo phiên. Badge count cho `Đơn cần xử lý` và `Pre-order cần xử lý` nếu có dữ liệu.

### 3.2. Topbar

- Breadcrumb: `Sản phẩm / Tất cả sản phẩm / Chỉnh sửa`.
- Global search: tìm mã đơn, SKU, tên figure, tên khách (theo quyền).
- Quick Create: `Thêm sản phẩm`, `Tạo collection`, `Tạo banner` (theo quyền).
- Notifications: tồn kho thấp, đơn mới, pre-order sắp tới, xuất bản lỗi.
- User menu: tên người dùng, vai trò, tài khoản, đăng xuất.
- Mobile: menu button + page title + thông báo + avatar; search mở overlay.

### 3.3. URL / Route map

| URL trên admin | Màn hình |
|---|---|
| `/login` | Đăng nhập |
| `/forgot-password` | Khôi phục tài khoản (nếu backend hỗ trợ) |
| `/` | Dashboard |
| `/tasks` | Việc cần xử lý |
| `/orders` | Danh sách đơn hàng |
| `/orders/[id]` | Chi tiết đơn hàng |
| `/preorders` | Danh sách pre-order |
| `/preorders/[id]` | Chi tiết chiến dịch/đợt pre-order |
| `/products` | Danh sách sản phẩm |
| `/products/new` | Tạo sản phẩm |
| `/products/[id]/edit` | Chỉnh sửa sản phẩm |
| `/categories` | Danh mục / loại figure |
| `/series` | Anime Series / nhân vật |
| `/brands` | Nhà sản xuất |
| `/inventory` | Tồn kho |
| `/inventory/adjustments` | Nhật ký điều chỉnh |
| `/customers` | Danh sách khách hàng |
| `/customers/[id]` | Hồ sơ khách (có kiểm quyền) |
| `/content/homepage` | CMS trang chủ |
| `/collections` | Bộ sưu tập |
| `/collections/[id]/edit` | Chỉnh sửa bộ sưu tập |
| `/media` | Media Library |
| `/promotions` | Khuyến mãi |
| `/reviews` | Đánh giá |
| `/analytics` | Báo cáo tổng quan |
| `/analytics/products` | Báo cáo sản phẩm |
| `/analytics/inventory` | Báo cáo tồn kho |
| `/settings/store` | Cấu hình cửa hàng |
| `/settings/team` | Nhân sự & quyền |
| `/settings/audit-logs` | Nhật ký hệ thống |
| `/403` | Không đủ quyền |
| `/404` | Không tìm thấy tài nguyên |

Đây là route nội bộ của ứng dụng admin riêng, **không** đặt chung dưới đường dẫn storefront kiểu `/admin` ở port 4321.

---

## 4. App shell — bố cục chuẩn

### 4.1. Desktop (khuyến nghị ≥ 1280 px)

```text
┌────────────────────────┬───────────────────────────────────────────────────────────┐
│  WIBU STORE  / ADMIN   │  Breadcrumbs / Global Search        Alerts   User Menu     │
├────────────────────────┼───────────────────────────────────────────────────────────┤
│                        │                                                           │
│  TỔNG QUAN             │  PAGE TITLE                               PRIMARY ACTION   │
│   Dashboard            │  Description / contextual metrics                         │
│   Việc cần xử lý       │                                                           │
│                        │  ┌─────────────────────────────────────────────────────┐   │
│  BÁN HÀNG             │  │ Filters / Search / Date range / Bulk action         │   │
│   Đơn hàng             │  ├─────────────────────────────────────────────────────┤   │
│   Pre-order            │  │                                                     │   │
│   Khách hàng           │  │ Data / Charts / Form / Canvas                       │   │
│                        │  │                                                     │   │
│  SẢN PHẨM              │  └─────────────────────────────────────────────────────┘   │
│   Sản phẩm             │                                                           │
│   ...                  │  Help / Footer metadata                                     │
│                        │                                                           │
│  [Collapse sidebar]    │                                                           │
└────────────────────────┴───────────────────────────────────────────────────────────┘
```

**Bố cục kỹ thuật:** Sidebar sticky hoặc fixed; vùng main có min-width hợp lý và tự cuộn; header sticky ở cạnh trên. Tránh lồng nhiều vùng scroll dọc. Filters và table headers chỉ sticky nếu không gây xung đột với topbar.

### 4.2. Tablet (768–1279 px)

- Sidebar có thể thu gọn thành icon rail hoặc off-canvas.
- KPI grid 2 cột; layout 2-panel (form + preview) chuyển thành 1 cột hoặc 60/40 nếu còn đủ rộng.
- Bảng giữ cột cốt lõi, cột phụ ẩn vào row detail; hỗ trợ scroll ngang có chỉ báo.
- Toolbar tách thành 2 hàng thay vì nén các control.

### 4.3. Mobile (< 768 px)

- Sidebar dùng drawer phủ màn hình, đóng bằng Escape / overlay.
- Header giữ logo hoặc page title ngắn, thông báo và avatar.
- Danh sách đơn/ sản phẩm có **mobile card view** thay cho ép bảng 8–10 cột.
- Primary action dùng nút vừa chạm; form một cột; bottom sticky action bar cho `Lưu nháp` / `Lưu thay đổi` khi cần.
- Không yêu cầu đầy đủ tính năng quản trị phức tạp trên mobile; bulk import/export có thể hiển thị thông báo “Khuyến nghị desktop”.

### 4.4. Breakpoints và grid

| Màn hình | Grid layout | Hành vi |
|---|---|---|
| 320–639 px | 4 cột | Cards 1 cột, form 1 cột, toolbar cuộn ngang hoặc wrap |
| 640–767 px | 8 cột | KPI 2 cột, list cards hoặc compact table |
| 768–1023 px | 8 cột | Sidebar collapsed, KPI 2 cột |
| 1024–1279 px | 12 cột | Sidebar collapsed hoặc full theo không gian |
| ≥1280 px | 12 cột | Sidebar full, KPI 4 cột, form hai vùng |

---

## 5. Dashboard — trang tổng quan

### 5.1. Wireframe

```text
┌──────────────────────────────────────────────────────────────────────────────────┐
│  DASHBOARD                                [Hôm nay ▼] [Xuất báo cáo]             │
│  Tình hình kinh doanh Wibu Store                                                 │
├──────────────────────────────────────────────────────────────────────────────────┤
│  [Doanh thu]      [Đơn mới]      [Chờ xử lý]      [Sắp hết hàng]                  │
├───────────────────────────────────────────────────┬──────────────────────────────┤
│  DOANH THU / ĐƠN HÀNG                            │  CÔNG VIỆC ƯU TIÊN           │
│  (line/bar chart + date range)                    │  • Đơn chờ xác nhận          │
│                                                   │  • Pre-order gần hạn         │
├───────────────────────────────────────────────────┤  • SKU tồn kho thấp         │
│  ĐƠN HÀNG GẦN ĐÂY                                ├──────────────────────────────┤
│  #ID | KHÁCH | TRẠNG THÁI | TIỀN | ACTION        │  SẢN PHẨM BÁN CHẠY           │
│  ...                                              │  thumbnail + qty + revenue   │
└───────────────────────────────────────────────────┴──────────────────────────────┘
```

### 5.2. KPI cards

| KPI | Nội dung | Tương tác |
|---|---|---|
| Doanh thu | Doanh thu được ghi nhận theo định nghĩa backend | Tooltip “cách tính”; filter thời gian |
| Đơn mới | Số đơn được tạo trong kỳ | Click xem danh sách đã lọc |
| Đơn cần xử lý | Số đơn ở trạng thái nghiệp vụ cần xử lý | Click mở task queue |
| Tồn kho thấp | SKU dưới ngưỡng đã cài | Click tới Inventory filter |

**Lưu ý tính toán:** Không cộng số tiền đặt cọc pre-order vào doanh thu sản phẩm cuối cùng hai lần; cách ghi nhận cần được backend/kế toán xác định thống nhất.

### 5.3. Biểu đồ và work queue

- Trend chart: trục thời gian, legend, hover tooltip, hỗ trợ bảng dữ liệu thay thế.
- Date presets: hôm nay / 7 ngày / 30 ngày / custom.
- Alerts: theo mức độ, thời điểm, link đến bản ghi; phân biệt thông tin và cảnh báo khẩn.
- Recent orders: tối đa 5–10 bản ghi; nút `Xem tất cả`.
- Sản phẩm bán chạy: ảnh thumbnail, tên, SKU, số lượng bán, doanh thu nếu có quyền xem.

---

## 6. Orders — quản lý đơn hàng

### 6.1. Danh sách đơn hàng (`/orders`)

```text
┌───────────────────────────────────────────────────────────────────────────────┐
│  ĐƠN HÀNG                                          [Export] [Tạo đơn thủ công*] │
│  [Tất cả] [Mới] [Đang xử lý] [Đang giao] [Hoàn tất] [Hủy]                  │
│  [Tìm mã đơn/khách]  [Ngày] [Thanh toán] [Vận chuyển] [Lọc nâng cao]         │
├───────┬──────────┬─────────────┬─────────────┬─────────┬────────┬──────────────┤
│ Chọn  │ Mã đơn   │ Khách       │ Tình trạng  │ Tổng tiền│ Ngày   │ Thao tác     │
├───────┼──────────┼─────────────┼─────────────┼─────────┼────────┼──────────────┤
│  □    │ #WS-...  │ ...         │ Badge       │ ...     │ ...    │ Xem / ...    │
└───────┴──────────┴─────────────┴─────────────┴─────────┴────────┴──────────────┘
```

*Tạo đơn thủ công chỉ xuất hiện nếu backend có nghiệp vụ và quyền tương ứng.*

**Bộ lọc:** trạng thái thực hiện đơn, trạng thái thanh toán, loại đơn (`standard/pre-order/mixed` nếu backend hỗ trợ), kênh bán, date range, đơn vị vận chuyển, khoảng tiền. Lọc phải có `Reset`, chip active filter, URL query phản ánh trạng thái để chia sẻ nội bộ.

**Bảng:** mã đơn, khách hàng, số mặt hàng, loại đơn, payment badge, fulfillment badge, tổng tiền, ngày tạo, actions. Hỗ trợ sort, pagination server-side, export async nếu số lượng lớn. Không tải toàn bộ đơn vào trình duyệt để lọc.

### 6.2. Chi tiết đơn (`/orders/[id]`)

**Header:** `#WS-2026-xxxx`, ngày tạo, badges `Payment`, `Fulfillment`, menu tác vụ.

**Main 2/3:** dòng sản phẩm (image, tên, SKU, variant, qty, unit price), timeline nghiệp vụ, địa chỉ nhận hàng (theo quyền), ghi chú khách, lịch sử trạng thái.

**Side 1/3:** thanh toán, tổng tiền/ship/discount, tracking, trạng thái vận chuyển, customer mini-profile, internal notes.

**Các hành động điều kiện:** Xác nhận đơn, bắt đầu đóng gói, đánh dấu đã gửi hàng, cập nhật tracking, ghi nhận kết quả hủy/hoàn theo dữ liệu backend. Luôn hiển thị hành động hợp lệ với trạng thái hiện tại; thao tác có ảnh hưởng tài chính phải xác nhận rõ.

### 6.3. State model UI

Giữ **tối thiểu 3 nhóm trạng thái độc lập**:

- **Order lifecycle:** `new → confirmed → processing → completed`; có nhánh `cancelled` hoặc `on-hold` theo backend.
- **Payment:** `unpaid / pending / partially_paid / paid / refunded / partially_refunded / failed` theo nghiệp vụ.
- **Fulfillment:** `unfulfilled / picking / packed / shipped / delivered / returned` theo nhà vận chuyển.

Trạng thái hiển thị là **giá trị từ API**, không được tự suy diễn `đã giao = đã thanh toán`. Các transition thực tế phải tuân theo state machine backend.

### 6.4. Bulk actions và an toàn

- Cho phép bulk select trên một trang; thông báo rõ nếu áp dụng cho nhiều trang/kết quả lọc.
- Các thao tác đổi trạng thái hàng loạt phải được backend cho phép; không cung cấp bulk delete đơn.
- Với hành động nguy hiểm: confirm dialog chứa số đơn, trạng thái dự kiến, nguyên nhân và thông báo kết quả từng bản ghi.
- Luôn phản ánh trạng thái `pending / success / partial success / failed` của lô thao tác.

---

## 7. Products — quản lý sản phẩm

### 7.1. Danh sách (`/products`)

**Tabs:** `Tất cả`, `Đang bán`, `Bản nháp`, `Ngừng bán`, `Pre-order`.

**Filters:** series anime, nhân vật, loại figure, nhà sản xuất, khoảng giá, khả dụng, trạng thái xuất bản, stock threshold.

**Table:** thumbnail, tên, SKU/slug, series, loại figure, giá từ–đến (nếu có biến thể), trạng thái, tồn khả dụng, updatedAt, actions.

**Toolbar:** `Thêm sản phẩm`, `Import`, `Export`, bulk status change. Tránh bulk delete vĩnh viễn ở MVP.

### 7.2. Tạo/sửa sản phẩm — multi-section form

```text
┌───────────────────────────────────────────────────────────────────────────────┐
│  SẢN PHẨM / TẠO MỚI                                  [Lưu nháp] [Xuất bản]   │
├──────────────────────────────────────────────────┬────────────────────────────┤
│  THÔNG TIN CƠ BẢN                                │  TRẠNG THÁI                │
│  - Tên, slug, mô tả ngắn, mô tả chi tiết          │  Draft / Published         │
│                                                  │                            │
│  PHÂN LOẠI                                      │  KÊNH HIỂN THỊ             │
│  - Series anime, nhân vật, loại figure, hãng      │  Storefront                │
│                                                  │                            │
│  MEDIA                                          │  TÓM TẮT SEO               │
│  - Ảnh đại diện, gallery, alt, thứ tự             │  Meta title / snippet      │
│                                                  │                            │
│  BIẾN THỂ & GIÁ                                 │  PREVIEW                   │
│  - SKU, giá, option, stock, cân nặng             │  Product card preview      │
│                                                  │                            │
│  LOGISTICS & SEO                                │                            │
│  - Kích thước, đóng gói, metadata                │                            │
└──────────────────────────────────────────────────┴────────────────────────────┘
```

**Nhóm trường:**

1. **Basic:** tên sản phẩm, slug, mô tả, highlights, visibility.
2. **Classification:** anime series, nhân vật, figure type, brand/manufacturer, collection, tags.
3. **Media:** cover thumbnail, gallery có sắp xếp, hình ở góc phụ, alt text, cảnh báo ảnh chưa đạt yêu cầu.
4. **Variants & Pricing:** option (size, version, scale), SKU độc nhất, giá bán, giá so sánh (nếu có), currency, tax category (nếu có).
5. **Inventory:** tracking, available/reserved, cảnh báo tồn thấp. Không nhập trực tiếp số tồn tổng nếu hệ thống dùng inventory ledger; dẫn tới module điều chỉnh.
6. **Specifications:** scale (1/7, 1/8...), chiều cao, chất liệu, hãng, ngày phát hành, tình trạng hộp, license nếu cần.
7. **SEO:** title, description, canonical slug, Open Graph preview.
8. **Publication:** draft / scheduled / published / archived theo backend; preview trước publish.

**Validation UX:** validate inline, focus field lỗi đầu tiên, lỗi được giữ đến khi sửa; cảnh báo rời trang nếu có thay đổi chưa lưu; status `Đã lưu lúc...` nếu có autosave thật.

### 7.3. Variant editor

- Mỗi variant có SKU, lựa chọn, giá, tồn kho, ảnh riêng (tùy trường hợp).
- Hiển thị ma trận biến thể có khả năng mở rộng; nếu >30 variants, dùng bảng thay vì cards.
- Ngăn trùng SKU và lựa chọn trùng trong cùng sản phẩm.
- Cho phép bật/tắt bán từng variant, nhưng đảm bảo liên kết đơn hàng cũ không bị mất.

### 7.4. Đồng bộ storefront

- Chỉ dữ liệu published/visible mới xuất hiện ngoài storefront.
- Sau publish có trạng thái `Đang đồng bộ / Đã đồng bộ / Lỗi`; cung cấp retry được kiểm soát nếu có cơ chế invalidation/cache.
- Product card preview sử dụng cùng bộ semantic tokens và tỷ lệ ảnh như storefront.
- Khi cập nhật giá, đơn đã tạo phải dùng **snapshot giá lúc đặt đơn**, không cập nhật lịch sử thanh toán.

---

## 8. Inventory — tồn kho và điều chỉnh

### 8.1. Inventory overview (`/inventory`)

**Bảng:** SKU, sản phẩm/variant, kho (nếu có), `on-hand`, `reserved`, `available`, ngưỡng tồn thấp, trạng thái, thời gian cập nhật.

**Định nghĩa hiển thị:** `available = on-hand - reserved` **chỉ khi backend đang dùng mô hình đó**; API phải là nguồn chuẩn và có thể có thêm damaged/incoming/quarantine.

**Filters:** sắp hết, hết hàng, kho, series, brand, loại figure, SKU.

### 8.2. Stock adjustment drawer

Trường: sản phẩm/SKU, kho, loại giao dịch (`nhập`, `điều chỉnh tăng/giảm`, `hỏng`, `kiểm kê`), số lượng, lý do, ghi chú, chứng từ (nếu có).

- Bắt buộc preview số lượng trước/sau.
- Không cho tùy ý giảm dưới mức hệ thống cho phép.
- Thao tác gửi API an toàn; không cập nhật giả định thành công khi chưa có kết quả.
- Ghi audit event và hiển thị nguồn thay đổi (đơn hàng, nhập kho, thao tác tay).

### 8.3. Inventory log

Bảng có bộ lọc SKU, thời gian, người thực hiện, loại giao dịch và nguồn; mỗi dòng chứa qty delta, quantity snapshot, reference ID, reason. Không chỉnh sửa lịch sử giao dịch đã ghi; chỉ thêm giao dịch bù nếu cần.

---

## 9. Pre-order Management — nghiệp vụ quan trọng của figure store

### 9.1. Danh sách đợt pre-order

**Cards + table switch (tùy mật độ):** ảnh figure, tên sản phẩm, ngày mở/đóng đặt trước, ngày phát hành dự kiến, số lượng slot, đã nhận cọc, trạng thái.

**Status riêng:** `scheduled`, `open`, `closed`, `awaiting_release`, `arrived`, `fulfilling`, `completed`, `cancelled` — chỉ là danh mục UI đề xuất; backend xác định trạng thái thực tế.

### 9.2. Chi tiết chiến dịch

```text
┌───────────────────────────────────────────────────────────────────────────┐
│  GOJO SATORU — PRE-ORDER BATCH 01           [Chỉnh sửa] [Đóng nhận cọc]    │
│  [Đang nhận đặt trước]  01/11/2026 – 30/11/2026                           │
├──────────────────────────────────────────────┬────────────────────────────┤
│  [Số slot] [Đã đặt] [Cọc hợp lệ] [Còn lại]   │  THÔNG TIN PHÁT HÀNH       │
│                                              │  ETA / Supplier status     │
├──────────────────────────────────────────────┤                            │
│  DANH SÁCH ĐẶT TRƯỚC                        │  CÀI ĐẶT CỌC             │
│  Order | Buyer | Deposit status | Action      │  Amount / policy           │
├──────────────────────────────────────────────┴────────────────────────────┤
│  RELEASE TIMELINE / THÔNG BÁO / LỊCH SỬ THAY ĐỔI                           │
└───────────────────────────────────────────────────────────────────────────┘
```

### 9.3. Những trường cốt lõi

- Sản phẩm + variant, batch ID, giới hạn slot, quota còn nhận, thời gian nhận đặt trước.
- Giá dự kiến/chính thức, số tiền hoặc tỷ lệ đặt cọc, hạn thanh toán phần còn lại (nếu áp dụng).
- ETA dự kiến và lịch thay đổi ETA; tách **ngày phát hành hãng** khỏi **ngày hàng về cửa hàng**.
- Supplier/manufacturer; nguồn cập nhật; điều khoản hoàn/hủy cọc.
- Lịch sử thanh toán cọc và khoản phải thu, do backend/payment service cung cấp.

### 9.4. Chỉ dẫn UX tránh sai sót

- Badge `Đã đặt cọc`, `Chưa cọc`, `Cọc thất bại`, `Đã thanh toán đủ` phải tách khỏi `Đơn đã xác nhận`.
- Mọi sửa đổi giá/cọc/ETA sau khi khách đặt phải có xác nhận, bản ghi audit và chính sách thông báo khách nếu phù hợp.
- Không được sử dụng UI để tự xác nhận thanh toán chỉ dựa trên screenshot chuyển khoản; backend là nguồn xác minh.
- Với số lượng giới hạn, slot reservation phải do server xử lý nguyên tử để tránh overbooking.

---

## 10. CMS Trang chủ — quản lý giao diện Anime Pop / Comic

### 10.1. Mục tiêu

Admin quản lý nội dung cho các section của storefront mà **không sửa source code**. Cho phép chọn figure, đổi headline, CTA, thứ tự section, hero artwork, nhưng **không** tùy ý chèn code HTML/JS.

### 10.2. Trang CMS (`/content/homepage`)

```text
┌─────────────────────────────────────────────────────────────────────────────┐
│  TRANG CHỦ                      [Xem trước] [Lưu nháp] [Xuất bản]            │
├───────────────────────────────┬─────────────────────────────────────────────┤
│  SECTION TREE                 │  LIVE / STAGING PREVIEW                     │
│  ≡ Announcement Bar           │                                             │
│  ≡ Hero Comic Cover Collage   │       [Desktop] [Tablet] [Mobile]           │
│  ≡ Explore Categories         │                                             │
│  ≡ Trending Now               │       VISUAL PREVIEW OF SELECTED SECTION    │
│  ≡ Featured Collections       │                                             │
│  ≡ Pre-order Spotlight        │                                             │
│  ≡ Shop by Anime              │                                             │
│  ≡ New Arrivals               │                                             │
│  ≡ Collector's Corner         │                                             │
├───────────────────────────────┴─────────────────────────────────────────────┤
│  Properties panel theo section đang chọn                                      │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 10.3. Section types được quản lý

| Section | Thuộc tính chỉnh sửa | Ràng buộc |
|---|---|---|
| Announcement Bar | Text, link, màu/theme preset, lịch hiển thị | Giới hạn độ dài; không HTML tùy ý |
| **Hero Comic Cover Collage** | Headline, tagline, figure, hình chính tách nền, background, badge, CTA, slide order | Giới hạn safe-area text/figure, crop desktop/mobile, tối đa số slide có thể cấu hình |
| Explore by Category | Danh mục được highlight, thứ tự, thumbnail | Dùng category ID hợp lệ |
| Trending Now | Mode manual/automatic, product IDs, số item | Chỉ sản phẩm visible/published |
| Featured Collections | Collection IDs, banner, layout preset | Preview đúng viewport |
| Pre-order Spotlight | Campaign IDs, text callout, CTA | ETA lấy từ nguồn nghiệp vụ; tránh ghi đè thủ công |
| Shop by Anime | Series IDs, cover, thứ tự | Series phải tồn tại |
| New Arrivals | Số lượng, chế độ lọc | Sắp xếp theo logic đã định nghĩa |
| Collector's Corner | Bài viết, hình ảnh, gallery | Quyền sử dụng asset và alt text |

### 10.4. Hero builder — thiết kế riêng

**Form bên trái hoặc drawer:**
- Campaign name (internal), trạng thái nháp/xuất bản.
- Main featured product; series anime và giá **đọc từ catalog** (không nhập lại).
- Hero display headline; subtitle; graphic variant/preset.
- Figure asset: PNG/WebP/AVIF phù hợp tách nền, background preset, decorative comic layer.
- Graphic sticker/badge (cho phép chọn template được duyệt).
- CTA label + internal route; mobile-specific crop/reposition.
- Schedule (nếu API cho phép) và thứ tự slide.

**Preview bên phải:** Desktop 1440, tablet 768, mobile 390; kiểm tra text clipping, z-index, contrast, figure safe-area. Preview không đồng nghĩa đã publish.

### 10.5. Draft / Publish / Rollback

- Save Draft → Preview → Publish; trạng thái có timestamp, người sửa.
- Cảnh báo khi một section tham chiếu tới sản phẩm đã ẩn/hết hiệu lực.
- Có lịch sử phiên bản và rollback khi backend hỗ trợ; ở MVP tối thiểu lưu audit + snapshot đã publish.
- Nếu có CDN/cache, hiển thị tiến độ đồng bộ sau publish.
- Một chiến dịch không xuất bản nếu CTA không có link hợp lệ hoặc asset thiếu.

---

## 11. Collections, Category, Series & Brand

### 11.1. Danh mục / loại figure

CRUD theo nhóm: Scale Figure, Nendoroid, Action Figure, Model Kits... Mỗi danh mục có name, slug, icon/cover, order, visibility, SEO. Có cảnh báo trước khi archive nếu danh mục đang liên kết sản phẩm.

### 11.2. Anime Series và nhân vật

- Series: tên chính thức, slug, aliases/tên tiếng Nhật (nếu cần), thumbnail, status.
- Nhân vật: name, series ID, aliases; tránh nhập tự do làm trùng tên.
- Có search combobox trong product editor.

### 11.3. Collection editor

- Tên, slug, cover/banner, mô tả, SEO, trạng thái, lịch hiển thị nếu có.
- Mode `manual` (sắp xếp sản phẩm thủ công) và `rule-based` (theo bộ lọc) nếu backend cho phép.
- Preview collection card, trang collection và mobile crop.

### 11.4. Hãng / Manufacturer

Trường: name, logo, description, country (nếu cần), status. Có thể dùng brand để lọc catalog; nhà cung cấp hàng hóa và hãng sản xuất là hai khái niệm khác nhau, không nên gộp tùy tiện.

---

## 12. Media Library

### 12.1. Layout

```text
┌──────────────────────────────────────────────────────────────────────────┐
│ MEDIA LIBRARY                                [Tạo folder] [Tải lên]       │
│ [Search] [File type] [Date] [Used/Unused] [Sort]                         │
├───────────────────┬──────────────────────────────────────────────────────┤
│ Folders           │  Thumbnail Grid / List toggle                         │
│  Product Images   │  [img] [img] [img] [img]                               │
│  Hero Campaigns   │  [img] [img] [img] [img]                               │
│  Categories       │                                                     │
│  Branding         │  Selected file → detail inspector / quick actions   │
└───────────────────┴──────────────────────────────────────────────────────┘
```

### 12.2. Upload / asset processing UX

- Drag/drop và file picker; progress riêng từng file, retry/cancel.
- Hiển thị MIME type, dung lượng, dimensions, format, thời gian tạo, người tải lên.
- Kiểm tra kích thước, loại file, giới hạn dung lượng và cảnh báo độ phân giải thấp; validate **cả client và server**.
- Hero asset bắt buộc có crop desktop/mobile hoặc focal point; product gallery có alt text.
- Khi xóa file được tham chiếu, phải chặn hoặc cảnh báo + hiển thị nơi đang sử dụng.
- Signed upload/download URLs và quyền truy cập phải do API/backend quyết định; **không lộ khóa S3 trong client**.
- Nếu cần tối ưu ảnh, quy trình resize/WebP/AVIF/thumbnails là trách nhiệm xử lý media của server/service phù hợp.

---

## 13. Customers, Promotions, Reviews

### 13.1. Customers (`/customers`)

- Danh sách: customer ID, tên, email/phone masked nếu cần, số đơn, total spending theo định nghĩa backend, last order, status.
- Chi tiết: hồ sơ tối thiểu, danh sách đơn, ghi chú nội bộ (có audit), hoạt động hỗ trợ.
- Search theo quyền; không hiển thị toàn bộ dữ liệu nhạy cảm cho Analyst/Content Editor.
- Hỗ trợ export chỉ cho vai trò được cấp và có audit.

### 13.2. Promotions (`/promotions`)

- Danh sách: mã, loại giảm, giá trị, hiệu lực, usage count, status, target audience/channel.
- Form: code, rules, min/max order, eligible products/collections, quota, thời gian.
- Preview “điều kiện áp dụng”; backend validate điều kiện và chống sử dụng vượt quota.
- Không lưu logic khuyến mãi độc lập trong UI.

### 13.3. Reviews (`/reviews`)

- Queue: pending / approved / rejected / reported.
- Card: sản phẩm, rating, nội dung review, ảnh, ngày, trạng thái, lý do moderation.
- Actions: approve/reject/hide, reason, audit trail; role hạn chế.

---

## 14. Analytics / Reporting

### 14.1. Layout báo cáo

**Header:** report title, time range, comparison period, export. **Body:** KPI row → trend chart → breakdown tables. Mỗi chart có title, subtitle giải thích metric, tooltip và data table alternative.

### 14.2. Báo cáo tối thiểu

| Report | Metrics | Bộ lọc |
|---|---|---|
| Sales Overview | Orders, recognized sales, AOV nếu định nghĩa đầy đủ | Date, channel, order type |
| Product Performance | Units sold, gross/net amount, returned units | Series, manufacturer, figure type |
| Inventory Health | Low/out-of-stock SKU, days of stock nếu có data | Warehouse, series, brand |
| Pre-order Funnel | Open campaigns, reservation count, deposit conversion | Batch, release window |

**Quy tắc báo cáo:** `Revenue`, `GMV`, `Collected payments`, `Deposits`, `Refunds` phải là các metric có công thức riêng; không dùng cùng nhãn cho các phép tính khác nhau. Empty state phải giải thích nếu dữ liệu chưa được đo lường.

---

## 15. Settings & User Management

### 15.1. Store settings

- General: tên shop, logo, timezone, currency VND, contact, địa chỉ hỗ trợ.
- Catalog: default stock threshold, policies, units, feature toggles theo backend.
- Checkout & logistics: vùng giao hàng, shipping providers, payment methods chỉ ở mức cấu hình UI mà API hỗ trợ.
- SEO/content: mặc định metadata, social links, policies.

Không lưu khóa bí mật thanh toán hay S3 ở form frontend dưới dạng có thể đọc lại. Các credential nhạy cảm phải cấu hình phía server hoặc qua cơ chế secret-management có kiểm soát.

### 15.2. Team / Roles

- Members table: name, email, role, last activity, account status.
- Invite flow: email → role/permission preview → send invitation → acceptance.
- Edit role: xác nhận và tránh thao tác tự tước quyền cuối cùng của Super Admin.
- Permissions matrix theo **resource × action** như `products.read/create/update/publish`, `orders.read/manage/refund`, `inventory.adjust`, `cms.publish`, `analytics.read`, `users.manage`.

### 15.3. Audit logs

| Trường | Ví dụ |
|---|---|
| Actor | User ID + vai trò |
| Timestamp | Datetime + timezone |
| Action | `product.publish`, `inventory.adjust`, `order.cancel` |
| Resource | ID + deep-link |
| Change summary | Trước → Sau (trường hợp phù hợp) |
| Correlation ID | ID request / trace để hỗ trợ debug |
| Result | success/failure |

Không lưu password, token, thông tin thẻ hoặc dữ liệu nhạy cảm vào audit log.

---

## 16. Thư viện components và interaction patterns

### 16.1. Layout components

`AdminShell`, `SidebarNav`, `SidebarGroup`, `Topbar`, `GlobalSearch`, `Breadcrumbs`, `PageHeader`, `ContentSection`, `RightInspector`, `StickyActionBar`, `PermissionGate` (chỉ hỗ trợ hiển thị).

### 16.2. UI primitives

`Button`, `IconButton`, `Link`, `Input`, `SearchInput`, `Textarea`, `Select`, `Combobox`, `DateRangePicker`, `Checkbox`, `Radio`, `Toggle`, `Badge`, `Tooltip`, `Popover`, `Drawer`, `Dialog`, `Tabs`, `Pagination`, `Toast`, `Skeleton`, `EmptyState`, `Alert`, `Progress`, `FileUpload`, `Avatar`, `Table`.

### 16.3. Domain components

`OrderStatusBadge`, `PaymentStatusBadge`, `FulfillmentStatusBadge`, `ProductTable`, `ProductVariantEditor`, `InventoryLevelBadge`, `StockAdjustmentDrawer`, `PreorderBatchCard`, `DepositStatusBadge`, `CampaignHeroPreview`, `SectionTree`, `ProductPicker`, `MediaPicker`, `PublishWorkflowPanel`, `MetricCard`, `AuditEventTimeline`.

### 16.4. Interaction rules

| Tình huống | Behavior bắt buộc |
|---|---|
| Save | Disable lặp trong lúc gửi, spinner, phản hồi kết quả rõ |
| Network error | Thông báo cụ thể, retry, giữ form values |
| 401 | Chuyển tới login, giữ return path hợp lệ |
| 403 | Hiển thị trang không đủ quyền; không giả vờ dữ liệu trống |
| 404 | Resource not found + quay lại danh sách |
| Validation 422 | Map lỗi theo field + summary đầu form |
| Conflict 409 | Cảnh báo dữ liệu đã được thay đổi ở phiên khác; reload/compare trước khi ghi đè |
| Delete/archive | Confirm, mô tả hậu quả, nhãn action chính xác |
| Dirty form | Confirm khi rời trang hoặc đóng tab |
| Empty state | Giải thích nguyên nhân và CTA hợp ngữ cảnh |
| Loading | Skeleton giữ bố cục; không nhấp nháy vô hạn |
| Partial success | Nêu bản ghi nào thành công/thất bại |
| Keyboard | Tab/Shift+Tab, Escape, Enter, focus return sau modal |

**Phân biệt `Xóa`, `Lưu trữ`, `Ẩn bán`, `Ngừng nhận cọc`:** đây là những hành động khác nhau, không thay nhãn cho nhau.

---

## 17. Frontend architecture — Astro + Tailwind CSS

### 17.1. Ứng dụng độc lập và cổng chạy

**Môi trường phát triển mặc định:**

| Dịch vụ | URL local | Mục đích |
|---|---|---|
| Storefront Astro | `http://localhost:4321` | Website khách hàng |
| **Admin Astro** | **`http://localhost:4322`** | Website quản trị |
| Backend/API | Do dự án quy định | Đăng nhập, catalog, đơn, tồn, CMS, audit |

Đặt **`server.port = 4322` trong cấu hình Astro của ứng dụng admin**, hoặc đặt trong script chạy riêng của admin và duy trì cấu hình nhất quán. Không chỉ dựa vào việc 4321 đang bị chiếm để Astro tự nhảy port. Khi test tích hợp cần kiểm tra chính xác 4321/4322, không dùng port tự tăng không dự đoán được.

**Lưu ý:** Port dev không quyết định port production. Với Node standalone, cấu hình port runtime và reverse proxy được quản lý bởi hệ thống deploy. Production dùng host/subdomain riêng và HTTPS; không expose admin trực tiếp trên Internet chỉ vì nó dùng port khác.

### 17.2. Tổ chức workspace (sơ đồ thư mục, không phải code)

```text
wibu-store/
├── apps/
│   ├── storefront/                # Astro site khách hàng — dev 4321
│   │   ├── src/
│   │   ├── public/
│   │   └── astro.config.*
│   └── admin/                     # Astro admin — dev 4322
│       ├── src/
│       │   ├── pages/             # URLs quản trị, login, 403, 404
│       │   ├── layouts/           # AdminShell, AuthLayout
│       │   ├── components/
│       │   │   ├── ui/            # Primitive UI
│       │   │   ├── shell/         # Sidebar, Topbar, Breadcrumbs
│       │   │   ├── data/          # Table, filters, pagination
│       │   │   ├── forms/         # Form fields, validation UI
│       │   │   └── domain/        # Orders, Products, Preorder, CMS...
│       │   ├── features/          # Module state + view models
│       │   ├── lib/               # Typed API clients, auth helpers
│       │   ├── styles/            # Tailwind entry + admin theme tokens
│       │   ├── middleware.*       # Session/auth guard SSR
│       │   └── types/             # Frontend view models
│       ├── public/
│       └── astro.config.*
├── packages/
│   ├── brand-tokens/             # Màu/font/semantic tokens dùng chung
│   ├── shared-types/             # API contracts/public types
│   └── shared-utils/             # Formatter, date, currency, safe helpers
└── workspace files               # Scripts chạy từng app, lint, docs
```

Không phụ thuộc trực tiếp code UI marketing của storefront trong admin. Chỉ chia sẻ phần có tính ổn định như tokens, utilities và type contracts. Nên cân nhắc sinh types từ OpenAPI schema nếu backend công bố schema.

### 17.3. Rendering và interactive islands

- **SSR/on-demand:** toàn bộ trang admin có dữ liệu, xác thực và phân quyền theo request.
- **Astro layout/components:** app shell, navigation, section layout, bảng ở trạng thái xem cơ bản.
- **Client islands:** bảng tương tác nặng (sort/filter/edit inline), form editor phức tạp, media upload, drag-reorder section, biểu đồ.
- Có thể chọn **React islands** cho các editor dữ liệu phức tạp nếu đội ngũ cần hệ sinh thái component; **không bắt buộc dùng React** cho cả ứng dụng.
- Dữ liệu nhạy cảm lấy tại server khi phù hợp; client interactions phải thông qua API được bảo vệ.

### 17.4. Tailwind CSS 4

- Sử dụng tích hợp chính thức qua **Vite plugin của Tailwind CSS 4**; không dùng `@astrojs/tailwind` legacy dành cho Tailwind 3.
- Brand tokens có lớp semantic và lớp component tokens; class sử dụng thống nhất giữa shell/form/table.
- Không ghép utility strings từ dữ liệu CMS không tin cậy.
- CSS custom chỉ dùng cho những trường hợp layout phức tạp như Comic Cover Preview, focus pattern và animation được tái sử dụng.

### 17.5. Data-access architecture

**Luồng chuẩn:** Admin Page/Island → Admin API client/BFF (nếu dùng) → Backend API → Database/Media service. SSR có thể truy cập API qua server-side client; tuyệt đối không đưa secret/service token vào bundle browser.

**Gợi ý tích hợp với Laravel nếu được chọn:**

- Dùng backend Laravel API làm nguồn chuẩn cho auth, RBAC, CRUD, order state transitions, inventory ledger và media upload authorization.
- Có thể chọn mô hình **Backend-for-Frontend (BFF)** trên Astro để xử lý session/cookie phía server và proxy API; hoặc same-site auth có thiết kế cookie/CORS/CSRF rõ ràng.
- Tránh lưu access token dài hạn trong `localStorage`.
- Chỉ whitelist origin cần thiết trong CORS; với cookie credential cần cấu hình chặt chẽ.
- Chống CSRF cho các thao tác thay đổi trạng thái nếu auth dựa vào cookie.

### 17.6. API contract tối thiểu

| Domain | Read | Write/Action |
|---|---|---|
| Auth | `me`, permissions, sessions | login, logout, reset, refresh/revoke |
| Dashboard | KPI, charts, task feed | — |
| Products | list/detail/variants/categories | create/update/archive/publish |
| Inventory | balances, movement log | adjust, reserve/release thông qua backend |
| Orders | list/detail/timeline | valid transitions, tracking, refund/cancel workflows |
| Pre-order | batches, reservations, deposits | create/update/close batch, allowed transitions |
| CMS | draft, published snapshot, sections | save draft, preview, publish, rollback |
| Media | list, usage references | upload authorization, metadata, delete/archive |
| Customers | list/detail by role | notes / controlled updates |
| Reports | aggregated data | report export if authorized |
| Settings | config, roles, audit | update config, invites, permission changes |

**Quy ước:** Pagination, sorting, filters, error schema, validation details, correlation ID, rate limits và idempotency cho mutation cần được chuẩn hóa cùng backend. Giá tiền lưu/trao đổi theo kiểu số nguyên đơn vị VND hoặc dạng decimal chính xác được backend quy định; không dùng float để tính tiền.

### 17.7. Data models/UI view models

**Product:** id, slug, title, status, seriesId, characterIds, brandId, categoryIds, coverAssetId, galleryAssetIds, description, specifications, variants, seo, publishedAt.

**Variant:** id, productId, sku, optionValues, sellingPrice, compareAtPrice, stockTracking, availability, mediaIds.

**Order:** id, displayCode, customerSummary, lineItemSnapshots, totals, currency, paymentStatus, fulfillmentStatus, orderStatus, addresses (redacted by permission), createdAt, updatedAt.

**InventoryBalance:** sku/variantId, warehouseId (if used), onHand, reserved, available, lowStockThreshold, updatedAt.

**InventoryMovement:** id, sku, delta, reason, source, actor, occurredAt.

**PreorderBatch:** id, productVariantId, startAt, closeAt, expectedReleaseAt, estimatedArrivalAt, depositPolicy, slotLimit, reservedSlots, status, updatedAt.

**HomepageSection:** id, type, position, enabled, settings JSON validated by type, draftVersion, publishedVersion, lastEditedBy.

**AuditEvent:** id, action, actorId, resourceType, resourceId, changeSummary, correlationId, result, timestamp.

Type trên là hợp đồng hiển thị gợi ý, **không phải cấu trúc database bắt buộc**. UI dùng IDs ổn định, lưu snapshot lịch sử nghiệp vụ và không tự tin tưởng JSON từ CMS.

---

## 18. Security, privacy & permissions

### 18.1. Xác thực

- Login page riêng, không dùng admin shell.
- Ưu tiên MFA/2FA cho quản trị viên khi backend/identity provider hỗ trợ.
- Session cookie an toàn với `HttpOnly`, `Secure` ở HTTPS, `SameSite` phù hợp với mô hình domain; rotation/expiration thực hiện server-side.
- SSR kiểm tra session trước khi dựng trang protected; loading client-side không thay cho server authorization.
- Logout revoke session server-side và điều hướng về login.

### 18.2. RBAC

| Permission group | Owner | Store Manager | Catalog Editor | Fulfillment | Support | Analyst |
|---|---|---|---|---|---|---|
| Products / CMS | Full | Manage | Edit (publish theo quyền) | Read | Read limited | Read |
| Orders | Full | Manage | Read limited | Process assigned | Support | Aggregate only |
| Inventory | Full | Manage | Read/limited adjust | Read/adjust assigned | Read limited | Aggregate |
| Pre-order | Full | Manage | Edit campaigns | Fulfill assigned | Support | Aggregate |
| Customers / PII | Full under policy | Allowed by policy | Minimal | Minimal | Needed scope | No raw PII |
| Reports | Full | Full | Limited | Limited | Limited | Read reports |
| Roles / secrets | Full | Limited | None | None | None | None |

Bảng chỉ là baseline UX; **authorization backend có tính quyết định ở từng endpoint/resource**. Nếu user không có quyền, ẩn hành động và vẫn trả 403 cho request trái phép.

### 18.3. Nguyên tắc an toàn

- Cấu hình CORS, CSRF, content-security policy, security headers và upload scanning phù hợp deployment.
- Dùng rate limiting login và các mutation nhạy cảm; lưu log đăng nhập thất bại.
- Escape/sanitize rich content trước khi hiển thị trên storefront; tránh render HTML không kiểm soát từ CMS.
- Không expose API keys, S3 credentials, internal service URLs chứa token hoặc secrets trong asset public.
- Không cache HTML chứa thông tin quản trị cá nhân bằng shared CDN theo cách làm rò dữ liệu.
- Không dùng port khác, URL khó đoán hoặc `robots.txt` như cơ chế bảo mật.

---

## 19. Accessibility & performance

### 19.1. Accessibility

- Mục tiêu **WCAG 2.2 AA** cho luồng đăng nhập, CRUD, duyệt đơn và CMS.
- Focus state dễ nhìn, keyboard navigable sidebar/menu/dropdown/modal.
- Label và mô tả lỗi có liên kết với input; có `aria-live` cho save/toast cần thiết.
- Table có headings/accessible sorting; charts có summary và dạng bảng.
- Tương phản text tối thiểu phù hợp cấp AA; không dùng chữ trắng trên comic yellow.
- Control quan trọng có vùng chạm mục tiêu tối thiểu khoảng 44×44 px khi có thể; đáp ứng tối thiểu tiêu chí WCAG.
- Tôn trọng `prefers-reduced-motion` cho transitions.

### 19.2. Performance targets

- Không tải dữ liệu toàn bộ catalog/orders vào client; pagination/lazy-loading theo server.
- Hoãn tải chart/editor/media list khi chưa hiện hoặc chưa truy cập.
- Ảnh thumbnail tối ưu kích thước; virtualize bảng rất dài nếu cần.
- Bộ lọc gõ tự do có debounce; cancel request cũ khi đổi query nhanh.
- Giới hạn bundle JS cho shell; chỉ hydrate islands có tương tác.
- Cần instrumentation cho API latency, web vitals và errors trong môi trường thật; mục tiêu performance phụ thuộc thiết bị và dữ liệu.

---

## 20. Workflow và các trạng thái giao diện

### 20.1. Page states bắt buộc

Mỗi màn hình danh sách/chi tiết phải định nghĩa: `initial loading`, `success`, `empty`, `no search results`, `permission denied`, `API error`, `offline/intermittent`, `stale data`, `mutation pending`, `mutation success`, `mutation failed`.

### 20.2. Form workflow chuẩn

1. **Create** → điền trường bắt buộc → save draft → kiểm tra lỗi → optional preview → publish khi đủ quyền.
2. **Edit** → tải dữ liệu và version → sửa → validate → submit → xử lý conflict → success toast + updated timestamp.
3. **Archive** → xác nhận ràng buộc với dữ liệu liên quan → API action → thông báo kết quả.
4. **Dangerous mutation** → show impacted records → require explicit confirmation → audit → detail refresh.

### 20.3. Notifications / task center

- Notification có loại, mức độ, nội dung ngắn, thời gian, link đến tài nguyên.
- Đã đọc/chưa đọc không thay trạng thái nghiệp vụ thực tế.
- Task item ưu tiên dựa vào hạn xử lý và mức độ; có filter `Unassigned / Mine / All` nếu backend hỗ trợ.

---

## 21. Kế hoạch triển khai theo giai đoạn

### Giai đoạn 0 — Chuẩn bị kiến trúc

- Tạo app Astro admin riêng; xác nhận port **4322** không xung đột 4321.
- Dùng Tailwind CSS 4, Be Vietnam Pro, shared brand tokens.
- Chốt contract API, roles, auth model, chính sách session.
- Tạo app shell responsive, login, 403, 404.

**Deliverable:** admin shell + navigation + mock data và design system ổn định.

### Giai đoạn 1 — Catalog & Media

- Products list, create/edit, variants, categories, series, brands.
- Media Library: upload, asset picker, product gallery.
- Inventory overview và stock adjustment (khi backend sẵn sàng).

**Deliverable:** tạo/publish figure và hiển thị đúng trên storefront.

### Giai đoạn 2 — Orders & Pre-order

- Orders list/detail, timeline, trạng thái thanh toán/giao hàng tách biệt.
- Fulfillment action, pre-order campaign management, deposit status.
- Dashboard KPI cơ bản và task queue.

**Deliverable:** nhân sự có thể vận hành giao dịch hằng ngày từ admin.

### Giai đoạn 3 — CMS & Promotions

- Homepage sections builder, Hero Comic Cover Collage preview.
- Draft/publish/rollback, featured collections, banner.
- Promotions, reviews moderation.

**Deliverable:** đội vận hành thay nội dung trang chủ mà không sửa code.

### Giai đoạn 4 — Reporting & Hardening

- Báo cáo chuyên sâu, export, audit logs, user/role settings.
- Accessibility/performance/security QA, E2E critical flows.
- Production release checklist và hướng dẫn vận hành.

**Deliverable:** admin đủ tin cậy cho môi trường thực tế.

---

## 22. Acceptance Criteria / Checklist nghiệm thu

### 22.1. Cấu hình & routing

- [ ] Storefront hoạt động ở `localhost:4321` trong môi trường dev.
- [ ] **Admin độc lập ở `localhost:4322`**, có thể mở đồng thời mà không tranh port.
- [ ] Route admin không render trong storefront và ngược lại.
- [ ] Trang admin bảo vệ bằng session/authorization thực tế (không chỉ `display:none`).
- [ ] Có trang 401/403/404 và logout phù hợp.

### 22.2. Design & responsive

- [ ] Light theme đồng nhất với Wibu Store; brand red/yellow/blue sử dụng tiết chế.
- [ ] Be Vietnam Pro hiển thị tiếng Việt tốt trên form và data tables.
- [ ] Sidebar, topbar, breadcrumbs và action hierarchy nhất quán.
- [ ] Ở 1440, 1024, 768, 390, 320 px không mất các CTA cốt lõi.
- [ ] Form, modal, drawer, table, empty states, loading/error states có thiết kế riêng.
- [ ] Keyboard navigation và contrast được kiểm tra.

### 22.3. Catalog, Orders, Inventory

- [ ] Products list có search/filter/sort/pagination đúng dữ liệu từ API.
- [ ] Product editor có media, variant, giá, trạng thái draft/published, validation.
- [ ] Cập nhật sản phẩm phản ánh đúng tới storefront khi publish/sync hoàn tất.
- [ ] Orders detail phân biệt trạng thái đơn, thanh toán và giao hàng.
- [ ] Inventory adjustment có lý do, quantity before/after và audit log.
- [ ] Pre-order tách được deposit status, slot, ETA và fulfillment.
- [ ] Mutation lỗi không hiển thị thành công giả; có retry/giải thích.

### 22.4. CMS

- [ ] Có thể đổi sản phẩm Hero, asset, headline và CTA qua CMS.
- [ ] Preview 3 viewport desktop/tablet/mobile.
- [ ] Draft không tự xuất hiện public; publish có trace/audit.
- [ ] CMS chống tham chiếu asset/product không hợp lệ.

### 22.5. Security & production

- [ ] Mỗi endpoint backend xác thực và kiểm quyền, không chỉ phụ thuộc front-end.
- [ ] Không lộ access token/API key/S3 secret trên trình duyệt.
- [ ] Có giới hạn session và bảo vệ CSRF nếu dùng cookie auth.
- [ ] Audit với thao tác nhạy cảm; không lưu secrets vào logs.
- [ ] Production admin triển khai bằng HTTPS và host/subdomain riêng hoặc truy cập nội bộ thích hợp.
- [ ] Kiểm tra E2E: login → tạo sản phẩm → publish → xem storefront → tạo đơn test → xử lý đơn.

---

## 23. Những quyết định cần chốt trước khi kết nối backend

| Câu hỏi | Hướng mặc định đề xuất | Ảnh hưởng |
|---|---|---|
| API backend là gì? | Dùng chung một backend, có thể là Laravel API | Auth, contract, deploy |
| Catalog có variants/warehouse không? | Chuẩn bị UI hỗ trợ biến thể; warehouse optional | SKU, tồn kho |
| Pre-order được cọc hay trả đủ? | Định nghĩa policies theo từng batch | Payment, order, reporting |
| Điều kiện xuất bản CMS? | Draft → preview → publish, có version | Content model / cache |
| Khách hàng có tài khoản không? | Support guest và registered nếu backend cho phép | Customer module |
| Hệ thống phân quyền? | Resource×action, backend là nguồn chuẩn | Nav, guard, API |
| Phương thức thanh toán/vận chuyển? | Không hardcode UI trước khi có contract | Workflow order |
| Domain production? | Tách admin subdomain và storefront | Cookies, CORS, TLS |

**Các mục chưa được chốt không cản trở triển khai UI mock**, nhưng phải được xác nhận trước khi gọi một tính năng là hoàn chỉnh về nghiệp vụ.

---

## 24. Design handoff cho frontend / AI coding agent

**Thứ tự ưu tiên thực hiện UI:**

1. Tạo app Admin Astro riêng, Tailwind CSS 4, port 4322, SSR scaffolding và AuthLayout.
2. Implement toàn bộ tokens, typography, Sidebar/Topbar/Breadcrumbs, Button/Input/Badge/Dialog/Drawer/Table.
3. Mock Dashboard, Products list, Product Editor, Orders list/detail, Inventory, Pre-orders, Homepage CMS.
4. Chuẩn hóa state/loading/errors trước khi kết nối thật.
5. Tách API client có type contracts, không hardcode dữ liệu nghiệp vụ trong components.
6. Kết nối backend theo từng vertical slice, có auth/RBAC server-side và audit.
7. Kiểm thử responsive, keyboard, API failures và production settings.

**Không được tự ý:**

- Đổi light theme admin sang dark theme chỉ vì storefront trước đây từng theo Dark Premium.
- Dùng comic panels/halftone trong bảng đơn hàng, form thanh toán hoặc biểu đồ.
- Gộp admin vào storefront port 4321, hoặc dùng việc đổi port làm cách bảo mật.
- Đưa toàn bộ Astro admin thành client-only SPA nếu không có yêu cầu kỹ thuật rõ.
- Tự tạo nghiệp vụ thanh toán, trạng thái cọc và trừ tồn chỉ ở phía frontend.
- Công khai trang admin mà không có authorization thực tế.

---

## 25. Tài liệu kỹ thuật tham chiếu

- Astro — Styling và Tailwind CSS 4: https://docs.astro.build/en/guides/styling/
- Astro — Configuration Reference (đặc biệt `server.port`): https://docs.astro.build/en/reference/configuration-reference/
- Astro — On-demand Rendering / SSR: https://docs.astro.build/en/guides/on-demand-rendering/
- Astro — Node Adapter: https://docs.astro.build/en/guides/integrations-guide/node/
- Astro — Middleware: https://docs.astro.build/en/guides/middleware/
- Astro — Sessions: https://docs.astro.build/en/guides/sessions/

**Quan hệ tài liệu:** Spec admin này bổ sung cho **`Wibu_Store_Anime_Pop_Comic_Light_Theme_Spec.md`** (storefront v2.0). Hai sản phẩm dùng chung hệ nhận diện nhưng có layout và hành vi riêng. Đặc tả này là tài liệu thiết kế/triển khai; không thay cho tài liệu API, security review hoặc quy định kế toán-vận hành.
