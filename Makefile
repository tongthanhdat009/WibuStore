# Wibu Store — dev orchestration, KHONG DOCKER (api :8000, storefront :4321, admin :4322)
# Backend chay bang PHP host + pdo_pgsql lay tu RPM Fedora (make install, khong can sudo).
# DB: Postgres san co 127.0.0.1:5432 (user app, db wibu_store / wibu_store_test).

ROOT        := $(CURDIR)
BACKEND     := $(ROOT)/backend
FRONT       := $(ROOT)/frontend
ADMIN       := $(ROOT)/frontend-admin
API_PORT    := 8000
STORE_PORT  := 4321
ADMIN_PORT  := 4322
SESSION     := wibu
PHP_EXT_DIR := $(HOME)/.local/lib/php-ext
PHP_INI_DIR := $(HOME)/.local/lib/php-ext-ini
export PHP_INI_SCAN_DIR := /etc/php.d:$(PHP_INI_DIR)
PHP         := php
ARTISAN     := cd $(BACKEND) && $(PHP) artisan
COMPOSER    := composer
DB_PASS     := $(shell grep '^DB_PASSWORD=' $(BACKEND)/.env 2>/dev/null | cut -d= -f2- | tr -d '"')

# Giet process dang giu port (truoc khi mo server dev)
define kill-port
@lsof -ti:$(1) 2>/dev/null | xargs -r kill -9 2>/dev/null; sleep 1; \
lsof -ti:$(1) >/dev/null 2>&1 && echo "Port $(1) van ban!" || echo "Port $(1) trong."
endef

.PHONY: help install dev dev-backend dev-storefront dev-admin stop status logs doctor db-create migrate seed fresh test pint queue schedule

