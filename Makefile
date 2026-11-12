# Wibu Store — dev orchestration (pwsh, chay duoc tren Windows + Linux/macOS co pwsh)
# Backend chay bang PHP host + pdo_pgsql (Windows: bat extension=pdo_pgsql trong php.ini).
# DB: Postgres san co 127.0.0.1:5432 (user app, db wibu_store / wibu_store_test).
# QUAN TRONG: ban make Windows32 khong truyen duoc UTF-8 qua command line,
# nen moi DONG RECIPE (dong bat dau bang TAB) phai la ASCII thuan (tieng Viet
# khong dau). Chu co dau chi duoc dung trong comment va sau ## (help text).
# Luu y: KHONG dung .ONESHELL — moi dong recipe la mot process pwsh rieng,
# dong nao cung phai tu chua (noi lenh bang ";" tren cung dong).
# Target "dev" chay ca 3 services TRONG CUNG 1 TERMINAL thong qua
# scripts/dev.ps1 (1 process pwsh duy nhat, Ctrl+C dung het).

ifeq ($(OS),Windows_NT)
SHELL := pwsh.exe
else
SHELL := pwsh
endif
.SHELLFLAGS := -NoProfile -Command

ROOT        := $(CURDIR)
BACKEND     := $(ROOT)/backend
FRONT       := $(ROOT)/frontend
ADMIN       := $(ROOT)/frontend-admin
DEV_SCRIPT  := $(ROOT)/scripts/dev.ps1
API_PORT    := 8000
STORE_PORT  := 4321
ADMIN_PORT  := 4322
PHP         := php
COMPOSER    := composer

# Giet process dang giu port (truoc khi mo server dev)
define kill-port
if (Get-Command Get-NetTCPConnection -ErrorAction SilentlyContinue) { Get-NetTCPConnection -LocalPort $(1) -ErrorAction SilentlyContinue | ForEach-Object { try { Stop-Process -Id $$_.OwningProcess -Force -ErrorAction Stop; Write-Output "Killed port $(1) (PID $$($$_.OwningProcess))" } catch { } }; Start-Sleep -Seconds 1 } else { $$p = @(lsof -ti :$(1) 2>/dev/null); if ($$p.Count -gt 0 -and $$p[0]) { Stop-Process -Id $$p -Force -ErrorAction SilentlyContinue } }
endef

# Chon package manager: uu tien pnpm (repo co pnpm-lock.yaml), fallback npm.
# Lay ban .cmd tren Windows de tranh vuong ExecutionPolicy cua file .ps1.
define pick-pm
$$pm = if ($$env:OS -eq 'Windows_NT') { if (Get-Command pnpm.cmd -ErrorAction SilentlyContinue) { 'pnpm.cmd' } else { 'npm.cmd' } } else { if (Get-Command pnpm -ErrorAction SilentlyContinue) { 'pnpm' } else { 'npm' } }
endef

# Chay 1 lenh trong thu muc con, giu exit code that (tranh Pop-Location nuot loi)
define in-dir
Push-Location "$(1)"; $(2); $$rc = $$LASTEXITCODE; Pop-Location; exit $$rc
endef

# Chay astro dev truc tiep (pnpm exec / npx): tranh khac biet truyen args
# giua `pnpm run` va `npm run`, va tranh lap --port o admin.
define astro-dev
if (Get-Command pnpm -ErrorAction SilentlyContinue) { $$ex = if ($$env:OS -eq 'Windows_NT') { 'pnpm.cmd' } else { 'pnpm' }; $(call in-dir,$(1),& $$ex exec astro dev --port $(2) --host 0.0.0.0) } else { $$nx = if ($$env:OS -eq 'Windows_NT') { 'npx.cmd' } else { 'npx' }; $(call in-dir,$(1),& $$nx astro dev --port $(2) --host 0.0.0.0) }
endef

.PHONY: help install dev dev-backend dev-storefront dev-admin stop status logs doctor db-create migrate seed fresh test pint queue schedule

