# WIBU STORE — ANIME POP / COMIC STYLE FRONTEND SPECIFICATION

> **Phiên bản:** 2.0 — 17/10/2026  
> **Trạng thái:** Design & frontend implementation specification  
> **Sản phẩm:** Website thương mại điện tử bán mô hình anime, manga, action figure, Nendoroid và collectibles  
> **Hướng nghệ thuật:** **Anime Pop Art × Comic/Manga Editorial × Modern E-commerce**  
> **Theme mặc định:** **Light theme** (không triển khai dark mode ở giai đoạn đầu)  
> **Frontend:** **Astro + Tailwind CSS 4 + TypeScript**  
> **Phạm vi:** UI/UX, design system, trang, component, tương tác, cấu trúc frontend và tiêu chí nghiệm thu. **Không có code mẫu.**

---

## 1. Tóm tắt quyết định thiết kế

### 1.1. Concept đã chọn

Wibu Store được thiết kế như một **cửa hàng manga-pop hiện đại**: cảm giác đọc một trang truyện tranh nhiều màu, kết hợp ảnh chụp mô hình thật và trải nghiệm mua sắm thương mại điện tử rõ ràng.

**Từ khóa:** `Comic cover`, `Anime pop`, `Bold typography`, `Graphic collage`, `Halftone`, `Ink outline`, `Collectibles`, `Clean commerce`.

**Nguyên tắc cốt lõi:** **Visual nổi bật ở marketing; giao diện đơn giản ở thao tác mua hàng.** Không biến toàn bộ website thành các khung truyện tranh gây nhiễu khi tìm kiếm, so sánh sản phẩm hoặc thanh toán.

### 1.2. Thay đổi so với spec v1 Dark Premium

| Thành phần | Trước đây — Dark Premium | Bản mới — Anime Pop / Comic |
|---|---|---|
| Theme | Nền navy/charcoal tối | Nền giấy trắng ngà, bề mặt trắng |
| Cảm xúc | Showroom cao cấp, cinematic | Vui tươi, cá tính, năng lượng manga |
| Hero | Chia đôi text / khung ảnh | **Comic Cover Collage**: figure lớn xếp lớp với typography và panel |
| Màu chính | Electric cyan | **Pop red** dành cho CTA và brand |
| Màu hỗ trợ | Neon cyan/pink | **Comic yellow**, cobalt blue, pink pastel |
| Typography | Clean sans + dark display | **Anton** cho chữ lớn tiếng Anh, **Be Vietnam Pro** cho UI/tiếng Việt |
| Đường viền | Thin border, soft glow | Solid ink-outline, shadow offset tiết chế |
| Card sản phẩm | Premium dark card | Clean light card, badge comic |
| Animation | Glow/parallax | Slide-in, sticker pop, panel reveal; có giảm chuyển động |

### 1.3. Những điều bắt buộc / không được làm

**Nên làm:**

- Dùng hình figure thực tế, nền trong suốt hoặc nền studio sạch, đủ sắc nét.
- Để một điểm nhìn chính duy nhất trong mỗi campaign; có khoảng trống để sản phẩm nổi bật.
- Áp dụng comic graphic tại Hero, category, campaign và một số badge; không áp dụng tràn lan cho mọi form.
- Ưu tiên điều hướng và giỏ hàng trực quan như website bán hàng chuyên nghiệp.
- Thiết kế mobile theo bố cục riêng; không chỉ thu nhỏ desktop.

**Không làm:**

- Không dựng Hero 50/50 gồm text bên trái và một khung ảnh gradient chữ nhật lớn bên phải như phiên bản cũ.
- Không đặt pattern halftone phía sau đoạn văn dài, giá tiền hoặc thông tin thanh toán.
- Không dùng font manga/decorative cho body text, giá, mô tả hoặc form.
- Không tự động phát âm thanh, video nặng hay hiệu ứng gây mất tập trung.
- Không sao chép nguyên giao diện hay artwork từ mẫu tham khảo; cần bản sắc Wibu Store và quyền sử dụng asset hợp lệ.

---

## 2. Khách hàng mục tiêu & ưu tiên chuyển đổi

| Nhóm khách | Hành vi | UI phải hỗ trợ |
|---|---|---|
| Người sưu tầm figure | Tìm theo series, nhân vật, hãng, phiên bản | Search, filter chuyên sâu, thông số rõ |
| Fan anime mới bắt đầu | Khám phá anime/nhân vật yêu thích | Category card hấp dẫn, shop by anime |
| Khách săn hàng pre-order | Theo dõi mở đặt trước, cọc, ngày phát hành | Pre-order zone, timeline và trạng thái nhất quán |
| Khách mua quà | Tìm sản phẩm theo giá và loại | Navigation đơn giản, price range, best-seller |

**Luồng chính:** Trang chủ → Danh mục/Anime/Collection → Danh sách sản phẩm → Chi tiết → Giỏ hàng → Checkout.

**Luồng pre-order:** Trang chủ → Pre-order → Chi tiết → Đọc điều khoản đặt cọc → Tạo đơn → Xác nhận từ backend.

**KPI cần theo dõi khi có analytics:** click-through từ Hero, tỷ lệ xem PDP, add-to-cart rate, search-to-product rate, checkout completion rate và hiệu suất mobile.

---

## 3. Design system — LIGHT THEME

### 3.1. Bảng màu chính thức