help: ## Hien thi cac lenh
	@grep -E '^[a-z-]+:.*?## ' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS=":.*?## "} {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

install: ## Cai pdo_pgsql (RPM, khong sudo) + composer + pnpm
	@mkdir -p $(PHP_EXT_DIR) $(PHP_INI_DIR) /tmp/wibu-rpm
	@if [ ! -f $(PHP_EXT_DIR)/pdo_pgsql.so ]; then \
		dnf download --destdir /tmp/wibu-rpm php-pgsql >/dev/null 2>&1 && \
		(cd /tmp/wibu-rpm && rpm2cpio php-pgsql-*.x86_64.rpm | cpio -idmv ./usr/lib64/php/modules/*.so >/dev/null 2>&1) && \
		cp /tmp/wibu-rpm/usr/lib64/php/modules/*.so $(PHP_EXT_DIR)/ && \
		rm -rf /tmp/wibu-rpm && echo "OK: pdo_pgsql -> $(PHP_EXT_DIR)"; \
	fi
	@echo "extension=$(PHP_EXT_DIR)/pdo_pgsql.so" > $(PHP_INI_DIR)/99-pgsql.ini
	@echo "extension=$(PHP_EXT_DIR)/pgsql.so" >> $(PHP_INI_DIR)/99-pgsql.ini
	@$(PHP) -m | grep -q pdo_pgsql && echo "OK: php co pdo_pgsql" || (echo "THIEU pdo_pgsql"; exit 1)
	cd $(BACKEND) && $(COMPOSER) install --no-interaction
	cd $(FRONT) && pnpm install
	cd $(ADMIN) && pnpm install

dev-backend: ## [1/3] Mo server backend :8000 (giet port cu truoc)
	$(call kill-port,$(API_PORT))
	cd $(BACKEND) && $(PHP) artisan serve --host=0.0.0.0 --port=$(API_PORT)

dev-storefront: ## [2/3] Mo server storefront :4321 (giet port cu truoc)
	$(call kill-port,$(STORE_PORT))
	cd $(FRONT) && pnpm dev --port $(STORE_PORT)

dev-admin: ## [3/3] Mo server admin :4322 (giet port cu truoc)
	$(call kill-port,$(ADMIN_PORT))
	cd $(ADMIN) && pnpm dev --port $(ADMIN_PORT)

dev: ## Chay ca 3 trong tmux (giet 3 ports truoc)
	$(call kill-port,$(API_PORT))
	$(call kill-port,$(STORE_PORT))
	$(call kill-port,$(ADMIN_PORT))
	@tmux kill-session -t $(SESSION) 2>/dev/null; true
	tmux new-session -d -s $(SESSION) -n api "cd $(BACKEND) && $(PHP) artisan serve --host=0.0.0.0 --port=$(API_PORT)"
	tmux new-window -t $(SESSION) -n storefront "cd $(FRONT) && pnpm dev --port $(STORE_PORT)"
	tmux new-window -t $(SESSION) -n admin "cd $(ADMIN) && pnpm dev --port $(ADMIN_PORT)"
	@echo "Chay: api :$(API_PORT), storefront :$(STORE_PORT), admin :$(ADMIN_PORT)  (tmux attach -t $(SESSION))"

stop: ## Dung ca 3 (tmux + process le tren 3 ports)
	-tmux kill-session -t $(SESSION) 2>/dev/null
	-lsof -ti:$(API_PORT),$(STORE_PORT),$(ADMIN_PORT) 2>/dev/null | xargs -r kill -9 2>/dev/null
	@echo "Stopped."

status: ## Trang thai 3 services
	@curl -s -o /dev/null -w "api :$(API_PORT) -> %{http_code}\n" http://localhost:$(API_PORT)/api/v1/health || echo "api :$(API_PORT) -> down"
	@curl -s -o /dev/null -w "storefront :$(STORE_PORT) -> %{http_code}\n" http://localhost:$(STORE_PORT)/ || echo "storefront -> down"
	@curl -s -o /dev/null -w "admin :$(ADMIN_PORT) -> %{http_code}\n" http://localhost:$(ADMIN_PORT)/ || echo "admin -> down"

logs: ## Xem logs (make logs W=api|storefront|admin)
	tmux capture-pane -p -t $(SESSION):$${W:-api}

doctor: ## Kiem tra moi truong (php ext, postgres, node, ports)
	@$(PHP) -m | grep -q pdo_pgsql && echo "OK: php + pdo_pgsql ($$(php -v | head -1))" || echo "THIEU pdo_pgsql (chay make install)"
	@$(PHP) -r 'try { $$p = new PDO("pgsql:host=127.0.0.1;port=5432;dbname=wibu_store", "app", "$(DB_PASS)"); echo "OK: postgres wibu_store\n"; } catch (Exception $$e) { echo "FAIL: postgres: ".$$e->getMessage()."\n"; }'
	@node -v; pnpm -v

db-create: ## Tao databases (dung PHP, khong can psql client)
	@$(PHP) -r '$$pdo = new PDO("pgsql:host=127.0.0.1;port=5432;dbname=postgres", "app", "$(DB_PASS)"); foreach (["wibu_store", "wibu_store_test"] as $$db) { if (!$$pdo->query("SELECT 1 FROM pg_database WHERE datname=\x27$$db\x27")->fetch()) { $$pdo->exec("CREATE DATABASE $$db OWNER app"); echo "created $$db\n"; } else { echo "exists $$db\n"; } }'
	@$(PHP) -r '$$pdo = new PDO("pgsql:host=127.0.0.1;port=5432;dbname=wibu_store", "app", "$(DB_PASS)"); $$pdo->exec("CREATE EXTENSION IF NOT EXISTS pg_trgm"); $$pdo->exec('"'"'CREATE EXTENSION IF NOT EXISTS "uuid-ossp"'"'"'); echo "extensions ok\n";'

migrate: ## Chay migrations (db wibu_store)
	cd $(BACKEND) && $(PHP) artisan migrate --force

seed: ## Seed roles + demo data
	cd $(BACKEND) && $(PHP) artisan db:seed --force

fresh: ## Reset DB dev + seed lai
	cd $(BACKEND) && $(PHP) artisan migrate:fresh --seed --force

test: ## Chay backend tests (db wibu_store_test)
	cd $(BACKEND) && $(PHP) artisan test

pint: ## Kiem tra code style
	cd $(BACKEND) && $(PHP) ./vendor/bin/pint --test

queue: ## Chay queue worker (foreground)
	cd $(BACKEND) && $(PHP) artisan queue:work --tries=3 --backoff=5

schedule: ## Chay scheduler (foreground)
	cd $(BACKEND) && $(PHP) artisan schedule:work
