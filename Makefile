SHELL := /usr/bin/env bash

COMPOSE := docker compose
COMPOSE_PROD := docker compose -f docker-compose.prod.yml

.PHONY: help setup setup-local up down logs ps shell artisan migrate seed test phpstan ml-test build prod-config

help:
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "%-18s %s\n", $$1, $$2}'

setup: ## Prepara archivos de entorno local.
	@test -f .env || cp .env.docker.example .env
	@test -f ml-service/.env || cp ml-service/.env.example ml-service/.env

setup-local: ## Prepara el proyecto local sin contenedores.
	@test -f .env || cp .env.example .env
	composer install
	npm install
	php artisan key:generate
	php artisan migrate --seed
	php artisan storage:link
	php artisan scout:import "App\Models\Producto"
	npm run build

up: setup ## Levanta entorno local.
	$(COMPOSE) up -d --build

down: ## Detiene entorno local.
	$(COMPOSE) down

logs: ## Muestra logs locales.
	$(COMPOSE) logs -f --tail=200

ps: ## Lista servicios.
	$(COMPOSE) ps

shell: ## Abre shell en contenedor Laravel.
	$(COMPOSE) exec app bash

artisan: ## Ejecuta artisan, ejemplo: make artisan CMD="route:list".
	$(COMPOSE) exec app php artisan $(CMD)

migrate: ## Ejecuta migraciones.
	$(COMPOSE) exec app php artisan migrate

seed: ## Ejecuta seeders.
	$(COMPOSE) exec app php artisan db:seed

test: ## Ejecuta tests Laravel si PHPUnit esta instalado.
	$(COMPOSE) exec app sh -lc 'test -x vendor/bin/phpunit && vendor/bin/phpunit || php -v'

ml-test: ## Ejecuta tests ML.
	$(COMPOSE) exec ml-api python -m pytest tests

build: ## Construye imagenes locales.
	$(COMPOSE) build app worker scheduler ml-api ml-worker

prod-config: ## Valida compose de produccion.
	$(COMPOSE_PROD) config
