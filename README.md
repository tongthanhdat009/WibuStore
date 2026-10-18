# Wibu Store — Anime Figure & Collectibles E-commerce

Mono-repo: **Astro storefront** (`frontend/`, :4321) + **Astro admin** (`frontend-admin/`, :4322) + **Laravel 13 REST API** (`backend/`, :8000, PostgreSQL).

Spec duy nhất: `Wibu_Store_Laravel_PostgreSQL_Backend_Architecture_Spec.md` (backend), `Wibu_Store_Anime_Pop_Comic_Light_Theme_Spec.md` (storefront), `Wibu_Store_Admin_Astro_Tailwind_Design_Spec.md` (admin).

## Yeu cau

- Docker + Postgres dang chay (`postgres-docker-postgres-1`, port `127.0.0.1:5432`, user `app`).
- Node 20+ + pnpm, tmux (cho `make dev`). Backend khong can PHP local (chay qua image `wibu-php:8.3`).

## Chay nhanh (3 services)

```bash
make doctor     # kiem tra postgres / ports / node / php image
make db-create  # tao db wibu_store + wibu_store_test (1 lan)
make fresh      # migrate + seed demo (admin@wibustore.vn / Admin@123)
make dev        # chay api :8000 + storefront :4321 + admin :4322 (tmux 'wibu')
make status     # kiem tra 3 services
make stop       # dung tat ca
```

Luu y: neu port `8000` dang bi container khac chiem, doi sang port khac khi dev:

```bash
make dev API_PORT=8001
```

Chay le tung service: `make dev-backend`, `make dev-storefront`, `make dev-admin`.

## Storefront tich hop API

`frontend/` lay catalog tu API (`PUBLIC_API_URL`, mac dinh `http://localhost:8000`),
fallback du lieu tinh khi backend offline. Build luc backend dang chay se
prerender trang chi tiet cho toan bo san pham API (`frontend/.env`).
Gio hang luu `variantId` tu API; checkout goi `POST /api/v1/orders` that
(COD/chuyen khoan), hien ma don `WB-...` tu server thay vi mock.

## Backend

```bash
make test       # phpunit (db wibu_store_test, 10 tests)
make pint       # code style
make queue      # queue worker (mail giao dich)
make schedule   # scheduler (expire reservation, purge carts)
```

- REST base path `/api/v1`, contract loi `{error:{code,message}}`, tien integer VND.
- Auth: Sanctum session/cookie — frontend goi `/sanctum/csrf-cookie` truoc, gui `Origin` + `X-XSRF-TOKEN`.
- OpenAPI: `backend/docs/api/openapi.yaml`.
- Khong commit secrets: `.env` da gitignore, password chi doc tu moi truong.

## Tai khoan demo

| Role        | Email              | Mat khau   |
| ----------- | ------------------ | ---------- |
| super_admin | admin@wibustore.vn | Admin@123  |