help: ## Hien thi cac lenh
	@Select-String -Path $(MAKEFILE_LIST) -Pattern '^([A-Za-z0-9_.-]+)\s*:.*##\s*(.*)$$' | ForEach-Object { $$m = $$_.Matches[0].Groups; Write-Host ("  {0,-14} {1}" -f $$m[1].Value, $$m[2].Value) }

install: ## Cai deps: check php pdo_pgsql + composer + pnpm/npm
	if (-not (Get-Command php -ErrorAction SilentlyContinue)) { Write-Error 'Thieu PHP 8.3+ - cai tu https://windows.php.net/download/ va them vao PATH'; exit 1 }
	$$m = php -m 2>&1 | Out-String; if ($$m -notmatch 'pdo_pgsql') { Write-Error 'Thieu pdo_pgsql - mo php.ini, bo comment dong extension=pdo_pgsql (Windows) roi chay lai'; exit 1 }; Write-Output 'OK: php + pdo_pgsql'
	if (-not (Get-Command composer -ErrorAction SilentlyContinue)) { Write-Error 'Thieu composer - cai tu https://getcomposer.org/download/'; exit 1 }
	$(call pick-pm); if (-not (Get-Command $$pm -ErrorAction SilentlyContinue)) { Write-Error 'Thieu pnpm/npm - cai Node 20+ tu https://nodejs.org/'; exit 1 }; Write-Output "OK: $$pm"
	$(call in-dir,$(BACKEND),composer install --no-interaction)
	$(call pick-pm); $(call in-dir,$(FRONT),& $$pm install)
	$(call pick-pm); $(call in-dir,$(ADMIN),& $$pm install)

dev-backend: ## [1/3] Mo server backend :8000 (giet port cu truoc)
	$(call kill-port,$(API_PORT))
	$(call in-dir,$(BACKEND),php artisan serve --host=0.0.0.0 --port=$(API_PORT))

dev-storefront: ## [2/3] Mo server storefront :4321 (giet port cu truoc)
	$(call kill-port,$(STORE_PORT))
	$(call astro-dev,$(FRONT),$(STORE_PORT))

dev-admin: ## [3/3] Mo server admin :4322 (giet port cu truoc)
	$(call kill-port,$(ADMIN_PORT))
	$(call astro-dev,$(ADMIN),$(ADMIN_PORT))

dev: ## Chay ca 3 TRONG CUNG 1 terminal (Ctrl+C de dung het)
	pwsh -NoProfile -ExecutionPolicy Bypass -File "$(DEV_SCRIPT)" -ApiPort $(API_PORT) -StorePort $(STORE_PORT) -AdminPort $(ADMIN_PORT)

stop: ## Dung ca 3 (giet process tren 3 ports)
	$(call kill-port,$(API_PORT))
	$(call kill-port,$(STORE_PORT))
	$(call kill-port,$(ADMIN_PORT))
	Write-Output 'Stopped.'

status: ## Trang thai 3 services
	try { $$r = Invoke-WebRequest -Uri 'http://localhost:$(API_PORT)/api/v1/health' -UseBasicParsing -TimeoutSec 5; Write-Output "api :$(API_PORT) -> $$($$r.StatusCode)" } catch { Write-Output 'api :$(API_PORT) -> down' }
	try { $$r = Invoke-WebRequest -Uri 'http://localhost:$(STORE_PORT)/' -UseBasicParsing -TimeoutSec 5; Write-Output "storefront :$(STORE_PORT) -> $$($$r.StatusCode)" } catch { Write-Output 'storefront -> down' }
	try { $$r = Invoke-WebRequest -Uri 'http://localhost:$(ADMIN_PORT)/' -UseBasicParsing -TimeoutSec 5; Write-Output "admin :$(ADMIN_PORT) -> $$($$r.StatusCode)" } catch { Write-Output 'admin -> down' }

logs: ## Xem log (backend/storage/logs/laravel.log)
	Get-Content '$(BACKEND)/storage/logs/laravel.log' -Tail 100 -ErrorAction SilentlyContinue; Write-Output '(storefront/admin log nam o terminal dev tuong ung)'

