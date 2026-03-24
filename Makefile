.DEFAULT_GOAL := help

PLUGIN_NAME := sensei-lms
WP_ENV := COMPOSE_PROJECT_NAME=$(PLUGIN_NAME) npx @wordpress/env
WP_ENV_TESTS := COMPOSE_PROJECT_NAME=$(PLUGIN_NAME)-tests npx @wordpress/env --config .wp-env.tests.json
NODE_MIN_VERSION := 20
WP ?= latest
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

# Write override files when WP or PHP overrides are specified.
# Writes both .wp-env.override.json and .wp-env.tests.override.json.
# Resolves WP value to a wp-env core source:
#   latest  -> no override (uses config as-is)
#   6.8     -> WordPress/WordPress#6.8-branch (GitHub mirror)
#   7.0-beta1, 6.9-RC1 -> wordpress.org zip URL
#   nightly -> wordpress.org nightly zip
define write_override
	@if [ "$(WP)" != "latest" ] || [ -n "$(PHP)" ]; then \
		WP_VAL="$(WP)"; \
		if [ "$$WP_VAL" = "nightly" ]; then \
			WP_CORE='"https://wordpress.org/nightly-builds/wordpress-latest.zip"'; \
		elif echo "$$WP_VAL" | grep -qiE -- '-beta|-rc'; then \
			WP_CORE="\"https://wordpress.org/wordpress-$$WP_VAL.zip\""; \
		elif [ "$$WP_VAL" != "latest" ]; then \
			WP_CORE="\"WordPress/WordPress#$$WP_VAL-branch\""; \
		fi; \
		OVERRIDE=""; \
		( \
			printf '{\n'; \
			if [ "$(WP)" != "latest" ]; then \
				printf '  "core": %s' "$$WP_CORE"; \
				if [ -n "$(PHP)" ]; then printf ',\n'; else printf '\n'; fi; \
			fi; \
			if [ -n "$(PHP)" ]; then \
				printf '  "phpVersion": "%s"\n' "$(PHP)"; \
			fi; \
			printf '}\n'; \
		) > .wp-env.override.json; \
		cp .wp-env.override.json .wp-env.tests.override.json; \
		echo "Override: $$(cat .wp-env.override.json | tr -d '\n')"; \
	else \
		rm -f .wp-env.override.json .wp-env.tests.override.json; \
	fi
endef

define clean_override
	@rm -f .wp-env.override.json .wp-env.tests.override.json
endef

## Development environment
install: ## Install dependencies (requires Node 20+)
	$(check_node)
	npm install

install-php: ## Install PHP dependencies via wp-env (requires: make up)
	$(WP_ENV) run cli --env-cwd=wp-content/plugins/sensei composer install

up: ## Start WordPress dev and test environments (WP=6.8|7.0-beta1|nightly PHP=8.3)
	$(check_node)
	$(write_override)
	@if [ "$(WP)" != "latest" ] || [ -n "$(PHP)" ]; then \
		echo "Starting wp-env with WP=$(WP)$${PHP:+ PHP=$(PHP)}"; \
	else \
		echo "Starting wp-env with latest WordPress"; \
	fi
	$(WP_ENV) start --update
	$(WP_ENV_TESTS) start --update

down: ## Stop WordPress dev and test environments
	-$(WP_ENV) stop
	-$(WP_ENV_TESTS) stop
	$(clean_override)

destroy: ## Remove WordPress environment containers and data
	-$(WP_ENV) destroy
	-$(WP_ENV_TESTS) destroy
	$(clean_override)

logs: ## Show WordPress environment logs
	$(WP_ENV) logs

shell: ## Open a shell in the WordPress container
	$(WP_ENV) run cli bash

wp: ## Run a wp-cli command (CMD="plugin list")
	$(WP_ENV) run cli wp $(CMD)

## Testing
test-up: ## Start WordPress test environment only
	$(check_node)
	$(write_override)
	$(WP_ENV_TESTS) start --update

test-down: ## Stop WordPress test environment only
	-$(WP_ENV_TESTS) stop
	$(clean_override)

test-php: ## Run PHPUnit tests via wp-env (requires: make test-up or make up)
	$(WP_ENV_TESTS) run cli --env-cwd=wp-content/plugins/sensei vendor/bin/phpunit -c phpunit.xml

## Code quality
lint: ## Run PHP CodeSniffer
	npm run lint-php

## Build
build: ## Build plugin zip via wp-env (requires: make up)
	npm run build:assets
	rm -f assets/dist/css/jquery-ui.js
	$(WP_ENV) run cli --env-cwd=wp-content/plugins/sensei composer install
	$(WP_ENV) run cli --env-cwd=wp-content/plugins/sensei composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts
	npm run archive

## Help
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

.PHONY: install install-php up down destroy logs shell wp test-up test-down test-php lint build help