| Semantic token | HEX | Vai trò | Hướng dẫn |
|---|---|---|---|
| `color.canvas` | `#FFFDF7` | Nền toàn trang, sắc giấy ấm | Default body background |
| `color.canvasAlt` | `#F7F5F0` | Section xen kẽ | Phân vùng mà không tạo cảm giác nặng |
| `color.surface` | `#FFFFFF` | Product card, modal, dropdown | Không dùng gradient rực rỡ cho card mua hàng |
| `color.surfaceSoft` | `#FFF4DC` | Nền nhấn, callout nhẹ | Dùng cho campaign hoặc khối giới thiệu |
| `color.ink` | `#17141F` | Heading, text chính, viền mạnh | Ink gần đen, tránh #000 tuyệt đối ở diện rộng |
| `color.textSecondary` | `#534F5C` | Nội dung phụ | Dùng cho mô tả, metadata quan trọng |
| `color.textMuted` | `#6A6572` | Caption, thời gian, nhãn phụ | Không nhạt hơn cho text nhỏ |
| `color.border` | `#D8D4D0` | Viền trung tính, đường phân cách | Card thương mại, form |
| `color.inkBorder` | `#26212B` | Viền comic/hero/sticker | 2–3 px ở nơi mang tính đồ họa |
| `color.brand` | `#D6284F` | CTA chính, brand red | Chữ trắng; tương phản khoảng 4.9:1 |
| `color.brandHover` | `#AF1E40` | Hover/focus brand | Tránh sáng quá khiến chữ trắng khó đọc |
| `color.accentYellow` | `#FFD44A` | Comic panel, tag promo, nền campaign | Dùng chữ màu ink |
| `color.accentBlue` | `#3258D7` | Nhấn phụ, link/graphic | Dùng chữ trắng khi ở dạng nút/panel đậm |
| `color.accentPink` | `#FFE6ED` | Panel phụ, collectible theme | Dùng chữ ink |
| `color.accentMint` | `#DDF9EB` | Collection phụ hoặc visual details | Không làm màu CTA chính |
| `color.success` | `#147A4B` | Trạng thái còn hàng, thông báo thành công | Kèm văn bản, không chỉ dùng màu |
| `color.warning` | `#9A5A00` | Cảnh báo/đặt trước cần lưu ý | Dùng chữ có độ tương phản cao |
| `color.danger` | `#B42318` | Lỗi form, thao tác nguy hiểm | Không sử dụng làm graphic accent thông thường |

**Tỷ lệ màu tham khảo:** 65% nền trắng/ngà, 20% đen/màu chữ/khung viền, 10% vàng comic và các panel sáng, 5% đỏ brand + xanh cobalt theo ngữ cảnh. Đây là định hướng art direction, không phải phép đo bắt buộc trên từng trang.

**Màu của Hero theo collection:** Có thể thay nền panel theo anime (vàng/cam/xanh/đỏ), nhưng CTA chính và các token điều hướng phải nhất quán. Bất kể artwork nào, tên, giá và CTA luôn dễ đọc.

### 3.2. Typography

**Bộ font chọn:**

| Vai trò | Font | Quy tắc |
|---|---|---|
| Display tiếng Anh | **Anton** | Chỉ dùng headline ngắn, uppercase, comic poster, số hiệu; không dùng cho đoạn dài hoặc tiếng Việt |
| Hệ thống UI, heading tiếng Việt và body | **Be Vietnam Pro** | Font chính trên toàn site; hỗ trợ tiếng Việt, rõ ở cỡ nhỏ |
| Ký tự Nhật thực sự (tùy chọn) | **Noto Sans JP** | Chỉ nạp nếu content có tiếng Nhật; không dùng chữ Nhật chỉ để lấp khoảng trống |

**Typography scale đề xuất:**

| Thành phần | Desktop | Mobile | Font / weight / line-height |
|---|---|---|---|
| Hero display English | 76–112 px | 42–58 px | Anton / 400 / 0.92–1.0 |
| Hero Vietnamese heading | 42–64 px | 30–40 px | Be Vietnam Pro / 800 / 1.12 |
| Section title | 36–48 px | 26–32 px | Be Vietnam Pro / 800 / 1.15 |
| Campaign oversized numbering | 72–128 px | 48–72 px | Anton / 400 / 1.0 |
| Product detail title | 28–36 px | 22–28 px | Be Vietnam Pro / 700–800 / 1.3 |
| Product card title | 15–17 px | 14–16 px | Be Vietnam Pro / 600–700 / 1.45 |
| Body | 16 px | 15–16 px | Be Vietnam Pro / 400 / 1.6 |
| Price prominent | 22–26 px | 18–22 px | Be Vietnam Pro / 800 / 1.25 |
| Button | 14–16 px | 14–15 px | Be Vietnam Pro / 700 / 1.2 |
| Eyebrow, small metadata | 12–13 px | 12 px | Be Vietnam Pro / 600–700 / 1.4 |

**Quy chuẩn:** Chỉ dùng **2 font bắt buộc** để giữ tốc độ. Anton dành cho tiếng Anh và cụm như `NEW DROP`, `YOUR NEXT OBSESSION`, `FIGURE OF THE WEEK`. Heading tiếng Việt phải dùng Be Vietnam Pro; không ép uppercase với đoạn tiếng Việt dài. Giới hạn mỗi đoạn body khoảng 60–75 ký tự trên desktop.

### 3.3. Spacing & layout tokens

| Nhóm | Giá trị đề xuất |
|---|---|
| Spacing unit | 4 px |
| Spacing scale | 4 / 8 / 12 / 16 / 24 / 32 / 40 / 48 / 64 / 80 / 96 / 128 px |
| Content container | Tối đa 1360–1440 px, canh giữa |
| Gutter desktop | 48–64 px |
| Gutter tablet | 24–32 px |
| Gutter mobile | 16–20 px |
| Section spacing desktop | 88–112 px |
| Section spacing mobile | 52–72 px |
| Product grid gap | 20–24 px desktop; 12–16 px mobile |
| Border standard | 1 px neutral |
| Comic outline | 2–3 px ink |
| Radius product card | 16 px |
| Radius button | 10–12 px |
| Radius image/panel | 18–24 px |
| Radius sticker | 999 px hoặc dạng góc nhọn tùy campaign |
| Offset shadow | 4–6 px lệch phải/dưới; chỉ cho sticker/CTA/graphic card |

### 3.4. Hình khối / họa tiết manga

1. **Halftone dots:** Chấm tròn giảm opacity, đặt ở góc panel; không lót bên dưới thông tin giá.
2. **Speed lines:** Đường tốc độ phía sau figure chính, hướng về điểm nhìn trung tâm.
3. **Comic panels:** Các mảng nền màu hình thang, polygon hoặc hình chữ nhật xoay nhẹ, viền mực 2–3 px.
4. **Speech bubble:** Dùng rất hạn chế cho nhãn campaign như `NEW DROP!` hoặc `LIMITED`; tránh khiến người dùng tưởng đó là thành phần chat.
5. **Sticker badges:** `BEST SELLER`, `PRE-ORDER`, `NEW`, `LIMITED` với text ngắn, không đè lên chi tiết figure.
6. **Page numbering:** `01 / 04`, `VOL. 01` hoặc `ISSUE 01` là mô-típ visual, không phải số trang kỹ thuật.

**Quy tắc mật độ:** Tối đa 2–3 graphic motifs nổi bật trong một viewport. Các section có product grid phải giảm mật độ graphic xuống đáng kể.

