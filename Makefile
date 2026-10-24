.DEFAULT_GOAL := help

PLUGIN_NAME := sensei-lms
WP_ENV := node scripts/sensei-env.cjs
NODE_MIN_VERSION := 22
WP ?=
PHP ?=

define check_node
	@NODE_VERSION=$$(node --version 2>/dev/null | sed 's/v//'); \
	if [ -z "$$NODE_VERSION" ]; then \
		echo "Error: Node.js is not installed. Please install Node.js $(NODE_MIN_VERSION)+ from https://nodejs.org"; \
		exit 1; \
	fi; \
	NODE_MAJOR=$$(echo "$$NODE_VERSION" | cut -d. -f1); \
	if [ "$$NODE_MAJOR" -lt "$(NODE_MIN_VERSION)" ]; then \
		echo "Error: Node.js v$$NODE_VERSION found, but $(NODE_MIN_VERSION)+ is required."; \
		echo "If using nvm: nvm install $(NODE_MIN_VERSION) && nvm use $(NODE_MIN_VERSION)"; \
		exit 1; \
	fi
endef

## Development environment
install: ## Install dependencies (requires Node 22+)
	$(check_node)
	npm install

install-php: ## Install PHP dependencies via wp-env (requires: make up)
	$(WP_ENV) run cli --env-cwd=wp-content/plugins/sensei composer install

up: ## Start WordPress (NAME=label CLONE_FROM=id WP=6.8 PHP=8.3)
	$(check_node)
	WP="$(WP)" PHP="$(PHP)" CLONE_FROM="$(CLONE_FROM)" SENSEI_ENV_NAME="$(NAME)" $(WP_ENV) start

down: ## Stop the selected worktree environment
	$(WP_ENV) stop

destroy: ## Remove this worktree environment and its data
	$(WP_ENV) destroy

env-status: ## Show selected environment status and URLs
	$(WP_ENV) status

env-list: ## List managed environments across worktrees
	$(WP_ENV) list

env-prune: ## Preview orphans; remove selected IDs with APPLY=1 IDS=id,id
	APPLY="$(APPLY)" IDS="$(IDS)" $(WP_ENV) prune

env-retry: ## Retry interrupted development data initialization
	$(WP_ENV) retry

logs: ## Show WordPress environment logs
	$(WP_ENV) logs

shell: ## Open a shell in the WordPress container
	$(WP_ENV) run cli bash

wp: ## Run a wp-cli command (CMD="plugin list")
	@[ -n "$(CMD)" ] || { echo "Error: CMD is required. Example: make wp CMD=\"plugin list\""; exit 1; }
	$(WP_ENV) run cli wp $(CMD)

## Testing
test-php: ## Run PHPUnit tests via wp-env (requires: make up)
	$(WP_ENV) run tests-cli --env-cwd='wp-content/plugins/sensei' vendor/bin/phpunit -c phpunit.xml

test-php-filter: ## Run targeted PHPUnit tests via wp-env (FILTER="TestClass" or FILTER="TestClass::method")
	@[ -n "$(FILTER)" ] || { echo "Error: FILTER is required. Example: make test-php-filter FILTER=\"Sensei_Quiz_Test\""; exit 1; }
	$(WP_ENV) run tests-cli --env-cwd='wp-content/plugins/sensei' vendor/bin/phpunit -c phpunit.xml --filter '$(FILTER)'

## Code quality
lint: ## Run PHPCS via the same diff-based check CI uses
	./scripts/linter-ci

psalm: ## Run Psalm static analysis under each CI PHP version (parsed from .github/workflows/psalm.yml)
	@# Collect unique versions from literal arrays and every fromJSON array in conditional matrices.
	@VERSIONS=$$(grep -E "^[[:space:]]+php:" .github/workflows/psalm.yml | grep -oE '\[[^]]*\]' | grep -oE '[0-9]+\.[0-9]+' | sort -u); \
	if [ -z "$$VERSIONS" ]; then \
		echo "Error: could not parse PHP matrix from .github/workflows/psalm.yml"; exit 1; \
	fi; \
	for v in $$VERSIONS; do \
		BIN="/opt/homebrew/opt/php@$$v/bin"; \
		if [ ! -x "$$BIN/php" ]; then \
			echo "Error: PHP $$v not found at $$BIN. Install with: brew install php@$$v"; \
			exit 1; \
		fi; \
		echo "==> Psalm on PHP $$v"; \
		PATH="$$BIN:$$PATH" vendor/bin/psalm --no-cache --diff || exit $$?; \
	done

## Changelog
changelog: ## Add a changelog entry
	npm run changelog

## Build
build: ## Build plugin zip via wp-env (requires: make up)
	npm run build:assets
	rm -f assets/dist/css/jquery-ui.js
	$(WP_ENV) run cli --env-cwd=wp-content/plugins/sensei composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts
	npm run archive

## Help
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

.PHONY: env-status env-list env-prune env-retry install install-php up down destroy logs shell wp test-php test-php-filter lint psalm changelog build help
