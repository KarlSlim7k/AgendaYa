SHELL = /bin/sh
COMPOSER = composer
PHP = php
NPM = npm

.PHONY: help install dev build test clean

help: ## Mostrar ayuda
	@echo "Comandos disponibles:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

install: ## Instalar dependencias
	$(COMPOSER) install
	$(NPM) install
	cp -n .env.example .env || true
	$(PHP) artisan key:generate

dev: ## Iniciar desarrollo (Vite HMR)
	$(NPM) run dev

build: ## Build assets para producción
	$(NPM) run build

test: ## Ejecutar tests
	$(PHP) artisan test

migrate: ## Ejecutar migraciones
	$(PHP) artisan migrate

fresh: ## Reset BD + seeders
	$(PHP) artisan migrate:fresh --seed

clean: ## Limpiar cache
	$(PHP) artisan cache:clear
	$(PHP) artisan config:clear
	$(PHP) artisan route:clear
	$(PHP) artisan view:clear