### 3.5. Iconography, border, shadow

- Icon line-weight 1.75–2 px, bo góc vừa phải, đồng nhất trên toàn site.
- Hành động quan trọng luôn có label hoặc accessible name; không chỉ dựa vào icon.
- Không đặt outline 3 px lên tất cả product card: chỉ campaign card, category feature và sticker cần comic outline.
- Shadow offset nên mang sắc ink, không dùng glow neon.

---

## 4. Grid & responsive

| Vùng | Viewport tham chiếu | Grid | Quy tắc |
|---|---:|---:|---|
| Mobile nhỏ | 320–374 px | 4 cột | Không bắt buộc hiển thị 2 product card nếu ảnh quá nhỏ |
| Mobile | 375–639 px | 4 cột | Listing 2 cột, panel hero xếp dọc |
| Tablet | 640–1023 px | 8 cột | Listing 2–3 cột, navigation gọn |
| Desktop | 1024–1439 px | 12 cột | Listing 3–4 cột |
| Wide | ≥1440 px | 12 cột | Listing 4 cột, Hero có không gian collage rộng |

- Độ cao Hero desktop theo nội dung, thường 620–760 px (không bắt buộc full viewport).
- Kích thước ảnh figure tính theo **chiều cao hình**, đảm bảo không crop đầu/vũ khí/base của mô hình.
- Trên mobile: thứ tự **eyebrow → headline → ảnh figure → giá/CTA → thumbnails**. Hero không được cao quá mức khiến người dùng khó nhận ra phần tiếp theo.
- Sticky header desktop 72–80 px, mobile 60–64 px; announcement bar không cần sticky.
- Cho phép vùng sản phẩm kéo ngang bằng thao tác touch, nhưng không ẩn hoàn toàn mũi tên hoặc dấu hiệu có thể cuộn.

---

## 5. Site map / các trang phải thiết kế

| Route tham khảo | Trang | Ưu tiên |
|---|---|---|
| `/` | Homepage | P0 |
| `/figures` | Catalog tổng | P0 |
| `/categories/[slug]` | Danh mục figure | P0 |
| `/anime/[slug]` | Sản phẩm theo anime/series | P1 |
| `/collections` | Danh sách collection | P1 |
| `/collections/[slug]` | Trang bộ sưu tập | P1 |
| `/products/[slug]` | Product detail | P0 |
| `/pre-orders` | Pre-order listing | P1 |
| `/search` | Kết quả tìm kiếm | P0 |
| `/cart` | Giỏ hàng | P0 |
| `/checkout` | Thanh toán | P0 (phụ thuộc backend) |
| `/wishlist` | Yêu thích | P1 |
| `/account/*` | Tài khoản / đơn hàng | P1 (phụ thuộc backend) |
| `/policies/*`, `/contact`, `/about` | Chính sách và thông tin cửa hàng | P0/P1 |

**Không bổ sung landing page chỉ để trang trí nếu không dẫn đến collection, listing hoặc PDP thực sự.**

---

## 6. Homepage — bố cục tổng thể

### 6.1. Thứ tự section

1. Announcement bar + Header.
2. **Comic Cover Hero** (điểm nhấn chính).
3. Quick Explore / Shop by Category.
4. Trending Figures.
5. Featured Manga Collection.
6. Pre-order Spotlight.
7. Shop by Anime.
8. New Arrivals.
9. Collector's Corner / Social Proof (P2).
10. Footer.

### 6.2. Global header & announcement

**Announcement bar:** Nền comic yellow, chữ ink đậm; nội dung 1 câu ngắn (ưu đãi vận chuyển / thông tin pre-order). Desktop cao 32–36 px, mobile 28–32 px. Không tự động chạy chữ liên tục.

**Desktop header:** Bề mặt trắng, viền đáy 1 px. Bên trái logo Wibu Store; ở giữa các menu `Figures`, `Anime`, `Collections`, `Pre-order`; bên phải ô tìm kiếm + account/wishlist/cart. Search ưu tiên đủ rộng cho tên nhân vật hoặc series.

**Mobile header:** Logo → icon Search → icon Cart → menu. Search mở thành trang/overlay riêng; menu dạng drawer chứa đầy đủ danh mục và chính sách. Cart count thể hiện bằng badge có text tương đương cho screen reader.

**Sticky behavior:** Khi cuộn, header gọn hơn nhưng không che section anchor. Giữ tương phản khi overlay lên Hero sáng.

---

## 7. Hero chính — COMIC COVER COLLAGE (THAY HOÀN TOÀN SPLIT HERO)

### 7.1. Art direction

**Tên concept:** `WIBU COMIC COVER — THE NEXT COLLECTIBLE`.

Hero phải giống **bìa manga / poster sản phẩm pop-art**, không phải hai cột text và ảnh quảng cáo. Một figure được đặt lớn gần trung tâm, hơi lệch phải; background gồm 2–3 tấm graphic panel màu sắc tương phản, nét viền comic; chữ display kích thước lớn đóng vai trò nhịp bố cục.

**Các lớp visual từ sau ra trước:**

1. Nền `paper` sáng hoặc panel vàng comic cỡ lớn.
2. Chấm halftone, speed lines mờ, nhãn series hoặc số issue.
3. Panel nghiêng / khối nền cobalt và pink tạo chiều sâu.
4. Typography display cực lớn phía sau nhân vật, nhưng không làm tên sản phẩm khó đọc.
5. Ảnh figure thật **đã tách nền**, chiều cao nổi bật.
6. Sticker `NEW DROP` / `LIMITED`, card thông tin giá và CTA, bộ chọn slide nhỏ.

### 7.2. Wireframe desktop định hướng

```text
┌─────────────────────────────────────────────────────────────────────────────┐
│  [ISSUE 001 • FEATURED DROP]                    [NEW ARRIVAL!]             │
│                                                                             │
│    YOUR NEXT               BIG FIGURE PHOTO          + COMIC PANEL         │
│    OBSESSION.                (FOREGROUND)            / SPEED LINES          │
│                          [GIANT CHARACTER]                                  │
│    Series / name                   ┌───────────────┐                         │
│    2-line intro                    │ COLORED PANEL │                         │
│                                    └───────────────┘                         │
│    Price + stock               [SMALL STICKER]                               │
│    [VIEW FIGURE →]  [PRE-ORDER]                         [01 / 04]            │
│                                                                             │
│           [thumbnail 01] [thumbnail 02] [thumbnail 03] [thumbnail 04]       │
└─────────────────────────────────────────────────────────────────────────────┘
```

