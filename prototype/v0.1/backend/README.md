# CapyMeal — Backend

API de Laravel 12 para CapyMeal, un diario personal de comidas (explícitamente no una app de fitness/calorías).

Para levantar el proyecto completo (backend + frontend + base de datos) con Docker, ver la
[guía de desarrollo](../README.md) en la raíz de `prototype/v0.1/` — ese es el README mantenido
con los pasos reales de instalación.

## Comandos específicos de este paquete

Corridos desde `prototype/v0.1/`, con el proyecto ya levantado (`docker compose up`):

```bash
# Tests
docker exec -it capymeal-backend php artisan test

# Análisis estático (Larastan, nivel 5)
docker exec -it capymeal-backend composer phpstan

# Estilo (Pint)
docker exec -it capymeal-backend composer lint    # solo reporta
docker exec -it capymeal-backend composer format  # aplica los arreglos
```

## Stack

- Laravel 12, PHP 8.4
- Sanctum (auth por cookie de sesión httpOnly, con bearer token como fallback para clientes viejos)
- PostgreSQL 18 (Neon en producción)
- DomPDF (export del diario a PDF)
- Sentry (monitoreo de errores)

## Deploy

Render, vía `Dockerfile.prod` (FrankenPHP, no `artisan serve`). Auto-deploy desde la rama `dev`
(no `main`). Variables de entorno configuradas en el dashboard de Render, no en este repo.
