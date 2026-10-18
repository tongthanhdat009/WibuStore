# Wibu Store — dev orchestration (storefront :4321, admin :4322, api :8000)
# Backend chay qua image wibu-php:8.3 (da co pdo_pgsql) + Postgres docker san co.
# DB: 127.0.0.1:5432, user app, db wibu_store / wibu_store_test.

ROOT        := $(CURDIR)
BACKEND     := $(ROOT)/backend
FRONT       := $(ROOT)/frontend
ADMIN       := $(ROOT)/frontend-admin
PHP_IMG     := wibu-php:8.3
API_PORT    := 8000
STORE_PORT  := 4321
ADMIN_PORT  := 4322
SESSION     := wibu
DB_CONTAINER ?= postgres-docker-postgres-1

.PHONY: help install doctor db-create migrate seed fresh dev dev-backend dev-storefront dev-admin stop status logs test pint queue schedule openapi-lint

help: ## Hien thi cac lenh
	@grep -E '^[a-z-]+:.*?## ' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS=":.*?## "} {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

doctor: ## Kiem tra moi truong (docker postgres, ports, node, php image)
	@echo "== postgres =="; docker exec $(DB_CONTAINER) psql -U app -d wibu_store -c "SELECT 1" >/dev/null && echo "OK: postgres $(DB_CONTAINER) -> wibu_store" || echo "FAIL: khong ket noi duoc postgres"
	@echo "== ports =="; (command -v ss >/dev/null && ss -ltn || netstat -ltn 2>/dev/null) | grep -E ":($(API_PORT)|$(STORE_PORT)|$(ADMIN_PORT))" && echo "^ ports tren dang duoc dung (luu y $(API_PORT) hien co the bi portfolio-backend chiem)" || echo "OK: 3 ports trong"
	@echo "== node/pnpm =="; node -v; pnpm -v
	@echo "== php image =="; docker image inspect $(PHP_IMG) >/dev/null 2>&1 && echo "OK: $(PHP_IMG)" || echo "THIEU: build bang 'make install'"

install: ## Cai dependencies (composer + pnpm) va build php image
	docker build -t $(PHP_IMG) -f $(BACKEND)/Dockerfile.dev $(BACKEND)
	docker run --rm -v $(BACKEND):/work -w /work $(PHP_IMG) composer install --no-interaction
	cd $(FRONT) && pnpm install
	cd $(ADMIN) && pnpm install

db-create: ## Tao databases tren postgres docker san co
	docker exec $(DB_CONTAINER) psql -U app -d postgres -c "SELECT 1 FROM pg_database WHERE datname='wibu_store'" | grep -q 1 || docker exec $(DB_CONTAINER) psql -U app -d postgres -c "CREATE DATABASE wibu_store OWNER app"
	docker exec $(DB_CONTAINER) psql -U app -d postgres -c "SELECT 1 FROM pg_database WHERE datname='wibu_store_test'" | grep -q 1 || docker exec $(DB_CONTAINER) psql -U app -d postgres -c "CREATE DATABASE wibu_store_test OWNER app"
	docker exec $(DB_CONTAINER) psql -U app -d wibu_store -c 'CREATE EXTENSION IF NOT EXISTS pg_trgm; CREATE EXTENSION IF NOT EXISTS "uuid-ossp";'

migrate: ## Chay migrations (db wibu_store)
	docker run --rm --network host -v $(BACKEND):/work -w /work $(PHP_IMG) php artisan migrate --force

seed: ## Seed roles + demo data
	docker run --rm --network host -v $(BACKEND):/work -w /work $(PHP_IMG) php artisan db:seed --force

fresh: ## Reset DB dev + seed lai
	docker run --rm --network host -v $(BACKEND):/work -w /work $(PHP_IMG) php artisan migrate:fresh --seed --force

dev: ## Chay ca 3 (tmux session 'wibu': api/storefront/admin)
	@tmux has-session -t $(SESSION) 2>/dev/null && echo "Session $(SESSION) dang chay (make status / make stop)" && exit 1 || true
	tmux new-session -d -s $(SESSION) -n api "docker run --rm --network host --name wibu-api -v $(BACKEND):/work -w /work $(PHP_IMG) php artisan serve --host=0.0.0.0 --port=$(API_PORT)"
	tmux new-window -t $(SESSION) -n storefront "cd $(FRONT) && pnpm dev --port $(STORE_PORT)"
	tmux new-window -t $(SESSION) -n admin "cd $(ADMIN) && pnpm dev --port $(ADMIN_PORT)"
	@echo "Chay: api :$(API_PORT), storefront :$(STORE_PORT), admin :$(ADMIN_PORT)  (tmux attach -t $(SESSION))"

dev-backend: ## Chay rieng backend :8000 (foreground)
	docker run --rm --network host -v $(BACKEND):/work -w /work $(PHP_IMG) php artisan serve --host=0.0.0.0 --port=$(API_PORT)

dev-storefront: ## Chay rieng storefront :4321 (foreground)
	cd $(FRONT) && pnpm dev --port $(STORE_PORT)

dev-admin: ## Chay rieng admin :4322 (foreground)
	cd $(ADMIN) && pnpm dev --port $(ADMIN_PORT)

stop: ## Dung ca 3
	-tmux kill-session -t $(SESSION) 2>/dev/null
	-docker stop wibu-api 2>/dev/null
	@echo "Stopped."

status: ## Trang thai 3 services
	@tmux list-windows -t $(SESSION) 2>/dev/null || echo "tmux session '$(SESSION)' chua chay"
	@curl -s -o /dev/null -w "api :$(API_PORT) -> %{http_code}\n" http://localhost:$(API_PORT)/api/v1/health || echo "api :$(API_PORT) -> down"
	@curl -s -o /dev/null -w "storefront :$(STORE_PORT) -> %{http_code}\n" http://localhost:$(STORE_PORT)/ || echo "storefront -> down"
	@curl -s -o /dev/null -w "admin :$(ADMIN_PORT) -> %{http_code}\n" http://localhost:$(ADMIN_PORT)/ || echo "admin -> down"

logs: ## Xem logs (make logs W=api|storefront|admin)
	tmux capture-pane -p -t $(SESSION):$${W:-api}

test: ## Chay backend tests (db wibu_store_test)
	docker run --rm --network host -v $(BACKEND):/work -w /work $(PHP_IMG) php artisan test

pint: ## Kiem tra code style
	docker run --rm -v $(BACKEND):/work -w /work $(PHP_IMG) ./vendor/bin/pint --test

queue: ## Chay queue worker (foreground)
	docker run --rm --network host -v $(BACKEND):/work -w /work $(PHP_IMG) php artisan queue:work --tries=3 --backoff=5

schedule: ## Chay scheduler (foreground)
	docker run --rm --network host -v $(BACKEND):/work -w /work $(PHP_IMG) php artisan schedule:work