Đây là **wireframe bố trí tương đối**. Không cần triển khai đúng theo tọa độ ký tự. Bố cục cuối phải cân bằng với hình dáng figure thực tế; nhân vật có tóc/vũ khí/base lớn cần khoảng trống riêng.

### 7.3. Thành phần Hero bắt buộc

| Thành phần | Nội dung | Hướng dẫn vị trí |
|---|---|---|
| Eyebrow | `NEW DROP`, `FEATURED`, `PRE-ORDER` | Góc trên bên trái, dùng label ngắn |
| Display headline | `YOUR NEXT OBSESSION.` | 2–3 dòng, không che mặt figure |
| Product name | Ví dụ tên nhân vật + scale | Dưới headline hoặc callout cạnh figure |
| Series/brand | Anime, manufacturer, line | Metadata nhỏ nhưng vẫn đọc được |
| Price | VNĐ đúng format | Không để đè lên graphic nhiều màu |
| Status | In stock / Pre-order / Sold out | Badge có text và logic rõ |
| Primary CTA | `Xem mô hình` / `Đặt trước` | Dễ thấy khi nhìn lần đầu |
| Secondary CTA | `Khám phá bộ sưu tập` | Không cạnh tranh về màu với primary CTA |
| Figure artwork | Ảnh figure tách nền có bản quyền sử dụng | Chiếm ưu thế thị giác, không bị crop |
| Navigation | Thumbnail + `01/04`, nút prev/next | Người dùng chủ động chuyển |

**Thông điệp gợi ý:** `UNBOX YOUR NEXT OBSESSION.` hoặc `COLLECT THE UNIVERSE.` Các câu tiếng Anh này phục vụ display, còn mô tả và thao tác mua hàng ưu tiên tiếng Việt.

### 7.4. Trạng thái và tương tác Hero

- Mặc định hiển thị 1 sản phẩm/collection được biên tập nổi bật.
- Thumbnail chọn các campaign khác nhau; thay đổi ảnh, tên, giá, trạng thái, panel màu và CTA như một khối đồng bộ.
- **Không autoplay** ở bản đầu; nếu thêm về sau cần pause và tôn trọng reduced motion.
- Nút next/prev, thumbnail và tab phải điều khiển được bằng keyboard; focus rõ ràng.
- Không sử dụng WebGL bắt buộc. Ảnh PNG/WebP/AVIF tách nền kết hợp CSS layers là đủ.
- Khi ảnh không tải, giữ bố cục ổn định, có nền thay thế và vẫn đọc được nội dung sản phẩm.
- Nếu sản phẩm hết hàng, CTA mua phải được thay bằng trạng thái hợp lệ; không vẫn hiển thị `Mua ngay` như hàng có sẵn.

### 7.5. Hero mobile

- Bỏ các panel nhỏ gây chồng chéo; giữ 1 nền chính + 1 halftone motif.
- Headline rút xuống tối đa 2–3 dòng, ưu tiên tiếng Việt ngắn hoặc display English rõ.
- Figure nằm trung tâm, cao khoảng 250–380 px tùy thiết bị và asset.
- Tên/giá/trạng thái và CTA hiển thị gần figure, không bị đẩy quá xa.
- Thumbnail thành hàng cuộn ngang hoặc chấm chỉ báo có label hỗ trợ.
- CTA trải ngang gần full-width; không cố giữ hai nút cạnh nhau trên màn hình hẹp.

---

## 8. Các section homepage còn lại

### 8.1. Quick Explore — Shop by Category

**Bố cục:** Một hàng 4 category card desktop (`Scale Figures`, `Nendoroid`, `Action Figures`, `Model Kits`); tablet 2 cột; mobile 2 cột hoặc cuộn ngang tùy độ rộng.

**Visual:** Mỗi category là comic panel với nền màu pastel khác nhau, ảnh figure không che tên, title lớn góc dưới, arrow icon. 1 card nổi bật có thể chiếm gấp đôi chiều ngang nếu ảnh đủ đẹp.

**Hover:** Ảnh phóng 3–5%, mũi tên dịch nhẹ; tránh xoay quá nhiều làm layout rung.

### 8.2. Trending Figures

**Mục đích:** Chuyển từ phần nghệ thuật sang giao diện mua sắm rõ ràng.

- Heading: `TRENDING NOW` dùng Anton; subheading tiếng Việt với Be Vietnam Pro.
- Product grid: desktop 4 cột, tablet 2–3, mobile 2.
- Card có ảnh sản phẩm nền sáng, tên, series/brand, giá, trạng thái và wishlist.
- Quick-add chỉ hiển thị nếu sản phẩm không cần chọn biến thể/đọc điều khoản pre-order.
- Dùng tab `Nổi bật`, `Bán chạy`, `Yêu thích` nếu có dữ liệu; không hiện tab giả.

### 8.3. Featured Manga Collection

**Bố cục:** Một comic banner lớn lệch trái + 2 panel nhỏ bên phải, khác với grid sản phẩm đều nhau.

**Visual:** Dùng tông thương hiệu của collection và cutout figure, headline dạng poster. Có subtitle giải thích series/collection. Link đi đến trang bộ sưu tập thực.

**Không làm:** Kéo nguyên ảnh cover manga có bản quyền làm nền toàn chiều rộng khi chưa có quyền sử dụng.

### 8.4. Pre-order Spotlight

**Bố cục:** Nền `surfaceSoft` hoặc `canvasAlt`, có nhãn `PRE-ORDER ZONE`, danh sách 3–4 figure sắp phát hành.

Mỗi card hiển thị **tình trạng nhận đặt**, **ngày phát hành dự kiến**, **giá sản phẩm**, **số tiền đặt cọc nếu có**, **đường dẫn điều khoản**. Không dùng nhãn `sắp hết hàng` nếu không có cơ sở dữ liệu đáng tin cậy.

### 8.5. Shop by Anime

**Bố cục:** Lưới logo/cover franchise hoặc danh mục theo chữ cái; 4–6 series nổi bật trên trang chủ.

**Visual:** Các card hình poster, tối giản graphic ngoài ảnh chính. Có thể thêm số lượng figure theo series nếu backend cung cấp.

### 8.6. New Arrivals

**Bố cục:** Grid tiêu chuẩn 4 cột desktop / 2 cột mobile, card giống section Trending để đảm bảo nhất quán.