doctor: ## Kiem tra moi truong (php ext, postgres, node, ports)
	if (-not (Get-Command php -ErrorAction SilentlyContinue)) { Write-Error 'THIEU php (cai PHP 8.3+ roi chay make install)'; exit 1 }
	$$m = php -m 2>&1 | Out-String; if ($$m -match 'pdo_pgsql') { Write-Output 'OK: php + pdo_pgsql'; php -v } else { Write-Error 'THIEU pdo_pgsql (chay make install)'; exit 1 }
	$$cfg = @{ }; Get-Content '$(BACKEND)/.env' | ForEach-Object { if ($$_ -match '^\s*([^#=]+?)\s*=\s*(.*)$$') { $$cfg[$$Matches[1].Trim()] = ($$Matches[2].Trim() -replace '^[''"]|[''"]$$', '') } }; $$env:WB_DB_PASS = $$cfg['DB_PASSWORD']; php -r 'try { $$p = new PDO("pgsql:host=127.0.0.1;port=5432;dbname=wibu_store", "app", getenv("WB_DB_PASS")); echo "OK: postgres wibu_store\n"; } catch (Exception $$e) { echo "FAIL: postgres: ".$$e->getMessage()."\n"; }'
	node -v; $(call pick-pm); & $$pm -v; Write-Output "(pm dang dung: $$pm)"

db-create: ## Tao databases (dung PHP, khong can psql client)
	$$cfg = @{ }; Get-Content '$(BACKEND)/.env' | ForEach-Object { if ($$_ -match '^\s*([^#=]+?)\s*=\s*(.*)$$') { $$cfg[$$Matches[1].Trim()] = ($$Matches[2].Trim() -replace '^[''"]|[''"]$$', '') } }; $$env:WB_DB_PASS = $$cfg['DB_PASSWORD']; php -r '$$pdo = new PDO("pgsql:host=127.0.0.1;port=5432;dbname=postgres", "app", getenv("WB_DB_PASS")); foreach (["wibu_store", "wibu_store_test"] as $$db) { if (!$$pdo->query("SELECT 1 FROM pg_database WHERE datname=\x27$$db\x27")->fetch()) { $$pdo->exec("CREATE DATABASE $$db OWNER app"); echo "created $$db\n"; } else { echo "exists $$db\n"; } }'
	$$cfg = @{ }; Get-Content '$(BACKEND)/.env' | ForEach-Object { if ($$_ -match '^\s*([^#=]+?)\s*=\s*(.*)$$') { $$cfg[$$Matches[1].Trim()] = ($$Matches[2].Trim() -replace '^[''"]|[''"]$$', '') } }; $$env:WB_DB_PASS = $$cfg['DB_PASSWORD']; php -r '$$pdo = new PDO("pgsql:host=127.0.0.1;port=5432;dbname=wibu_store", "app", getenv("WB_DB_PASS")); $$pdo->exec("CREATE EXTENSION IF NOT EXISTS pg_trgm"); $$pdo->exec("CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\""); echo "extensions ok\n";'

migrate: ## Chay migrations (db wibu_store)
	$(call in-dir,$(BACKEND),php artisan migrate --force)

seed: ## Seed roles + demo data
	$(call in-dir,$(BACKEND),php artisan db:seed --force)

fresh: ## Reset DB dev + seed lai
	$(call in-dir,$(BACKEND),php artisan migrate:fresh --seed --force)

test: ## Chay backend tests (db wibu_store_test)
	$(call in-dir,$(BACKEND),php artisan test)

pint: ## Kiem tra code style
	$(call in-dir,$(BACKEND),php ./vendor/bin/pint --test)

queue: ## Chay queue worker (foreground)
	$(call in-dir,$(BACKEND),php artisan queue:work --tries=3 --backoff=5)

schedule: ## Chay scheduler (foreground)
	$(call in-dir,$(BACKEND),php artisan schedule:work)
