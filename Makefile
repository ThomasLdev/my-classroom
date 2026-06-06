# Executables (local)
DOCKER_COMP = docker compose

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec php

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY  = $(PHP) bin/console

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build up start down logs sh composer vendor sf cc test
.PHONY        : ecs ecs-fix rector rector-fix phpstan test-unit test-functional test-db qa qa-front qa-full
.PHONY        : biome biome-fix test-e2e

## —— 🎵 🐳 The Symfony Docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	@$(DOCKER_COMP) build --pull --no-cache

up: ## Start the docker hub in detached mode (no logs)
	@$(DOCKER_COMP) up --detach

start: build up ## Build and start the containers

down: ## Stop the docker hub
	@$(DOCKER_COMP) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

sh: ## Connect to the FrankenPHP container
	@$(PHP_CONT) sh

bash: ## Connect to the FrankenPHP container via bash so up and down arrows go to previous commands
	@$(PHP_CONT) bash

test: ## Start tests with phpunit, pass the parameter "c=" to add options to phpunit, example: make test c="--group e2e --stop-on-failure"
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/phpunit $(c)

test-unit: ## Run the unit test suite (no database needed)
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/phpunit --testsuite unit

test-functional: ## Run the functional test suite (needs the test database — run "make test-db" first)
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/phpunit --testsuite functional

test-db: ## (Re)create the test database from scratch and migrate it — safe to re-run
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/console doctrine:database:drop --force --if-exists
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/console doctrine:database:create
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

## —— Quality 🩺 ———————————————————————————————————————————————————————————————
ecs: ## Check coding standard (ECS)
	@$(COMPOSER) ecs

ecs-fix: ## Fix coding standard (ECS)
	@$(COMPOSER) ecs:fix

rector: ## Check pending refactorings (Rector, dry-run)
	@$(COMPOSER) rector

rector-fix: ## Apply refactorings (Rector)
	@$(COMPOSER) rector:fix

phpstan: ## Run static analysis (PHPStan level max + PHPat); warms the container first
	@$(SYMFONY) cache:warmup --env=dev
	@$(COMPOSER) phpstan

qa: ## Run the whole quality suite in CI order: ECS → Rector → PHPStan → DB → tests
qa: ecs rector phpstan test-db test

qa-front: biome test-e2e

qa-full: qa qa-front

## —— Front 🎨 —————————————————————————————————————————————————————————————————
biome: ## Lint the front-end with Biome (read-only)
	@$(DOCKER_COMP) run --rm biome ci assets

biome-fix: ## Format and fix the front-end with Biome
	@$(DOCKER_COMP) run --rm biome check --write assets

test-e2e: ## Run Playwright E2E tests (starts the stack if needed)
	@$(DOCKER_COMP) run --rm playwright


## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction
vendor: composer

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: c=c:c ## Clear the cache
cc: sf