Hiển thị ngày thêm hàng hoặc nhãn `Mới về`; tránh dùng sticker rực rỡ cho cả 8 sản phẩm cùng lúc.

### 8.7. Collector's Corner — P2

Khối nội dung biên tập nhẹ: `Cách chọn scale figure`, `Cách bảo quản mô hình`, gallery ảnh sản phẩm có quyền sử dụng, review. Không để UGC lên website nếu chưa có quyền và quy trình kiểm duyệt.

### 8.8. Footer

Footer nền ink `#17141F` là **vùng tối duy nhất có quy mô lớn** trong theme sáng; text trắng/ngà. Gồm menu mua sắm, chính sách đổi trả/đặt trước, hỗ trợ khách hàng, social, thông tin doanh nghiệp và phương thức thanh toán nếu đã tích hợp thật.

---

## 9. Product listing / Catalog

### 9.1. Bố cục

**Desktop:** Breadcrumb → page title + kết quả → toolbar sắp xếp → sidebar filter 240–280 px → product grid 3–4 cột. **Mobile:** Breadcrumb rút gọn → title → sticky/surface toolbar `Lọc` / `Sắp xếp` → grid 2 cột.

### 9.2. Filter đề xuất

- Anime/series; nhân vật; hãng sản xuất.
- Dòng mô hình: scale, Nendoroid, action, model kit.
- Giá; tình trạng (còn hàng, pre-order, hết hàng); release period.
- Scale/kích thước nếu có; sắp xếp theo mới nhất, giá, bán chạy.

Filter cần sync với URL để chia sẻ, lưu bookmark và hoạt động với back/forward. Filter trên mobile là drawer/bottom sheet, có nút `Áp dụng` và `Xóa bộ lọc`.

### 9.3. Product card chuẩn

| Khu vực | Nội dung | Quy tắc |
|---|---|---|
| Media | Ảnh figure rõ, tỉ lệ thống nhất | Nền sạch, giữ khoảng thở, có placeholder |
| Badge | `NEW`, `PRE-ORDER`, `SOLD OUT` | Chỉ 1–2 badge cùng lúc |
| Metadata | Series, hãng sản xuất | Dòng chữ nhỏ nhưng đủ tương phản |
| Title | Tên figure + phiên bản | Clamp 2 dòng, tránh nhảy độ cao |
| Price | Giá niêm yết / khuyến mãi nếu hợp lệ | Format VNĐ rõ |
| Action | Xem chi tiết / wishlist / quick add | Không tạo CTA bị disabled giả |

**Visual:** Card trắng, 1 px neutral border, 16 px radius; hover chỉ nâng nhẹ hoặc outline ink mỏng. Một dải màu ở phía trên card có thể phân biệt collection nhưng không bắt buộc.

### 9.4. Các trạng thái phải có

- Loading skeleton theo đúng kích thước card.
- Empty category, empty search, empty filter và lỗi API.
- Sold out, discontinued, pre-order closed, archived.
- Pagination hoặc load-more có URL và khả năng quay lại vị trí trước đó.

---

## 10. Product Detail Page (PDP)

### 10.1. Bố cục desktop

- Trên: Breadcrumb và nhãn series.
- Trái 55–60%: ảnh sản phẩm lớn + gallery thumbnails + zoom.
- Phải 40–45%: tên, series, hãng, scale, giá, trạng thái, số lượng, nút mua/đặt trước, vận chuyển/đổi trả.
- Dưới: mô tả, thông số kỹ thuật, ảnh chi tiết, sản phẩm liên quan.

**Khác homepage:** PDP chủ yếu nền trắng, tránh những graphic speed lines phía sau hình ảnh chi tiết vì sản phẩm cần được đánh giá chính xác.

### 10.2. Gallery & product image

- Cho phép xem nhiều góc (mặt trước, sau, base, hộp, chi tiết).
- Có zoom bằng click/tap; modal có nút đóng, Esc và focus management.
- Ảnh không bị cắt làm mất chi tiết quan trọng.
- Khi có ảnh 360°/3D thật, đó là feature tùy chọn, không phải yêu cầu bắt buộc của phiên bản đầu.

### 10.3. Trạng thái mua hàng

| Trạng thái | CTA chính | Thông tin thêm |
|---|---|---|
| Có sẵn | `Thêm vào giỏ` | Số lượng khả dụng nếu backend cho phép công bố |
| Đặt trước | `Đặt trước` | Giá cọc, tổng tiền, dự kiến nhận, điều khoản |
| Hết hàng | Không tạo đơn thường | `Thông báo khi có hàng` nếu có chức năng |
| Sắp ra mắt | Xem thông tin | Dự kiến mở bán nếu đã xác nhận |
| Ngừng bán | Không cho mua | Có thể dẫn đến sản phẩm tương tự |

**Bất biến:** Tồn kho, tổng thanh toán, giá cọc, điều kiện trả cọc phải được backend xác thực. Frontend chỉ trình bày, không tự quyết định tính hợp lệ của giao dịch.

### 10.4. Mobile PDP

Gallery trên cùng, theo sau là title → giá → trạng thái → CTA → thông số. Sticky purchase bar có thể xuất hiện khi vùng mua chính ra khỏi viewport; phải chừa safe-area và không che nội dung.

---

## 11. Search, Cart, Checkout, Pre-order

### 11.1. Search

- Desktop: search box trong header, gợi ý gồm ảnh nhỏ, sản phẩm, series và category.
- Mobile: màn tìm kiếm riêng hoặc overlay full-height, autofocus có kiểm soát.
- Debounce nhập liệu; không truy vấn mỗi ký tự nếu không cần.
- Empty query, no results, recent query nếu có lưu cục bộ và cho phép xóa.
- Kết quả có URL chia sẻ được; keyword phải hiển thị/escape an toàn.

### 11.2. Cart

- Trên desktop có thể dùng cart drawer để phản hồi add-to-cart, đồng thời có trang `/cart` đầy đủ.
- Cart row: ảnh, tên, thuộc tính phiên bản, giá, trạng thái hàng, số lượng, remove.
- Tách dòng tiền **hàng có sẵn** và **pre-order** nếu chính sách không cho thanh toán chung; quy tắc này chờ backend/business xác nhận.
- Empty cart có CTA về catalog, không hiển thị tổng tiền giả.
- Trong cart, giá/tồn kho cần xác thực lại trước checkout.

### 11.3. Checkout

**UI ưu tiên tối giản, không mang graphic comic dày đặc.** Các vùng chính: thông tin liên hệ, địa chỉ, vận chuyển, thanh toán, tổng đơn, điều khoản cần chấp nhận. Trên mobile giữ một cột, tổng đơn ở vùng thu gọn có thể mở.

- Validation rõ ràng theo trường.
- Thông báo lỗi có text và hướng khắc phục.
- Không hiển thị `Đặt hàng thành công` khi backend hoặc cổng thanh toán chưa xác nhận.
- Checkout là giao diện; backend/payment gateway, chống gian lận, phí vận chuyển, dữ liệu cá nhân là các phần triển khai riêng.

### 11.4. Trang Pre-order

Có filter theo `Đang mở`, `Sắp mở`, `Đã đóng`; item có timeline/ETA và nhắc rằng ngày phát hành là dự kiến. Hiển thị rõ tiền đặt cọc khác với giá sản phẩm đầy đủ; điều khoản hủy/hoàn cọc có đường dẫn riêng.

---

## 12. Component inventory

### 12.1. Global / layout

`AnnouncementBar`, `SiteHeader`, `DesktopNavigation`, `MobileMenu`, `SearchOverlay`, `Breadcrumb`, `SectionHeader`, `Footer`, `Pagination`, `Toast`, `Modal`, `Drawer`, `EmptyState`, `LoadingSkeleton`.

### 12.2. Comic marketing

`ComicCoverHero`, `HeroFigureCutout`, `ComicPanel`, `HalftonePattern`, `SpeedLineAccent`, `StickerBadge`, `IssueCounter`, `FeaturedCollectionBanner`, `CategoryComicCard`, `AnimeSeriesCard`, `CampaignThumbnailRail`.

### 12.3. Commerce

`ProductCard`, `ProductGrid`, `PriceDisplay`, `StockStatus`, `PreOrderBadge`, `ProductGallery`, `VariantSelector`, `QuantitySelector`, `AddToCartButton`, `WishlistButton`, `FilterSidebar`, `FilterDrawer`, `SortMenu`, `SearchSuggestionList`, `CartItem`, `CartSummary`, `CheckoutForm`, `PreOrderTerms`.

**Quy tắc component:** Mọi component phải có mô tả trách nhiệm, content slots/props, responsive behavior, trạng thái loading/empty/error/disabled, interaction/focus state và yêu cầu accessible label. Không nhúng nội dung hard-code của Gojo/Naruto vào component tái sử dụng.

---

## 13. Tương tác và motion design

| Tình huống | Motion khuyến nghị | Thời gian tham chiếu |
|---|---|---|
| CTA hover | Dịch lên 1–2 px / shadow offset nhẹ | 120–180 ms |
| Product card hover | Scale media rất nhẹ, viền rõ | 180–240 ms |
| Hero đổi slide | Crossfade figure + dịch panel ngắn | 300–450 ms |
| Comic sticker reveal | Scale từ 0.96 lên 1 | 180–240 ms |
| Menu/drawer | Slide + backdrop fade | 180–280 ms |
| Toast | Fade/slide ngắn, không chặn thao tác | 160–240 ms |

**Không dùng:** hiệu ứng đảo card liên tục, parallax mạnh theo chuột, tự động rung CTA, text animation từng ký tự không cần thiết, chuyển cảnh chặn thao tác mua.

**Reduced motion:** Khi người dùng yêu cầu giảm chuyển động, tắt transform lớn, parallax, các hiệu ứng reveal không thiết yếu. Đừng giấu nội dung vì animation không chạy.

---

## 14. Hướng dẫn triển khai Astro + Tailwind CSS 4 — KHÔNG CODE

### 14.1. Lựa chọn kỹ thuật

| Hạng mục | Công nghệ / định hướng |
|---|---|
| App framework | Astro, TypeScript |
| Styling | Tailwind CSS 4, tích hợp bằng Vite plugin theo hướng dẫn Astro hiện hành |
| UI architecture | Astro components cho phần tĩnh, interactive islands cho phần cần trạng thái |
| Client interaction | Vanilla TypeScript trước; cân nhắc Preact/Svelte khi workflow thực sự phức tạp |
| Icons | Một bộ icon SVG nhất quán; ưu tiên tree-shaking |
| Images | Astro image optimization cho ảnh local; CDN resizing với ảnh từ CMS/backend |
| Data source | Mock typed fixtures trước, sau đó adapter gọi API |
| Backend | Không nằm trong phạm vi spec này; thiết kế contract để nối về sau |
| Theme | Light only trong v2; token semantic cho phép mở rộng về sau |

### 14.2. Rendering strategy

- Trang marketing, category, collection và phần lớn nội dung PDP render thành HTML server/static có SEO tốt.
- Header search suggestion, wishlist, cart counter/drawer, filter động và checkout form sử dụng interactive islands hoặc JavaScript nhỏ đúng nơi cần.
- Không biến toàn bộ site thành SPA nếu không cần.
- Dữ liệu giá/tồn kho động cần chiến lược kiểm tra lại từ API và cache phù hợp; SEO page HTML không được coi là nguồn xác thực giao dịch.
- Build tĩnh hay SSR chọn theo nhu cầu tồn kho, cập nhật catalog, auth và deployment; có thể dùng hybrid rendering theo trang.

### 14.3. Tổ chức thư mục — ở mức trách nhiệm

| Khu vực trong `src` | Mục đích |
|---|---|
| `pages` | Routes trang chủ, listing, PDP, search, cart, checkout |
| `layouts` | Site shell, SEO metadata, global header/footer |
| `components/ui` | Button, Badge, Input, Modal, Drawer, Skeleton, Toast |
| `components/comic` | Panel, sticker, halftone, hero collage, campaign visual |
| `components/catalog` | Product card, filters, sort, grid, pagination |
| `components/product` | Gallery, price, variants, purchase controls |
| `components/cart` | Cart drawer, cart item, summary |
| `components/checkout` | Form, step, summary, payment state UI |
| `styles` | Global style, design tokens, font configuration, graphic utilities |
| `lib` | Format tiền, URL/filter parser, helpers, API client |
| `types` | Product, collection, campaign, cart, order types |
| `data` | Mock products/categories/hero campaigns giai đoạn đầu |
| `assets` | Logo, graphic SVG nguyên bản, ảnh local đã tối ưu |

### 14.4. Tailwind architecture

1. Khai báo **semantic tokens** từ mục 3 để component dùng token thay vì rải hex tùy ý.
2. Chỉ tạo utility/custom class cho motif đặc thù như halftone, comic border, offset shadow, speed lines.
3. Dùng component variants thống nhất: primary/secondary/ghost/danger cho button; in-stock/pre-order/sold-out cho status.
4. Điều chỉnh breakpoint theo layout thực tế, không thiết kế chỉ quanh một chiều rộng desktop.
5. Không thêm thư viện UI hoặc animation nặng chỉ để làm một nhãn comic.
6. Tách art direction (graphic panel) khỏi data và action của sản phẩm.

### 14.5. Nội dung có thể cấu hình

Các phần nên cho phép lấy dữ liệu từ CMS/API: announcement, hero campaign, danh mục nổi bật, bộ sưu tập, danh sách trending/new arrival, sản phẩm pre-order, social link và chính sách. Tuy nhiên cấu trúc `ComicCoverHero` và design system là quy chuẩn frontend; CMS không nên cho phép tùy ý thay mọi màu/font gây mất đồng nhất.

---

## 15. Data model / hợp đồng dữ liệu ở mức khái niệm

**Product cần:** ID, slug, name, series/anime, character, manufacturer, category, scale, specifications, price, currency, available variants, stock status, pre-order state, deposit, expected release date, images, SEO fields.

**Hero campaign cần:** ID, title, eyebrow, description, active product/collection, figure image (transparent), asset focal point, background variant/panel palette, badge, CTA labels + destinations, display order, publication dates.

**Collection/Anime cần:** ID, slug, title, description, hero artwork, products, theme accent (giới hạn trong bảng màu hợp lệ), SEO metadata.

**Cart cần:** line item, variant, quantity, displayed price, final price verification state, subtotal, shipping/discount/tax estimates, purchase type (in-stock/pre-order).

**Các trạng thái có thể phát sinh:** loading, ready, empty, error, stale data, unavailable, authentication required. Khi mất kết nối API, giao diện phải nêu rõ giới hạn thay vì giả định vẫn mua được.

---

## 16. Asset direction & content rules

### 16.1. Ảnh và tỷ lệ khung

| Asset | Định hướng |
|---|---|
| Main Hero Figure | PNG/WebP/AVIF nền trong suốt, ảnh gốc đủ lớn để tránh mờ ở desktop; có vùng an toàn cho tóc/vũ khí |
| Hero panel graphic | SVG/CSS do đội thiết kế tự tạo, không phụ thuộc ảnh nền khổng lồ |
| Product listing image | Tỷ lệ nhất quán 1:1 hoặc 4:5; figure nằm trọn trong khung |
| Product detail | Ảnh chất lượng cao nhiều góc, zoom được; giữ màu sản phẩm trung thực |
| Category / Collection | Hình nghệ thuật có điểm nhìn rõ, có chỗ đặt chữ |
| Anime series banner | Artwork hợp pháp, có fallback graphic do Wibu Store thiết kế |

**Quyền sử dụng:** Hình anime, character key art, logo franchise và ảnh sản phẩm phải được sử dụng theo quyền, giấy phép hoặc nguồn cho phép thích hợp. Tài liệu tham khảo thiết kế không cấp quyền tái sử dụng artwork.

### 16.2. Copywriting

- Giọng văn trẻ trung, nhanh gọn, nhưng thông tin mua hàng phải chính xác.
- Dùng tiếng Việt cho tên chức năng và chính sách; tiếng Anh chỉ là phần tạo dấu ấn visual như `DROP`, `COLLECT`, `LIMITED`.
- Tránh marketing mơ hồ như `Chỉ còn 1` nếu không có dữ liệu; không dùng countdown giả.
- Pre-order phải phân biệt rõ `giá dự kiến`, `giá cuối cùng`, `tiền cọc` và `thời gian dự kiến`.
- Viết alt text theo **mô hình thực tế và góc chụp**, không lặp keyword SEO.

---

## 17. Accessibility & usability

- Đạt mục tiêu **WCAG 2.2 AA** cho thao tác và nội dung trọng yếu.
- Văn bản nhỏ/body cần contrast tối thiểu 4.5:1; icon/outline điều khiển tối thiểu 3:1 ở nơi áp dụng.
- CTA brand red `#D6284F` trên chữ trắng có độ tương phản khoảng 4.9:1; màu vàng luôn đi với chữ ink, không dùng chữ trắng.
- Tất cả link/button/toggle phải điều khiển bằng keyboard, có focus-visible rõ.
- Image carousel có control tiếp theo/trước, không phụ thuộc swipe.
- Modal/drawer có focus trap hợp lý, Esc để đóng (trừ bước bắt buộc an toàn), trả focus về trigger.
- Form cần label hiển thị, mô tả lỗi cụ thể; placeholder không thay thế label.
- Target chạm tối thiểu theo tiêu chí WCAG 2.2 và ưu tiên 44 × 44 px với icon quan trọng.
- Không đặt thông tin thiết yếu duy nhất trong ảnh hoặc chữ outline trang trí.
- Nội dung vẫn đọc được nếu animation hoặc một asset decoration không tải.

---

## 18. Performance, SEO và chất lượng

### 18.1. Mục tiêu performance

| Chỉ số | Mục tiêu tham chiếu |
|---|---|
| LCP | ≤ 2.5 giây ở mức đánh giá p75 thực tế |
| INP | ≤ 200 ms ở mức đánh giá p75 thực tế |
| CLS | ≤ 0.1 ở mức đánh giá p75 thực tế |
| Main Hero image | Ưu tiên tải, không lazy-load ảnh LCP |
| Below-the-fold images | Lazy-load, khai báo kích thước để tránh CLS |
| JavaScript | Chỉ hydrate chức năng tương tác cần thiết |
| Fonts | Ưu tiên 2 họ bắt buộc, subset phù hợp, font-display hợp lý |

Các chỉ tiêu là **mục tiêu kiểm thử**, không phải cam kết đạt trên mọi thiết bị/mạng.

### 18.2. SEO / discovery

- Semantic HTML: một H1 chính mỗi trang, H2 cho các section, breadcrumb logic.
- Title/meta description riêng cho home, category, series, collection và PDP.
- Structured data `Product`/`Offer` cho dữ liệu thực và hợp lệ; breadcrumb structured data nếu cần.
- Canonical & rules index/noindex cho filter/search phù hợp chiến lược SEO.
- Sitemap, robots, Open Graph image cho series/collection, URL slug nhất quán.
- Hình figure có alt text thật; logo/border/halftone trang trí không cần alt mô tả dài.

### 18.3. Kiểm thử

Desktop Chrome/Firefox/Safari; iOS Safari và Android Chrome; breakpoint 320 / 375 / 768 / 1024 / 1440 px; keyboard-only flow; reduced-motion; slow network; mock API errors; long product names; giá rất dài; ảnh thiếu/tải lỗi.

---

## 19. Milestones triển khai

### M0 — Design foundation

- Chốt logo Wibu Store phiên bản dùng trên nền sáng, font và toàn bộ token.
- Xây layout grid, header, footer, button, badge, form, product card.
- Tạo graphic SVG gốc: halftone, sticker, comic panel, speed line.

### M1 — Homepage / Hero

- Xây Comic Cover Hero có tối thiểu 3 campaign mock.
- Hoàn thiện quick categories, trending, featured collection, pre-order, shop by anime và new arrivals.
- Rà soát desktop/mobile, kiểm tra art direction với ảnh figure thật.

### M2 — Discovery & PDP

- Hoàn thiện catalog, category, anime landing, search và filter có URL.
- Xây product detail, gallery, variants, trạng thái stock/pre-order.
- Test các tên sản phẩm dài và phiên bản đặc biệt.

### M3 — Cart & checkout UI

- Cart drawer + cart page, checkout form, validation, loading/error/success placeholders.
- Định nghĩa điểm nối API; không giả định backend đã có.

### M4 — Polish & release readiness

- Rà soát accessibility, SEO, ảnh/fonts, performance.
- Kiểm thử các luồng mua hàng, empty state, lỗi API, responsive.
- Chuyển mock data sang CMS/API khi có contract chính thức.

---

## 20. Acceptance criteria / Definition of Done

### 20.1. Thị giác

- [ ] Theme mặc định **light** với nền giấy `#FFFDF7`, không còn canvas navy từ v1.
- [ ] Hero đã chuyển thành **comic cover collage**, không giữ split rectangle 50/50.
- [ ] Figure là điểm nhìn chính và không bị các graphic chiếm ưu thế.
- [ ] Font Anton chỉ xuất hiện đúng vai trò display tiếng Anh; Be Vietnam Pro là font UI/body.
- [ ] CTA brand red, hệ badge và border nhất quán trên toàn site.
- [ ] Product grid và checkout sạch sẽ, không bị trang trí quá mức.

### 20.2. Tính năng

- [ ] Menu, search, filters, pagination, PDP, gallery, cart UI vận hành phù hợp với dữ liệu mock/API.
- [ ] Các trạng thái tồn kho/pre-order/sold-out/empty/error được xử lý.
- [ ] URL filter/search có thể chia sẻ; thao tác back/forward đúng.
- [ ] Các nút checkout không báo thành công sai khi chưa có xác nhận backend.

### 20.3. Responsive & kỹ thuật

- [ ] Không có horizontal overflow ngoài vùng slider được chủ đích.
- [ ] Hero/PDP/catalog đọc và thao tác tốt ở 320 px, 375 px, tablet, desktop.
- [ ] Focus, keyboard, reduced motion, form error và contrast đã kiểm tra.
- [ ] Đã đo Core Web Vitals hoặc chạy Lighthouse và ghi nhận điều cần tối ưu.
- [ ] Không có lỗi build TypeScript/Astro/Tailwind và không tải asset không có quyền.

---

## 21. Các quyết định cần xác nhận trước giai đoạn production

| Quyết định | Giá trị mặc định trong spec | Cần xác nhận với business/backend |
|---|---|---|
| Theme | Light only | Có triển khai dark mode về sau không? |
| Màu brand | Pop red `#D6284F` | Logo hiện tại có tương thích? |
| Main hero | Comic Cover Collage | Sản phẩm nào có ảnh tách nền đúng chuẩn? |
| Hero behavior | Manual thumbnail/navigation | Có cần auto-rotation không? (khuyến nghị không) |
| Pre-order | Hiện giá/cọc/ngày dự kiến | Quy tắc cọc, hủy, nhận hàng, tách cart? |
| Stock | Hiển thị dữ liệu backend | Có cho phép thông báo khi có hàng? |
| Checkout | Frontend form theo contract | Gateway, ship provider, account/guest flow? |
| CMS | Cấu hình nội dung campaign | Ai quản trị banner, giá, sản phẩm? |

---

## 22. Nguồn tham khảo

**Nguồn cảm hứng giao diện (không sao chép trực tiếp):**

1. [E-commerce website for Anime Merch — Auxano Global Services, Dribbble](https://dribbble.com/shots/19061671-E-commerce-website-for-Anime-Merch) — Mẫu C đã chọn, tham khảo ngôn ngữ Anime Merch / Comic e-commerce.
2. [KYIORO — Anime & Manga Merchandise E-commerce, Behance](https://www.behance.net/gallery/243950509/KYIORO-Anime-manga-merchandise-Ecommerce) — Tham khảo cách kết hợp visual identity táo bạo và flow mua hàng.

**Tài liệu triển khai kỹ thuật:**

3. [Astro — Styles and CSS](https://docs.astro.build/en/guides/styling/) — Tailwind CSS 4 qua Vite plugin.
4. [Astro — Islands architecture](https://docs.astro.build/en/concepts/islands/) — Chỉ hydrate các khu vực cần tương tác.
5. [Tailwind CSS — Documentation](https://tailwindcss.com/docs) — Design tokens, responsive utilities và styling conventions.
6. [Web Content Accessibility Guidelines 2.2 — W3C](https://www.w3.org/TR/WCAG22/) — Contrast, keyboard, pointer targets và form usability.
7. [web.dev — Core Web Vitals](https://web.dev/articles/vitals) — LCP, INP, CLS.

---

## Kết luận thực thi

**Hướng thiết kế cuối cùng:** **Anime Pop / Comic Editorial, Light Theme**, đặt ảnh figure thật vào các poster panel sáng với typography mạnh, đường viền mực và một số họa tiết manga mang tính điểm nhấn. **Hero Comic Cover Collage** là yếu tố nhận diện chủ đạo; catalog, PDP, cart và checkout giữ tính rõ ràng, tin cậy, dễ mua.

**Quy tắc ưu tiên khi phát triển:** `Khả năng mua hàng > ảnh sản phẩm > tính dễ đọc > trang trí > animation`.
